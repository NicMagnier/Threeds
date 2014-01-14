<?php
include_once 'lib/threeds.class.php';
$Threeds = new Threeds();

$Threeds->Auth->signout();
header("Location: /");
?>