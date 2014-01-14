<?php
	include_once 'lib/threeds.class.php';
	$Threeds = new Threeds();
	
	$userGID = $page;
	$user = $Threeds->getUserInfo($userGID);
	if ( $user===false ) {
		header("Location: /404");
	}
	
	$imgList = $Threeds->getUserImageList($user['authID'], 12);

	if ( isset($_POST['name']) && $Threeds->hasAdminRight($user['authID']) ) {
		$Threeds->setProfile(stripslashes($_POST['name']), stripslashes($_POST['profile']), stripslashes($_POST['twitter']));
		$user = $Threeds->getUserInfo($Threeds->Auth->getAuthenticationID());
	}
	
	include 'layout/header.php';

//
// Somebody visit a user page
//
if ( $Threeds->hasAdminRight($user['authID'])===false ) {
	echo '<div class="dcBlock dcLeft">';
	echo '<h2 class="embossText">';
	echo $user['name'];
	if ( !empty($user['twitterName']) ) {
		echo '<span><a href="http://twitter.com/'.$user['twitterName'].'">@'.$user['twitterName'].'</a></span>';
	}
	echo '</h2>';
	echo '<p>'.$Threeds->string2html($user['profile'], true).'</p>';
	echo '</div>';

	echo '<div class="dcBlock dcRight"><h3 class="embossText">Latest Pictures</h3>';
	foreach($imgList as $img) {
		$gid = $img['gid'];
		echo '<div class="blockThumb">';
		echo '<a href="'.$gid.'">';
		echo '<div class="imgCanvas">';
		echo '<div class="imgCenter"><div class="imgContainer"><img src="'.$Threeds->getThumbJPS($gid).'" onload="imgWiggle(this, '.$img['parallax'].');"></div></div>';
		echo '</div>';
		echo '</a>';
		echo '<a href="'.$Threeds->getMPO($gid).'" class="mposhortcut">3D View</a>';
		echo '</div>';
	}
	echo '<p><a href="/recent/'.$user['gid'].'">Check more of '.$user['name'].'\'s pictures ...</a></p>';
	echo '</div>';
}

//
// The user is the admin of the page
//
else {
?>
<div class="form500 formBlock" id="editprofile" style="display:none;">
	<h2>Edit your profile</h2>
	<form method="post" action="/<?php echo $userGID; ?>">
		<p>
		<label for="pass">How should we call you?</label>
		<input id="formName" type="text" name="name" value="<?php echo $user['name']; ?>" />
		</p>
		
		<p>
		<label for="pass">Are you on Twitter?</label>
		<input id="formName" type="text" name="twitter" value="@<?php echo $user['twitterName']; ?>" />
		</p>
		
		<p>
		<label for="pass">Describe how awesome you are:</label>
		<textarea name="profile" cols=25 rows=15><?php echo $user['profile']; ?></textarea>
		</p>
		
		<p><input type="submit" value="Save My Profile" /></p>
	</form>
</div>
	
<div class="dcBlock dcLeft embossText" id="profile">
<h2>
	<?php
		echo $user['name'];
		
		if ( !empty($user['twitterName']) ) {
			echo '<span><a href="http://twitter.com/'.$user['twitterName'].'">@'.$user['twitterName'].'</a></span>';
		}
	?> 
</h2>
	<p>
	<?php
		if ( empty($user['profile']) && $user['name']==Threeds_Config::DEFAULT_NAME && !empty($imgList) )
			echo 'This is the moment to take the time to edit your profile. You can change your name and add a short descripton of yourself.';
		else
			echo $Threeds->string2html($user['profile'], true);
	?>
	</p>

	<button onclick="$('#latestpic').hide();$('#profile').hide();$('#editprofile').show();">Edit My Profile</button>
</div>

<div class="dcBlock dcRight" id="latestpic">
<h3 class="embossText">My Latest Pictures</h3>
<?php
	if ( empty($imgList) ) {
		echo '<p class="embossText">No pictures uploaded yet. <a href="/upload">Start now</a>.</p>';
	}
	else {
		foreach($imgList as $img) {
			$gid = $img['gid'];
			echo '<div class="blockThumb">';
			echo '<a href="'.$gid.'">';
			echo '<div class="imgCanvas">';
			echo '<div class="imgCenter"><div class="imgContainer"><img src="'.$Threeds->getThumbJPS($gid).'" onload="imgWiggle(this, '.$img['parallax'].');"></div></div>';
			echo '</div>';
			echo '</a>';
			echo '<a href="'.$Threeds->getMPO($gid).'" class="mposhortcut">3D View</a>';
			echo '</div>';
		}
		echo '<p><a href="/recent/'.$user['gid'].'">Check more of my pictures ...</a></p>';
	}
?>
</div>

<?php
}
	include 'layout/footer.php';
?>