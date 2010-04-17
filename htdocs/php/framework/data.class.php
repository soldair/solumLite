<?
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
class data{
	private static $dataLog = '';

	public static function get($object,$method,$params = array()){
		$log = $object.' '.(is_array($method)?implode(' , ',$method):$method)."\n\t".json_encode($params)."\n";
		$res = false;

		$class = request::readConfig('data_object_dir').'/'.$object.'.class.php';

		if(file_exists($class)){
			require_once $class;
			$object = 'do'.$object;
			if(class_exists($object)){
				$object = new $object();
				if(is_callable(array($object,$method),false,$callable)){
					$ret = $object->$method($params);
				} else {
					$log .= "\t invalid data object class action\n";
					$ret = self::error('invalid data object class action');
				}
			} else {
				$log .= "\t invalid data object class\n";
				$ret = self::error('invalid data object class');
			}
		} else {
			$log .= "\t unknown data object class\n";
			$ret = self::error('unknown data object class');
		}
		self::$dataLog .= $log;
		return $ret;
	}

	public static function success($data,$msg = ''){
		return self::response(false,$msg,$data);
	}

	public static function error($msg){
		return self::response(true,$msg);
	}

	public static function response($error = false,$msg = '',$data = array()){
		return  array('error'=>$error,'msg'=>$msg,'data'=>$data);
	}

	public static function pageOffset(&$page_size,$page,$total){
		$page_size = ($page_size>30?30:intval($page_size));
		$offset = $page*$page_size;
		return $offset;
	}

	public static function numPages($total,$page_size){
		return ceil($total/$page_size);
	}

	public static function getDataLog(){
		return self::$dataLog;
	}
}

?>