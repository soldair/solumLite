<?php
require dirname(dirname(dirname(__FILE__))).'/solumConstants.php';
require SITE_ROOT.'/config.php';
$data_path = SITE_ROOT.'/php/data';

require $data_path.'/data.class.php';
require $data_path.'/format.class.php';

$lib_path = $data_path.'/lib';
foreach(scandir($lib_path) as $file){
	if(strlen($file) > 4){
		if(substr($file,(strlen($file)-4)) == '.php'){
			require $lib_path.'/'.$file;
		}
	}
}

require SITE_ROOT.'/php/http/sessioncookie.class.php';

//invalid if hit directly with no session
sessionCookie::init();
if(!sessionCookie::tries()){
	header("HTTP/1.0 404 Not Found");
	exit('invalid request');
}


$width = isset($_GET['width']) ? $_GET['width'] : '100';
$height = isset($_GET['height']) ? $_GET['height'] : '40';

$characters = 6;
new CaptchaSecurityImages($width,$height,$characters);

/*
* Updated by Ryan Day for the solumlite framework and mycypher
* 4-22-2009
*
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

	public function __construct($width='120',$height='40',$characters='6') {
		$code = $this->generateCode($characters);
		// font size will be 75% of the image height
		$font_size = $height * 0.75;
		$image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
		// set the colours
		$background_color = imagecolorallocate($image, 0, 0, 0);
		$text_color = imagecolorallocate($image, 255, 145, 72);
		$noise_color = imagecolorallocate($image, 219, 125, 62);
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

		#################################
		$this->save($code);
		#################################
	}

	private function generateCode($characters) {
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


	private function save($code){
		$session_id = sessionCookie::$session['session_id'];
		$captcha = DBTable::get('captcha');
		$rows = $captcha->loadRowsWhere(array('session_id'=>$session_id));
		if(!count($rows)){
			$new = $captcha->loadNewRow();
			$new->session_id = $session_id;
		} else {
			$new = $rows[0];
		}
		$new->code = $code;
		$new->created = time();
		$new->save();

		$gc = loadBalance(10);
		if($gc >= 8){
			$halfhour = (time()-(60*30));
			$captcha->deleteRowsWhere(array('created'=>array('<',$halfhour)));
		}
	}
}
?>