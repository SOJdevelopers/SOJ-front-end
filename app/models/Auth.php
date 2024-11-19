<?php

class Auth {
	public static function check() {
		global $myUser;
		return $myUser != null;
	}
	public static function id() {
		global $myUser;
		return $myUser['username'];
	}
	public static function user() {
		global $myUser;
		return $myUser;
	}
	public static function login($username, $remember = true) {
		if (!validateUsername($username)) {
			return;
		}
		$_SESSION['username'] = $username;
		$_SESSION['login_time'] = date('Y-m-d H:i:s');
		if ($remember) {
			$remember_token = DB::selectFirst("select remember_token from user_info where username = '$username'")['remember_token'];
			if ($remember_token == '') {
				$remember_token = uojRandString(60);
				DB::update("update user_info set remember_token = '$remember_token' where username = '$username'");
			}

			$expire = time() + 60 * 60 * 24 * 365 * 10;
			Cookie::safeSet('uoj_username', $username, $expire, '/', array('httponly' => true));
			Cookie::safeSet('uoj_remember_token', $remember_token, $expire, '/', array('httponly' => true));
		}
	}
	public static function logout() {
		unset($_SESSION['username']);
		unset($_SESSION['last_visited']);
		Cookie::safeUnset('uoj_username', '/');
		Cookie::safeUnset('uoj_remember_token', '/');
		DB::update("update user_info set remember_token = '' where username = '" . Auth::id() . '\'');
	}

	private static function initMyUser() {
		global $myUser;
		$myUser = null;

		Cookie::safeCheck('uoj_username', '/');
		Cookie::safeCheck('uoj_remember_token', '/');
	
		if (isset($_SESSION['username'])) {
			if (!validateUsername($_SESSION['username'])) {
				return;
			}
			$myUser = queryUser($_SESSION['username']);
			return;
		}

		$remember_token = Cookie::safeGet('uoj_remember_token', '/');
		if ($remember_token != null) {
			$username = Cookie::safeGet('uoj_username', '/');
			if (!validateUsername($username)) {
				return;
			}
			$myUser = queryUser($username);
			if ($myUser['remember_token'] !== $remember_token) {
				$myUser = null;
			}
			return;
		}
	}
	public static function init() {
		global $myUser;
		
		Auth::initMyUser();
		if ($myUser) {
			if (isBannedUser($myUser))
				$myUser = null;
		}

		if ($myUser) {
			if (!$_SESSION['login_time'])
				$myUser = null;
		}

		if ($myUser) {
			$login_time = $_SESSION['login_time'];
			$logout_all_time = DB::selectFirst("select logout_all_time from user_logout_all where username='{$_SESSION['username']}'")['logout_all_time'];
			if (!$login_time) {
				$login_time = '2024-09-15 08:00:00';
			}
			if (!$logout_all_time) {
				$logout_all_time = '2024-09-15 10:00:00';
			}
			$login_time = new DateTime($login_time);
			$logout_all_time = new DateTime($logout_all_time);
			if ($login_time <= $logout_all_time)
				$myUser = null;
		}

		if ($myUser) {
			DB::update("update user_info set remote_addr = '" . DB::escape($_SERVER['REMOTE_ADDR']) . "', http_x_forwarded_for = '" . DB::escape($_SERVER['HTTP_X_FORWARDED_FOR']) . "', latest_login = now() where username = '" . DB::escape($myUser['username']) . "'");
			$_SESSION['last_visited'] = time();
		}
	}
}
