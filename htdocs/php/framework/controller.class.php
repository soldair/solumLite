<?
class controller{
	protected $data;
	public function __construct($data){
		$this->data = $data;
		//do all init and data population here
	}

	public function result($view = null){
		return $this->data;
	}
}
?>