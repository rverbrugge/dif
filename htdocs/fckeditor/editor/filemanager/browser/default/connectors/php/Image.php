<?php
/**
 * This file is part of the DIF Web Framework
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2007 Ramses Verbrugge
 * @package Common
 */

/**
 * Main configuration 
 * @package Common
 */
class Image
{

	/**
	* current request url path
	* @var string
  */
	private $path;

	/**
	* filname
	* @var string
  */
	private $file;

	/**
	* image width
	* @var integer
  */
	private $width;

	/**
	* image height
	* @var integer
  */
	private $height;

	/**
	* mime type of image
	* @var string
  */
	private $mime;

	/**
	* Image object
	* @var Resource
  */
	private $img;

	private $uploaded;

	/**
	 * Constructor
	 *
	 * handles image functions
	 * @param string can be an _FILES item or a filename or a path and filename
	 * @param string optional path when only a filename was given
	 */
	public function __construct($file, $path=NULL)
	{
		$this->initialize($file, $path);
	}

	public function __destruct()
	{
		if(isset($this->img)) imagedestroy($this->img);
		if($this->uploaded) $this->delete();
	}

	private function initialize($file, $path=NULL)
	{
		if(is_array($file))
		{
			// uploaded file
			// check if file is realy uploaded
			if(!array_key_exists('tmp_name', $file) || !is_uploaded_file($file['tmp_name'])) throw new Exception('wrong file.');

			// first move uploaded file to safe (read safemode) location
			$this->file = basename($file['tmp_name']);
			/*
			$director = Director::getInstance();
			$tmpPath = $director->getTempPath();
			move_uploaded_file($file['tmp_name'], $tmpPath."/".$this->file);
			*/

			$this->setPath(dirname($file['tmp_name']));
			$this->filename = $file['name'];
			$this->uploaded = true;
		}
		else
		{
			// file from database or whatever
			$this->setPath((isset($path)) ? realpath($path) : realpath(dirname($file)));
			$this->file = basename($file);
			$this->filename = $this->file;
			$this->uploaded = false;

			// check if file exists
			if(!is_file($this->getFileName())) $this->file = '';
		}
		// check if file is image
		//if(!$this->isImage($this->filename)) throw new Exception("file type not supported: {$this->filename}");
		if(!$this->isImage($this->filename)) $this->file = '';
	}

	public static function isImage($filename)
	{
		$supportType = array('jpg','jpeg','gif','bmp','png','swf','tiff');
		return in_array(Utils::getExtension($filename), $supportType);
	}
	
