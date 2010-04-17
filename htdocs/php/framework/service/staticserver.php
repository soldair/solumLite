<?php
/**************************************************************************\
* Written by:  Ryan Day <soldair@ryanday.org>                              *
* Copyright 2006-2008 Ryan Day                                             *
* http://ryanday.org
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/
// seconds, minutes, hours, days
$expires = 60*60*24*40;

$valid_extensions = array('css','js','jpg','jpeg','png','gif','txt','htc');

$root = $_SERVER['DOCUMENT_ROOT'];

if(!isset($_GET['path'])){
	$path = ltrim($_SERVER['REQUEST_URI'],'/');
} else {
	$path = ltrim($_GET['path'],'/');
}
$path = $root.'/'.$path;
$ext = ltrim(strrchr($path,'.'),'.');

if(file_exists($path) && in_array($ext,$valid_extensions)){
	$mime = "";
	switch($ext){
		case "css":
			$mime = "text/css";
			require_once($root.'/php/http/cssparser.class.php');
			$cachedPath = CSSParser::getBuiltCssFileName($path);
			if(file_exists($cachedPath)){
				$path = $cachedPath;
			}
			break;
		case "js":
			$mime = "text/javascript";
			break;
		case "txt":
			$mime = "text/plain";
			break;
		case "htc"://ie htc behavior files
			$mime = "text/x-component";
		default:
			if(is_executable('/usr/bin/file')){
				$arg = escapeshellarg($path);
				$mime = trim(`/usr/bin/file -b --mime-type $arg 2> /dev/null`);
			}/* else {
				$mime = "application/octet-stream";
			}*/
	}

	header("Content-type: ".$mime);
	header("Pragma: public");
	header("Cache-Control: max-age=$expires, must-revalidate, public");

	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

	$con = file_get_contents($path);
	header("Content-length: ".strlen($con));
	echo $con;
} else {
	header($_SERVER['SERVER_PROTOCOL']." 404");
}
?>