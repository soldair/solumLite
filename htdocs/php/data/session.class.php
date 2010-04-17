<?
/**************************************************************************\
* Written by:  Ryan Day <soldair@ryanday.org>                              *
* Copyright 2006-2010 Ryan Day                                             *
* http://ryanday.org
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/
class doSession{
	/**
	* PURPOSE: lookup an existing session or create a new one
	* $args
	* 	sid = session_key
	*/
	public function get($args){
		extract(extractable(array('key','sid','remember','timezone'),$args));

		if(isset($sid)){
			if(!$ret = $this->findSession($key,$sid)){
				if(!$ret){
					$ret = $this->makeNewSession($timezone);
				}
			}
			if(!$ret){
				$ret = data::error('error saving session');
			} else {
				$ret = data::success($ret);
			}
		} else {
			$ret = data::success($this->makeNewSession());
		}

		$this->gc();
		return $ret;
	}

	public function login($args){
		extract(extractable(array('users_id','key','sid','remember'),$args));
		$users_id = intval($users_id);
		if($users_id){
			if($session = DBTable::get('session')->loadRowsWhere(array('key'=>$key))){
				$session = $session[0];
				$session->logged_in = 1;
				$session->last_request = time();
				$session->users_id = $users_id;
				$session->timedout = 0;

				$session->save();
				
				return data::success($session->export());
			}
			return data::error('unable to locate session');
		}
		return data::error('user_id required');
	}

	public function logout($args){
		extract(extractable(array('users_id','key'),$args));
		$users_id = intval($users_id);
		if($users_id){
			if($session = DBTable::get('session')->loadRowsWhere(array('key'=>$key))){
				$session = $session[0];
				$session->logged_in = 0;
				$session->save();
				
				return data::success($session->export());
			}
			return data::error('unable to locate session');
		}
		return data::error('user_id required');
	}

	#########
	#PRIVATE#
	#########

	private function gc(){
		$bin = loadBalance($bins = 50);
		switch($bin){
			case 1:
				//delete regular sessions inactive for just a little over an hour
				$where = array('last_request'=>array('<',time()-40000),'remember'=>0);
				break;
			case 2:
				//delete remembered/regular sessions inactive for one month
				$where = array('last_request'=>array('<',time()-2592000));
				break;
			default:
				if($bin >=40){//timeout inactive not remembered sessions
					dbTable::get('session')->updateRowsWhere(
							array('timedout'=>1,'logged_in'=>0),
							array('last_request'=>array('<',time()-20000),'remember'=>0)
							);
				}
		}
		if(isset($where)){
			dbTable::get('session')->deleteRowsWhere($where);
		}
	}

	private function makeNewSession($timezone = 0){
		$ret = false;
		$row = dbTable::get('session')->loadNewRow();
		$row->created = time();
		$row->last_request = time();
		$row->users_id = 0;
		$row->key = self::makeKey();
		$row->timedout = 0;
		$row->logged_in = 0;
		$row->timezone = intval($timezone);
		$row->save();
		if($row->session_id){
			$ret = $row->export();
		}
		return $ret;
	}

	private function findSession($key,&$sid){
		//$args = func_get_args();

		if($key){
			$ret = dbTable::get('session')->loadRow($key);
			if($ret){
				if($ret->key != $sid){
					$ret = false;
				}
			}
		} else {
			$ret = dbTable::get('session')->loadRowsWhere(array('key'=>$sid));
			if($ret){
				$ret = $ret[0];
			}
		}
		if($ret){
			if($ret->timedout == 1){
				dbTable::get('session')->delete($ret);
				$ret = false;
			}
			if($ret){
				//update last request
				if($ret->last_request < (time()-(5*60))){
					$ret->last_request = time();
					$ret->save();
				}
			}
		}
		$ret = ($ret?$ret->export():false);
		return $ret;
	}

	private function makeKey(){
		$key = '';
		do{
			$key = sha1(microtime(true).'ChuckNorris');
		} while(db::q1v("select count(*) from session where `key`='$key'") > 0);
		return $key;
	}

	public function __destruct(){
		if(isset($this->test)){
			echo "<hr/>";
			var_dump($this->test);
		}
	}
}
?>