	public function setPath($path)
	{
		$this->path = $path;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function overlay($image, $percentage=100)
	{
		// check fil file is set
		if(!$this->getFileName() || !$image->getFileName()) return;
    $destImage = $this->getImage();
		if(!imagealphablending($destImage, true)) throw new Exception("error in alpha blending mode");

    $sourceImage = $image->getImage();
		if(!imagealphablending($sourceImage, true)) throw new Exception("error in alpha blending mode");

		//imagecopymerge($destImage, $sourceImage, 0, 0, 0, 0, $image->getWidth(), $image->getHeight(), 100);
		imagecopy($destImage, $sourceImage, 0, 0, 0, 0, $image->getWidth(), $image->getHeight());//, 100);
	}

	public function resize($width, $height=0, $expand=false)
	{
		// check fil file is set
		if(!$this->getFileName()) return;

		$imgWidth = $this->getWidth();
		$imgHeight = $this->getHeight();
		$newWidth = $imgWidth;
		$newHeight = $imgHeight;

		// check if we have to resize
		if(($width 	== 0 || $imgWidth == $width || (!$expand && $imgWidth < $width)) && 
			 ($height == 0 || $imgHeight == $height || (!$expand && $imgHeight < $height))) return;

    //define new dimensions
		if($width > 0 && ($expand || $imgWidth > $width))
		{
			$newWidth = $width;
			$newHeight = ceil($imgHeight / ($imgWidth / $width));
		}

		if($height > 0 && ($expand || $newHeight > $height))
		{
			$tmpHeight = $newHeight;
			$newHeight = $height;
			$newWidth = ceil($newWidth / ($tmpHeight / $height));
		}

		$dest = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($dest,$this->getImage(),0,0,0,0,$newWidth,$newHeight,$this->getWidth(), $this->getHeight());
		//imagecopyresized($dest,$this->getImage(),0,0,0,0,$newWidth,$newHeight,$this->getWidth(), $this->getHeight());
		imagedestroy($this->img);

		$this->img = $dest;

		$this->setWidth($newWidth);
		$this->setHeight($newHeight);
	}

	public function crop($startx, $starty, $width, $height, $destWidth, $destHeight, $expand=true)
	{
		// check fil file is set
		if(!$this->getFileName()) return;

		// check settings
		if($startx < 0) $startx = 0;
		if($starty < 0) $starty = 0;

		if($width == 0) $width = $height;
		if($height == 0) $height = $width;

		if($width == 0 && $height == 0)
		{
			$width = $this->getWidth();
			$height = $this->getHeight();
		}

		$endx = $startx + $width;
		$endy = $starty + $height;

		
		// check if requested width and height fit in image if expanding is prohibited
		if(!$expand && ($this->getWidth() < $width || $this->getHeight() < $height)) return;

		// resize width
		if($this->getWidth() < $width) $this->resize($width,0,true);

		// resize height
		if($this->getHeight() < $height) $this->resize(0,$height,true);

		$imgWidth = $this->getWidth();
		$imgHeight = $this->getHeight();

		if($endx >= $imgWidth)
		{
			$startx -= ($endx - $imgWidth);
			if($startx < 0) return; // this is not possible because of first checks
		}

		if($endy >= $imgHeight)
		{
			$starty -= ($endy - $imgHeight);
			if($starty < 0) return; // this is not possible because of first checks
		}


		$dest = imagecreatetruecolor($destWidth, $destHeight);
		imagecopyresampled($dest,$this->getImage(),0,0,$startx,$starty,$destWidth, $destHeight, $width,$height);
		//imagecopyresized($dest,$this->getImage(),0,0,$startx,$starty,$destWidth, $destHeight, $width,$height);
		imagedestroy($this->img);

		$this->img = $dest;

		$this->setWidth($destWidth);
		$this->setHeight($destHeight);
	}

	public function setWidth($width)
	{
		$this->width = $width;
	}

	public function setHeight($height)
	{
		$this->height = $height;
	}

	public function getWidth()
	{
		if(!isset($this->width))
			$this->setImageSize();

		return $this->width;
	}

	public function getHeight()
	{
		if(!isset($this->height))
			$this->setImageSize();

		return $this->height;
	}

	public function getMime()
	{
		if(!isset($this->mime))
			$this->setImageSize();

		return $this->mime;
	}

	private function setImageSize()
	{
		$size = $this->getFileName() ? getimagesize($this->getFileName()) : array(0,0,0);
		$this->width = $size[0];
		$this->height = $size[1];
		$this->mime = $size[2];
	}

	public function getImage()
	{
		if(!isset($this->img))
		{
			if(!$this->getFileName()) return;

			$this->img = imagecreatefromstring(file_get_contents($this->getFileName()));
			if(!imagealphablending($this->img, false)) throw new Exception("error in alpha blending mode");
			imagesavealpha($this->img, true);
		}

		return $this->img;
	}

	public function save($file=NULL)
	{
		// check fil file is set
		if(!$this->getFileName()) return;

		$filename = (isset($file)) ? $file : $this->getFileName();
		switch(Utils::getExtension($filename))
		{
			case 'jpg' :
			case 'jpeg' : imagejpeg($this->getImage(), $filename, 95); break;
			case 'gif' : imagegif($this->getImage(), $filename); break;
			default : imagepng($this->getImage(), $filename, 9); break;
		}
	}

	public function delete()
	{
		// check fil file is set
		if(!$this->getFileName()) return;

		unlink($this->getFileName());
	}

	public function getFileName($path=true)
	{
		if(!$this->file) return;

		return $path ? "{$this->path}/{$this->file}" : $this->file;
	}

}

?>
