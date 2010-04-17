<?php
class site_base_controller extends controller{

	const MODE_INTERNAL_PAGE = 1;

	private $pages_path = 'site/pages';
	private $panels_path = 'site/panels';

	private $view;

	public function result($view){

		$this->view = $view;


		//default request to page routing
		$mode = self::MODE_INTERNAL_PAGE;
		$first = Request::get(0);
		if(!$first){
			$first = 'home';
		}

		//handle non existant pages
		switch($mode){
			case self::MODE_INTERNAL_PAGE:
				if($page = $this->getPage($first)){
					$view->page = $page;
					break;
				}
			default:
				$view->page = $this->pages_path."/404";
				$view->missing = $first;
				break;
		}

		//set site name for page titles
		$site_name = request::readConfig('site_name');
		if(!$site_name){
			$url = parse_url(SITE_ROOT_URL);
			$site_name = $url['host'];
			$parts = explode('.',$site_name);
			if(count($parts) > 2){
				unset($parts[0]);
				$site_name = implode('.',$parts);
			}
		}

		$view->site_name = $site_name;

		$view->js_includes = array();
		$view->css_includes = array();

		includeJS($view,'/js/jquery-1.4.2.min.js');
		includeJS($view,'/js/jquery.extensions.js');
		includeJS($view,'/js/swfobject.js');

		includeCSS($view,'/css/reset.css');
		includeCSS($view,'/css/base.css');

		$view->page = $this->loadViewHTML($view->page);

		$view->header = $this->panels_path.'/header';

		$view->footer = $this->panels_path.'/footer';

		$view->popups = $this->panels_path.'/popups';

		//---------------
		//---------------

		$ua = get($_SERVER,'HTTP_USER_AGENT');
		$ie6 = strpos($ua,'MSIE 6.0') !== false;
		$ie7 = strpos($ua,'MSIE 7.0') !== false;
		$ie8 = strpos($ua,'MSIE 8.0') !== false;
		$ie9 = strpos($ua,'MSIE 9.0') !== false;

		$opera = false;
		if(strpos($ua,'Opera')  !== false ){
			$parts = explode($ua,'Presto/2.');
			//presto 2.3 and later are rumored to have border-radius
			$opera = true;
			if(isset($parts[1]) && intval($parts[1][0]) > 2){
				$opera = false;
			}
		}

		$konqueror = false;
		if(strpos($ua,'Konqueror')!== false){
			$parts = explode($ua,'KHTML/4.');

			$konqueror = true;
			if(isset($parts[1]) && intval($parts[1][0]) > 2){
				$konqueror = false;
			}
		}

		$ie = $ie6 || $ie7 || $ie8;

		//---------------
		//---------------

		if($ie6){
			//includeJS($view,'/js/ie.js');
			//includeCSS($view,'/css/ie6.css');
		}

		if($ie || $opera || $konqueror){
			//includeCSS($view,'/css/corners.css');
		}

		//---------------
		$this->cssIncludes();
		$this->jsIncludes();
	}

	public function cssIncludes(){
		$view = $this->view;
		$includes = '';
		if(isset($view->css_includes)){
			foreach($view->css_includes as $url){
				if(is_array($url)){
					if(!isset($url['rel'])){
						$url['rel'] = 'stylesheet';
					}
					if(isset($url['href'])){

						//$url['href'] = appendMTime($url['href']);

						$ret = CSSParser::run($url['href']);
						if($ret && isset($ret['mtime'])){
							$file = basename($url['href']);
							$dir = dirname($url['href']);

							$url['href'] = $dir.'/'.$ret['mtime'].'-'.$file;
						}

						$includes .= '<link '.formatParams($url).' />';
					}
				} else if($url){
					//$url = appendMTime($url);
					$ret = CSSParser::run($url);
					if($ret && isset($ret['mtime'])){

						$file = basename($url);
						$dir = dirname($url);
						$url = $dir.'/'.$ret['mtime'].'-'.$file;

					}
					$includes .= '<link rel="stylesheet" href="'.$url.'" />';
				}
			}
		}
		$view->css_includes = $includes;
		return $includes;
	}

	protected function jsIncludes(){
		$view = $this->view;
		$includes = '';
		if(isset($view->js_includes)){
			foreach($view->js_includes as $url){
				if(is_array($url)){
					if(isset($url['src'])){
						$url['src'] = appendMTime($url['src']);
						$includes .= '<script '.formatParams($url).' ></script>';
					}
				} else {
					if($url){
						$url = appendMTime($url);
						$includes .= '<script type="text/javascript" src="'.$url.'" ></script>';
					}
				}
			}
		}
		$view->js_includes = $includes;
		return $includes;
	}

	protected function loadViewHTML($view,$vars = array()){

		$vars = array_merge($this->view->export(),$vars);

		ob_start();
		$view = new solumView($view,$vars);
		$view->view();
		$html = ob_get_contents();
		ob_end_clean();

		//passvar from page tpl to the base view
		$this->view->import($view->export());

		return $html;
	}

	protected function getPage($name){
		$path = $this->pages_path;
		if(file_exists(SITE_ROOT."/".solumView::$view_path."/$path/$name.php")){
			return "$path/$name";
		}
		return false;
	}
}

//convience methods to make up for not designing a simpler front controller to enhance the use of the modular/nested controller pattern

function includePopup($name){
	$class = 'site_panels_popups_controller';
	if(!class_exists($class)){
		require_once(dirname(__FILE__).'/panels/popups.php');
	}
	if(class_exists($class)){
		site_panels_popups_controller::$popups[] = site_panels_popups_controller::$dir.$name;
	}
}

function includeCSS($view,$file){
	$view->css_includes = array_merge($view->css_includes,array($file));
}

function includeJS($view,$file){
	$view->js_includes = array_merge($view->js_includes,array($file));
}
