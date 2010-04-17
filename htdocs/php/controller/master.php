<?
class master_controller extends controller{

	public static $base_view_name = 'base';
	public static $default_module = 'site';



	public function result($view){
		$this->loadModuleFromRequest($view);
	}

	public function loadModuleFromRequest($view){
		$module = self::$default_module;
		$base_view = self::$base_view_name;

		$chunk = Request::get(0);
		if($chunk == 'service'){
			$module = $base_view = 'service';
		}

		$view->set('base_view',$module.'/'.$base_view);
	}
} 
