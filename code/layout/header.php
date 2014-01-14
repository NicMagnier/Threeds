<!DOCTYPE html>
<html>
<head>
<title>Threeds.me</title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<?php
	$minify = '.min';

	$cssfile = $versionPath.'/layout/style'.$minify.'.css';

	if ($Threeds->is3DS()) {
		$cssfile = $versionPath.'/layout/style.3ds'.$minify.'.css';
		echo '<meta name="viewport" content="width=320, user-scalable=no">';
		echo '<script>window.scrollTo(40, 220);</script>';
	}
	else {
		echo '<meta name="viewport" content="width=720, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />';
	}

	// if this is a user page	
	if ( $page[0]=='u' )
		$rss = $versionDomain.'/rss/'.$page;
	else
		$rss = $versionDomain.'/rss';
		
	// if this is an image page
	if ( $page[0]=='i' )
		echo '<link rel="image_src" href="'.$versionDomain.'/'.$page.'.jpg" />';
?>

<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo $rss; ?>" />
<link rel="stylesheet" type="text/css" href="<? echo $cssfile; ?>">
<link rel="shortcut icon" href="<? echo $versionPath; ?>/layout/favicon.ico" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script>LoadAnimation = $('<img/>').attr("src", "<? echo $versionPath; ?>/layout/loading.gif");</script>
<script src="<? echo $versionPath; ?>/js/wiggle<? echo $minify; ?>.js" type="text/javascript"></script>
</head>

<body class="noWiggleScript" onload="wiggle.init();">

<div id="fixedtop"><div id="header">

	<div id="header_content">
	<?php
		// add the QR code, except if it's on the 3DS
		if ( $Threeds->is3DS()==false ) {
			if ( $page[0]=='i' ) {
				$url = 'http://threeds.me/'.$page.'.mpo';
				echo '<div id="qrtips">Scan this QR Code to check this image on your 3DS</div>';
			}
			else {
				$url = 'http://threeds.me/'.$page.'/qr';
				echo '<div id="qrtips">Scan this QR Code to check this page on your 3DS</div>';
			}

			echo '<div id="qr"><img src="http://chart.googleapis.com/chart?cht=qr&chs=80x80&chld=L|0&chl='.urlencode($url).'" alt="QR code" width="80" height="80"/></div>';
		}
	?>
	<a href="/"><h1>Threeds.me <span>Share your Nintendo 3DS pictures</span></h1></a>
	</div>

<form id="uploadform" method="post" action="/upload" enctype="multipart/form-data">
<input onchange="$('#uploadform').submit();" type="file" name="mpo" id="formfile" />
</form>

<div id="menu">
<ul class="big">
<li><a href="/twitter">Post on Twitter</a></li>
<?php
	// Upload Link - For 3DS
	if ( $Threeds->is3DS() ) {
?>
<li><a href="#" onclick="$('#formfile').click();return false;">Quick Upload</a></li>
<?php
	// Upload Link - For other browser
	} else{
?>
	<li><a href="/upload">Upload Pictures</a></li>
<?php } ?>

</ul>
<ul class="small">
	<li><a href="/">Front page</a></li>
	<li><a href="/recent">Explore</a></li>

<?php
	// When LazyRegistered
	if ( $Threeds->Auth->isLazyRegistration() ) {
?>
<li><a href="/u">Your Page</a></li>

<?php
	// When logged normaly
	} else if ( $Threeds->Auth->isLog() ) {
?>
<li>Logged as <a href="/u"><?php echo $Threeds->getUserName(); ?></a></li>
<li><a href="/signout">Signout</a></li>

<?php
	// When not logged
	} else {
?>
<li><a href="/signin">Signin</a></li>
<li><a href="/createaccount">Create Account</a></li>

<?php } ?>
</ul>
</div>

<?php
	// display an notification if the user is LazyRegistered
	if ( $Threeds->Auth->isLazyRegistration() ) {
		$headerMessage = '<p>Don\'t risk to lose the ability to edit your pictures. <a href="/secure">Secure your account</a>.</p>';
	}

	// We display the error message in the header on the 3DS
	if ( empty($headerMessage)==false && $Threeds->is3DS() ) {
		echo '<div class="blockContent notification">'.$headerMessage.'</div>';
	}
?>

</div></div>

<div id="container">

<?php
	// We display the error message in the container most of the time
	if ( empty($headerMessage)==false && $Threeds->is3DS()==false ) {
		echo '<div class="blockContent notification">'.$headerMessage.'</div>';
	}
?>