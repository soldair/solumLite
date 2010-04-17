<?php
//copyright ryan day 2010

//libgeneral.php
#################################################
// GENERAL USE

function get($arr,$key,$fail = null){
	if(isset($arr[$key])) return $arr[$key];
	return $fail;
}

function gpval($key,$fail = null){
	$gp = array_merge($_GET,$_POST);
	return get($gp,$key,$fail);
}

function rqval($key,$fail = null){
	return get($_REQUEST,$key,$fail);
}

//date to age
function age($year,$month,$day) {
	$year_diff = date("Y") - $year;
	$month_diff = date("m") - $month;
	$day_diff = date("d") - $day;
	
	if ($year_diff < 0) return 0;
	if ($month_diff < 0) $year_diff--;
	elseif (($month_diff==0) && ($day_diff < 0)) $year_diff--;
	return $year_diff;
}

//debugging function from the days when globals were passed with reckless abandon
function whatGlobals(){
	foreach($GLOBALS as $var=>$val){
		if($var != 'GLOBALS'){
			echo "$var =<br />";
			var_dump($val);
			echo "<hr />";
		}else{
			echo "<b>GLOBALS DEFINED:</b><hr />";
		}
	}
}

//used to make session ids as unique as posible
function base_convert2($numstring, $frombase, $tobase) {
	$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	$tostring = substr($chars, 0, $tobase);

	$length = strlen($numstring);
	$result = '';
	for($i = 0; $i < $length; $i++) {
		$number[$i] = strpos($chars, $numstring{$i});
	}

	do {
		$divide = 0;
		$newlen = 0;
		for($i = 0; $i < $length; $i++) {
			$divide = $divide * $frombase + $number[$i];
			if($divide >= $tobase) {
				$number[$newlen++] = (int)($divide / $tobase);
				$divide = $divide % $tobase;
			} elseif($newlen > 0) {
				$number[$newlen++] = 0;
			}
		}

		$length = $newlen;
		$result = $tostring{$divide}. $result;
	}
	while($newlen != 0);
	return $result;
}

//midnight timestamp used for TODAY constant
function day_time($time = false){
	if($time === false){
		$time = time();
	}
	return mktime(0,0,0,idate('m',$time),idate('d',$time),idate('Y',$time));
}

//this shortcut was added for compatability with old code that isnt an issue anymore
function sqlEsc($val){
	Db::sqlEsc($val);
}

//remote file exists check
function fileExists($url){
	if(instr($url,'http://')){
		$c = @fopen($url,'r');
		if($c){
			fclose($c);
			return true;
		}
		return false;
	}else{
		return file_exists($url);
	}
}

//linux shell style quick mysql file import
function mysqlImport($file,$configpath = '../config.php'){
	if(file_exists($file)){
		$pw = DB_PASSWORD;
		if(!empty($pw)){
			$pw = "-p".DB_PASSWORD." ";
		}
		exec("mysql ".DB_NAME." -u".DB_USERNAME." $pw< $file",$out);
		return true;
	}else{
		return false;
	}
}

//string parsing- strpos with truefalse result
function instr($str,$find){
	if(is_object($str) || is_object($find)){
		throw new Exception('here it is');
	}
	if(strpos($str,$find) !== false){
		return true;
	}else{
		return false;
	}
}

//check if isset keys in an array return true only if all keys are set
function keysIsset($keys,$subject){
	if(!is_array($keys)){
		$keys = array($keys);
	}
	foreach($keys as $k){
		if(!isset($subject[$k])){
			return false;
		}
	}
	return true;
}
//combined empty and isset for an array to check multiple key values 
//returns false only if all keys are set and !empty
function keysEmpty($keys,$subject){
	if(!is_array($keys)){
		$keys = array($keys);
	}
	foreach($keys as $k){
		if(isset($subject[$k])){
			if(empty($subject[$k])){
				return true;
			}
		}else{
			return true;
		}
	}
	return false;
}

