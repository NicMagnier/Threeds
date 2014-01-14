<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	if ( $arguments[0][0]=='u' )
		$userGID = array_shift($arguments);

	$numberOfItem = 10;

	if ( empty($userGID) ) {
		$link = $versionDomain.'/';
		$title = 'Threeds.me';
		$imgList = $Threeds->getRecentImages($numberOfItem);
		$desc = $img['description'];
		$desc = 'Images recently posted';
	}
	else {
		$link = $versionDomain.'/'.$userGID;
		$user = $Threeds->getUserInfo($userGID);
		$title = $user['name'].' at Threeds.me';
		$imgList = $Threeds->getUserImageList($user['authID'], $numberOfItem);
		$desc = 'Images recently posted by '.$user['name'];
	}
	
header('Content-Type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
<title><?php echo $title; ?></title>
<link><?php echo $link; ?></link>
<description></description>
		
<?php
	foreach($imgList as $img) {
		$gid = $img['gid'];
		$link = $versionDomain.'/'.$gid;
		$imgurl = $versionDomain.'/'.$gid.'.jpg';
		$desc = $img['description'];
		$title = $img['description'];

		if ( empty($title) )
			$title = 'No title';
?>
<item>
<title><?php echo $title; ?></title>
<link><?php echo $link; ?></link>
<media:thumbnail url="<?php echo $imgurl ?>" />
<media:content url="<?php echo $imgurl ?>" />
<description>&lt;img src=&quot;<?php echo $imgurl ?>&quot; alt=&quot;&quot;/&gt; &lt;p&gt;<?php echo $desc; ?>&lt;/p&gt;</description>
<pubDate><?php echo date('r', $img['dateCreated']);?></pubDate>
<guid isPermaLink="false"><?php echo $gid; ?></guid>
</item>
<?php
	}
?>
</channel></rss>