<?php
/**
* passes session information in cookie form to the browser
*/
class sessionCookie{
	public static $session;
	public static $user;

	public static function init(){
		$ret = false;

		$res = data::get('session','get',self::cookieArgs());

		if(!$res['error']){
			$ret = true;
			self::$session = $res['data'];
			self::cookie($res['data']['key'],$res['data']['session_id']);
		} else {
			var_dump($res);
			exit('session error!');
		}
		return $ret;
	}

	private static $remember;
	public static function remember(){
		if(self::$session['remember']){
			self::$remember = 1;
		} else if(!isset(self::$remember)){
			$remember = 0;
			if(isset($_COOKIE['mc_remember'])){
				$remember = ($_COOKIE['mc_remember']?1:0);
			}
			self::$remember = $remember;
		}
		return self::$remember;
	}

	private static $timezone;
	public static function timezone(){
		if(!isset(self::$timezone)){
			$tz = rqval('tz');
			if($tz){
				$tz = intval(ltrim($tz,'-'));
				date_default_timezone_set("Etc/GMT".($tz >= 0 ? '+'.($tz) : $tz));
			}else{
				$default_tz = request::readConfig('default_timezone');
				date_default_timezone_set($default_tz?$default_tz:'America/Los_Angeles');
			}
		}
		return self::$timezone;
	}

	private static $tries;
	public static function tries(){
		$prefix = self::getPrefix();
		if(!isset(self::$tries)){
			self::$tries = (isset($_COOKIE[$prefix.'_tries'])?($_COOKIE[$prefix.'_tries']+1):0);
		}
		return self::$tries;
	}

	private static function cookie($sid,$key){
		$time = $_SERVER['REQUEST_TIME']+(365*24*60*60);

		$prefix = self::getPrefix();

		//$remember = self::remember();
		//setcookie('mc_remember', $remember,$time,'/');

		setcookie($prefix.'_sid', $sid, $time,'/');
		if($key){
			setcookie($prefix.'_key', $key, $time,'/');
		}
		setcookie($prefix.'_tries',(self::tries()?1:0),$time,'/');
	}

	private static function cookieArgs(){
		$prefix = self::getPrefix();

		$args = array('timezone'=>self::timezone());

		$args['sid'] = get($_COOKIE,$prefix.'_sid');
		$args['key'] = get($_COOKIE,$prefix.'_key');

		return $args;
	}

	public static function getUser(){
		if(self::$session && self::$session['logged_in']==1 && !isset(self::$user)){
			$res = data::get('user','get',array('users_id'));
			self::$user = false;
			if(!$res['error']){
				self::$user = $res['data'];
			}
		}
		return self::$user;
	}

	private static function getPrefix(){
		return request::readConfig('session_cookie_prefix');
	}
}
