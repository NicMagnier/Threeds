<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	// Action
	if ( !empty($_POST['login']) ) {
		if ( $Threeds->Auth->signin($_POST['login'], $_POST['pass']) )
			header("Location: /u");
			
		$loginerror = true;
	}
	
	if ( $loginerror ) {
		$message = 'Oups! this is not the correct password. <a href="/forgotpassword">Did you forget it?</a>';
	}
	
	if ( !empty($_COOKIE['login']) )
		$defaultLogin = $_COOKIE['login'];
	if ( !empty($_POST['login']) )
		$defaultLogin = $_POST['login'];
		
	include 'layout/header.php';

	if ( !empty($message) )
		echo '<div class="blockContent error">'.$message.'</div>';

?>
	<div class="form500"><div class="formBlock">
		<h2>Signin with a Twitter account</h2>
		<a href="/twitter/signin"><input type="button" value="Signin ..."/></a>
	</div></div>
	
	<form class="form500" method="post" action="/signin">
	<div class="formBlock">
		<h2>Signin with an email account</h2>
		
		<p>
		<label for="login">Your e-mail</label>
		<input type="text" autocorrect="off" autocapitalize="off" id="login" name="login" value="<?php echo $defaultLogin; ?>"/>
		</p>
		
		<p>
		<label for="pass">Password</label>
		<input type="password" id="pass" name="pass" <?php if (!empty($defaultLogin)) echo 'autofocus'; ?> />
		</p>
		
		<p><input type="submit" name="submit" value="Signin" /></p>
	</div>
	</form>
	
	<div class="form500"><div class="formBlock">
		<h2>Forgot your Password?</h2>
		<a href="/forgotpassword"><input type="button" value="Reset Password"/></a>
	</div></div>
	
<?php
	include 'layout/footer.php';
?>

