<?php
$t = microtime(true);
require "solumConstants.php";
require "../config.php";
require "php/framework/kickstart.php";

bench::mark('core_time',$t);
bench::end('core_time');

//-------------------------------

bench::mark('app_time');

sessionCookie::init();

///EXECUTE APPLICATION FRONT CONTROLLER/ROUTER HERE

$default_vars = array('authenticated'=>sessionCookie::$session['logged_in'],'user'=>sessionCookie::getUser());

$view = new solumView('master',$default_vars);
$view->view();

bench::end('app_time');

//-------------------------------

if(request::readConfig('debug')){
	echo getDebugInfo();
}