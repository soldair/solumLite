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
* This is the Solum framework <MYSQLI> db class
*/


class db{
	#########
	#dbwrite#
	#########
	/**
	* runs a data write query
	* NOTE ALL DATABASE WRITES SHOULD BE SENT THROUGH THIS FUNCTION
	* @param string $sql
	* @param mixed $wait
	*	if you have read hosts this will trigger mysql wait for replication functions to give data time to propagate
	*	bool(1) or int max seconds to wait
	*	if wait fails the read host connection is replaced with the write connection for the remainer if the request
	* @return array
	*/
	public static function write($sql,$wait = true){
		return self::singleton()->run($sql,'write',$wait);
	}
	########
	#dbread#
	########
	/**
	* returns a full resultset with int keys
	* @param string $sql
	* @return array
	*/
	public static function qry($sql){
		return self::singleton()->run($sql,'qry');
	}

	/**
	* returns a full resultset with assoc keys
	* @param string $sql
	* @return array
	*/
	public static function qryAssoc($sql){
		return self::singleton()->run($sql,'qryAssoc');
	}

	/**
	* returns first colum of resultset with int keys
	* @param string $sql
	* @return array
	*/
	public static function q1c($sql){
		return self::singleton()->run($sql,'q1c');
	}

	/**
	* returns first row of resultset with int keys
	* @param string $sql
	* @return array or false on failure
	*/
	public static function q1r($sql){
		return self::singleton()->run($sql,'q1r');
	}

	/**
	* returns first row of resultset with assoc keys
	* @param string $sql
	* @return array or false on failure
	*/
	public static function q1rAssoc($sql){
		$res = self::singleton()->run($sql.' LIMIT 1','qryAssoc');
		return ($res?(isset($res[0])?$res[0]:false):false);
	}

	/**
	* returns first value from first row of resultset
	* @param string $sql
	* @return mixed or false on failure
	*/
	public static function q1v($sql){
		return self::singleton()->run($sql,'q1v');
	}
	################
	#public utility#
	################

	/**
	* use to clean values for queries
	* @param mixed $val
	* @return mixed escaped val
	*/
	public static function sqlEsc($val){
		return mysqli_real_escape_string(self::singleton()->getConnection(),$val);
	}

	public static function esc($val){
		return self::sqlEsc($val);
	}

	/**
	* returns the primary key value of the last inserted row in this connection
	* @return int or bool on fail
	*/
	public static function insertId(){
		return self::getWriteConnection()->insert_id;
	}

	/**
	* connection init is private/automatic so you should be able to check if the connection has happened already
	* @return bool
	*/
	public static function isConnected(){
		return (self::singleton()->getConnection()?true:false);
	}

	/**
	* connection init is private/automatic so you should be able to check if the connection has happened already
	* @return bool
	*/
	public static function addReadHosts(Array $hosts){
		self::$read_hosts = array_merge($hosts,(is_array(self::$read_hosts)?self::$read_hosts:array()));
		return true;
	}

	/**
	* checks the first word in a query for a write statement keyword
	* @return bool
	*/
	public static function isValidWrite($sql){
		$obj = self::$instance;
		return !$obj->isValidRead($sql);
	}

	#untested... feeling adventourous?
	/*public static function multiQuery($sql,$getResults = false,$assoc = true){
		return self::$instance->multi_query($sql,$getResults,$assoc);
	}*/

	/**
	* for batch inserts. runs 200 rows at a time
	* @return bool
	*/
	public static function insertBlock($table,$fields,$infields){
		if(empty($fields) || empty($infields)){
			return false;
		}
		if(!is_array($fields) || !is_array($infields)){
			return false;
		}
		$keys = array_keys($infields);
		if(!is_array($infields[$keys[0]])){
			return false;
		}
		$qrytop = "INSERT INTO `$table`(`".implode('`,`',$fields)."`) VALUES ";
		$build = '';
		$max = 200;
		$i = 0;
		foreach($infields as $f_row){
			$vals = '(';
			foreach($f_row as $f=>$d){
				if(!is_int($d)){
					if(empty($d)){
						$d = "''";
					}else{
						$d = "'".self::sqlEsc($d)."'";
					}
				}
				$vals .= $d.',';
			}
			$build .= rtrim($vals,',').'),';
			$i++;
			if($i == 200){
				$i = 0;
				$sql = $qrytop.rtrim($build,',');
				self::write($sql);
				$build = '';
			}
		}
		if(!empty($build)){
			$sql = $qrytop.rtrim($build,',');
			self::write($sql);
		}
		return true;
	}

	/**
	* checks query result of all tables in database for table
	* @param string $table the name of the table
	* @param string $database the name of a database or empty for the current database
	*/
	private static $tables = array();
	public function tableExists($table,$database = ''){
		$tables = self::getTables($database);
		return in_array($table,$tables);
	}


