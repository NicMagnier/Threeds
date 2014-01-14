<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	if ( isset($_POST['hash']) )	$hash = $_POST['hash'];
	else							$hash = $arguments[0];
	
	if ( isset($_POST['login']) )	$login = $_POST['login'];
	else							$login = $arguments[1];
	
	// Try to secure
	if ( isset($_POST['hash']) ) {
		if ( $_POST['pass1'] != $_POST['pass2'] ) {
			$messageClass = 'error';
			$message = "<p>The passwords doesn't match. Please enter the same password in both fields.</p>";
		}
		else if ( $Threeds->Auth->resetPassword( $_POST['login'], $_POST['pass1'], $_POST['hash']) === true ) {
			$messageClass = 'confirmation';
			$message = '<p>Your password was reset successfully. Try to not forget it this time :)</p>';
			$message.= '<div class="rightalign"><button class="sexybutton sexysimple sexyblue sexsmall" onclick="window.location.href=\'/\'">Sure, Thanks</button></div>';
		}
		else {
			$messageClass = 'error';
			$message = "<p>An error occured. The request for password reset might be updated or doesn't match your login.</p>";
		}
	}

	include 'layout/header.php';

	// Message after the header
	if ( $message!=NULL ) echo '<div id="message" class="'.$messageClass.'">'.$message.'</div>';

	if ( $messageClass != 'confirmation' )
	{
?>
	<form class="form500" method="post" action="/resetpassword">
	<div class="formBlock">
		<h2>Enter your new password</h2>

		<p>
		<label for="product">Your e-mail</label>
		<input type="text" id="login" name="login"/ value="<? echo $login; ?>">
		</p>
		
		<p><label for="pass1">New Password</label><input type="password" id="pass1" name="pass1"/></p>
		<p><label for="pass2">Password confirmation</label><input type="password" id="pass2" name="pass2"/></p>
		
		<p><label for="hash">Verification Number received by e-mail</label><input type="text" id="hash" name="hash" value="<?php echo $hash;?>"/></p>
		
		<p><input type="submit" name="submit" value="Reset my Password" /></p>
	</div>
	</form>

<?php 
	}
	
	include 'layout/footer.php';
?>
