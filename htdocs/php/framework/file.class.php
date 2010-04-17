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
class File{
	############################
	#STATIC OBJECT INSTANTIATION#
	############################

	public static function load($path){
		if(is_file($path)){
			return new File($path);
		}
		return false;
	}

	/** loadRemoteFile(string $url,string $destination[,bool $overwrite_old = false])
	*	VISIBILITY: PUBLIC
	*	USE: downloads a file from url and returns a new file object
	*	
	*/
	public static function loadRemote($url,$destination,$overwrite_old = false){
		$url = strtolower($url);
		if(instr($url,'http://')){
			$file = basename($url);
			if(!self::parsePath(/*&*/$destination,/*&*/$file,$overwrite_old)){
				return false;
			}
			if(basename($url) != $file){
				//now we know the new name is ok but is the download name ok?
				if(is_file($destination.'/'.basename($url)) && !$overwrite_old){
					$tmp = '';
					do{
						$ext = '.tmp'.rand(0,999);
						$tmp = $destination.'/'.basename($url).$ext;

					}while(file_exists($tmp));//ensure unique tmp

					$tmpfile = self::load($destination.'/'.basename($url));
					if(!$tmpfile->mv($tmp)){
						return false;
					}
				}
			}
			if(self::wgetFile($url,$destination)){
				$fileobj =  new File($destination.'/'.basename($url));
				if(!empty($file)){
					$fileobj->mv($destination.'/'.$file);
				}
				if(isset($tmpfile)){
					$tmpfile->mv($destination.'/'.basename($url));
				}
				return $fileobj;
			}
			
		}
		return false;
	}

	/** delete(object{File} &$file);
	*	VISIBILITY: PUBLIC
	*	USE: deletes a file represented by a file object
	*/
	public static function delete(File &$file){
		$path = $file->get('path');
		if(is_file($path)){
			unlink($path);
			if(is_file($path)){
				return false;
			}
			$file = null;
			return true;
		}
		return null;
	}

	public static function upload($destination,$fileext,&$fileObjects,&$error,$limit = 1){
		//$logp = '/tmp/uploadtest.log'; 
		///@n set refrenced vars
		$fileObjects = array();
		$error = array();
		///@n make sure the upload directory is writeable and is a directory
		if(is_writeable($destination)){
			if(!is_file($destination)){
				//file_put_contents($logp,"\n********preparing to itterate files***********\n",FILE_APPEND);
				///@n itterate $_FILES
				foreach($_FILES as $k=>$f){
					$ext = (empty($fileext)?self::getExt($f['name']):$fileext);
					$hashedname = self::uploadHash($destination,$ext);
					//file_put_contents($logp,$hashedname."\n",FILE_APPEND);
					if(move_uploaded_file($f['tmp_name'],$hashedname)){
						$fileObjects[] = new File($hashedname);
					}else{
						$error[$k]=$f['tmp_name'];
					}
					///@n use numerical keys as break trigger
					if($k+1 == $limit){
						break;
					}
				}
			} else {
				//file_put_contents($logp,"\n********IS A REGULAR FILE***********\n",FILE_APPEND);
			}
		}else{
			//file_put_contents($logp,"\n********not writeable ".`stat $destination`."**********\n",FILE_APPEND);
		}
		//file_put_contents($logp,"\n*******************\n",FILE_APPEND);
		///@return <bool>
		if(!empty($error) || empty($fileObjects)){
			//file_put_contents($logp,"i have ERRORS:\n".var_export($error,true)."\n or is this empty>>>>>\n".var_export($fileObjects,true)."\nFILES:\n".var_export($_FILES,true)."\ndestination:\n".var_export($destination,true),FILE_APPEND);
			return false;
		}
		return true;
	}
	#########
	#UTILITY#
	#########
	/**
	*	@return <string> returns empty if no '.' found else everything after the last .
	*/
	public static function getExt($filename){
		if(instr($filename,'.')){
			$pos = strrpos($filename,'.');//last .
			$len = strlen($filename);
			if($pos != $len && $pos != 0){
				return substr($filename,$pos+1,$len);
			}
		}
		return '';
	}

