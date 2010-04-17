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

class dbData{
	/**
	*@p array stores all of the queried values
	*/
	private static $data = array();
	/**
	* base method for adding a row of data to a table
	* @param string $table
	* @param string $pk
	* @param array $data a field with key the same as tghe pk must be set
	* @return bool false if the primary key doesnt exist in the data array
	*/
	public static function addRow($table,$pk,Array $data){
		$ret = false;
		if(isset($data[$pk])){
			$ret = true;
			$tableArr =& self::tableRefrence($table);
			$tableArr[$pk.'-'.$data[$pk]] = $data;
		}
		return $ret;
	}
	/**
	* add many rows to a table
	* @param string $table
	* @param string $pk
	* @param array $data multi dimensional array of rows compatable for dbData::addRow
	*/
	public static function addRows($table,$pk,Array $data){
		foreach($data as $row){
			self::addRow($table,$pk,$row);
		}
		return true;
	}

	/**
	* this is to remove all data from this obejct
	* @return true
	*/
	public static function emptyData(){
		self::$data = array();
		return true;
	}

	/**
	* remove all array entries related to a table that may or may not be defined in the data array
	* @param string $table
	* @return bool true
	*/
	public static function emptyTable($table){
		$tableArr =& self::tableRefrence($table);
		$tableArr = array();
		return true;
	}
	/**
	*remove data from a table by the pk and the pk field name
	* @param string $table
	* @param string $pk
	* @param int $pkval
	*
	* @return bool true
	*/
	public static function emptyRow($table,$pk,$pkval){
		$row =& self::rowRefrence($table,$pk,$pkval);
		$row = array();
		return true;
	}

	/**
	* return cached data
	* @return array
	*/
	public static function export(){
		return self::$data;
	}

	/**
	* returns a refrence to a row array from a table array in the data array
	* @return &array
	*/
	public static function &rowRefrence($table,$pk,$pkval){
		if($pkval){
			$tableRef =& self::tableRefrence($table);
			if(!isset($tableRef[$pk.'-'.$pkval])){
				$tableRef[$pk.'-'.$pkval] = array();
			}
			return $tableRef[$pk.'-'.$pkval];
		} else {
			//return a refrenvce to a different array. this makes sure we dont create a key for missed rows
			//also makes sense when dealing with insert/update difference
			$var = array();
			return $var;
		}
	}

	/**
	* returns a refrence to a table array in the data array
	* @return &array
	*/
	public static function &tableRefrence($table){
		if(!isset(self::$data[$table])){
			self::$data[$table] = array();
		}
		return self::$data[$table];
	}

}
?>