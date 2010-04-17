<?
/**************************************************************************\
*   Copyright 2008-2009 Ryan Day                                           *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/
//plugin from solum framework adapted for PF and lgpled as required
date_default_timezone_set('America/Los_Angeles');

/**
* PURPOSE:
*	this lib allows you to use dynamic full URLS
*	you do not need to use ANY relative URLs and the Absolute urls will always work reguardless of Environment
*	NO MORE DEFINING SITE URL INFORMATION IN CONFIG FILES! =)
*
*NOTE: IN ORDER FOR THESE CONSTANTS TO WORK THIS FILE MUST BE PLACED IN THE DOCUMENT ROOT OF YOUR APPLICATION
*
*NOTE: all path constants do not have a trailing '/'
*
*NOTE: in my experience php translates directory sepperators from linux to windows on built in commands like include etc. ,
*      but use caution when using these constants in direct shell commands (system(), exec(), ``, etc.) if you are
*      worried about cross platform compatabillity.
*/

//if the constants conflict with your application namespacing please enter a value into the var below
$config_constant_prefix = '';
if(isset($_SERVER) && isset($_SERVER['SERVER_NAME'])){
	$abpath = dirname($_SERVER['SCRIPT_FILENAME']);
	$controller = basename($_SERVER['SCRIPT_FILENAME']);
	//get uri
	
	// we can't allow for query strings in the REQUEST_URI
	$pos = strpos($_SERVER['REQUEST_URI'], '?');
	if($pos !== false) {
		//$_GET = parse_url(subsr($_SERVER['REQUEST_URI'], $pos);
		parse_str(substr($_SERVER['REQUEST_URI'], $pos+1), $_GET);
		$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $pos);
	}
	
	$uri = $_SERVER['REQUEST_URI'];
	if(!empty($_SERVER['QUERY_STRING'])){
		$uri = $_SERVER['REQUEST_URI'] = trim(str_replace($_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']),'?');
	}
	if(strpos($_SERVER['REQUEST_URI'],$controller)!==false){
		$pos = strpos($_SERVER['REQUEST_URI'],$controller);
		if($pos+strlen($controller) == strlen($_SERVER['REQUEST_URI'])){
			//controller name appears at end of uri
			$uri = dirname($_SERVER['REQUEST_URI']);
		}else{
			//this is an error case that shouldn't happen
			$uri = $_SERVER['REQUEST_URI'];
		}
	}
	
	$url = 'http://'.$_SERVER['SERVER_NAME'].$uri;
	
	if(strrpos($abpath,basename(dirname(__FILE__)))+(strlen(basename(dirname(__FILE__)))) == strlen($abpath)){
		$rooturl = $url;
		$rootpath = $abpath;
	}else{//the below works for the above case also, the above case is just less processing
		$parts = explode(basename(dirname(__FILE__)),$abpath);
		$last = $parts[(count($parts)-1)];
		$pos = strpos($last,'/'); 
		if($pos !== false){
			if($pos != 1){
				$last = substr($last,strpos($last,'/'));
			}
		}else{
			$last = '';
		}
		$rooturl = str_replace($last,'',$url);
		$rootpath = str_replace($last,'',$abpath);
	}
	$servername = $_SERVER['SERVER_NAME'];
} else {
	$servername = '';
	$rooturl = '';
	$url = '';
}
//base url of your application
if(!defined($config_constant_prefix.'SITE_SERVER_URL')){
	define($config_constant_prefix.'SITE_SERVER_URL','http://'.$servername);
}
if(!defined($config_constant_prefix.'SITE_SERVER_URL_SECURE')){
	define($config_constant_prefix.'SITE_SERVER_URL_SECURE','https://'.$servername);
}
$rooturl = rtrim($rooturl,'/');
//base url of your application
if(!defined($config_constant_prefix.'SITE_ROOT_URL')){
	define($config_constant_prefix.'SITE_ROOT_URL',$rooturl);
}

//current url with uri
if(!defined($config_constant_prefix.'SITE_URL')){
	define($config_constant_prefix.'SITE_URL',$url);
}

//path to the toplevel directory of your application
if(!defined($config_constant_prefix.'SITE_ROOT')){
	define($config_constant_prefix.'SITE_ROOT',dirname(__FILE__));
}

?>