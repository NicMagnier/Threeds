<?php

include_once 'database.class.php';
include_once 'authentification.class.php';

class Threeds_Config {
	const SQL_HOST = 'your_sql_host';
	const SQL_DB = 'your_database';
	const SQL_USER = 'your_username';
	const SQL_PASS = 'your_password';
	
	const TWITTER_KEY = 'your_twitter_key';
	const TWITTER_SECRET = 'your_twitter_password';
	
	const TABLE_PREFIX = 'threeds_';
	const TABLE_USER = 'threeds_user';
	const TABLE_IMAGE = 'threeds_image';
	const COOKIE_DOMAIN = '.threeds.me';
	
	const PATH_MPO = 'data/mpo';
	const PATH_JPS = 'data/jps';
	const PATH_THUMB = 'data/thumb';
	
	const JPS_WIDTH = 640;
	const THUMB_WIDTH = 128;
	const THUMB_HEIGHT = 96;
	
	const DEFAULT_NAME = 'No name yet';
	
	const PARALLAX_MIN = 0.84;
	const PARALLAX_MAX = 1.2;
	const PARALLAX_DEFAULT = 0.9;
	
	const ADMIN_AUTHID = -1; // user id that has admin rights (yeah quite dirty uh!)
}

class Threeds {
	public $DB;
	public $Auth;
	
	private $userGID;
	private $userName;
	private $idCounter = 0;
	
	public function __construct() {
		$this->DB = new DB(
			Threeds_Config::SQL_HOST, Threeds_Config::SQL_DB,
			Threeds_Config::SQL_USER, Threeds_Config::SQL_PASS);
		
		$this->Auth = new Authentication(
			$this->DB, Threeds_Config::TABLE_PREFIX,
			Threeds_Config::COOKIE_DOMAIN,
			Threeds_Config::TWITTER_KEY, Threeds_Config::TWITTER_SECRET);
	}

	function is3DS() {
		return (strpos($_SERVER['HTTP_USER_AGENT'], 'Nintendo 3DS')!==false);
	}
	
	function getHTMLUniqueID() {
		return ++$this->idCounter;
	}
	
	function getUserGID() {
		if ( !$this->Auth->isLog() )
			return false;
			
		if ( !empty($this->userGID) )
			return $this->userGID;

		$u = $this->DB->query( 'SELECT name, twitterName, gid FROM '.Threeds_Config::TABLE_USER.
			' WHERE authID = '.$this->Auth->getAuthenticationID() );
			
		$this->userGID = $u[0]['gid'];
		$this->twitterName = $u[0]['twitterName'];
		$this->userName = $u[0]['name'];

		return $this->userGID;
	}
	
	function getUserName() {
		if ( !$this->Auth->isLog() )
			return false;
			
		if ( !empty($this->userName) )
			return $this->userName;

		$u = $this->DB->query( 'SELECT name, gid FROM '.Threeds_Config::TABLE_USER.
			' WHERE authID = '.$this->Auth->getAuthenticationID() );
			
		$this->userGID = $u[0]['gid'];
		$this->userName = $u[0]['name'];
		
		if ( empty($this->userName) )
			return Threeds_Config::DEFAULT_NAME;
		
		return $this->userName;
	}
	
	function getTwitterClass() {
		return $this->Auth->getTwitterClass();
	}
	
	function hasAdminRight($objectAuthID) {
		if ( $objectAuthID==$this->Auth->getAuthenticationID() )
			return true;
			
		if ( Threeds_Config::ADMIN_AUTHID==$this->Auth->getAuthenticationID() )
			return true;
			
		return false;
	}
	
	function createAccount( $newLogin, $newPassword) {
		if ( $this->Auth->createAccount( $newLogin, $newPassword)==false )
			return false;
			
		if ( $this->Auth->isLog()==false )
			return false;
			
		$id = $this->Auth->getAuthenticationID();
		$this->DB->insertRow( Threeds_Config::TABLE_USER,
			array ( 'authID' => $id, 'gid' => $this->globalID('u',$id)));
		
		return true;
	}
	