	public static function uploadHash($destination,$ext = ''){
		///@n clean destination
		$destination = ($destination != '/'?rtrim($destination,'/'):$destination);
		///@n set empty path
		$path = '';
		///@n make sure i am checking in a real directory for pre-existing names
		if(file_exists($destination) && !is_file($destination)){
			///@n start hash loop
			do{
				$file = rand(1,8000).'-'.microtime(true);
				$file = str_replace('.','',$file);
				$file = self::base_convert2($file,16,64).(empty($ext)?'':'.'.$ext);
				$path = $destination.'/'.$file;
			}while(file_exists($path));
		}
		return (empty($path)?false:$path);
	}
	################
	#PRIVATE STATIC#
	################

	private static function parsePath(&$destination,&$file = '',$overwrite_old = false){
		$mv2 = false;
		if(is_file($destination.'/'.$file) && !empty($file)){//destination is a directory but the file exists
			if(!$overwrite_old || !is_writeable($destination.'/'.$file)){
				return false;
			}
		}elseif(is_file($destination)){//destination has a new name and the file already exists
			$mv2 = basename($destination);
			$destination = dirname($destination);
			if(!$overwrite_old || !is_writeable($destination)){
				return false;
			}
		}elseif(file_exists($destination)){//located dir
			if(!is_writeable($destination)){
				return false;
			}
		}elseif(file_exists(dirname($destination))){//found destination dir with new filename
			$mv2 = basename($destination);
			$destination = dirname($destination);
			if(!is_writeable($destination)){
				return false;
			}
		}else{//i have a path to a directory that doesnt exist
			return false;
		}
		if(!empty($mv2)){
			$file = $mv2;
		}
		return true;
	}

	public function get($prop){
		return (isset($this->_data[$prop])?$this->_data[$prop]:null);
	}
	/** wgetFile(string $url,string $destination)
	*	VISIBILITY: PRIVATE
	*	USE: downloads a file from url and places it at location
	*	@param 	string the url for the file including 'http://'
	*	@param 	string the web writeable folder in which the file will be placed
	*	@return <string> file path on successful download or <false> on fail
	*
	*/
	private static function wgetFile($url,$destination){
		$file = basename($url);
		if(!empty($file) && is_writeable($destination)){
			exec("wget -nH -nd -P$destination $url",$bla);
			if(file_exists("$destination/$file")){
				return true;
			}
		}
		return false;
	}

	private static function error($method,$msg){
		throw new Exception('File::'.$method.' ERROR - '.$msg);
	}
	################
	#OBJECT METHODS#
	################
	protected function __construct($path_and_filename){
		$this->_data['path'] = $path_and_filename;
		$this->_data['file'] = basename($path_and_filename);
	}

	public function cmd($executable,$args = array()){
		$args = $this->formatArgs($args);
		if($args !== false){
			exec($executable.$args,$out);
			return $out;
		}
		return false;
	}

	public function rm($destination){
		if($this->_data['path'] != $destination){
			if(is_file($destination) && is_writeable($destination)){
				$this->cmd('rm',array('-f',$destination));
				if(!file_exists($destination)){
					return true;
				}
			}
		}
		return false;
	}

	public function cp($destination,$ow = false){
		if($this->_data['path'] != $destination){
			if(self::parsePath($destination,$file,$ow)){
				$destination .= (empty($file)?'/'.$this->_data['file']:"/$file");
				$this->cmd('cp',array('-f',$this->_data['path'],$destination));
				return File::load($destination);
			}
		}
		return false;
	}

	public function mv($destination,$ow = false){//allow overwrite?
		if(self::parsePath($destination,$file,$ow)){
			$exe = 'mv';
			$args = array();
			if($ow){
				$args[] = '-f';
			}
			$args[] = $this->_data['path'];
			if(!empty($file)){
				$destination = $destination.'/'.$file;
			}
			$args[] = $destination;
			self::cmd($exe,$args);
			if(file_exists($destination)){
				$this->_data['path'] = $destination;
				$this->_data['file'] = basename($destination);
				return true;
			}
		}
		return false;
	}
	/** __call($nm,$args)
	*	VISIBILITY: PUBLIC
	*	USE: to execute file management functions where method not provided
	*	@param string the name of the program to exec
	*	@param array an array of args
	*	@return <false> on recursive rm or <array> the exec output of an execution
	*/
	public function __call($nm,$args){
		if($nm == 'rm'){
			if(in_array('-R',$args) || in_array('-r',$args) || in_array('--recursive',$args)){
				self::error('__call','cannot use recurisive rm');
				return false;
			}
		}
		if($nm == 'rmdir'){
			self::error('__call','cannot be used to remove directories');
			return false;
		}
		return $this->cmd($nm,$args);
	}

