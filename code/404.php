<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	include 'layout/header.php';
	echo '<div class="blockContent error"><p>This isn\'t the page you\'re looking for.</p><p>Error 404.</p></div>';

	echo '<div class="sBlock">';
	$imgList = $Threeds->getRandomImages(8);
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
	echo '</div>';

	include 'layout/footer.php';
?>

