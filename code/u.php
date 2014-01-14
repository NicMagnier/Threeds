<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	$gid = $Threeds->getUserGID();
	header("Location: /$gid");
?>