	/** __get()
	*	VISIBILITY: PUBLIC
	*	USE: get keys from the private data array like properties
	*	@param string the key in the data array
	*	@return returns data if isset in private data array else empty string
	*/
	public function __get($prop){
		$ret = $this->get($prop);
		return ($ret === null?'':$ret);
	}

	public function __set($prop,$val){
		$this->_data[$prop] = $val;
		return true;
	}
	
	/** export()
	*	VISIBILITY: PUBLIC
	*	USE: returns the private data array
	*	@param NONE
	*	@return the private data array
	*/
	public function export(){
		return $this->_data;
	}

	private static $identified = array();
	private static $idFailed = array();
	public function isImage(&$dimensions = ''){
		if(!isset(self::$identified[$this->get('path')])){
			$res = $this->cmd('identify',array('-ping',$this->get('path')));
			self::$identified[$this->get('path')] = array('width'=>0,'height'=>0);
			$poass = false;
			if(!empty($res)){
				$res = str_replace($this->get('path').' ','',$res);
				$parts = explode(' ',$res[0]);
				foreach($parts as $p){
					$dparts = explode('x',$p);//200x100
					if(count($dparts) == 2){
						if(instr($dparts[1],'+')){
							$again = explode('+',$dparts[1]);//100+0+0
							$dparts[1] = $again[0];
						}
						if(ctype_digit($dparts[0]) && ctype_digit($dparts[1])){
							$w = intval($dparts[0]);
							$h = intval($dparts[1]);
							self::$identified[$this->get('path')]['width'] = $w;
							self::$identified[$this->get('path')]['height'] = $h;
							//protection from oddly tall image
							if(($w*3)<$h){
								self::$idFailed[] = $this->get('path');
							}
							$pass = true;
							break;
						}
					}
				}
			}
			if(!$pass){
				self::$idFailed[] = $this->get('path');
			}
		}

		if(isset(self::$identified[$this->get('path')])){
			$dimensions = self::$identified[$this->get('path')];
		}

		$result =  (in_array($this->get('path'),self::$idFailed)?false:true);
		if(!$result){
			//exit(json_encode(var_export($this->get('path'),true)));
		}
		return $result;
	}

	public function mogrify($width=0,$quality = 100){
		if($this->isImage($dimensions)){
			$command = 'nice -n 19 mogrify';
			$args = array();
			if($width > 0 && $dimensions['width'] != $width){
				$args[] = '-resize';
				$args[] = $width.'x';
			}
			$args[] = '-quality';
			$args[] = $quality;
			$args[] = $this->get('path');

			$this->cmd($command,$args);
			return true;
		}
		return false;
	}

	/** formatArgs(array $args)
	*	VISIBILITY: PRIVATE
	*	USE: concatenates and escapes arguments for exec etc.
	*	@param array the args you would like concatenated
	*	@return <string> the arguments or <false> if one of the arguments is not string/int/float
	*/
	private function formatArgs($args){
		$out = '';
		foreach($args as $k=>$v){
			if(!empty($v)){
				if(is_array($v) || is_object($v) || is_bool($v)){
					self::error('formatArgs','only string/int/float arguments accepted');
					return false;
				}
				$out .= ' '.escapeshellarg($v);
			}
		}
		return $out;
	}

	private $_data = array();
	//this hash function is a holdout from ages past
	public static function base_convert2($numstring, $frombase, $tobase) {
		$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
		$tostring = substr($chars, 0, $tobase);
	
		$length = strlen($numstring);
		$result = '';
		for($i = 0; $i < $length; $i++) {
			$number[$i] = strpos($chars, $numstring{$i});
		}
	
		do {
			$divide = 0;
			$newlen = 0;
			for($i = 0; $i < $length; $i++) {
				$divide = $divide * $frombase + $number[$i];
				if($divide >= $tobase) {
					$number[$newlen++] = (int)($divide / $tobase);
					$divide = $divide % $tobase;
				} elseif($newlen > 0) {
					$number[$newlen++] = 0;
				}
			}
	
			$length = $newlen;
			$result = $tostring{$divide}. $result;
		}
		while($newlen != 0);
		return $result;
	}
}
?>