	/**
	* queries all tables in database
	* @param string $database the name of a database or empty for the current database
	*/
	public function getTables($database = ''){
		if(!isset(self::$tables[$database])){
			self::$tables[$database] = self::q1c("show tables ".($database?"from $database":''));
		}
		return self::$tables[$database];
	}

	##################################################################################
	#Direct connection access: use with care the read/write funtions should be enough#
	##################################################################################
	/**
	* direct access to the write connection
	* @return MYSQLI
	*/
	public static function getWriteConnection(){
		self::singleton();
		return self::$write_connection;
	}

	/**
	* direct access to the read connection
	* keep in mind that this var may be a refrence to the write connection
	* @return MYSQLI
	*/
	public static function getReadConnection(){
		self::singleton();
		return self::$read_connection;
	}

	###################
	#Public Properties#
	###################
	/**
	* @p int the number of read queries executed
	*/
	public static $query_count = 0;
	/**
	* @p float the total time for all read queries
	*/
	public static $query_time = 0.0;
	/**
	* @p int the number of write queries executed
	*/
	public static $query_count_w = 0;
	/**
	* @p float the total time for all write queries
	*/
	public static $query_time_w = 0.0;
	//a formatted list of each query along with time for execution
	/**
	* @p string all sql run through methods associated with time and is read or write
	*	only tracked if constant QUERY_LIST is defined and true
	*/
	public static $query_list = '';

	/**
	* @p bool this will make all calls to  self::dbError do nothing at all. use it wisely and turn it back on when you are done
	*/
	public static $ignore_errors = false;

	###############################################
	#Private Properties: connections and singleton#
	###############################################

	//the currently selected read host from the pool of available read hosts
	private static $read_host;
	//the pool of available read hosts
	private static $read_hosts;
	//the refrence to the read connection or null
	private static $read_connection;
	//the refrence to the write connection
	private static $write_connection;
	//the singleton
	public static $instance;

	private static function dbError($msg,$sql){
		if(!self::$ignore_errors){
			$msg = "solumDb error:$msg SQL: $sql";
			if(!DEBUG){
				trigger_error($msg);
			}else{
				throw new Exception($msg);
			}
		}
	}

	/**
	* checks for read host constant and convert constant value to an array of hopefully ips/hostnames
	* @return void
	*/
	private static function _checkForReadHosts(){
		if(defined('DATABASE_READ_HOSTS')){
			self::addReadHosts(explode(',',DATABASE_READ_HOSTS));
		}
	}

	private static function singleton(){
		if(!self::$instance){
			self::$instance = new self;
		}
		return self::$instance;
	}
	#######################
	#PRIVATE INSTANTIATION#
	#######################
	private function __construct(){
		$this->connect();
	}
	#########################
	#SQL EXECUTION FUNCTIONS#
	#########################

	private function connect(){
		//this function connects to both the read(if needed) and write server
		//when we add read servers we need to build out no connect write if no write is necessary
		//its simple because all writes go through one
		if(!isset(self::$write_connection)){
			self::$write_connection = false;
			for($i = 0; $i < 3; $i++) {
				$wcon = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				if(mysqli_connect_errno()) {
					if(defined('DEBUG_VERBOSE')){
						if(DEBUG_VERBOSE){
							echo "Db write host error:".mysqli_connect_error()."<br/>";
						}
					}
					sleep(1);
				} else {
					self::$write_connection = $wcon;
					break;
				}
			}
			if(!self::$write_connection){
				throw new Exception(SITE_SERVER_URL.' is temporarily unavailable');
			}
		}
		self::_checkForReadHosts();
		if(!isset(self::$read_connection)){
			if(isset(self::$read_hosts)){
				//flag read host as failed so we dont try it agian
				self::$read_connection = false;
				//localize read host var
				$db_read_hosts = self::$read_hosts;
				//total number of read hosts
				$count = count($db_read_hosts);
				//the index in the readhosts array of the server to try first
				$start = $i = loadBalance($count);//function defined in modules/api/lib/general.php
				//loop breaking flag so we dont try to connect forever
				$break = 0;
				while(true) {
					$db_read_host = $db_read_hosts[$i];
					$con = @new mysqli($db_read_host, DB_USER, DB_PASS, DB_NAME);
					//setup to attempt to connect to all hosts twice before giving up
					if(mysqli_connect_errno()) {
						$i++;
						if($i == $count) {
							$i = 0;
						}elseif($i == $start){
							$break++;
							if($break == 2){
								if(defined('DEBUG_VERBOSE')){
									if(DEBUG_VERBOSE){
										echo "DB read host error:".mysqli_connect_error()."<br/>";
									}
								}
								break;
							}else{
								sleep(1);
							}
						}
					} else {
						self::$read_host = $db_read_host;
						self::$read_connection = $con;
						break;
					}
				}
				if(!self::$read_connection){
					//i have no read hosts i want to run all queries on the master
					self::$read_connection = self::$write_connection;
				}
			}else{
				//i have no read hosts i want to run all queries on the master
				self::$read_connection = self::$write_connection;
			}
		}
	}

