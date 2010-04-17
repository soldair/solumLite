<?php
/**************************************************************************\
*   Copyright 2008-2009 Ryan Day                                           *
*   http://Ryanday.org
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/

/**
* This class provides query building functions that can power orm style data management or just simplify queries
*/
class dbHelper{

	############################
	#DATA WRITE QUERY FUNCTIONS#
	############################
	/**
	* Returns affected rows. runs sql replace query
	*
	* @param string $table 
	* @param array $assocarray
	* @param array $onduplicateupdate
	* @return [int or false]
	*
	* @see dbHelper::onDuplicate() for $onduplicateupdate array formatting
	*/
	public static function insertQry($table,$assocarray,$onduplicateupdate = array()){
		if(is_array($assocarray) && !empty($table)){
			$fields = self::joinFields(array_keys($assocarray));
			$vals = self::joinValues($assocarray);
			if(!empty($fields) && !empty($vals)){
				$sql = "INSERT INTO $table($fields) VALUES($vals)";
				$sql.= self::onDuplicate($onduplicateupdate);
				return db::write($sql);
			}
		}
		return false;
	}

	/**
	* Returns affected rows. this runs update queries
	* @param string $table
	* @param array $setassocarray
	*	@see dbHelper::joinFieldValue() for $setassocarray formatting
	* @param array $wherearray
	*	@see dbHelper::joinWhere() for $wherearray formatting
	* @param mixed $setor
	*	@see dbHelper::andOr() for more info on $setor formatting
	* @param int $limit
	*
	* @return int (affected rows)
	*/
	public static function updateQry($table,$setassocarray,$wherearray,$setor = 'AND',$limit = 0){
		$table = "`$table`";
		$set = self::joinFieldValue($setassocarray);
		$where = self::joinWhere($wherearray,$setor);
		$limit = intval($limit);
		if(!empty($limit)){
			$limit = "LIMIT ".$limit;
		}else{
			$limit = '';
		}
		$sql = "UPDATE $table SET $set WHERE $where $limit";
		return db::write($sql);
	}

	/**
	* Returns affected rows. runs sql replace query
	*
	* @param string $table 
	* @param array $assocarray
	* @return [int or false]
	*
	* NOTE replace is slow because is requires both and insert and delete operation while checking keys
	* USE dbHelper::insertQry with onduplicate update when ever you can
	*/
	public static function replaceQry($table,$assocarray){

		if(is_array($assocarray) && !empty($table)){
			$fields = self::joinFields(array_keys($assocarray));
			$vals = self::joinValues($assocarray);
			if(!empty($fields) && !empty($vals)){
				$sql = "REPLACE INTO $table ($fields) VALUES($vals)";
				return db::write($sql);
			}
		}
		return false;
	}


	/**
	* returns affected rows or false. this runs a real delete query
	* @param string $table - the table you would like to delete from
	* @param array $whereassoc
	* @param mixed $andor
	* @return [int or false]
	*
	* @see dbHelper::joinWhere() for array formatting
	* @see dbHelper::andOr() for more info for $andor formatting
	*/
	public static function deleteQry($table,$whereassoc,$andor = 'AND',$bypassWhere = 'DONT_DELETE_EVERYTHING'){
		$good = false;
		if(empty($whereassoc) && $bypassWhere == 'DELETE_EVERYTHING'){
			$where = '1';
			$good = true;
		}else{
			$where = self::joinWhere($whereassoc,$andor);
			if(!empty($where)){
				$good = true;
			}
		}
		if($good){
			$sql = "DELETE FROM $table WHERE $where";
			return db::write($sql);
		}
		return false;
	}

	#####################################
	#QUERY FORMATTING/BUILDING FUNCTIONS#
	#####################################

	/**
	* Returns a string formatted for adding on duplicate key update to an insert query
	* @param array $updateassoc - the field value pairs you would like to update if it is a duplicate
	* @return string
	*
	* @see dbHelper::joinFieldValue() for $updateassoc formatting
	* NOTE no sanity check to make sure primary key or unique field is included in insert
	*/
	public static function onDuplicate($updateassoc){
		$str = '';
		if(!empty($updateassoc)){
			$vals = self::joinFieldValue($updateassoc);
			if(!empty($vals)){
				$str = " ON DUPLICATE KEY UPDATE $vals";
			}
		}
		return $str;
		
	}

