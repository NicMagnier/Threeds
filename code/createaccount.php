<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	// Is The user already secured
	if ( $Threeds->Auth->isLog() ) {
		header("Location: /");
	}

	// Try to secure
	if ( !empty($_POST['login']) ) {
		if ( $_POST['pass1'] != $_POST['pass2'] ) {
			$message = "<p>The passwords doesn't match. Please enter the same password in both fields.</p>";
		}
		else if ( !$Threeds->Auth->isValidEmail( $_POST['login']) ) {
			$message = "<p>This is not a valid e-mail address.</p>";
		}
		else if ( $Threeds->createAccount( $_POST['login'], $_POST['pass1']) === true ) {
			header("Location: /u");
		}
		else {
			$message = "<p>The login is already used by an other user. Please use a different login or <a href='/signin'>Sign-in</a> if you already have an account.</p>";
		}
	}

	include 'layout/header.php';

	if ( !empty($message) )
		echo '<div class="blockContent error">'.$message.'</div>';

?>
	<form class="form500" method="post" action="/createaccount">
	<div class="formBlock">
		<h2>Create your account</h2>
		
		<p>
		<label for="login">Your email</label>
		<input type="text" autocorrect="off" autocapitalize="off" id="login" name="login" value="<? echo $_POST['login']; ?>"/>
		</p>
		
		<p>
		<label for="pass">Password <em>(be creative)</em></label>
		<input type="password" id="pass1" name="pass1"/>
		</p>
		
		<p>
		<label for="pass">Password confirmation</label>
		<input type="password" id="pass2" name="pass2"/>
		</p>
		
		<p><input type="submit" name="submit" value="Create your account" /></p>
	</div>
	</form>

<?php 
	include 'layout/footer.php';
?>
