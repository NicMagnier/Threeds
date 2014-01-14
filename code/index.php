<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	include 'layout/header.php';
	
?>

<div class="dcBlock dcRight">
<?php
	$randomList = $Threeds->getRandomImages(6);
	$newList = $Threeds->getRecentImages(6);
	
	$imgList = array_merge($newList, array('hr'), $randomList);
	
	foreach($imgList as $img) {
		if ( $img=='hr' ) {
			echo '<hr>';
			continue;
		}
	
		$gid = $img['gid'];
		echo '<div class="blockThumb">';
		echo '<a href="'.$gid.'">';
		echo '<div class="imgCanvas">';
		echo '<div class="imgCenter"><div class="imgContainer"><img src="'.$Threeds->getThumbJPS($gid).'" onload="imgWiggle(this, '.$img['parallax'].');"></div></div>';
		echo '</div>';
		echo '</a>';
		echo '<a href="'.$Threeds->getMPO($gid).'" class="mposhortcut">3D View</a>';
		echo '</div>';
	}
?>
</div>

<div class="dcBlock dcLeft">
	<div class="embossText">
	<p><strong>Threeds.me is the right place to post and share pictures taken from your Nintendo 3DS, Mii pictures and QR Codes.</strong></p>

	<p>Here some of the delicious features on the menu:
	<ul>
		<li>Post your pictures directly on Twitter.</li>
		<li>Upload quickly and easily pictures from your 3DS.</li>
		<li>Backup all your mpo pictures stored on your PC in one easy upload.</li>
		<li>It's fast, free, and ad-free!</li>
	</ul>
	</p>
	</div>
<?php
	if ( $Threeds->Auth->isLog()===false ) {
?>
<div class="form500">
	<div class="formBlock">
		<h2>Start now</h2>
		<p><a href="/twitter/signin"><input type="button" value="Signin with Twitter"/></a></p>
		<p><a href="/upload"><input type="button" value="Upload Pictures"/></a></p>
	</div>
</div>
<?php
	}
?>
</div>
	
<?php
	include 'layout/footer.php';
?>
