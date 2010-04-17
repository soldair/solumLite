<?php
class site_pages_404_controller extends controller{
	public function result($view){
		header($_SERVER['SERVER_PROTOCOL']." 404 NOT FOUND");
	}
}