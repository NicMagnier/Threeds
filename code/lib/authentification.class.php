<?php

include_once 'database.class.php';
include_once 'tmhOAuth.php';
include_once 'tmhUtilities.php';

class Authentication {
	const OK = 1;
	const ERR_LOGIN_ALREADY_EXIST = -1;
	const ERR_LOGIN_DOESNT_EXIST = -2;
	const ERR_WRONG_PASSWORD = -3;
	const ERR_LOGIN_IS_NOT_MAIL = -4;
	const ERR_TWITTER_REQUEST_TOKEN = -5;
	const ERR_TWITTER_ACCESS_TOKEN = -6;
	
	const SESSION_TIMEOUT = 15; // in days

	// 
	var	$m_DB;
	var $m_DBAuthenticationTable = 'authentication';
	var $m_DBPasswordResetTable = 'passwordrecovery';
	var $m_isLog = false;
	var $m_isLazyRegistration = false;
	var $m_authID;
	var $m_sessionID;
	var $m_login;
	var $m_lastError;
	var $m_cookieDomain;
	var $m_debugOutput;
	
	// twitter related variables
	var $m_twitterEnabled = false;
	var $m_isTwitterLog = false;
	var $m_twitterUserID;
	var $m_twitterScreenName;
	var $m_twitterOAuth;
	
	function __construct( $db, $tablePrefix, $cookieDom, $twitterKey='', $twitterSecret='') {
		$this->m_DB = $db;
		$this->m_DBAuthenticationTable = $tablePrefix.$this->m_DBAuthenticationTable;
		$this->m_DBPasswordResetTable = $tablePrefix.$this->m_DBPasswordResetTable;
		$this->m_cookieDomain = $cookieDom;
		
		// setup twitter
		if ( $twitterKey!='' && $twitterSecret!='' ) {
			$this->twitterInit($twitterKey, $twitterSecret);
		}

		// try to log
		$this->silentSignin();
	}
	
	function isLog() {
		return $this->m_isLog;
	}
	
	function getAuthenticationID() {
		return $this->m_authID;
	}
	
	function getLogin() {
		return $this->m_login;
	}
	
	function getLastError() {
		return $this->m_lastError;
	}
	
	function setCookieDomain( $domain) {
		$this->m_cookieDomain = $domain;
	}
	
	function signin( $login, $password) {
		// TODO, when fail, check if somebody try to brute force the login form
		$this->m_login = $this->cleanLogin($login);
		$this->m_isLog = false;
		$this->m_isLazyRegistration = false;
		
		// and now we try to signin
		$auth = $this->m_DB->getRow( 'SELECT id, sessionID FROM '.$this->m_DBAuthenticationTable.' WHERE login = "'.$this->m_login.'" AND hashpass = "'.$this->hashPassword($password).'"');

		if( empty($auth) ) {
			if ( $this->loginExist($login) )
				$this->m_lastError = self::ERR_WRONG_PASSWORD;
			else
				$this->m_lastError = self::ERR_LOGIN_DOESNT_EXIST;

			// can be useful to save fail login to be able to use it in pages like 'forgot password'
			setcookie( 'login', $this->m_login, time() + 3600 * 24 , '/', $this->m_cookieDomain);
			return false;
		}
		
		// The user is logged
		$this->m_authID = $auth['id'];
		$this->m_isLog = true;
		$this->cleanPasswordReset( $login);
		
		if ( empty($auth['sessionID']) ) {
			$this->m_sessionID = $this->uniqueHash();
			$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.
				' SET sessionID = "'.$this->m_sessionID.'", attemptCounter = 0, attemptLast = NOW()'.
				' WHERE id = "'.$this->m_authID.'"');
		}
		else {
			$this->m_sessionID = $auth['sessionID'];
		}

		// store cookies
		setcookie( 'login', $this->m_login, time() + 3600 * 24 * 365 , '/', $this->m_cookieDomain);
		setcookie( 'sid', $this->m_sessionID, time() + 3600 * 24 * self::SESSION_TIMEOUT, '/', $this->m_cookieDomain);