	/**
	* Returns single value cleaned for sql or if you pass an array a string with all values cleaned comma delimited
	* @param mixed $val
	* @return string
	*/
	public static function cleanValue($val){
		if(!is_bool($val) && !is_null($val)){
			if(!is_int($val)){
				if(!is_array($val)){
					$val = "'".db::sqlEsc($val)."'";
				} else {
					$cat = "'";
					foreach($val as $v){
						$cat .= self::cleanValue($v)."',";
					}
					$val = rtrim($cat,',');
				}
			}
		}else{
			$val = bool2str($val);
		}
		return $val;
	}

	/**
	* Returns values formatted for select and insert delimits with ` in the case of a mysql namespace collision (its happened)
	* @param array $fields numerically indexed field names as values
	* @return string
	*/
	public static function joinValues($vals){//insert
		$str = '';
		foreach($vals as $k=>$v){
			$v = self::cleanValue($v);
			$str .= ",$v";
		}
		return ltrim($str,',');
	}


	/**
	* Returns fields formatted for select and insert delimits with ` in the case of a mysql namespace collision (its happened)
	* @param array $fields numerically indexed field names as values
	* @return string
	*/
	public static function joinFields($fields){//insert or select
		//nonalias field string
		$str_fields = '';
		//mysql can throw query error due to namespace confilicts with query field names 'group' or others
		foreach($fields as $f){
			if(instr($f,'`')){//already formatted
				$str_fields .= ",$f";
			}else{
				$str_fields .= ",`$f`";
			}
		}
		return ltrim($str_fields,',');
	}

	/**
	* Returns a string formatted for an update query in setter fashion
	*
	* @param array $assoc
	* @return string
	*
	* $value1 = array('1');
	* $assoc = array(
	*		'field1'=>'value', <makes "`field1`='value'">
	*		'field2'=>array(1), <makes "`field2`=`field2`+1">
	*		'field3'=>array('-',1), <makes "`field3`=`field3`-1">
	*		'field3'=>array('@',1), <makes "`field3`=`field3`@1">NOTE OPERATOR ASSUMED CORRECT
	*		'field3'=>array('field2',1), <makes "`field3`=`field2`+1">NOTE OPERATOR ASSUMED ONE CHARACTER DEFAULT TO FIELD
	*		'field2'=>array('field2','-',3), <makes "`field3`=`field2`-3">NOTE OPERATOR NOT CHECKED FOR LENGTH
	*	);
	*/
	public static function joinFieldValue($assoc){//update or onduplicate
		$str = '';
		foreach($assoc as $f=>$v){
			if(!is_array($v)){
				$str .= ",`$f`=".self::cleanValue($v);
			}else if(!empty($v)){
				$count = count($v);
				$op = '+';
				$field = $f;
				if($count == 1){
					$int = intval($v[0]);
				}elseif($count == 2){
					$int = intval($v[1]);
					if(strlen($v[0]) == 1){
						$op = $v[0];
					}else{
						$field = $v[0];
						
					}
				}else{
					$int = intval($v[2]);
					$op = $v[1];
					$field = $v[0];
				}
				$str .= ",`$f`=`$field`{$op}{$int}";
			}
		}
		return ltrim($str,',');
	}

