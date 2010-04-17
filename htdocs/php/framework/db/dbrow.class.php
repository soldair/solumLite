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
* the instantiated use for this class provideds methods to manipulate a row of data.
*/
class dbRow{
	#################
	# PUBLIC STATIC #
	#################
	/**
	* this is the only way to instantiate a row
	* this method is executed by dbTable
	* @param string $tableName - the name of the table that this row belongs to
	* @param string $primaryKey - the name of the primary key field
	* @param int $pkval - the pk value of the row
	*
	* @return mixed bool false or dbRow
	*/
	public static function getRow($table,$pk,$pkval){
		if(!empty($table) && !empty($pk)){
			$data = dbData::rowRefrence($table,$pk,$pkval);
			return new self($table,$pk,$data);
		}
		return false;
	}

	##########
	# PUBLIC #
	##########
	/**
	* this method performs both insert and update
	* this only runs a query if data has changed
	* this updates dbData through the orig refrence
	* saving behavior is determined by the contents of the orig array
	*	if empty orig insert else update
	* after a save all objects that refrence this orig data will be refcrenced to the new data
	*
	* @return bool or int affected rows
	*/
	public function save(){
		$res = true;
		$changedData = $this->changedData();
		if(count($changedData)){
			if(!empty($this->orig)){
				$res = (dbHelper::updateQry($this->tableName,$changedData,array($this->primaryKey=>$this->data[$this->primaryKey]))?true:false);
				$this->orig = $this->data;
			} else {
				//if this row has been deleted but someone else saves data to it this will automatically restore the row from $data
				$res = (dbHelper::insertQry($this->tableName,$this->data)?true:false);
				$this->data[$this->primaryKey] = db::insertID();
				dbData::addRow($this->tableName,$this->primaryKey,$this->data);
				$this->orig =& dbData::rowRefrence($this->tableName,$this->primaryKey,$this->data[$this->primaryKey]);
			}
		}
		return $res;
	}

	public function loadRelatedRow($table,$field = ''){

		if(!$field) {
			$field = $table.'_id';
		}

		return DBTable::get($table)->loadRow($this->get($field));

	}

	/**
	* sets value of key to value in working copy of data
	* @return mixed
	*/
	public function set($key,$value){
		$this->data[$key] = $value;
		return true;
	}

	/**
	* return value of key in working copy of data
	* @return mixed
	*/
	public function get($key){
		return (isset($this->data[$key])?$this->data[$key]:null);
	}

	/**
	* is key set in working copy of data
	* @return bool
	*/
	public function is_set($key){
		return isset($this->data[$key]);
	}

	/**
	* returns working copy of the data array
	* @return string
	*/
	public function export(){
		return $this->data;
	}

	/**
	* returns data array that represents that data oin the db
	* @return string
	*/
	public function exportOrig(){
		return $this->orig;
	}

	/**
	* returns the name of the pk field in the table this row is related to
	* @return string
	*/
	public function getPrimaryKeyFieldName(){
		return $this->primaryKey;
	}

	/**
	* returns the name of the table this row is related to
	* @return string
	*/
	public function getTableName(){
		return $this->tableName;
	}

	/**
	* because of the orm style it is possible for a row to be deleted at some unknown depth changing the saving behavior of this object to an insert
	* this allows you to detect that and 
	* @return bool
	*/
	public function wasDeleted(){
		//if data pk and no orig pk i was probably deleted at some unknow nesting depth
		$ret = false;
		if(isset($this->data[$this->primaryKey])){
			if($this->data[$this->primaryKey]){
				if(!isset($this->orig[$this->primaryKey])){
					$ret = true;
				}
			}
		}
		return true;
	}

	/**
	* this returns all values in data that are different then orig
	* @return array
	*/
	public function changedData(){
		$res = array();
		foreach($this->data as $k=>$v){
			$add = false;
			if($k != $this->primaryKey){
				$add = true;
				if(array_key_exists($k,$this->orig)){//support for null values
					if($this->orig[$k] == $v){
						$add = false;
					}
				}
				if($add){
					$res[$k] = $v;
				}
			}
		}
		return $res;
	}

	/**
	* resets all values in data to their orignal values
	* @param bool $complete
	*	if true [default] data becomes an exact copy of orig
	*	if false all key values in orig are written to data which overwrites conflicting key values in data
	*
	* @return true
	*/
	public function revert($complete = true){
		if($complete){
			$this->data = $this->orig;
		} else {
			foreach($this->orig as $k=>$v){
				$this->data[$k] = $v;
			}
		}
		return true;
	}
	###############
	# PUBLIC MAGIC#
	###############

	public function __get($key){
		return $this->get($key);
	}

	public function __set($key,$value){
		return $this->set($key,$value);
	}

	public function __isset($key){
		return $this->is_set($key);
	}

	###########
	# PRIVATE #
	###########
	/**
	* @p array the current dataset that you are working with
	*/
	private $data = array();
	/**
	* @p array a refrence to the dbdata data row or array
	*/
	private $orig = array();
	private $tableName = '';
	private $primaryKey = '';
	private function __construct($tableName,$primaryKey,&$data){
		$this->tableName = $tableName;
		$this->primaryKey = $primaryKey;
		if(!empty($data)){
			$this->orig =& $data;
		}
		$this->data = $data;
	}
}
?>