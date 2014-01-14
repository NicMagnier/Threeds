<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	if ( $arguments[0][0]=='u' )
		$userGID = array_shift($arguments);
	$pageshift = array_shift($arguments);
	$nextshift = $pageshift+1;
	$previousshift = $pageshift-1;
	
	if ( $Threeds->is3DS() )
		$numberPerPage = 20;
	else
		$numberPerPage = 40;

	if ( !empty($userGID) ) {
		$linkpath = '/recent/'.$userGID.'/';
		$user = $Threeds->getUserInfo($userGID);
		if ( $user===false ) {
			$userGID = 0;
		}
		else {
			$imgList = $Threeds->getUserImageList($user['authID'], $numberPerPage, $pageshift);
			$before = $Threeds->getUserImageList($user['authID'], 1, $pageshift*$numberPerPage-1);
			$after = $Threeds->getUserImageList($user['authID'], 1, ($pageshift+1)*$numberPerPage);
		}
	}
	
	if ( empty($userGID) ) {
		$linkpath = '/recent/';
		$imgList = $Threeds->getRecentImages($numberPerPage, $pageshift);
		$before = $Threeds->getRecentImages(1, $pageshift*$numberPerPage-1);
		$after = $Threeds->getRecentImages(1, ($pageshift+1)*$numberPerPage);
	}
		
	include 'layout/header.php';
	
	
	echo '<div class="sBlock">';
	echo '<h2 class="embossText">';
	if ( empty($userGID) )
		echo 'Recent pictures';
	else
		echo '<a href="/'.$user['gid'].'">'.$user['name'].'</a>\'s Recent pictures';
	echo '</h2>';
	
	foreach($imgList as $img) {
		$gid = $img['gid'];
		echo '<div class="blockThumb">';
		echo '<a href="/'.$gid.'">';
		echo '<div class="imgCanvas">';
		echo '<div class="imgCenter"><div class="imgContainer"><img src="'.$Threeds->getThumbJPS($gid).'" onload="imgWiggle(this, '.$img['parallax'].');"></div></div>';
		echo '</div>';
		echo '</a>';
		echo '<a href="'.$Threeds->getMPO($gid).'" class="mposhortcut">3D View</a>';
		echo '</div>';
	}
	
	echo '<div>';
	if ( !empty($before) )
		echo '<a class="previous" href="'.$linkpath.$previousshift.'">&laquo; Previous</a>';
	if ( !empty($after) )
		echo '<a class="next" href="'.$linkpath.$nextshift.'">Next &raquo;</a>';
	echo '</div>';
	echo '</div>';
	
	include 'layout/footer.php';
?>