	/**
	* Returns a properly formatted string where clause for use directly in sql queries
	* @param array $whereassoc
	* @param mixed $andor string default delimiter or array of delimiters with length one less then the number of clauses
	*
	* $whereassoc = array(
	*		'field1'=>'value', <makes "`field1`='value'">
	*		'field1'=>array('field'=>'field3'); <makes "`field1`=`field3`>
	*		'field1'=>array('!=','field'=>'field3'); <makes "`field1`!=`field3`>
	*		'field1'=>array('<','field'=>'field3','-1'); <makes "`field1`<`field3`-1>
	*		'field1'=>array('<','field'=>'field3',1); <makes "`field1`<`field3`+1>
	*		'field2'=>array(1), <makes "`field2` = 1">NOTE might as well use 'field2'=>1
	*		'field3'=>array('<',1), <makes "`field3` < 1">
	*		'field4'=>array('@',1), <makes "`field4` @ 1">NOTE OPERATOR ASSUMED CORRECT
	*		'field5'=>array(array(1,2,3,4)), <makes "`field5` IN(1,2,3,4)">NOTE with no opperator assumed IN
	*		'field6'=>array(true,array(1,2,3,4)), <makes "`field6` IN(1,2,3,4)">
	*		'field7'=>array(false,array(1,2,3,4)), <makes "`field7` NOT IN(1,2,3,4)">
	*		'field8'=>array('ANYTEXT',array(1,'lamp',3,4)) <makes "`field8` ANYTEXT(1,'lamp',3,4)">
	*	);
	*
	* //clause delimiter
	* $andor = array('AND (','OR',') AND')
	*/
	public static function joinWhere($whereassoc,$andor = 'AND'){

		self::andOr(count($whereassoc),/*&*/$andor);
		$wkeys = array_keys($whereassoc);
		$out = '';
		for($i = 0;$i<count($whereassoc);$i++){
			$wkey = $wkeys[$i];
			$wval = $whereassoc[$wkey];
			$logicalOp = (count($whereassoc)-1 != $i?' '.(isset($andor[$i])?$andor[$i]:'AND').' ':'');
			$out .= self::processWhereKey($wkey,$wval).$logicalOp;
		}
		return $out;
	}

	#########
	#PRIVATE#
	#########

	/**
	* opperates on array by refrence of appropraite length to delimit all where clauses
	* @param int $countarg - the number of where clauses
	* @param array $andor
	* @return void
	*
	* NOTE $andor can be 'AND' 'OR' or array('one','entry','per','clause','delimiter');
	* if you use the array method the text entries are assumed correct (side effect: you can (group) and long as you have 3 or more clauses =P)
	* if you have less entries in the $setor array than the $where array the remaining clauses will be delimited by 'AND'
	* if you have more entries in the $setor array the entries are dropped
	*/
	private static function andOr($countarg,&$andor){
		if($countarg){
			$lo = 'AND';
			if(!is_array($andor)){
				if(is_object($andor)){//this is a posibility so fruit off =P
					$andor = $lo;
				}
				$andor = strtoupper($andor);
				if($andor == 'OR'){
					$lo = 'OR';
				}
				$andor = array();
			}
			if(count($andor) > ($countarg-1)){
				while(count($andor)>($countarg-1)){
					array_pop($andor);
				}
			}elseif(count($andor) < $countarg-1){
				$andor = array_pad($andor,$countarg-1,$lo);
			}
	
			array_push($andor,'');//last val string empty
		}
	}

	/**
	* returns a string of one where clause. this method does all the heavy lifting
	* @param string $wkey - the field name
	* @param mixed $wval to value to compare to the field
	* @return string
	*/
	public static function processWhereKey($wkey,$wval){
		if(!is_int($wval)){
			$val = '';
			$key = '`'.$wkey.'`';
			$op = '=';
			$ret = '';
			if(is_array($wval)){
				$numvars = count($wval);
				if(isset($wval['field'])){
					if(isset($wval[0])){
						$op = $wval[0];
					}
					$math = '';
					if(isset($wval[1])){
						if(is_int($wval[1])){
							$wval[1] = '+'.$wval[1];
						}
						$math = $wval[1];
					}
					$val = '`'.$wval['field'].'`'.$math;
				}else{
					$val = $wval[0];
					if($numvars > 1){
						$op = $wval[0];
						$val = $wval[1];
					}
					if(is_array($val)){
						if($op == '=' ||$op === true){
							$op = ' IN';
						}elseif($op == '!=' || $op === false){
							$op = ' NOT IN';
						}
						$tmp = '';
						foreach($val as $l){
							$tmp .=",".self::cleanValue($l);
						}
						$val = '('.ltrim($tmp,',').')';
					}elseif(is_object($wval)){
						///TODO sub selecting
					}else{
						$val = self::cleanValue($val);
					}
				}
			}elseif(is_object($wval)){
				///TODO sub selecting
			}else{
				$val = self::cleanValue($wval);
			}
			$ret = $key.$op.$val;
			if(empty($key)){
				$ret = '';
			}
		}else{
			$ret = "`$wkey` = $wval";
		}
		return $ret;
	}
}
?>