<?php
/**************************************************************************\
* Written by:  Ryan Day <soldair@gmail.com>                                *
* Copyright 2006-2010 Ryan Day                                             *
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/

/*
general, procedural, widely applicable functions
*/

function get($arr,$key,$def = null){
	return isset($arr[$key])?$arr[$key]:$def;
}

function extractable($keys,$args){
	$ret = array();
	foreach($keys as $k){
		$ret[$k] = get($args,$k);
	}
	return $ret;
}

function rqval($key,$onfail = null){
	return get($_REQUEST,$key);
}

function gval($key,$onfail = null){
	return get($_GET,$key);
}

function gpval($key,$onfail = null){
	$gp = array_merge($_GET,$_POST);
	return get($gp,$key);
}

/*simple str check*/
function instr($str,$find){
	return (strpos($str,$find) !== false?true:false);
}

function loadBalance($bins = 1){
	$time = microtime(true);
	$base = crc32(intval(str_replace('.','',$time.'')));
	$bin = $base%$bins;
	return $bin;
}

//translates bool or null values into string representaion
function bool2str($boolornull){
	$ret = 'true';
	if(!$boolornull){
		if($boolornull === null){
			$ret = 'null';
		}else{
			$ret = 'false';
		}
	}
	return $ret;
}

/*
* Method: email_rfc
*  Validate email, RFC compliant version
* 
*  Originally by Cal Henderson, modified to fit Kohana syntax standards:
*  - http://www.iamcal.com/publish/articles/php/parsing_email/
*  - http://www.w3.org/Protocols/rfc822/
*
* Parameters:
*  email - email address
*
* Returns:
*  TRUE if email is valid, FALSE if not.
*/
function validateEmail($email){
	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
	$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
	$pair  = '\\x5c[\\x00-\\x7f]';

	$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
	$quoted_string  = "\\x22($qtext|$pair)*\\x22";
	$sub_domain     = "($atom|$domain_literal)";
	$word           = "($atom|$quoted_string)";
	$domain         = "$sub_domain(\\x2e$sub_domain)*";
	$local_part     = "$word(\\x2e$word)*";
	$addr_spec      = "$local_part\\x40$domain";

	return (bool) preg_match('/^'.$addr_spec.'$/', $email);
}

function quickFilter($regex,$str){
	$res = preg_match_all($regex,$str,$matches);
	$ret = '';
	if($matches){
		$ret = implode('',$matches[0]);
	}
	return $ret;
}

/**
 * Returns the relative time string (e.g. "2 days")
 *
 * @param integer $timestamp The UNIX timestamp whose relative equivalent will
 *                            be returned.
 *
 * @return string The relative equivalent of the timestamp
 */
function get_relative_time($timestamp)
{

	$today   = time();
	$relseconds = ($today - $timestamp);
	$relminutes = $relseconds / 60;
	$relhours = $relminutes / 60;
	$reldays = $relhours / 24;

	if($relseconds < 60){
		return 'moments ago';
	} else if($relminutes < 45) {
		return round($relminutes).' minute'.(round($relminutes) > 1?'s':'').' ago';
	} else if($relminutes < 60){
		return 'a while ago';
	} else if($relhours < 24){
		return round($relhours).' hour'.(round($relhours) > 1?'s':'').' ago';
	} else {
		if ($reldays >= 0 && $reldays < 1)
		{
			return "today";
		} 
		else if ($reldays >= 1 && $reldays < 2)
		{
			return "yesterday";
		}
		
		if ($reldays < 7)
		{
			$reldays = floor($reldays);
			return $reldays . ' day' . ($reldays != 1 ? 's' : '') . ' ago';
		}
		else if($reldays < 31)
		{
			$reldays = floor($reldays / 7);
			return $reldays . ' week' . ($reldays != 1 ? 's' : '') . ' ago';
		}
		else if ($reldays < (31 * 6))
		{
			$reldays = floor($reldays / 31);
			return $reldays . ' month' . ($reldays != 1 ? 's' : '') . ' ago';
		}
		return date("l, F j", $reldays);
	}
}

//midnight timestamp
function day_time($time = false){
	if($time === false){
		$time = time();
	}
	return mktime(0,0,0,idate('m',$time),idate('d',$time),idate('Y',$time));
}

function clean($str){
	return htmlentities($str,ENT_QUOTES);
}

function appendMTime($url){
	if(!instr($url,'://')){
		if(file_exists($p = SITE_ROOT.'/'.$url)){
			$url .= '?'.filemtime($p);
		}
	}
	return $url;
}

function anchor($ref,$data,$params = array()){
	if(!instr($ref,'http://')){
		if(!instr($ref,'?')){
			$ref = urlstr(request::mapUri($ref));
	
		}
	}
	$params = formatParams($params);
	echo "<a href='$ref' $params>$data</a>";
}

function formatParams($array){
	$params = '';
	foreach($array as $k=>$v){
		$params .= $k.'="'.$v.'" ';
	}
	return $params;
}

function urlstr($ref){
	if(is_array($ref)){
		$str = '?';
		foreach($ref as $k=>$v){
			$str .= "$k=$v&";
		}
		$ref = trim($str,'&');
	}
	return $ref;
}

function getDebugInfo(){

	$out = "<div style='clear:both;border:1px solid blue;margin:5px;background:#d4d4d4;padding:3px;margin-left:10px;mergin-right:10px;color:#000;'>
			<div style='background:white;padding:3px;'>
				<div>
					<b>benchmarks:</b>
					<div style='margin:5px;background:#D9FFCE;'>";
					foreach(bench::get_marks() as $m){
						$out .= "<div>$m: ".bench::elapsed_ms($m)." ms</div>";
					}
					$out .="<hr/>
						<div>peak memory: ".( (int) (memory_get_peak_usage()/1024))." kb</div>
						<div>database reads: ".db::$query_count." </div>
						<div>database writes: ".db::$query_count_w." </div>
						<div>database read time: ".sprintf('%0.5f',(db::$query_time)*1000)." ms</div>
						<div>database write time: ".sprintf('%0.5f',(db::$query_time_w)*1000)." ms</div>
					</div>
				</div>
				<div>
					<b>database queries:</b>".(QUERY_LIST?'[query list enabled]':'[query list DISABLED]')."
					<div style='margin:5px;background:#D9FFCE;'>
						<pre>".db::$query_list."</pre>
					</div>
				</div>
				<div>
					<b>session data:</b>
					<div style='margin:5px;background:#D9FFCE;'>";
					foreach(sessionCookie::$session as $k=>$v){
						$out .= "<div>$k: $v</div>";
					}
				$out .= "</div>
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
	return $out;
}


//delete?
function keysIsset($arr,$find){
	foreach($find as $k){
		if(!isset($arr[$k])){
			return false;
		}
	}
	return true;
}
?>