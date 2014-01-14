<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	$imageGID = $page;
	$image = $Threeds->getImageInfo($imageGID);
	if ( $image===false )
		header("Location: /404");
	
	$user = $Threeds->getUserInfo($image['authID']);
		
	include 'layout/header.php';
?>

<?php $htmlid = $Threeds->getHTMLUniqueID(); ?>

<a href="<?php echo $Threeds->GetMPO($imageGID); ?>"><div class="menuParallax menuBar mposhortcut">Fullscreen 3D View</div></a>
<div class="postContainter" id="<?php echo $imageGID; ?>">
<div class="imgCanvas blockFull"><div class="imgCenter">
	<div class="imgContainer" id="i<?php echo $htmlid; ?>" onclick="pfocus(event, this, '<?php echo $imageGID; ?>');"><img class="scaleFor3DS" src="<?php echo $Threeds->getFullJPS($imageGID); ?>" onload="imgWiggle(this, <?php echo $image['parallax'];?>);"/></div>
</div></div>

<div class="imgMenu">

<div class="menuParallax menuBar">
	<span>Parallax
<?php
	if ( $Threeds->is3DS() ) {
		echo '<span class="pctrl psub">&#8592;</span><input type="text" class="parallax"/><span class="pctrl padd">&#8594;</span>';
	}
	else {
		echo '<span class="pctrl psub">&#9664;</span><input type="text" class="parallax"/><span class="pctrl padd">&#9654;</span>';
		echo '<span class="tips">Click the picture to adjust the focus</span>';
	}
?>
	</span>
</div>

<div class="menuSave menuBar">
	<span class="save">Save</span>
	<span class="saved">Saved successfully</span>
	<span class="saving">Saving…</span>
</div>

<div class="description">
<?php 
	echo '<p>'.$Threeds->string2html($image['description'], true).'</p>';
	if ( $Threeds->hasAdminRight($image['authID']) ) {
		echo '<textarea>'.$image['description'].'</textarea>';
	}
	echo '<span class="signature">Posted by ';
	echo '<a href="/'.$user['gid'].'">'.$user['name'].'</a>';
	echo '</span>';
?>
</div>

<?php if ( $Threeds->hasAdminRight($image['authID']) ) { ?>
<div class="menuDelete menuBar">
	<span><a href="javascript:if(confirm('Do you want to delete this picture?')){document.location.href='delete/<?php echo $imageGID; ?>/<?php echo $Threeds->getDeleteHashCode($imageGID); ?>'}void(0);">Delete Picture</a></span>
</div>
<?php } ?>

<div class="menuShare menuBar">
	<span><strong>Image url:</strong> <a href="<?php echo $versionDomain.'/'.$imageGID.'.jpg'; ?>"><?php echo $versionDomain.'/'.$imageGID.'.jpg'; ?></a></span>
</div>

</div>

</div>

<?php
	include 'layout/footer.php';
?>