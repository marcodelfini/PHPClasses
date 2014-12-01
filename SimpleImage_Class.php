<?PHP
/**
 *	SimpleImage Class | PHP 5
 *
 *	Copyright (c) 2012
 *	Marco Delfini <info@marcodelfini.com>
 *	http://marcodelfini.com
 *
 *	This library is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU Lesser General Public
 *	License as published by the Free Software Foundation; either
 *	version 2.1 of the License, or (at your option) any later version.
 *
 *	This library is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *	Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public
 *	License along with this library; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 *  http://www.gnu.org/copyleft/lesser.html
 *
 *  
 *  EXAMPLE:
 *  
 *  $image = new SimpleImage();
 *  $image->load("images/avatars/avatar.png");
 *  					
 *  if($image->GetLarghezza() > 180 || $image->GetAltezza() > 250 || ($image->GetLarghezza() > 180 && $image->GetAltezza() > 250))
 *  	$image->RidimensionaInScala(180, 250, "fissa");
 *  							
 *  $image->Save("images/avatars/avatar.png");
 *  
**/

class SimpleImage {

	var $image;
	var $image_type;
	var $filename;
	
	function Load($filename){		
		$this->filename = $filename;
		$this->GetTipo();
		if($this->image_type == IMAGETYPE_JPEG){
			$this->image = imagecreatefromjpeg($this->filename);
			imagesavealpha($this->image, true);
		}elseif($this->image_type == IMAGETYPE_GIF){
			$this->image = imagecreatefromgif($this->filename);
			imagesavealpha($this->image, true);
		}elseif($this->image_type == IMAGETYPE_PNG){
			$this->image = imagecreatefrompng($this->filename);
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		}
	}
	
	function GetTipo(){
		$image_info = getimagesize($this->filename);
		$this->image_type = $image_info[2];
	}
	   
	function Save($filename, $compression = 75, $permissions = 0644){
		$this->filename = $filename;
		$this->GetTipo();
		if($this->image_type == IMAGETYPE_JPEG){
			imagejpeg($this->image, $this->filename, $compression);
		}else if($this->image_type == IMAGETYPE_GIF){
			imagegif($this->image, $this->filename);
		}else if($this->image_type == IMAGETYPE_PNG){
			imagepng($this->image, $this->filename);
		}
		chmod($this->filename, $permissions);
	}
	   
	function Permissions($permissions = 0644){
		chmod($this->filename, $permissions);
	}
	   
	function OutPut(){
		$this->GetTipo();
		if($this->image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image);
		}else if($this->image_type == IMAGETYPE_GIF){
			imagegif($this->image);
		}else if($this->image_type == IMAGETYPE_PNG){
			imagepng($this->image);
		}
	}
	   
	function VerificaSicurezza(){
		$txt = file_get_contents($this->filename);
		$image_safe = true;
		if(preg_match('#&(quot|lt|gt|nbsp|<?php);#i', $txt)){ $image_safe = false; }
		else if(preg_match("#&\#x([0-9a-f]+);#i", $txt)){ $image_safe = false; }
		else if(preg_match('#&\#([0-9]+);#i', $txt)){ $image_safe = false; }
		else if(preg_match("#([a-z]*)=([\`\'\"]*)script:#iU", $txt)){ $image_safe = false; }
		else if(preg_match("#([a-z]*)=([\`\'\"]*)javascript:#iU", $txt)){ $image_safe = false; }
		else if(preg_match("#([a-z]*)=([\'\"]*)vbscript:#iU", $txt)){ $image_safe = false; }
		else if(preg_match("#(<[^>]+)style=([\`\'\"]*).*expression\([^>]*>#iU", $txt)){ $image_safe = false; }
		else if(preg_match("#(<[^>]+)style=([\`\'\"]*).*behaviour\([^>]*>#iU", $txt)){ $image_safe = false; }
		else if(preg_match("#</*(applet|link|style|script|iframe|frame|frameset)[^>]*>#i", $txt)){ $image_safe = false; }
		return $image_safe;
	}
	
	function GetLarghezza(){
		return imagesx($this->image);
	}
	
	function GetAltezza(){
		return imagesy($this->image);
	}
	
	function RidimensionaPerAltezza($altezza){
		$ratio = $altezza / $this->GetAltezza();
		$larghezza = $this->GetLarghezza() * $ratio;
		$this->Ridimensiona($larghezza, $altezza);
	}
	
	function RidimensionaPerLarghezza($larghezza){
		$ratio = $larghezza / $this->GetLarghezza();
		$altezza = $this->GetAltezza() * $ratio;
		$this->Ridimensiona($larghezza, $altezza);
	}
	
	function Scala($scale){
		$larghezza = $this->GetLarghezza()*$scale/100;
		$altezza = $this->GetAltezza()*$scale/100;
		$this->Ridimensiona($larghezza, $altezza);
	}
	
	function Ridimensiona($larghezza, $altezza, $in_alto = 0, $a_sinistra = 0){
		$new_image = imagecreatetruecolor($larghezza, $altezza);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefilledrectangle($new_image, 0, 0, $larghezza, $altezza, $transparent);
		imagecopyresampled($new_image, $this->image, 0, 0, $a_sinistra, $in_alto, $larghezza, $altezza, $this->GetLarghezza(), $this->GetAltezza());
		$this->image = $new_image;
	}
	
	function RidimensionaInScala($new_l, $new_h, $tipo = "fissa"){
		/*
		fissa: con stesso coefficente sulle due dimensioni, quello minore
		dinamica: con coefficente diverso sulle due dimensioni
		*/
		$old_l = $this->GetLarghezza();
		$old_h = $this->GetAltezza();
		$in_alto = 0;
		$a_sinistra = 0;
		
		if($tipo == "fissa"){
			$scale = min($new_l/$old_l, $new_h/$old_h);
			$thumb_l = floor($scale*$old_l);
			$thumb_h = floor($scale*$old_h);
		}else{
			if($old_l <= $old_h){ //portrait
				$lamda = $new_l/$old_l;
				if ($lamda < 1) {
					$thumb_l = (int)round($lamda*$old_l);
					$thumb_h = (int)round($lamda*$old_h);
					$in_alto = (int)round(($thumb_h-$new_h)/2);
					$a_sinistra = 0;
				}
			}else{ //landscape
				$lamda = $new_h/$old_h;
				if($lamda < 1){
					$thumb_l = (int)round($lamda*$old_l);
					$thumb_h = (int)round($lamda*$old_h);
					$a_sinistra = (int)round(($thumb_l-$new_l)/2);
					$in_alto = 0;
				}
			}
		}
		
		//echo "DEBUG: thumb: ".$thumb_l."x".$thumb_h." new_max: ".$new_l."x".$new_h." old: ".$old_l."x".$old_h."\n";
		$this->Ridimensiona($thumb_l, $thumb_h, $in_alto, $a_sinistra);
	}	
}
	
?>