	function createLazyAccount() {
		if ( $this->Auth->createLazyRegistration()==false )
			return false;

		if ( $this->Auth->isLog()==false )
			return false;
			
		$id = $this->Auth->getAuthenticationID();
		$this->DB->insertRow( Threeds_Config::TABLE_USER,
			array ( 'authID' => $id, 'gid' => $this->globalID('u',$id)));
		
		return true;
	}

	function secureLazyAccount( $newLogin, $newPassword) {
		return $this->Auth->secureLazyRegistration( $newLogin, $newPassword);
	}

	function connectTwitterAccount($accessToken) {
		$this->Auth->connectTwitterAccount($accessToken);
		
		if ( $this->Auth->isTwitterLog() ) {
			$id = $this->Auth->getAuthenticationID();
			
			// check if the user is already in the database
			$result = $this->DB->query( 'SELECT authID, name FROM '.Threeds_Config::TABLE_USER.' WHERE authID = '.$id);
			$twitterUser = $this->Auth->getTwitterUserInfo();
			
			// we create the user if the account is new
			if ( empty($result) ) {
				$this->DB->insertRow( Threeds_Config::TABLE_USER,
					array ( 
						'authID' => $id,
						'gid' => $this->globalID('u',$id),
						'name' => $twitterUser->name,
						'twitterName' => $twitterUser->screen_name,
						'profile' => $twitterUser->description
						));
			} else {
				// User already exist, let's just update his profile if needed
				$name = $result[0]['name'];
				if ( empty($name) ) {
					$name = $twitterUser->name;
				}
		
				$this->DB->updateRow( Threeds_Config::TABLE_USER,
					array ( 'authID' => $id),
					array (
						'name' => $name,
						'twitterName' => $twitterUser->screen_name,
						));
			}

			return true;
		}
		
		return false;
	}
	
	function isImage($file) {
		if ( exif_imagetype($file)!=IMAGETYPE_JPEG )
			return false;
	
		// Get the size of the image
	    $size = getimagesize($file);
		if (isset($size) && $size[0] && $size[1] && $size[0] *  $size[1] > 0) {
			return true;
		}
		
		return false;
	}
	
	function newImage($tmpfile) {
		if ( $this->Auth->isLog()==false )
			return false;
		
		if ( $this->isImage($tmpfile)==false )
			return false;
			
		// insert image in DB			
		$authID = $this->Auth->getAuthenticationID();
		$this->DB->query( 'INSERT INTO '.Threeds_Config::TABLE_IMAGE.
			' (authID, dateCreated)'.
			' VALUES ("'.$authID.'", NOW())');
		
		// we update the gid
		$id = $this->DB->insertId();
		$gid = $this->globalID('i', $id);
		$this->DB->updateRow( Threeds_Config::TABLE_IMAGE,
			array ( 'id' => $id),
			array ( 'gid' => $gid) );
			
		// now we move the uploaded picture
		if ( move_uploaded_file($tmpfile, Threeds_Config::PATH_MPO.'/'.$gid.'.mpo')==false ) {
			// the image couldn't be moved, let's clean the mess
			$this->deleteImage($id);
			return false;
		}
		
		// generate the thumbnail
		$this->generateThumbnail($gid);
		
		// Get the best Parallax
		$p = $this->getBestParallax($gid);
		$this->DB->updateRow( Threeds_Config::TABLE_IMAGE,
			array ( 'id' => $id),
			array ( 'parallax' => $p) );
		
		return $gid;
	}
	
	function getDeleteHashCode($gid) {
		return md5('del'.$gid.$userGID);
	}
	
	function deleteImage($gid) {
		$image = $this->getImageInfo($gid);
		
		if ( $this->hasAdminRight($image['authID'])===false )
			return;
		
		// delete pictures
		@unlink($this->getMPOonServer($gid));
		@unlink($this->getThumbJPSonServer($gid));
		@unlink($this->getFullJPSonServer($gid));
		
		// delete database row
		$this->DB->query('DELETE FROM '.Threeds_Config::TABLE_IMAGE.' WHERE gid="'.$gid.'"');
	}
	
