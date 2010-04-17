<?php
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

/**
this class should be as portable as possible
--------------
how to use:

CSSParser::run($pathtocssfile);

--------------

 my job is to make css files cacheable based on grabbing the latest mtime of any image files in the css and the css itself
	1. i parse css files for url declarations
		each url declaration is checked to amke sure the file exists
			each refrence to a file that has already been found in the sweep but with a different url is changed to match the first ocurance
			this action will strip query strings from images
		each rel path is converted to path from document root
			../ ./ etc
		each declaration found may be prepended with $prependValue (if $prependValue) as this is used to "opt in" for client side caching via mod_rewrite

	2. while processing images i keep the max mtime and if this is greater than the mtime of the css file i will return it so you may add it to the css url to force redownload
	3. if data uris are supported the images are inlined as part of the css
TODO
	4. the parser should be able to cache the css in memcached/apc but should not provide its own access api
		

*/
class CSSParser{
	private static $root;
	private static $webRoot;
	private static $cssDir;

	public static function run($css){
		$t = microtime(true);
		$ret = false;
		$cssText = self::getCSS($css);
		if($cssText !== false && $cssText !== null){
			$mtime = filemtime($css);
			$images = self::parseURLRefs($cssText);

			//echo "<hr/>processing $css<br/>";

			if($images){
				//echo "&nbsp;&nbsp;&nbsp;&nbsp;found ".count($images)." images<br/>";

				$replaceData = self::getImageData($images,$maxmtime);
				if($maxmtime > $mtime) $mtime = $maxmtime;

				if($replaceData){
					$tmpFile = self::getBuiltCssFileName($css);

					$haveBuiltVersion = file_exists($tmpFile);
					$buildTime = 0;
					if($haveBuiltVersion){
						$buildTime = filemtime($tmpFile);
					}

					//echo "css+max image mtime is ".($mtime<$buildTime?'less than the last build':' BUILD =) ')."<br/>";

					if($mtime > $buildTime || !$haveBuiltVersion){
						$cssText = self::replaceImagePaths($replaceData,$cssText);
						file_put_contents($tmpFile,$cssText);
					} else {
						$csstext = true;
					}
				}

			}

			$ret =  array('css'=>$cssText ,'mtime'=>$mtime);
		}
		//echo sprintf("cssparser time: %0.4f <br/>",(microtime(true)-$t)*1000);
		return $ret;
	}

	//--------------------------------

	private static function getCSS(&$css){
		$css = ltrim($css,'/');
		if($css = realpath($css)){
			self::$root = rtrim($_SERVER['DOCUMENT_ROOT'],'/');
			self::$cssDir = dirname($css);
			self::$webRoot = '/'.trim(str_replace(self::$root,'',self::$cssDir),'/');

			return file_get_contents($css);
		}
		return false;
	}

	private static function parseURLRefs($cssText){
		preg_match_all('/url\((.*)\)/',$cssText,$matches);
		$ret = array();
		if(isset($matches[1])) $ret = array_unique($matches[1]);
		return $ret;
	}

	private static function getImageData($images,&$maxmtime){
		$replace = array();
		$replaceWith = array();
		$maxmtime = 0;
		foreach($images as $path){
			$untrimed = $path;
			$path = self::cleanImagePath($path);
			if($path){
				$path = self::stripQueryString($path);

				$fullPath = self::buildImagePath($path,/*&*/$is_rel);


				if(strpos($path,'http://') === false){
					if(file_exists($fullPath)){
						$mtime = filemtime($fullPath);
						if($mtime > $maxmtime) $maxmtime = $mtime;
	
						//if(isset($out[$fullPath])){
						//	echo "ohNo =( : including an image more than once with a different url! \n\t$fullPath\n\t$orig\n";
						//	$out[$fullPath]['original'][] = $untrimed;
						//} else {
						//	$out[$fullPath] = array('mtime'=>$mtime,'original'=>array($untrimed),'path'=>$path);
						//}

						if($is_rel){
							if(strpos($path,'../') === 0){
								$path = dirname(self::$webRoot).'/'.ltrim($path,'./');
							} else {
								$path = self::$webRoot.'/'.ltrim($path,'./');
							}
						}

						$replace[] = "url($untrimed)";
						
						//-/NOTE PREFIX IS HARDSET HERE!
						//$replaceWith[] = "url(/static$path?$mtime)";
						//echo $fullPath."<br/>";
						$uri = self::dataURI($fullPath);
						if($uri){
							$replaceWith[] = "url($uri)";
						} else {
							$replaceWith[] = "url($path?$mtime)";
						}
						//echo "url(/static$path?$mtime)<br/>";
					}
				}
			}
		}
		if($replace){
			return array('replace'=>$replace,'replace_with'=>$replaceWith);
		}
		return false;
	}

	private static function stripQueryString($path){
		if(strpos($path,'?') !== false){
			$parts = explode('?',$path);
			$path = $parts[0];
		}
		return $path;
	}

	private static function cleanImagePath($path){
		return trim($path,"\r\n\t '\"");
	}

	private static function buildImagePath($path,&$isRelative = true){
		$isRelative = true;
		$fullPath = self::$cssDir."/$path";# /css/../images/lala.jpg
		if($path[0] == '/'){
			$isRelative = false;
			$fullPath = self::$root."/$path";
		}
		return $fullPath;
	}

	private static function replaceImagePaths($replaceData,$cssText){
		return str_replace($replaceData['replace'],$replaceData['replace_with'],$cssText);
	}

	private static function dataURI($image){
		if(!self::mhtmlCompatable()){
			$ext = ltrim(strchr($image,'.'),'.');

			if($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif'){

				$data = base64_encode(file_get_contents($image));
				return 'data:image/'.($ext == 'jpg'?'jpeg':$ext).';base64,'.$data;
			}
		}
		return false;
	}

	private static $mhtml;
	private static function mhtmlCompatable(){
		if(!isset(self::$mhtml)){
			$ua = @$_SERVER['HTTP_USER_AGENT'];
			$mhtml = false;
			$mhtml_compatable = "/MSIE [67]\.0/";
			preg_match($mhtml_compatable,$ua,$matches);
			if($matches){
				$mhtml = true;
			}
			self::$mhtml = $mhtml;
		}
		return self::$mhtml;
	}

	public static function getBuiltCssFileName($cssPath){
		return '/'.trim(sys_get_temp_dir(),'/').'/cssparser-'.(self::mhtmlCompatable()?'':'datauri').'-'.str_replace(array('//','/','\\',' '),'_',$cssPath);
	}
}
