<?php
class site_panels_popups_controller extends controller{
	public static $popups = array();
	public static $dir = '/modules/refresh/popups/';
	public function result($view){
		$view->popups = self::$popups;
	}


}