//keys empty has a risk of ignoring the valid 0 string value
//this checked the strlen or the array len instead of the vale for a pass
function keysHaveLength($keys,$subject){
	if(!is_array($keys)){
		$keys = array($keys);
	}
	foreach($keys as $k){
		if(isset($subject[$k])){
			if(is_array($subject[$k])){
				if(count($subject[$k]) == 0){
					return false;
				}
			}else{
				if(strlen($subject[$k].'') == 0){
					return false;
				}
			}
		}else{
			return false;
		}
	}
	return true;
}

///NOTE not very useful
//extracts array key vals from $x big array and adds them to the refrenced array $arrout
//returns false when one of the keys does not exist in the subject array
//(function still adds key with empty value)
//bool arrtype 1 = assoc array
//hard to use but you can build an array out of selected vals from another array. the built array can already exist the vals will be added
function arrayExtract($keys,$subject,&$arrout,$arrtype = 0){
	if(!is_array($arrout)){
		$arrout = array();
	}
	$ret = true;
	foreach($keys as $k){
		if(isset($subject[$k])){
			if($arrtype == 1){
				$arrout[$k] = $subject[$k];
			}else{
				$arrout[] = $subject[$k];
			}
			$ret = false;
		}else{
			if($arrtype == 1){
				$arrout[$k] = '';
			}else{
				$arrout[] = '';
			}
		}
	}
	return $ret;
}

//server side domain lookup
function checkDNS($dns){
	$dns = escapeshellarg($dns);
	exec("nslookup $dns",$out);
	foreach($out as $k=> $m){
		if(instr($m,'NXDOMAIN')){
			return false;
		}
	}
	return true;
}

//i have never acctually used this but it will come in handy one day =P
function insert_str($stradd,$str,$pos){
	$p1 = substr($str,0,$pos);
	$p2 = substr($str,0,$pos+1);
	return $p1.$stradd.$p2;
}

/**its been damn useful
@param int 	<:$quan>the quantity to base plural off of
@param string 	<:$sn> 	the suffix applied if singluar
@param string 	<:$pl>	the suffix applied if plural
@param bool 	<:$out>	default <false> returns string. <true> echos
*/
function plural($quan,$sn='',$pl='s',$out= false){
	if($quan == 1){
		return $sn;
	}else{
		if(!$out){
			return $pl;
		}else{
			echo $pl;
			return '';
		}
	}
}

//formats an array into a get string
function array2url($link = array()){
	$linkstr = "";
	$bool = 0;
	if(!empty($link) && is_array($link)){
		foreach($link as $k => $v){
			if($bool == 0){
				$linkstr .= "$k=$v";
				$bool++;
			}else{
				$linkstr .= "&$k=$v";
			}
		}
		return $linkstr;
	}else{
		return '';
	}
}

//its been useful =)
function isOdd($i){
	return !is_int($i/2);
}

//handy check in unusual situations
function is_assoc_array($arr){
	$ret = false;
	if(is_array($arr)){
		foreach($arr as $k=>$v){
			if(!is_int($k)){
				$ret = true;
				break;
			}
		}
	}
	return $ret;
}

//super cool load balancer with 4.8-8% diff with even bins on about a million itterations
function load_balance($bins = 1){
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

//old paged query handling function
function limit($pagesize, $page) {
	$first = $pagesize * $page;
	return "LIMIT $first, $pagesize";
}
//old paged query handling function
function pages($pagesize, $total, &$page, &$last_page) {
	$first = $pagesize * $page;
	$last_page = ceil($total / $pagesize)-1;
	if($first >= $total) {
		$page = $last_page;
		$first = $page * $pagesize;
	}
}

function array_filter_by_prefix($arr,$prefix,$leave_prefix = false){
	$out = array();
	foreach($arr as $k=>$v){
		if(strpos($k,$prefix) === 0){
			if(!$leave_prefix){
				$k = substr($k,strlen($prefix));
			}
			$out[$k] = $v;
		}
	}
	return $out;
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
	$reldays = ($today - $timestamp) / (60 * 60 * 24);
	
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