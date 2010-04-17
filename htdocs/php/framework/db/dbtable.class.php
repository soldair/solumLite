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

class dbTable{

	##########
	# STATIC #
	##########

	private static $loadedTables = array();
	/**
	* returns table object based on the info you provided
	* @param string $tableName
	* @param string $primaryKey - the field name of the primary key
	* @return dbTable
	*/
	public static function get($tableName,$primaryKey = ''){
		$primaryKey = ($primaryKey?$primaryKey:$tableName.'_id');
		if(!isset(self::$loadedTables[$tableName])){
			self::$loadedTables[$tableName] = new self($tableName,$primaryKey);
		}
		return self::$loadedTables[$tableName];
	}

	/**
	* deletes from database and unsets a row object
	* @param dbRow $row
	*
	* @return bool or int affected rows
	*/
	public static function deleteRow(dbRow &$row){
		$ret = false;
		$pkField = $row->getPrimaryKeyFieldName();
		$table = $row->getTableName();
		if(isset($row->$pkField)){
			$pkValue = $row->$pkField;
			$ret = dbHelper::deleteQry($table,array($pkField=>$pkValue));
			if($ret){
				dbData::emptyRow($table,$pkField,$pkValue);
			}
			$row = null;
		}
		return $ret;
	}

	public static function delete(dbRow &$row){
		return self::deleteRow($row);
	}

	/**
	* queries data from the db if its not cached in dbData
	* @param string $table
	* @param string $pk
	* @param int $pkval
	*
	* @return array !REFRENCE!
	*/
	public static function &getRowData($table,$pk,$pkval){
		$rowRef = dbData::rowRefrence($table,$pk,$pkval);
		if(!$rowRef){
			$rowRef = db::q1rAssoc("select * from $table where $pk='".db::sqlEsc($pkval)."'");
		}
		return $rowRef;
	}

	##########
	# PUBLIC #
	##########

	/**
	* read only member accessor
	* @return string the name of the table represented by the table object
	*/
	public function getName(){
		return $this->tableName;
	}

	/**
	* read only member accessor
	* @return string the name of the primary key field in this table
	*/
	public function getPK(){
		return $this->primaryKey;
	}

	/**
	* load row for an insert
	* @return dbRow
	*/
	public function loadNewRow(){
		return dbRow::getRow($this->tableName,$this->primaryKey,0);
	}

	/**
	* load row for an update
	* @return dbRow or false
	*/
	public function loadRow($primaryKeyValue,$data = array()){

		$rows = $this->loadRowsWhere(array($this->primaryKey=>$primaryKeyValue));
		return (count($rows)?$rows[0]:false);
	}


	/**
	* Returns array of row objects.
	* @param mixed $where  - unlike the other "*Where" methods you may specify a string where clause
	*	@see dbHelper::joinWhere() for $where formatting
	* @param mixed $andor
	*	@see dbHelper::andOr() for more info on $andor formatting
	* @param int $limit
	*
	* @return array(dbRow)
	*/
	public function loadRowsWhere($where,$andor = 'AND',$limit=''){
		if(is_array($where)){
			$where = dbHelper::joinWhere($where,$andor);
		}
		$data = db::qryAssoc("SELECT * FROM {$this->tableName} WHERE $where $limit");
		//adds to row cache
		dbData::addRows($this->tableName,$this->primaryKey,$data);
		$ret = array();
		foreach($data as $r){
			//get loaded from row cache
			$ret[] = dbRow::getRow($this->tableName,$this->primaryKey,$r[$this->primaryKey]);
		}
		return $ret;
	}

	/**
	* Returns affected rows. this runs update queries on this table
	* @param array $set
	*	@see dbHelper::joinFieldValue() for $set formatting
	* @param array $where
	*	@see dbHelper::joinWhere() for $where formatting
	* @param mixed $andor
	*	@see dbHelper::andOr() for more info on $andor formatting
	* @param int $limit
	*
	* @return int (affected rows)
	*/
	public function updateRowsWhere($set,$where,$andor = 'AND',$limit = 0){
		$ret = dbHelper::updateQry($this->tableName,$set,$where,$andor,$limit);
		if($ret){
			dbData::emptyTable($this->tableName);
		}
		return $ret;
	}

	/**
	* delete all rows from this table matching where clause
	* @param array $where
	*	@see dbHelper::joinWhere() for $where formatting
	* @param mixed $andor
	* 	@see dbHelper::andOr() for more info on $setor formatting
	* @param string $bypasswhere this is a flag to prevent delete all if you pass an empty $where array
	*	@see dbHelper::deleteQry() for more info
	*
	* @return bool or int (affected rows)
	*/
	public function deleteRowsWhere($where,$andor = 'AND',$bypassWhere = 'DONT_DELETE_EVERYTHING'){
		$ret = dbHelper::deleteQry($this->tableName,$where,$andor,$bypassWhere);
		if($ret){
			dbData::emptyTable($this->tableName);
		}
		return $ret;
	}
	###########
	# PRIVATE #
	###########
	private $tableName;
	private $primaryKey;
	private function __construct($tableName,$primaryKey){
		$this->tableName = $tableName;
		$this->primaryKey = $primaryKey;
	}
}
?>