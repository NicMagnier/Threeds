<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	$image = $Threeds->getImageInfo($_POST['gid']);
	
	if ( $Threeds->hasAdminRight($image['authID']) ) {
		if ( isset($_POST['parallax']) ) {
			$Threeds->setParallax($_POST['gid'], $_POST['parallax']);
		}
	
		if ( isset($_POST['description']) ) {
			$Threeds->setDescription($_POST['gid'], stripslashes($_POST['description']));
		}
	}
?>
{
	"gid": "<?php echo $_POST["gid"]; ?>"
}