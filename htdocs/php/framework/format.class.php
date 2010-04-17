<?
/**************************************************************************\
* Written by:  Ryan Day <soldair@ryanday.org>                              *
* Copyright 2006-2010 Ryan Day                                             *
* http://ryanday.org
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU Lesser General Public License as published   *
*  by the Free Software Foundation; either version 2 of the License, or    *
*  (at your option) any later version.                                     *
\**************************************************************************/
class format{

	public static function toArray($result){
		return $result;
	}

	public static function toJSON($result){
		return json_encode($result);
	}

	public static function toXML ($result) {
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
		$xml .= self::recurseXML($result);
		return $xml;
	}

	public static function toSerialized($result){
		return serialize($result);
	}

	#########
	#PRIVATE#
	#########

	private static function recurseXML ($result, $level = 0) {
		$xml = "";
		$tabs = "\t";
		for ($i = 0; $i < $level; $i++) {
			$tabs .= "\t";
		}
		foreach ($result as $key => $value) {
			if (!is_array($value)) {
				$xml .= $tabs . "<{$key}>{$value}</{$key}>\n";
			} else {
				$xml .= $tabs . "<{$key}>\r\n";
				$xml .= self::recurseXML($value, $level + 1);
				$xml .= $tabs . "</{$key}>\r\n";
			}
		}
		if($level == 0){
			return "<document>\r\n".$xml."</document>";
		} else {
			return $xml;
		}
	}
}
?>