	private static $pings = 0;
	private static $lastPing = 0;
	private function run($sql,$type,$wait = true){
		$return = ($type == 'q1v'?'':array());
		if($this->isValidRead($sql)){
			$con = self::getReadConnection();
			self::$query_count++;
			$start = microtime(true);
			$r = $con->query($sql.($type == 'q1v' || $type == 'q1r'?' LIMIT 1':''));
			
			if($con->error != ''){
				if(instr($con->error,'server has gone away')){
					//allow a max of 3 pings within 20 seconds of each other before throwing an error
					if((time()-self::$lastPing) > 20){
						self::$pings = 0;
					}
					if(self::$pings < 3){
						self::$pings++;
						self::$lastPing = time();
						$con->ping();
						return $this->run($sql,$type,$wait);
					}
				}
				self::dbError($con->error,$sql);
			}else{
				$method = ($type != 'qryAssoc'?'fetch_row':'fetch_assoc');
				while($res = $r->$method()){
					if(!$this->addResult($res,/*&*/$return,$type)){
						break;
					}
				}
			}
			$time = microtime(true) - $start;
			self::$query_time += $time;
			$this->queryList($time,$sql,'R');
		}else{
			$con = self::getWriteConnection();
			$start = microtime(true);
			self::$query_count_w++;
			$r = $con->query($sql);
			$rows = $con->affected_rows;
			if($con->error) {
				if(instr($con->error,'server has gone away')){
					//allow a max of 3 pings within 20 seconds of each other before throwing an error
					if((time()-self::$lastPing) > 20){
						self::$pings = 0;
					}
					if(self::$pings < 3){
						self::$pings++;
						self::$lastPing = time();
						$con->ping();
						return $this->run($sql,$type,$wait);
					}
				}
				self::dbError($con->error,$sql);
			}
			if($wait && count(self::$read_hosts) > 0){
				$t = 1;
				if(is_int($wait)){
					$t = $wait;
				}
				$this->waitForReplication($t);
			}
			$time = microtime(true) - $start;
			self::$query_time_w += $time;
			$this->queryList($time,$sql,'W');
			return $rows;
		}
		return $return;
	}

	private function addResult($res,&$return,$type){
		$fret = true;
		if($type == 'q1c'){
			$return[] = $res[0];
		}elseif($type == 'qry' || $type == 'qryAssoc'){
			$return[] = $res;
		}elseif($type == 'q1r'){
			$return = $res;
			$fret = false;
		}else{//q1v
			$return = $res[0];
			$fret = false;
		}
		return $fret;
	}

	private function getConnection(){
		if(is_object(self::$read_connection)){
			return self::$read_connection;
		}else{
			return self::$write_connection;
		}
	}

	private function multi_query($sql,$getResults = false,$assoc = true){
		$ret = true;
		$con = self::getWriteConnection();
		$con->multi_query($sql);
		if($getResults){
			do {
				if ($result = $mysqli->use_result()) {
					$result_set = array();
					if($assoc){
						while ($row = $result->fetch_row()) {
							$resultSetc[] = $row;
						}
					} else {
						while ($row = $result->fetch_assoc()) {
							$resultSet[] = $row;
						}
					}
					$result->close();
					$ret[] = $result_set;
				}
			} while ($mysqli->next_result());
		}
		return $ret;
	}

	private function waitForReplication($time = 1){
		if(isset(self::$read_hosts)){
			$dbwrite = self::getWriteConnection();
			$db = self::getReadConnection();
			$t1 = microtime(true);
			$r = $dbwrite->query('SHOW MASTER STATUS');
			list($file, $offset, $rest) = $r->fetch_row();
			if($file) {
				$r = $db->query("SELECT MASTER_POS_WAIT('$file', $offset, $time)");
				list($ret) = $r->fetch_row();
				if($ret == -1) {
					//master is not ready use the write connection for the remainder of the request
					self::$read_connection = self::$write_connection;
				}
			}
			self::$query_time += microtime(true) - $t1;
		}
	}

	private function isValidRead($sql){
		$write = array('insert','update','replace','delete','truncate','drop','alter','create','grant');
		$tmp = strtolower(trim($sql));
		$tmp = explode(' ',$tmp);
		$tmp = $tmp[0];
		$ret = false;
		if(!in_array($tmp,$write)){
			$ret = true;
		}
		if($tmp == 'drop' || $tmp == 'create'){
			self::$tables = array();
		}
		return $ret;
	}

	private function queryList($time,$sql,$flag){
		if(defined('QUERY_LIST')) {
			if(QUERY_LIST){
				self::$query_list .= sprintf("$flag %0.4f %s \n",$time*1000,$sql);
			}
		}
	}
}
?>