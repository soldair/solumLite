<?php
require dirname(dirname(__FILE__)).'/solumConstants.php';
$config_path = $root.'/config.php';
require "../class.CoreLoader.php";
CoreLoader::init($config_path);
CoreLoader::load('Session_manager');
Session_Manager::init();
//invalid if hit directly with no session
if(Session_Manager::isNewSession()){
	header("HTTP/1.0 404 Not Found");
	exit('invalid request');
}
/*
* File: CaptchaSecurityImages.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 03/08/06
* Updated: 07/02/07
* Requirements: PHP 4/5 with GD and FreeType libraries
* Link: http://www.white-hat-web-design.co.uk/articles/php-captcha.php
* 
* This program is free software; you can redistribute it and/or 
* modify it under the terms of the GNU General Public License 
* as published by the Free Software Foundation; either version 2 
* of the License, or (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful, 
* but WITHOUT ANY WARRANTY; without even the implied warranty of 
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
* GNU General Public License for more details: 
* http://www.gnu.org/licenses/gpl.html
*
*/

class CaptchaSecurityImages {

	public $font = 'monofont.ttf';

	public function generateCode($characters) {
		/* list all possible characters, similar looking characters and vowels have been removed */
		$possible = '23456789bcdfghjkmnpqrstvwxyz';
		$code = '';
		$i = 0;
		while ($i < $characters) { 
			$code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
			$i++;
		}
		return $code;
	}

	public function __construct($width='120',$height='40',$characters='6') {

		$code = $this->generateCode($characters);
		// font size will be 75% of the image height
		$font_size = $height * 0.75;
		$image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
		// set the colours
		$background_color = imagecolorallocate($image, 255, 255, 255);
		$text_color = imagecolorallocate($image, 228, 130, 64);
		$noise_color = imagecolorallocate($image, 247, 141, 70);
		// generate random dots in background
		for( $i=0; $i<($width*$height)/3; $i++ ) {
			imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
		}
		// generate random lines in background 
		for( $i=0; $i<($width*$height)/150; $i++ ) {
			imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
		}
		// create textbox and add text
		$textbox = imagettfbbox($font_size, 0, $this->font, $code) or die('Error in imagettfbbox function');
		$x = ($width - $textbox[4])/2;
		$y = ($height - $textbox[5])/2;
		imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font , $code) or die('Error in imagettftext function');
		// output captcha image to browser
		header('Content-Type: image/jpeg');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		imagejpeg($image);
		imagedestroy($image);

		CoreLoader::load('Captcha');
		$session_id = Session_manager::getSession()->id;
		$table = new Captcha;
		$rows = $table->loadRowsWhere(array('ip'=>$_SERVER['REMOTE_ADDR'],'session_id'=>$session_id));
		if(isset($rows[0])){
			$row = $rows[0];
		}else{
			$row = $table->loadRow();
		}
		$row->created = time();
		$row->ip = $_SERVER['REMOTE_ADDR'];
		$row->session_id = $session_id;
		$row->code = $code;
		$res = $row->save();
		if(is_array($res)){
			trigger_error(var_export($res,true));
		}
	}

}

$width = isset($_GET['width']) ? $_GET['width'] : '100';
$height = isset($_GET['height']) ? $_GET['height'] : '40';
//$characters = isset($_GET['characters']) && $_GET['characters'] > 1 ? $_GET['characters'] : '6';
$characters = 6;
$captcha = new CaptchaSecurityImages($width,$height,$characters);

?>