		return true;
	}
	
	function silentSignin() {
		$this->m_isLog = false;
		$this->m_isLazyRegistration = false;
		
		$twitterLogin = false;
		$lazyLogin = false;
		
		if ( !isset($_COOKIE['sid']) ) {
			return false;
		}
		
		// Twitter login
		if ( isset($_COOKIE['twitterid']) && $this->m_twitterEnabled ) {
			$auth = $this->m_DB->getRow( 'SELECT id, DATEDIFF(NOW(), attemptLast) as day, twitterAccessToken, twitterAccessSecret, twitterUserID FROM '.$this->m_DBAuthenticationTable.' WHERE sessionID = "'.$_COOKIE['sid'].'" AND twitterUserID = "'.$_COOKIE['twitterid'].'"');

			if ( empty($auth)==false )
				$twitterLogin = true;
		}
		
		// Standard login
		if ( empty($auth) && isset($_COOKIE['login']) ) {
			$auth = $this->m_DB->getRow( 'SELECT id, DATEDIFF(NOW(), attemptLast) as day FROM '.$this->m_DBAuthenticationTable.' WHERE sessionID = "'.$_COOKIE['sid'].'" AND login = "'.$_COOKIE['login'].'"');
		}
		
		// Lazy registration
		if ( empty($auth) ) {
			$auth = $this->m_DB->getRow( 'SELECT id, DATEDIFF(NOW(), attemptLast) as day FROM '.$this->m_DBAuthenticationTable.' WHERE sessionID = "'.$_COOKIE['sid'].'" AND login IS NULL');
			
			if ( empty($auth)==false )
				$lazyLogin = true;
		}

		// no session
		if( empty($auth) ) {
			setcookie( 'sid', '', 1, '/', $this->m_cookieDomain);
			return false;
		}
		
		// session timeout except for lazy registration
		if( $auth['day']>self::SESSION_TIMEOUT && !$lazyLogin ) {
			$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.
				' SET sessionID = "" WHERE id = "'.$auth['id'].'"');
			setcookie( 'sid', '', 1, '/', $this->m_cookieDomain);
			return false;
		}
		
		// The user is logged
		$this->m_authID = $auth['id'];
		$this->m_isLog = true;
		$this->m_login = $_COOKIE['login'];
		$this->m_sessionID = $_COOKIE['sid'];

		$this->m_isLazyRegistration = $lazyLogin;
		$this->m_isTwitterLog = $twitterLogin;
		
		// configure Twitter OAuth
		if ( $this->m_isTwitterLog ) {
			$this->m_twitterUserID = $auth['twitterUserID'];
			$this->m_twitterOAuth->config['user_token']  = $auth['twitterAccessToken'];
			$this->m_twitterOAuth->config['user_secret'] = $auth['twitterAccessSecret'];
		}
		
		// renew session cookie
		if ( $this->m_isLazyRegistration )
			setcookie( 'sid', $_COOKIE['sid'], time() + 3600 * 24 * 365, '/', $this->m_cookieDomain );
		else
			setcookie( 'sid', $_COOKIE['sid'], time() + 3600 * 24 * self::SESSION_TIMEOUT, '/', $this->m_cookieDomain );
	}
	
	function signout() {
		// delete cookies
		setcookie( 'sid', '', 1, '/', $this->m_cookieDomain);
		setcookie( 'login', '', 1, '/', $this->m_cookieDomain);
		setcookie( 'twitterid', '', 1, '/', $this->m_cookieDomain);
		
		// clean database
		$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.
			' SET sessionID = "" WHERE id = "'.$this->m_authID.'"');
	
		// set variables
		$this->m_isLog = false;
		$this->m_isLazyRegistration = false;
		$this->m_isTwitterLog = false;
		$this->m_authID = NULL;
		$this->m_sessionID = NULL;
		$this->m_login = NULL;
		$this->m_sessionID = NULL;
	}
	
	function createAccount( $newLogin, $newPassword) {
		$this->signout();
		
		if ( $this->loginExist($newLogin) ) {
			$this->m_lastError = self::ERR_LOGIN_ALREADY_EXIST;
			return false;
		}
		
		$this->m_DB->insertRow( $this->m_DBAuthenticationTable, array ( 'login' => $this->cleanLogin($newLogin), 'hashpass' => $this->hashPassword( $newPassword)));
		return $this->signin( $newLogin, $newPassword);
	}
	
	function deleteAccount() {
		if ( !$this->isLog() )
			return false;
		
		$id = $this->m_authID;
		$this->signout();
		
		$this->m_DB->query( 'DELETE FROM '.$this->m_DBAuthenticationTable.'WHERE id = '.$id);
		return ($this->m_DB->affectedRows()!=0);
	}
	
	
	/*	LAZY REGISTRATION
		function to create an account without credential, the session ID is the only identifier
		As a result the user can only acces the account from only one computer.
		Later on the user can secure the account with a login and a password
	*/
	function isLazyRegistration() {
		return $this->m_isLazyRegistration;
		
	}

	function createLazyRegistration() {
		$this->signout();

		$sessionID = $this->uniqueHash();
		$this->m_DB->insertRow( $this->m_DBAuthenticationTable, array ( 'sessionID' => $sessionID));
		
		$this->m_authID = $this->m_DB->insertId();
		if ( isset($this->m_authID) )
		{
			$this->m_isLog = true;
			$this->m_isLazyRegistration = true;
			$this->m_sessionID = $sessionID;
			setcookie( 'sid', $sessionID, time() + 3600 * 24 * 365, '/', $this->m_cookieDomain );
			
			return true;
		}

		return false;
	}

	function secureLazyRegistration( $newLogin, $newPassword) {
		if ( !$this->isLog() )
			return $this->createAccount( $newLogin, $newPassword);
			
		if ( !$this->isLazyRegistration() )
			return false;
			
		if ( $this->loginExist($newLogin) ) {
			$this->m_lastError = self::ERR_LOGIN_ALREADY_EXIST;
			return false;
		}

		$this->m_DB->updateRow(
			$this->m_DBAuthenticationTable,
			array( 'sessionID' => $_COOKIE['sid'] ),
			array( 'login' => $this->cleanLogin($newLogin), 'hashpass' => $this->hashPassword( $newPassword))
			);
			
		if ( $this->m_DB->affectedRows()==0 )
			return false;
			
		return $this->signin( $newLogin, $newPassword);
	}



	/*	PASSWORD RESET
		First, request it with requestPasswordReset() which return a magicHash that need to be send to the user
		Second, reset the password with the resetPassword() with the new password and the magicHash to confirm this is a legit reset
	*/

	function resetPassword( $login, $newPassword, $magicHash) {
		$account = $this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.' AS auth, '.$this->m_DBPasswordResetTable.' AS requ '.
				'SET hashpass = "'.$this->hashPassword($newPassword).'" '.
				'WHERE auth.login = "'.$this->cleanLogin($login).'" AND requ.magichash = "'.$magicHash.'" AND requ.authID = auth.id ');
		
		if ( $this->m_DB->affectedRows()==0 && $this->verifyPassword( $login, $newPassword)==false )
			return false;
				
		$this->cleanPasswordReset($login);
		
		return $this->signin( $login, $newPassword);
	}
	
	function requestPasswordReset( $login) {
		// when we try to rest a password we set the login
		setcookie( 'login', $login, time() + 3600 * 24 , '/', $this->m_cookieDomain);
	
		if ( !$this->loginExist($login) ) {
			$this->m_lastError = self::ERR_LOGIN_DOESNT_EXIST;
			return false;
		}
		
		if ( !$this->isValidEmail($login) ) {
			$this->m_lastError = self::ERR_LOGIN_IS_NOT_MAIL;
			return false;
		}
		
		$authID = $this->m_DB->getRow( 'SELECT id FROM '.$this->m_DBAuthenticationTable.' WHERE login = "'.$this->cleanLogin($login).'"');
		
		if ( empty($authID) )
			return false;
			
		$magic = $this->uniqueHash($login);
		$this->m_DB->insertRow( $this->m_DBPasswordResetTable, array('authID' => $authID['id'], 'magichash' => $magic) );
		
		$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.' SET sig = "" WHERE authID = '.$authID['id']);
			
		if ( $this->m_DB->affectedRows()<=0 ) {
			$this->m_DB->query( 'UPDATE '.$this->m_DBPasswordResetTable.' SET magichash = '.$this->m_DB->quote($magic).' WHERE authID = '.$authID['id']);
		}
		
		return $magic;
	}
	
	private function cleanPasswordReset( $login) {
		$tauth = $this->m_DBAuthenticationTable;
		$tpass = $this->m_DBPasswordResetTable;

		return $this->m_DB->query( 'DELETE '.$tpass.' FROM '.$tpass.' JOIN '.$tauth.' WHERE '.$tauth.'.login = "'.$login.'" AND '.$tauth.'.id = '.$tpass.'.authID'); 
	}
	
	/* TWITTER FUNCTIONS
	*/
	
	private function twitterInit($key, $secret) {
		$this->m_twitterOAuth = new tmhOAuth(array(
		  'consumer_key'    => $key,
		  'consumer_secret' => $secret,
		));
		$this->m_twitterEnabled = true;
	}
	
	public function isTwitterVerifier() {
		return isset($_REQUEST['oauth_verifier']);
	}
	
	// function to call when the website is called back by twitter after authentification
	public function twitterVerifier() {
		if ( !$this->m_twitterEnabled ) return false;
		
		$this->m_twitterOAuth->config['user_token']  = $_COOKIE['requestToken'];
		$this->m_twitterOAuth->config['user_secret'] = $_COOKIE['requestTokenSecret'];

		$httpCode = $this->m_twitterOAuth->request(
			'POST',
			$this->m_twitterOAuth->url('oauth/access_token', ''),
			array( 'oauth_verifier' => $_REQUEST['oauth_verifier'] ));
	
		// twitter verified the user
		if ($httpCode == 200) {
			// Get access token
			$accessToken = $this->m_twitterOAuth->extract_params($this->m_twitterOAuth->response['response']);
			
			// clean old cookies
			setcookie('requestToken', '', time()-3600, '/', $this->m_cookieDomain);
			setcookie('requestTokenSecret', '', time()-3600, '/', $this->m_cookieDomain);
			
			return $accessToken;
		}
		
		$this->m_lastError = self::ERR_TWITTER_ACCESS_TOKEN;
		return false;
	}

	// After getting access token with twitterVerifier() the user can connect an account to it
	// It verify if the user has already an account, otherwise it creates one
	public function connectTwitterAccount($accessToken) {
		if ( empty($accessToken) ) return;

		// check if the user is already in the database
		$tuiResult = $this->m_DB->getRow( 'SELECT id, sessionID FROM '.$this->m_DBAuthenticationTable.' WHERE twitterUserID = '.$accessToken['user_id']);
		
		// no user with this twitter user_id, let's just create a new account for him
		if ( empty($tuiResult) ) {
			if ( $this->isLog()==false )
				$this->createLazyRegistration();

			$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.' SET '.
				'twitterAccessToken = "'.$accessToken['oauth_token'].'", '.
				'twitterAccessSecret = "'.$accessToken['oauth_token_secret'].'", '.
				'twitterUserID = '.$accessToken['user_id'].', '.
				'attemptLast = NOW() '.
				'WHERE id = '.$this->m_authID);
		}
		// user already have an account, let's just update with new access token
		else {
			if ( empty($tuiResult['sessionID']) )
				$this->m_sessionID = $this->uniqueHash();
			else
				$this->m_sessionID = $tuiResult['sessionID'];
			
			$this->m_DB->query( 'UPDATE '.$this->m_DBAuthenticationTable.' SET '.
				'sessionID = "'.$this->m_sessionID.'", '.
				'twitterAccessToken = "'.$accessToken['oauth_token'].'", '.
				'twitterAccessSecret = "'.$accessToken['oauth_token_secret'].'", '.
				'attemptLast = NOW() '.
				'WHERE twitterUserID = '.$accessToken['user_id']);

			if ( $this->m_DB->affectedRows()!=0 ) {
				$this->m_authID = $tuiResult['id'];
				$this->m_isLog = true;
			} else {
				return;
			}
		}

		$this->m_isTwitterLog = true;
		$this->m_twitterUserID = $accessToken['user_id'];
		$this->m_twitterScreenName = $accessToken['screen_name'];
		$this->m_twitterOAuth->config['user_token']  = $accessToken['oauth_token'];
		$this->m_twitterOAuth->config['user_secret'] = $accessToken['oauth_token_secret'];

		// set new cookies for next login
		setcookie( 'twitterid', $accessToken['user_id'], time() + 3600 * 24 * 365 , '/', $this->m_cookieDomain);
		setcookie( 'sid', $this->m_sessionID, time() + 3600 * 24 * self::SESSION_TIMEOUT, '/', $this->m_cookieDomain);
	}

	// check if the user signed in through twitter
	public function isTwitterLog() {
		return $this->m_isTwitterLog;
	}
	
	// user try to authenticate himself through twitter
	// !!! will redirect the user to the twitter page
	public function twitterAuthenticate($callbackUrl) {
		if ( !$this->m_twitterEnabled ) return;
		
		$params = array('oauth_callback' => $callbackUrl);
		$httpCode = $this->m_twitterOAuth->request('POST', $this->m_twitterOAuth->url('oauth/request_token', ''), $params);
		
		if ($httpCode == 200) {
			$oauth = $this->m_twitterOAuth->extract_params($this->m_twitterOAuth->response['response']);

			$authurl = $this->m_twitterOAuth->url('oauth/authenticate', '').'?oauth_token='.$oauth['oauth_token'];
			header('Location: '.$authurl);
			setcookie('requestToken', $oauth['oauth_token'], time()+60*60, '/', $this->m_cookieDomain);
			setcookie('requestTokenSecret', $oauth['oauth_token_secret'], time()+60*60, '/', $this->m_cookieDomain);
		} else {
			$this->m_lastError = self::ERR_TWITTER_REQUEST_TOKEN;
		}
	}
	
	// When the user is logged, use this function to check if access tokens are still valid
	// when the function is called, it also cache the twitter screen name
	public function twitterVerifyCredentials() {
		if ( !$this->m_twitterEnabled ) return false;
		
		if ( !$this->isTwitterLog() )
			return false;
		
		// now really check on twitter servers
		$httpCode = $this->m_twitterOAuth->request('GET', $this->m_twitterOAuth->url('1/account/verify_credentials'));
		if ($httpCode == 200) {
			$resp = json_decode($this->m_twitterOAuth->response['response']);
			$this->m_twitterScreenName = $resp->screen_name;
			return true;
		}
		
		// uh uh, Twiter couldn't verify the credentials, let's signout
		$this->signout();
		
		return false;
	}
	
	public function getTwitterScreenName() {
		if ( empty($this->m_twitterScreenName) )
			$this->twitterVerifyCredentials();
			
		return $this->m_twitterScreenName;
	}
	
	// retrieve some information about the user
	public function getTwitterUserInfo() {
		if ( !$this->m_twitterEnabled ) return false;
		
		if ( !$this->isTwitterLog() )
			return false;
		
		// now really check on twitter servers
		$httpCode = $this->m_twitterOAuth->request('GET', $this->m_twitterOAuth->url('1/users/show'), array(
		  'user_id' => $this->m_twitterUserID));
		if ($httpCode == 200) {
			return json_decode($this->m_twitterOAuth->response['response']);
		}
		
		return false;
	}
	
	public function getTwitterClass() {
		return $this->m_twitterOAuth;
	}
	

	/* TOOL FUNCTIONS
	*/
	public function isValidEmail( $login) {
		return filter_var($login, FILTER_VALIDATE_EMAIL);
//		return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $login);
	}
	
	public function loginExist( $login) {
		$result = $this->m_DB->getRow( 'SELECT id FROM '.$this->m_DBAuthenticationTable.' WHERE login = "'.$this->cleanLogin($login).'"');

		return !empty($result);
	}
	
	public function verifyPassword( $login, $pass) {
		$auth = $this->m_DB->getRow( 'SELECT id FROM '.$this->m_DBAuthenticationTable.' WHERE login = "'.$this->cleanLogin($login).'" AND hashpass = "'.$this->hashPassword($pass).'"');

		if( empty($auth) )
			return false;
		
		return $auth['id'];
	}	
	
	private function cleanLogin( $login) {
		// Remove spaces and switch to lower case
		return strtolower(str_replace(' ', '', $login));
	}
	
	private function hashPassword( $password) {
		$md5password = md5($password);
		return md5( substr($md5password, 0, 16).$password.substr($md5password, -16));
	}
	
	private function uniqueHash( $uniqueSeed = '') {
		return md5(time().$uniqueSeed.rand());
	}
}


?>