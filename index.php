<?php

$versionDomain = 'http://threeds.me';
$versionPath = 'http://threeds.me/code';
$versionUnixPath = './code';

if ( empty($p) ) {
	$page = 'home';
	include $versionUnixPath . '/index.php';
	exit;
}

// Mode Rewrite send the page and arguments
$arguments = explode('/', $p);
$page = array_shift($arguments);
$pagePath = $versionUnixPath . '/' . $page . '.php';

// normal php page
if ( file_exists($pagePath) )
	include $pagePath;
	
// image page
else if ( $page[0]=='i' ) {
	$ext = pathinfo($p, PATHINFO_EXTENSION);
	$gid = pathinfo($p, PATHINFO_FILENAME);
	
	if ( empty($ext) )
		include $versionUnixPath . '/image.php';
	else {
		switch ($ext) {
			case 'mpo':
			case 'jpg':
				$path = 'data/mpo/'.$gid.'.mpo';
				break;

			case 'jps':
				$path = 'data/jps/'.$gid.'.jps';
				break;

			case 'thumb':
				$path = 'data/thumb/'.$gid.'.jps';
				break;
		}
		
		if ( file_exists($path) ) {
			header("Pragma: public");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
			header("Content-Type: image/jpg");
			header("Content-Transfer-Encoding: binary"); 
			header("Content-Length: ".filesize($path)); 
			readfile($path);
		} else{
			header("Location: /404");
		}
	}
}
// user page
else if ( $page[0]=='u' )
	include $versionUnixPath . '/user.php';

// little trick, the server can serve a jpg but is in fact a mpo file
else if ( pathinfo($p, PATHINFO_EXTENSION)=='jpg' ) {
	$mpo = str_replace( '.jpg', '.mpo', './'.$p);
	if ( file_exists($mpo) ) {
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Content-Type: image/jpg");
		header("Content-Transfer-Encoding: binary"); 
		header("Content-Length: ".filesize($mpo)); 
		readfile($mpo);
	} else{
		header("Location: /404");
	}
}

// none of the above so error 404
else 
	header("Location: /404");
?>