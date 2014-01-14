<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	// Action
	if ( isset($_POST['login']) ) {
		$hash = $Threeds->Auth->requestPasswordReset( $_POST['login']);
		if ( $hash === false ) {
			$error = $Threeds->Auth->getLastError();
		}
		else {
			$Name = "Threeds"; //senders name
			$email = "you@domain.com"; //senders e-mail adress
			$recipient = $_POST['login']; //recipient
			$mail_body = "Hi,\r\n\r\n"; //mail body
			$mail_body.= "You have requested a reset of your password to access your account on Threeds.me\r\n";
			$mail_body.= "To reset it, simply go to the following address: http://www.threeds.me/resetpassword/".$hash.'/'.$_POST['login'];
			$mail_body.= "\r\n\r\nThank you!";
			$subject = "Reset your password"; //subject
			$header = "From: ". $Name . " <" . $email . ">\r\n"; //optional headerfields
			
			ini_set('sendmail_from', 'you@domain.com'); //Suggested by "Some Guy"
			mail($recipient, $subject, $mail_body, $header);
			
			$messageClass = 'confirmation';
			$message = '<p>We have sent you an e-mail with a short instruction on how to reset your password. You should receive it soon.</p>';
			$message.= '<div class="rightalign"><button class="sexybutton sexysimple sexyblue sexsmall" onclick="window.location.href=\'/\'">Thanks!</button></div>';
		}
	}

	if ( !empty($_COOKIE['login']) )
		$defaultLogin = $_COOKIE['login'];
	if ( !empty($_POST['login']) )
		$defaultLogin = $_POST['login'];
		
	switch ( $error ) {
		case Authentication::ERR_LOGIN_DOESNT_EXIST:
			$messageClass = 'error';
			$message = '<p>This e-mail doesn\'t exist in our system. Check if you typed it correctly or simply <a href="/createaccount">Create a new account</a>.</p>';
			break;
			
		case Authentication::ERR_LOGIN_IS_NOT_MAIL:
			$messageClass = 'error';
			$message = '<p>Your account is not a valid e-mail address. Unfortunately accounts without a valid e-mail cannot reset their password.</p>';
			break;
	}
	
	include 'layout/header.php';
	
	if ( $message!=NULL ) echo '<div id="message" class="blockContent '.$messageClass.'">'.$message.'</div>';
	
	if ( $messageClass != 'confirmation' ) {
?>

	<form class="form500" method="post" action="/forgotpassword">
	<div class="formBlock">
		<h2>Request the reset of your password</h2>
		
		<p>
		<label for="login">Your e-mail</label>
		<input autofocus type="text" id="login" name="login" value="<?php echo $defaultLogin; ?>"/>
		</p>
		
		<p><input type="submit" name="submit" value="Reset Password" /></p>
	</div>
	</form>
	
<?php
	}
	
	include 'layout/footer.php';
?>

