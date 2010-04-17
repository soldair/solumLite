<?php
class request{
	private static $uri;
	public static function get($key){
		self::_setURIChunks();
		return rqval($key);
	}

	public static function getSlugs(){
		self::_setURIChunks();
		return self::$uri;
	}

	private static $config = array();
	public static function readConfig($key){
		if(isset(self::$config[$key])){
			return self::$config[$key];
		}
		return null;
	}

	//it is not an accident that there is not a setter method for config vars. use flags or import an entire new config

	public static function getConfig(){
		return self::$config;
	}

	public static function addConfig($config){
		self::$config = $config;
	}

	private static $flags = array();
	public static function  readFlag($flag){
		if(isset(self::$flags[$flag])){
			return self::$flags[$flag];
		}
		return null;
	}

	public static function setFlag($flag,$value){
		self::$flags[$flag] = $value;
	}

	# /layout/screen/screenview
	# private static $map = array('l','s','v');
	public static function mapUri($uri){
		return '/'.trim($uri,'/');
	}

	private static function _setURIChunks(){
		if(!isset(self::$uri)){
			$str = substr(SITE_URL,(strlen(SITE_SERVER_URL)+1));
			self::$uri = array();
			if($str){
				self::$uri = explode('/',$str);
			}
			if(self::$uri){
				$_REQUEST = array_merge($_REQUEST,self::$uri);
			}
		}
	}
}
?>