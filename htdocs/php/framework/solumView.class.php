<?php
class solumView{
	protected $data = array();
	protected $filePath = '';
	protected $controller = '';
	protected $controllerClass = '';
	public $load;//compat

	//rel from root
	public static $controller_path = 'php/controller';
	public static $view_path = 'html';

	public function __construct($filePath,$data = array()){

		$controller_path = request::readConfig('view_controller_path');
		$view_path = request::readConfig('view_html_path');

		if(!$controller_path) $controller_path = self::$controller_path;
		if(!$view_path) $view_path = self::$view_path;

		$this->data = $data;
		$this->controller = SITE_ROOT.'/'.$controller_path.'/'.$filePath.'.php';
		$this->filePath = SITE_ROOT.'/'.$view_path.'/'.$filePath.'.php';

		$this->controllerClass = trim(str_replace('/','_',$filePath),'_').'_controller';

		$this->load = $this;
		$this->logInstantiate();
	}

	public function view($filePath = false,$data = array(),$pass_vars = false){
		if($filePath === false){
			if($obj = $this->getController()){
				$this->logController($obj);
				$res = $obj->result($this);
				$res = (is_array($res)?$res:array());

				$this->data = array_merge($this->data,$res);
			}

			extract($this->data);

			$this->logBuild();

			if($this->filePath){
				$this->logExecute();
				require $this->filePath;
			}
		} else {
			$view = new solumView($filePath,($data?($pass_vars?array_merge($data,$this->data):$data):$this->data) );
			$view->view();
		}
	}

	public function import($arr){
		$this->data = array_merge($this->data,$arr);
	}

	public function export(){
		return $this->data;
	}

	public function get($key){
		return isset($this->data[$key])?$this->data[$key]:'';
	}

	public function set($key,$val){
		$this->data[$key] = $val;
		return true;
	}
	
	public function __get($key){
		return $this->get($key);
	}

	public function __set($key,$val){
		return $this->set($key,$val);
	}

	public function __isset($key){
		return isset($this->data[$key]);
	}

	public function  __unset($key){
		unset($this->data[$key]);
	}

	protected function getController(){
		if(file_exists($this->controller)){
			require_once $this->controller;
			if(class_exists($this->controllerClass)){
				$obj = new $this->controllerClass($this->data);
				return $obj;
			}
		}
		return false;
	}

	########################
	# DEBUGING AND LOGGING #
	########################

	protected function logController($obj){
		$this->logMsg("<span style='background:#F1C9FF;'>executing controller ".get_class($obj)."</span>");
	}

	protected function logExecute(){
		$this->logMsg("executing view {$this->filePath}");
	}

	protected function logBuild(){
		$out = "</pre><b>building view vars for {$this->filePath}</b><div style='background:#FFC681;padding:5px;'>";
		foreach($this->data as $k=>$v){
			$dump = str_replace(array("\r","\n"),array('\r','\n'),clean(var_export($v,true)));
			if(strlen($dump) > 200){
				$dump = substr($dump,0,200).'...';
			}
			$out .= "$k: $dump<br/>";
		}
		$this->logMsg($out."</div><pre>");
	}

	protected function logInstantiate(){
		$this->logMsg("instantiating view {$this->filePath}");
	}

	public static function logMsg($msg){
		self::$viewLog .= "view log: $msg\n";
	}

	protected static $viewLog = '';
	public static function getViewLog(){
		return "<hr/><pre>".self::$viewLog."</pre>";
	}
}

?>