<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	// verify hash code for the picture
	if ( $Threeds->getDeleteHashCode($arguments[0])==$arguments[1] ) {
		// This function will also verify if the user have the right
		$Threeds->deleteImage($arguments[0]);
	}

	header("Location: /u");
?>