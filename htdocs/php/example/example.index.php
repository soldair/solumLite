<?php
$coreTime = microtime(true);
error_reporting(E_ALL);//View:;view
require dirname(__FILE__).'/solumConstants.php';
require SITE_ROOT.'/config.php';
define('QUERY_LIST',false);

$data_path = 'php/data';

require $data_path.'/data.class.php';
require $data_path.'/format.class.php';

$lib_path = $data_path.'/lib';
foreach(scandir($lib_path) as $file){
	if(strlen($file) > 4){
		if(substr($file,(strlen($file)-4)) == '.php'){
			require $lib_path.'/'.$file;
		}
	}
}


require 'php/http/sessioncookie.class.php';
require 'php/http/controller.class.php';
require 'php/http/adminController.class.php';
require 'php/http/request.class.php';
require 'php/http/solumView.class.php';
require 'php/http/cms.class.php';
require 'php/http/lib_compat.php';

$coreTime = sprintf('%0.5f',(microtime(true)-$coreTime)*1000);

$appTime = microtime(true);
sessionCookie::init();

/**init the page**/

request::addConfig($config);
$user = sessionCookie::getUser();
$default_vars = array('authenticated'=>sessionCookie::$session['logged_in'],'user'=>$user);

$view = new solumView('master',$default_vars);
$view->view();

/** done **/
if(request::readConfig('tracking_enabled')){
	/*
	$users_id = $user?$user['users_id']:0;
	DB::write("INSERT INTO `metrics`.`requests`(time,day_time,users_id,session_key,ip,user_agent,uri,referer) values(".time().",".day_time().",".$users_id.",'".sessionCookie::$session['key']."','".$_SERVER['REMOTE_ADDR']."','".DB::sqlEsc($_SERVER['HTTP_USER_AGENT'])."','".DB::sqlEsc($_SERVER['REQUEST_URI'])."','".(isset($_SERVER['HTTP_REFERER'])?DB::sqlEsc($_SERVER['HTTP_REFERER']):'')."') ");
	*/
}


if(!request::readConfig('debug')){
	exit();
}

if(request::get(0) == 'ajax'){
	exit();
}

$appTime = sprintf('%0.5f',(microtime(true)-$appTime)*1000);


$sessionData = '';
foreach(sessionCookie::$session as $k=>$v){
	$sessionData .= "<div>$k: $v</div>";
}

echo "<div style='clear:both;border:1px solid blue;margin:5px;background:#d4d4d4;padding:3px;margin-left:10px;mergin-right:10px;color:#000;'>
		<div style='background:white;padding:3px;'>
			<div>
				<b>benchmarks:</b>
				<div style='margin:5px;background:#D9FFCE;'>
					<div>core time: $coreTime ms</div>
					<div>app time: $appTime ms</div>
					<div>peak memory: ".( (int) (memory_get_peak_usage()/1024))." kb</div>
					<div>database reads: ".db::$query_count." </div>
					<div>database writes: ".db::$query_count_w." </div>
					<div>database read time: ".sprintf('%0.5f',(db::$query_time)*1000)." ms</div>
					<div>database write time: ".sprintf('%0.5f',(db::$query_time_w)*1000)." ms</div>
				</div>
			</div>
			<div>
				<b>database queries:</b>
				<div style='margin:5px;background:#D9FFCE;'>
					<pre>".db::$query_list."</pre>
				</div>
			</div>
			<div>
				<b>session data:</b>
				<div style='margin:5px;background:#D9FFCE;'>
					$sessionData
				</div>
			</div>
			<div>
				<b>data log:</b>
				<div style='margin:5px;background:#D9FFCE;'>
					<pre>".Data::getDataLog()."</pre>
				</div>
			</div>
			<div>
				<b>view log:</b>
				<div style='margin:5px;background:#D9FFCE;'>
					<pre>".solumView::getViewLog()."</pre>
				</div>
			</div>
		</div>
	</div>";
?>