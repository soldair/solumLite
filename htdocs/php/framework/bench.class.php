<?php
/* copyright ryan day 2010 */

class bench{
	private static $marks = array();

	public static function mark($mark,$started_from_microtime = false){
		if($started_from_microtime) $s = $started_from_microtime;
		else $s = microtime(true);

		self::$marks[$mark] = array('start'=>$s);
	}

	public static function end($mark){
		if(isset(self::$marks[$mark])) self::$marks[$mark]['end'] = microtime(true);
	}

	public static function elapsed($mark){
		if(isset(self::$marks[$mark])){
			if(isset(self::$marks[$mark]['end'])) $t = self::$marks[$mark]['end'];
			else $t = microtime(true);

			return $t-self::$marks[$mark]['start'];
		}
		return false;
	}

	public static function elapsed_ms($mark){
		if($t = self::elapsed($mark)){
			return sprintf('%0.5f',$t*1000);
		}
		return false;
	}

	public static function get_marks(){
		return array_keys(self::$marks);
	}
}