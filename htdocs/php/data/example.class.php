<?
/*
copyright ryan day 2010

call this function in a controller like this

$ret = Data::get('example','create',array('data'=>'i am data'));
if($ret['error']){
	echo $ret['error'];
} else {
	$data_i_just_saved = $ret['data']['data'];
	echo 'success';
}
*/
class doExample{
	private static $example_file = '/tmp/example.txt';

	public function create($args){
		//load data into the currnt scope - $data is always extracted into the current scope so no need to worry about undefined
		extract(extractable(array('data'),$args));
		//for a single value you would probably realistically use
		//$data = get($args,'data');

		if($data){
			file_put_contents(self::$example_file,$data);
			return data::success(array('file'=>self::$example_file,'data'=>$data));
		}
		return data::error("data required");
	}

	public function read(){
		if(file_exists())
		return data::success(file_get_contents(self::$example_file));
	}

	public function delete(){
		if(file_exists(self::$example_file)){
			unlink(self::$example_file);
		}
		return data::success(array('file'=>false));
	}
}
?> 