	function setProfile($name, $profile, $twitter='') {
		if ( !$this->Auth->isLog() )
			return;
		
		if ( !empty($twitter) ) {
			if ( $twitter[0]=='@' ) {
				 $twitter = substr( $twitter, 1 );
			}
		}

		$this->DB->updateRow( Threeds_Config::TABLE_USER,
			array ( 'authID' => $this->Auth->getAuthenticationID()),
			array ( 'name' => $name, 'profile' => $profile, 'twitterName' => $twitter) );
		
		$this->userName = $name;
	}

	function setParallax($gid, $parallax) {
		$this->DB->updateRow( Threeds_Config::TABLE_IMAGE,
			array ( 'gid' => $gid),
			array ( 'parallax' => $parallax) );
	}
	
	function setDescription($gid, $description) {
		$this->DB->updateRow( Threeds_Config::TABLE_IMAGE,
			array ( 'gid' => $gid),
			array ( 'description' => $description) );
			
	}

	function string2html($text, $createLink=false) {
		$text = htmlspecialchars($text);
		
		if ( $createLink ) {
			//check for links
			$text = preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" >$3</a>", $text);
			$text = preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" >$3</a>", $text);
			
			//check for e-mail
			$text = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text);

			//check for twitter
			$text = preg_replace("/(^|[\n ])@(\w+)/", '<a href="http://www.twitter.com/$2" target="_blank">@$2</a>', $text);
		}

		return nl2br($text);
	}

	// is can be authId or gid
	function getUserImageList($id, $numPerPage, $page=0) {
		if ( is_string($id) && $id[0]=='u') {
			$userImages = $this->DB->query( 'SELECT i.gid as gid, i.description as description, i.parallax as parallax, UNIX_TIMESTAMP(i.dateCreated) as dateCreated'.
				' FROM '.Threeds_Config::TABLE_USER.' as u, '.Threeds_Config::TABLE_IMAGE.' as i'.
				' WHERE i.authID = u.authID AND u.gid = "'.$id.'"'.
				' ORDER BY i.dateCreated DESC'.
				' LIMIT '.($numPerPage*$page).', '.$numPerPage);
		} else {
			$userImages = $this->DB->query( 'SELECT gid, description, parallax, UNIX_TIMESTAMP(dateCreated) as dateCreated'.
				' FROM '.Threeds_Config::TABLE_IMAGE.
				' WHERE authID = '.$id.
				' ORDER BY dateCreated DESC'.
				' LIMIT '.($numPerPage*$page).', '.$numPerPage);
		}
		
		return $userImages;
	}
	
	function getRecentImages($numPerPage, $page=0) {
		$recentImages = $this->DB->query( 'SELECT gid, description, parallax, UNIX_TIMESTAMP(dateCreated) as dateCreated'.
			' FROM '.Threeds_Config::TABLE_IMAGE.
			' ORDER BY dateCreated DESC'.
			' LIMIT '.($numPerPage*$page).', '.$numPerPage);
		
		return $recentImages;
	}
	
	function getRandomImages($num) {
		$recentImages = $this->DB->query( 'SELECT gid, description, parallax, UNIX_TIMESTAMP(dateCreated) as dateCreated'.
			' FROM '.Threeds_Config::TABLE_IMAGE.
			' ORDER BY RAND()'.
			' LIMIT '.$num);
		
		return $recentImages;
	}
	
	function getPrevNextImage($gid) {
		$prevImage = $this->DB->query( 'SELECT res.gid as gid, res.description as description, res.parallax as parallax'.
			' FROM '.Threeds_Config::TABLE_IMAGE.' as arg, '.Threeds_Config::TABLE_IMAGE.' as res'.
			' WHERE arg.gid="'.$gid.'" AND arg.authID=res.authID and res.id<arg.id'.
			' ORDER BY res.id DESC'.
			' LIMIT 1');
		$nextImage = $this->DB->query( 'SELECT res.gid as gid, res.description as description, res.parallax as parallax'.
			' FROM '.Threeds_Config::TABLE_IMAGE.' as arg, '.Threeds_Config::TABLE_IMAGE.' as res'.
			' WHERE arg.gid="'.$gid.'" AND arg.authID=res.authID and res.id>arg.id'.
			' ORDER BY res.id'.
			' LIMIT 1');

		$images = array_merge($nextImage, $prevImage);
		
		return $images;
	}
	
	function getUserInfo($id) {
		if ( is_string($id) && $id[0]=='u') {
			$user = $this->DB->query( 'SELECT authID, gid, name, twitterName, profile'.
				' FROM '.Threeds_Config::TABLE_USER.
				' WHERE gid = "'.$id.'"');
		} else {
			$user = $this->DB->query( 'SELECT authID, gid, name, twitterName, profile'.
				' FROM '.Threeds_Config::TABLE_USER.
				' WHERE authID = '.$id);
		}
		
		if ( empty($user) ) {
			return false;
		}
				
		if ( empty($user[0]['name']) ) 
			$user[0]['name'] = Threeds_Config::DEFAULT_NAME;

		return $user[0];
	}
	
	function getImageInfo($id) {
		if ( is_string($id) && $id[0]=='i') {
			$image = $this->DB->query( 'SELECT authID, gid, description, parallax'.
				' FROM '.Threeds_Config::TABLE_IMAGE.
				' WHERE gid = "'.$id.'"');
		} else {
			$image = $this->DB->query( 'SELECT authID, gid, description, parallax'.
				' FROM '.Threeds_Config::TABLE_IMAGE.
				' WHERE id = '.$id);
		}
		
		if ( empty($image) )
			return false;
			
		return $image[0];
	}
	
	function getMPO($gid) {
		return '/'.Threeds_Config::PATH_MPO.'/'.$gid.'.mpo';
	}
	
	function getMPOonServer($gid) {
		return Threeds_Config::PATH_MPO.'/'.$gid.'.mpo';
	}
	
	function getJPG($gid) {
		return '/'.Threeds_Config::PATH_MPO.'/'.$gid.'.jpg';
	}
	
	function getJPGonServer($gid) {
		return Threeds_Config::PATH_MPO.'/'.$gid.'.mpo';
	}
	
	function getThumbJPS($gid) {
		$path = '/'.Threeds_Config::PATH_THUMB.'/'.$gid.'.jps';
		
		if ( file_exists($this->getThumbJPSonServer($gid))==false )
			$this->generateThumbnail($gid);
			
		return $path;
	}
	
	function getThumbJPSonServer($gid) {
		$path = Threeds_Config::PATH_THUMB.'/'.$gid.'.jps';
		
		return $path;
	}
	
	function getFullJPS($gid) {
		$path = '/'.Threeds_Config::PATH_JPS.'/'.$gid.'.jps';
		
		if ( file_exists($this->getFullJPSonServer($gid))==false )
			$this->generateThumbnail($gid);
			
		return $path;
	}
	
	function getFullJPSonServer($gid) {
		$path = Threeds_Config::PATH_JPS.'/'.$gid.'.jps';
		
		return $path;
	}

	function getBestParallaxAroundPoint($gdImg, $x, $y, $debugfile=false) {
		$x = round($x);
		$y = round($y);
		$width = imagesx($gdImg) / 2;
		$searchWidth = round($width * 0.2);
		$minParallax = round((1.0-Threeds_Config::PARALLAX_MIN)*$width);
		$maxParallax = round((1.0-Threeds_Config::PARALLAX_MAX)*$width);
		
		// first we get the colours on the right image
		$cRightR = array();
		$cRightG = array();
		$cRightB = array();
		for ($ix=$x-$searchWidth, $i=0; $ix<=($x+$searchWidth); $ix++, $i++) {
			$c = imagecolorat($gdImg, $width + $ix, $y);
			$cRightR[$i] = ($c >> 16) & 0xFF;
			$cRightG[$i] = ($c >> 8) & 0xFF;
			$cRightB[$i] = $c & 0xFF;
		}
		
		// Now we search for the best point to look for the parallax
		$bestPoint = 1;
		$i = 1;
		$prevR = $cRightR[$i-1] - $cRightR[$i];
		$prevG = $cRightG[$i-1] - $cRightG[$i];
		$prevB = $cRightB[$i-1] - $cRightB[$i];
		$nextR = $cRightR[$i] - $cRightR[$i+1];
		$nextG = $cRightG[$i] - $cRightG[$i+1];
		$nextB = $cRightB[$i] - $cRightB[$i+1];
		$bestScore = $prevR*$prevR + $prevG*$prevG + $prevB*$prevB;
		$bestScore+= $nextR*$nextR + $nextG*$nextG + $nextB*$nextB;
		$end = count($cRightR)-1;
		
		for ( ; $i<$end; $i++ ) {
			$prevR = $cRightR[$i-1] - $cRightR[$i];
			$prevG = $cRightG[$i-1] - $cRightG[$i];
			$prevB = $cRightB[$i-1] - $cRightB[$i];
			$nextR = $cRightR[$i] - $cRightR[$i+1];
			$nextG = $cRightG[$i] - $cRightG[$i+1];
			$nextB = $cRightB[$i] - $cRightB[$i+1];
			$score = $prevR*$prevR + $prevG*$prevG + $prevB*$prevB;
			$score+= $nextR*$nextR + $nextG*$nextG + $nextB*$nextB;
			
			if ( $score > $bestScore) {
				$bestPoint = $i;
				$bestScore = $score;
			}
		}
		$shift = $bestPoint - $searchWidth;
		
		// the left picture, we cache the range of pixel in potential parallax
		$cLeftR = array();
		$cLeftG = array();
		$cLeftB = array();
		$minX = $x - $minParallax + $shift;
		$maxX = $x - $maxParallax + $shift;
		for ($ix=($minX-1), $i=0; $ix<=($maxX+1); $ix++, $i++) {
			$c = imagecolorat($gdImg, $ix, $y);
			$cLeftR[$i] = ($c >> 16) & 0xFF;
			$cLeftG[$i] = ($c >> 8) & 0xFF;
			$cLeftB[$i] = $c & 0xFF;
		}
		
		if ( $debugfile ) {
			$f = fopen('data/parallax.x'.$x.'.y'.$y.'.log', 'w');
		}
		
		// now we try to find the best parallax in these array
		$bestParallax = 1;
		$prevR = $cLeftR[0]-$cRightR[$bestPoint-1];
		$prevG = $cLeftG[0]-$cRightG[$bestPoint-1];
		$prevB = $cLeftB[0]-$cRightB[$bestPoint-1];
		$currR = $cLeftR[1]-$cRightR[$bestPoint];
		$currG = $cLeftG[1]-$cRightG[$bestPoint];
		$currB = $cLeftB[1]-$cRightB[$bestPoint];
		$nextR = $cLeftR[2]-$cRightR[$bestPoint+1];
		$nextG = $cLeftG[2]-$cRightG[$bestPoint+1];
		$nextB = $cLeftB[2]-$cRightB[$bestPoint+1];
		$bestScore = $prevR*$prevR + $prevG*$prevG + $prevB*$prevB;
		$bestScore+= ($currR*$currR + $currG*$currG + $currB*$currB)*2;
		$bestScore+= $nextR*$nextR + $nextG*$nextG + $nextB*$nextB;
		$end = count($cLeftR)-1;
		
		if ( $debugfile ) {
			fwrite($f, 'minParallax:'.$minParallax.'; maxParallax:'.$maxParallax.'; end:'.$end."\n");
			fwrite($f, 'p:1; score:'.$bestScore."\n");
			fwrite($f, "\tprevR:$prevR; prevG:$prevG; prevB:$prevB\n");
			fwrite($f, "\tcurrR:$currR; currG:$currG; currB:$currB\n");
			fwrite($f, "\tnextR:$nextR; nextG:$nextG; nextB:$nextB\n");
		}

		for ($i=2; $i<$end; $i++) {
			$prevR = $cLeftR[$i-1]-$cRightR[$bestPoint-1];
			$prevG = $cLeftG[$i-1]-$cRightG[$bestPoint-1];
			$prevB = $cLeftB[$i-1]-$cRightB[$bestPoint-1];
			$currR = $cLeftR[$i]-$cRightR[$bestPoint];
			$currG = $cLeftG[$i]-$cRightG[$bestPoint];
			$currB = $cLeftB[$i]-$cRightB[$bestPoint];
			$nextR = $cLeftR[$i+1]-$cRightR[$bestPoint+1];
			$nextG = $cLeftG[$i+1]-$cRightG[$bestPoint+1];
			$nextB = $cLeftB[$i+1]-$cRightB[$bestPoint+1];
			$score = $prevR*$prevR + $prevG*$prevG + $prevB*$prevB;
			$score+= ($currR*$currR + $currG*$currG + $currB*$currB)*2;
			$score+= $nextR*$nextR + $nextG*$nextG + $nextB*$nextB;

			if ( $debugfile ) {
				fwrite($f, 'p:'.$i.'; score:'.$score."\n");
				fwrite($f, "\tprevR:$prevR; prevG:$prevG; prevB:$prevB\n");
				fwrite($f, "\tcurrR:$currR; currG:$currG; currB:$currB\n");
				fwrite($f, "\tnextR:$nextR; nextG:$nextG; nextB:$nextB\n");
			}
	
			if ( $score < $bestScore ) {
				$bestParallax = $i;
				$bestScore = $score;
			}
		}
		
		if ( $debugfile ) {
			$c = imagecolorallocate($gdImg, 0, 255, 255);
			imagesetpixel($gdImg, $x + $shift - $minParallax, $y, $c);
			imagesetpixel($gdImg, $x + $shift - $minParallax+1, $y, $c);
			imagesetpixel($gdImg, $x + $shift - $maxParallax-1, $y, $c);
			imagesetpixel($gdImg, $x + $shift - $maxParallax, $y, $c);
			
			$c = imagecolorallocate($gdImg, 255, 0, 255);
			imagesetpixel($gdImg, $x + $shift + $width, $y, $c);
			imagesetpixel($gdImg, $x + $shift - $minParallax + $bestParallax - 1, $y, $c);

			imagepng($gdImg, 'data/debugbestparallax.png');
			fclose($f);
		}

		// Now we get the parallax
		if ($bestParallax==1)
			return Threeds_Config::PARALLAX_MIN;
			
		return Threeds_Config::PARALLAX_MIN + ($bestParallax-1)/($minParallax-$maxParallax) * (Threeds_Config::PARALLAX_MAX-Threeds_Config::PARALLAX_MIN);
	}
	
	function getBestParallax($gid) {
		$img = imagecreatefromjpeg($this->getThumbJPSonServer($gid));
		if ( $img===false )
			return Threeds_Config::PARALLAX_DEFAULT;
		
		$width = imagesx($img) / 2;
		$height = imagesy($img);

		$debugParallax = false;
		$parallax = $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.45, $debugParallax);
		$parallax+= $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.5, $debugParallax);
		$parallax+= $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.55, $debugParallax);
		$parallax+= $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.6, $debugParallax);
		$parallax+= $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.65, $debugParallax);
		$parallax+= $this->getBestParallaxAroundPoint($img, $width*0.5, $height*0.7, $debugParallax);
		$parallax/= 6;

		imagedestroy($img);
		return $parallax;	
	}
	
	
	
	
	
	/* PARALLAX DETECTION AREA */
	
	function getBestParallaxArea($gid, $x, $y, $debugfile=false) {
		$gdImg = imagecreatefromjpeg($this->getThumbJPSonServer($gid));
//		$gdImg = imagecreatefromjpeg($this->getFullJPSonServer($gid));

		if ( $gdImg===false )
			return Threeds_Config::PARALLAX_DEFAULT;
	
		// setup some variables
		$width = imagesx($gdImg) / 2;
		$height = imagesy($gdImg);
		$x = round($x*$width);
		$y = round($y*$height);
		$searchWidth = round($width * 0.2);
		$minParallax = -round((1.0-Threeds_Config::PARALLAX_MIN)*$width);
		$maxParallax = -round((1.0-Threeds_Config::PARALLAX_MAX)*$width);
		$areaW = 3;
		$areaH = 2;
		$areaWidth = $areaW*2 + 1;
		$areaHeight = $areaH*2 + 1;
		

		$areaX = $x - $areaW;
		$areaY = $y - $areaH;
		if ( $areaX<0 ) $areaX = 0;
		if ( $areaY<0 ) $areaY = 0;
		if ( $areaX+$areaWidth>=$width ) $areaX = $width - $areaWidth;
		if ( $areaY+$areaHeight>=$height ) $areaY = $height - $areaHeight;
		
		// Now we know wich area to look for, we cache it
		$cRightR = array();
		$cRightG = array();
		$cRightB = array();
		for ($iy=$areaY, $i=0; $i<$areaHeight; $iy++, $i++) {
			$cRightR[$i] = array();
			$cRightG[$i] = array();
			$cRightB[$i] = array();
			for ($jx=$areaX, $j=0; $j<$areaWidth; $jx++, $j++) {
				$c = imagecolorat($gdImg, $width + $jx, $iy);
				$cRightR[$i][$j] = ($c >> 16) & 0xFF;
				$cRightG[$i][$j] = ($c >> 8) & 0xFF;
				$cRightB[$i][$j] = $c & 0xFF;
			}
		}
		
		
		// We also cache the left picture first area
		$areaLeftX = $areaX + $minParallax;
		if ( $areaLeftX<0 ) {
			$areaLeftX = 0;
			$minParallax = $areaLeftX - $areaX;
		}

		$cLeftR = array();
		$cLeftG = array();
		$cLeftB = array();
		for ($iy=$areaY, $i=0; $i<$areaHeight; $iy++, $i++) {
			$cLeftR[$i] = array();
			$cLeftG[$i] = array();
			$cLeftB[$i] = array();
			for ($jx=$areaLeftX, $j=0; $j<$areaWidth+($maxParallax-$minParallax); $jx++, $j++) {
				$c = imagecolorat($gdImg, $jx, $iy);
				$cLeftR[$i][$j] = ($c >> 16) & 0xFF;
				$cLeftG[$i][$j] = ($c >> 8) & 0xFF;
				$cLeftB[$i][$j] = $c & 0xFF;
			}
		}
	
		// now we try to find the best parallax in these array
		$bestParallax = 0;
		$bestScore = $areaWidth*$areaHeight*3*256*256;
		for ($p=$minParallax, $right=($areaLeftX+$p+$areaWidth); $p<$maxParallax, $right<$width; $p++, $right++) {
			// we calculate the score for this parallax
			$score = 0;
			for ($i=0; $i<$areaHeight; $i++) { for ($j=0; $j<$areaWidth; $j++) {
				$R = $cLeftR[$i][$j] - $cRightR[$i][$j];
				$G = $cLeftG[$i][$j] - $cRightG[$i][$j];
				$B = $cLeftB[$i][$j] - $cRightB[$i][$j];
				$score+= $R*$R + $G*$G + $B*$B;
			}}
			
			if ( $score<$bestScore ) {
				$bestScore = $score;
				$bestParallax = $p;
			}
			
			// we update the left cache for the next parallax
			for ($i=0; $i<$areaHeight; $i++) {
				array_shift($cLeftR[$i]);
				array_shift($cLeftG[$i]);
				array_shift($cLeftB[$i]);
			}
		}
		
		if ( $debugfile ) {
			$c = imagecolorallocate($gdImg, 0, 255, 255);
			imagerectangle($gdImg, $areaX + $width, $areaY, $areaX + $areaWidth + $width, $areaY + $areaHeight, $c);
			imagerectangle($gdImg, $areaX + $bestParallax, $areaY, $areaX + $areaWidth + $bestParallax, $areaY + $areaHeight, $c);
			
			$c = imagecolorallocate($gdImg, 255, 0, 255);
			imagesetpixel($gdImg, $x + $width, $y, $c);
			imagerectangle($gdImg, $areaLeftX, $areaY-1, $areaX + $maxParallax, $areaY-1, $c);

			imagepng($gdImg, 'data/debug/'.$gid.'.png');
		}

		imagedestroy($gdImg);
		
		// Now we get the parallax
		if ($bestParallax==0)
			return Threeds_Config::PARALLAX_DEFAULT;
		
		return (1.0 + $bestParallax/$width);
	}	
	
	
	
	function generateThumbnail($gid) {
		// first we load the mpo
		$file = $this->getMPOonServer($gid);
		if ( file_exists($file)===false ) {
			return;
		}
		
		$filesize = filesize($file);
		$mpofile = fopen($file, 'rb');
		if ( !$mpofile )
			return;
		
		$content = fread( $mpofile, $filesize);
		fclose($mpofile);
		
		$middle = round($filesize / 2);
		$split = 0;
		for ( $shift = 0; $shift<$middle && $split==0; $shift++ ) {
			if ($content[0]==$content[0+$middle+$shift] && 
				$content[1]==$content[1+$middle+$shift] &&
				$content[2]==$content[2+$middle+$shift] &&
				$content[3]==$content[3+$middle+$shift] ) {
					$split = $middle+$shift;
			} else
			if ($content[0]==$content[0+$middle-$shift] && 
				$content[1]==$content[1+$middle-$shift] &&
				$content[2]==$content[2+$middle-$shift] &&
				$content[3]==$content[3+$middle-$shift] ) {
					$split = $middle-$shift;
			}
		}
		
		if ( $split==0 ) {
			$rightImageData = $leftImageData = $content;
		}
		else {
			$leftImageData = substr($content, 0, $split);
			$rightImageData = substr($content, $split);
		}
		
		$gdImgLeft = imagecreatefromstring($leftImageData);
		$gdImgRight = imagecreatefromstring($rightImageData);
		
		// create the full jps file
		$srcX = 0;
		$srcY = 0;
		$srcWidth = imagesx($gdImgLeft);
		$srcHeight = imagesy($gdImgLeft);
		$destWidth = Threeds_Config::JPS_WIDTH;
		$destHeight = $srcHeight * ($destWidth / $srcWidth);

		$jps = imageCreateTrueColor( $destWidth * 2, $destHeight );
		imageCopyResampled( $jps, $gdImgLeft, 0, 0, $srcX, $srcY, $destWidth, $destHeight, $srcWidth, $srcHeight );
		imageCopyResampled( $jps, $gdImgRight, Threeds_Config::JPS_WIDTH, 0, $srcX, $srcY, $destWidth, $destHeight, $srcWidth, $srcHeight );
		imagejpeg( $jps, $this->getFullJPSonServer($gid));

		// create thumbnail
		$thumb = imageCreateTrueColor( Threeds_Config::THUMB_WIDTH * 2, Threeds_Config::THUMB_HEIGHT );
		imageCopyResampled( $thumb, $jps, 0, 0, 0, 0,
			Threeds_Config::THUMB_WIDTH * 2, Threeds_Config::THUMB_HEIGHT,
			$destWidth * 2, $destHeight );
		imagejpeg( $thumb, $this->getThumbJPSonServer($gid), 90);

		imageDestroy( $jps);
		imageDestroy( $thumb);
		imageDestroy( $gdImgLeft);
		imageDestroy( $gdImgRight);
	}
	
	// Tools 
	private function globalID($prefix, $id) {
		return $prefix.$this->encodeBase62($id);
	}
	
	private function encodeBase62($value) {
		$chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		$str = '';
		do {
		    $m = bcmod($value, 62);
		    $str = $chars[$m] . $str;
		    $value = bcdiv(bcsub($value, $m), 62);
		} while(bccomp($value,0)>0);
		return $str;
	}
}

?>