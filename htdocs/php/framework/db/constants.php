<?
$db = array('USER','HOST','PASS','NAME','READ_HOSTS');
extract(request::readConfig('db'));

foreach($db as $suf){
	$var = "DB_$suf";
	if(!defined($var)){
		$val = strtolower($var);
		if(!isset($$val)){
			exit("cannot find config var $var");
		}

		define($var,$$val);
	}
}

if(!defined('DEBUG')){
	define('DEBUG',request::readConfig('debug'));
}

if(!defined('QUERY_LIST')) define('QUERY_LIST',false);
?>