<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	// The user try to signin through twitter.
	// we get a request token and redirect him to twitter page
	if ( $arguments[0]=='signin' ) {
		$Threeds->Auth->twitterAuthenticate($versionDomain.'/twitter');
	}
	if ( $arguments[0]=='signinToTweet' ) {
		setcookie('signinToTweet', 1, time()+60*60, '/', Threeds_Config::COOKIE_DOMAIN);
		$Threeds->Auth->twitterAuthenticate($versionDomain.'/twitter');
	}
	
	// twitter called us back and ask us to verify request token in order to get access token
	if ( $Threeds->Auth->isTwitterVerifier() ) {
		$result = $Threeds->Auth->twitterVerifier();
		
		// we get the access token from twitter, now let's sign-in the user		
		if ( $result !== false ) {
			$Threeds->connectTwitterAccount($result);
			
			if ( empty($_COOKIE['signinToTweet']) ) {
				header('Location: /u');
			}
			else {
				header('Location: /twitter');
				setcookie( 'signinToTweet', '', 1, '/', Threeds_Config::COOKIE_DOMAIN);
			}
		}
		else {
			$error = 'An wild twitter error appears. It could verify the user credentials. It\'s damn effective.';
		}
	}
	
	// User post something on twitter
	if ( isset($_POST['tweeterPost']) && ( !empty($_POST['tweet']) || sizeof($_FILES['picture'])!=0 ) ) {
		$twitter = $Threeds->getTwitterClass();
		$gid = NULL;

		if ( $_FILES['picture']['error']==UPLOAD_ERR_OK || $_FILES['picture']['error']!=UPLOAD_ERR_NO_FILE ) {
			if ( $_FILES['picture']['error']!=UPLOAD_ERR_OK ) {
				$error = 'An wild error appears. The error uses a problem in the upload. It\'s super effective.';
			} else {
				$gid = $Threeds->newImage($_FILES["picture"]["tmp_name"]);
				if ( $gid === false )
					$error = 'An wild error appears. The error couldn\'t generate the 3D picture. It\'s quite effective.';
				else {
					$Threeds->setDescription($gid, $_POST['tweet']);
					
					if ( $_POST['threedslink']=='true' )
						$_POST['tweet'].= ' threeds.me/'.$gid;
				}
			}
		}
		
		if ( empty($gid) || $_POST['uploadtwitter']!='true'  ) {
			$httpCode = $twitter->request('POST', $twitter->url('1.1/statuses/update'), array('status' => $_POST['tweet']));
		} else {
			$image = $Threeds->getJPGonServer($gid);
			if ( empty($_POST['tweet']) )
				$_POST['tweet'] = 'A 3D image';

			$httpCode = $twitter->request(
			  'POST',
			  'https://api.twitter.com/1.1/statuses/update_with_media.json',
			  array(
			    'media[]'  => "@{$image};type=image/jpeg;filename={$image}",
			    'status'   => $_POST['tweet'],
			  ),
			  true, // use auth
			  true  // multipart
			);
		}
		
		if ($httpCode == 200) {
			// TODO
			// save the tweet id on the image
			// redirect to image
			// or inform player of the success
			$resp = json_decode($twitter->response['response']);
			if ( !empty($gid) )
				header('Location: /'.$gid);
			else
				$notification = 'Tweet successfully posted: <a href="https://twitter.com/'.$resp->user->screen_name.'/status/'.$resp->id_str.'">see the tweet</a>';
			
//		  tmhUtilities::pr(json_decode($tmhOAuth->response['response']));
		} else {
			$e = json_decode($twitter->response['response']);
			$error = 'An wild error appears. '.$httpCode.'. Response:'.$e->error;
//			tmhUtilities::pr($twitter->response['response']);
		}
	}
	
	include 'layout/header.php';
	
	if ( !empty($error) )
		echo '<div class="blockContent error">'.$error.'</div>';
	
	if ( !empty($notification) )
		echo '<div class="blockContent notification">'.$notification.'</div>';
	
	// If the user is twitter logged	
	if ( $Threeds->Auth->isTwitterLog() ) {
?>
	
	<form class="form500" method="post" action="/twitter" enctype="multipart/form-data">
		<div class="formBlock"><h2>① Pick the picture to upload</h2>
			<input type="button" value="Choose a Picture ..." onclick="$('#twitterFileInput').click()"/>
		</div>
		
		<input id="twitterFileInput" class="hiddenFileInput" type="file" name="picture"/>
		<div class="formBlock"><h2>② Write your Tweet</h2>
		<textarea name="tweet"></textarea>
		</div>

		<input type="hidden" name="tweeterPost" value="1" />
		<div class="formBlock"><h2>③ Post to Twitter</h2>
			<p>
				<label class="cbinput"><input class="cbinput" name="threedslink" value="true" type="checkbox" checked="checked">Add a link to the 3D picture on threeds.me</p></label>
				<label class="cbinput"><input class="cbinput" name="uploadtwitter" value="true" type="checkbox">Upload the 2D picture to twitter</label>
			</p>
			
			<input type="submit" value="Tweet it!" />
		</div>
	</form>
<?php
	}
	
	// if the user is not signed in with twitter
	else {
?>

<div class="form500">
	<div class="formBlock"><h2>Signin with Twitter</h2>
		<a href="/twitter/signinToTweet"><input type="button" value="Signin ..."/></a>
	</div>
</div>
	
<?php
	}
	
	include 'layout/footer.php';
?>