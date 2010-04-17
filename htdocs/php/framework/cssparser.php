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
/*
this is a different attempt at a css parser.
designed to potentially support mhtml for ie browsers and parses in one pass for speed
*/
define('PATH',$path = get_requested_file());
define('DATA_URI',mhtml_compatable()?false:true);
if(PATH){
	if(file_exists($path)){
		$ext = strrchr($path,'.');
		switch($ext){
			case '.css':
				header('Content-type: text/css');
				if(DATA_URI === 'mthml'){
					//header("Content-type: text/plain");
					$mhtml = true;
				} else {
					$mhtml = false;
				}

				buffer_content();

				$t = microtime(true);
				$file = fopen($path,'r');

				$state = 'out';
				$prev_state = '';

				$lookingfor = '';

				$url_tmp = '';
				$break = 0;
				while(($c = fread($file,1)) !== false && strlen($c)){
					$break++;
					//if($break == 10000){
					//	var_dump($c);
					//	 break;
					//}
					$tmp = '';

					switch($state){
						case "out":

							if($c == '/'){
								if(!read_comment($file,$comment)){
									$c = '/';
									$tmp .= $comment;
								} else {
									$c = '';
								} 

							} else if($c == '{'){
								//echo "$state to rules\n";
								$state = 'rules';
							}
						case "rules":
							if($c == '/'){

								if(!read_comment($file,$comment)){
									$c = '/';
									$tmp .= $comment;
								} else {
									$c = '';
								}
							} else if($c == 'u'){
								$next = 'rl(';

								$url_c = fread($file,3);
								if($url_c == $next){
									
									//echo "$state to url\n";
									$state = 'url';

								}

								$tmp .= $url_c;

								
							} else if($c == '}'){
								//echo "$state to out\n";
								$state = 'out';
							}
							break;
						case 'url':
							if($c == '/'){
								if(!read_comment($file,$comment)){
									$url_tmp .= $c.$comment;
									$c = '';
								}

							} else if($c == ')'){
								//echo "$state to rules\n";
								$state = 'rules';
								//url end
								trim($url_tmp," '\"\r\n\t");
								if(strpos($url_tmp,'http://') === 0 || strpos($url_tmp,'data:') === 0){
									$url_tmp .= $c;
								} else {
									if($url_tmp[0] == '/'){
										$image = $_SERVER['DOCUMENT_ROOT'].'/'.$url_tmp;
									} else {
										$image = $path.'/'.$url_tmp;
									}
									$ext = ltrim(strchr($image,'.'),'.');

									if($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif'){
										if(file_exists($image)){
											if(!DATA_URI){
												$mtime = filemtime($image);
												$url_tmp .= "?$mtime";
											} else {
												$data = base64_encode(file_get_contents($image));
												if($mhtml){
													$url_tmp = mhtml_part($data);
												} else {
													$url_tmp = 'data:image/'.($ext == 'jpg'?'jpeg':$ext).';base64,'.$data;
												}
											}
										}
									}
									$url_tmp .= $c;
								}
								$c = $url_tmp;
								$url_tmp = '';
							} else {
								$url_tmp .= $c;
								$c = '';
							}
							break;
					}

					echo $c.$tmp;

				}
				$t = sprintf('time: %0.4f',(microtime(true)-$t)*1000);
				echo "\n/*------------------[$t]------------------*/";

				buffer_content();

				break;
		}
	}
}

$mhtml_parts = array();
function buffer_content(){
	static $on = false;
	static $mhtml_sepperator = '_ANY_STRING_WILL_DO_AS_A_SEPARATOR';

	global $mhtml_parts;

	if(!$on){
		if(DATA_URI === 'mhtml'){
			ob_start();
			$on = 'mhtml';
		} else {
			$on = true;
			implicit_flush_buffers();
		}
	} else if($on === 'mhtml'){
		$css = ob_get_contents();
		ob_end_clean();
		echo "/*\r\n".
		'Content-Type: multipart/related; boundary="'.$mhtml_sepperator.'"'."\r\n";
		foreach($mhtml_parts as $id=>$data){
			echo "--$mhtml_sepperator\r\n" .
			"Content-Location:$id\r\n" .
			"Content-Transfer-Encoding:base64\r\n\r\n" .
			"$data\r\n";
		}
		echo "*/\r\n\r\n";

		echo $css;
	}


}


function mhtml_part($data){
	global $mhtml_parts;
	$id = mhtml_id();
	$mhtml_parts[$id] = $data;
	return 'mhtml:http://'.$_SERVER['SERVER_NAME'].'/'.ltrim($_SERVER['REQUEST_URI'],'/').'!'.$id;
}

function mhtml_id(){
	static $i = 0;
	$i++;
	return 'i'.$i;
}

function implicit_flush_buffers(){
	while(@ob_end_flush())
	
	ob_implicit_flush(true);
}

function read_comment($handle,&$chrs){
	$chrs = $c = fread($handle,1);
	if($c == '*'){
		$hit_star = false;
		while(($c = fread($handle,1)) !== false){
			if($hit_star){
				$hit_star = false;
				if($c == '/') {
					$chrs .= $c;
					break;
				}
			}
			if($c == '*'){
				$hit_star = true;
			}
		}
		return true;
	}
	return false;
}

function get_requested_file(){
	$path = false;
	if(isset($_GET['path'])){
		$path = $_SERVER['DOCUMENT_ROOT'].'/'.$_GET['path'];
	}
	return $path;
}

function mhtml_compatable(){
	$ua = @$_SERVER['HTTP_USER_AGENT'];
	$mhtml = false;
	$mhtml_compatable = "/MSIE [67]\.0/";
	preg_match($mhtml_compatable,$ua,$matches);
	if($matches){
		$mhtml = true;
	}
	return $mhtml;
}
