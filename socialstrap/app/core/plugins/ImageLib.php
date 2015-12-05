<?php

/**
 * Image manipulation methods
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_ImageLib
{

	/**
	 * is valid image
	 */
	public static function isValidImage($filename)
	{
		if (! function_exists('exif_imagetype')){
			
			if ((list($width, $height, $type, $attr) = getimagesize($filename)) !== false ) {
				return true;
			}
			
			return false;
		}
		
		$imgtype = exif_imagetype($filename);
		
		if ($imgtype == IMAGETYPE_GIF || $imgtype == IMAGETYPE_JPEG || $imgtype == IMAGETYPE_PNG) {
			return true;
		}
	
		return false;
	}
	
	
	/**
	 * crop image
	 */
	public static function crop($filename, $x, $y, $w, $h, $targ_w, $targ_h)
	{
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		switch ($extension) {
			case 'gif':
				
				$img_r = imagecreatefromgif($filename);
				$dst_r = ImageCreateTrueColor($targ_w, $targ_h);
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);
				imagegif($dst_r, $filename);
				break;
			
			case 'jpg':
			case 'jpeg':
				$img_r = imagecreatefromjpeg($filename);
				$dst_r = ImageCreateTrueColor($targ_w, $targ_h);
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);
				imagejpeg($dst_r, $filename, 75);
				break;
			
			case 'png':
				$img_r = imagecreatefrompng($filename);
				$dst_r = ImageCreateTrueColor($targ_w, $targ_h);
				imagealphablending($dst_r, false);
				imagesavealpha($dst_r, true);
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);
				imagepng($dst_r, $filename, 9);
				break;
			
			default:
				return false;
		}
		
		return true;
	}

	/**
	 * rotate image
	 */
	public static function rotate($filename)
	{
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		switch ($extension) {
			case 'gif':
				
				$img_r = imagecreatefromgif($filename);
				$dst_r = imagerotate($img_r, - 90, 0);
				imagegif($dst_r, $filename);
				break;
			
			case 'jpg':
			case 'jpeg':
				$img_r = imagecreatefromjpeg($filename);
				$dst_r = imagerotate($img_r, - 90, 0);
				imagejpeg($dst_r, $filename, 75);
				break;
			
			case 'png':
				$img_r = imagecreatefrompng($filename);
				$dst_r = imagerotate($img_r, - 90, 0);
				imagealphablending($dst_r, false);
				imagesavealpha($dst_r, true);
				imagepng($dst_r, $filename, 9);
				break;
			
			default:
				return false;
		}
		
		return true;
	}

	/**
	 * resample image with max width and height (create a thumbnail)
	 */
	public static function resample($source_filename, $dest_filename, $width = null, $height = null, $maintain_ratio = true)
	{
		if (! $width && ! $height) {
			$width = Zend_Registry::get('config')->get('resample_maxwidth');
			$height = Zend_Registry::get('config')->get('resample_maxheight');
		}
		
		// Get new dimensions
		list ($width_orig, $height_orig) = getimagesize($source_filename);
		
		// Use the same image if image is smaller
		if ($maintain_ratio && $width_orig <= $width && $height_orig <= $height) {
			copy($source_filename, $dest_filename);
			return true;
		}
		
		if ($maintain_ratio) {
			$ratio_orig = $width_orig / $height_orig;
			
			if ($width / $height > $ratio_orig) {
				$width = $height * $ratio_orig;
			} else {
				$height = $width / $ratio_orig;
			}
		}
		
		$extension = strtolower(pathinfo($source_filename, PATHINFO_EXTENSION));
		
		switch ($extension) {
			case 'gif':
				$image_p = imagecreatetruecolor($width, $height);
				$image = imagecreatefromgif($source_filename);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				imagegif($image_p, $dest_filename);
				
				break;
			
			case 'jpg':
			case 'jpeg':
				$image_p = imagecreatetruecolor($width, $height);
				$image = imagecreatefromjpeg($source_filename);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				
				imagejpeg($image_p, $dest_filename, 75);
				break;
			
			case 'png':
				$image_p = imagecreatetruecolor($width, $height);
				imagealphablending($image_p, false);
				imagesavealpha($image_p, true);
				$image = imagecreatefrompng($source_filename);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				imagepng($image_p, $dest_filename, 9);
				break;
			
			default:
				
				return false;
		}
		
		return true;
	}
}