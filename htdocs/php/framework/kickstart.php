<?php
/**
* kickstart!
* this is the init script for the solumLite framework
* include me in index and start running
*/

//define framework dir
$lib_path = dirname(__FILE__);
define('FRAMEWORK_DIR',$lib_path);

//load
requireAll($lib_path);

if(!isset($config)) $config = array();
request::addConfig($config);

requireAll($lib_path.'/db');


function requireAll($dir){
	foreach(scandir($dir) as $file){
		if(strrchr($file,'.') == '.php'){
			//require once as to not re include this file
			require_once $dir.'/'.$file;
		}
	}
}