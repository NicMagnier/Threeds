<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	$p = $Threeds->getBestParallaxArea($_POST["gid"], $_POST["x"], $_POST["y"], true);
?>
{
	"parallax": <?php echo $p; ?>,
	"gid": "<?php echo $_POST["gid"]; ?>",
	"hid": "<?php echo $_POST["hid"]; ?>"
}