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
 * Create urls
 * @package utils
 */
class Gzip 
{
	/**
	 * gzip filename 
	 *
	 * @private string
	 */
	private $file;
	
	/**
	 * array of URL parameters
	 *
	 * @private array
	 */
	private $parameters;
	
	/**
	 * constructor
	 * @param string 	$url 			the optional URL (a string) to base the Url on
	 * @param string 	$parameters the optional URL (a string) with parameters only
	 * @return void
	 */
	public function __construct($file)
	{
		$this->file 		= $file;
		$this->parameters 		= array();
	}

	public function create($path)
	{
	}
	
	/**
	 * Set the object to the URL of the current page; this can be either the full
	 * URL (with parameters) or just the path.
	 * @param string	$url  			the URL (a string) to base this Url on
	 * @param string	$parameter  optional string of parameters (URL-encoded)
	 * @return void
	 */
	public function extract($path)
	{
		if(!is_file($this->file)) throw new Exception("file {$this->file} does not exist.");
		if(!($zh = gzopen($this->file, "r"))) throw new Exception("unable to open compressed archive {$this->file}.");

		$path = realpath($path);

		try
		{
			while (!gzeof ($zh)) 
			{
				// ----- Read the 512 bytes header
				$buffer = gzread ($zh, 512);
				$header = $this->readHeader($buffer);

				// ----- Look for empty blocks to skip
				if(!$header["filename"]) continue;
				$filename = $path.'/'.$header["filename"];

				// skip if file exists but not writable or if it is a directory
				if((file_exists($filename) && !is_writable($filename)) || is_dir($filename)) 
				{
					$this->skipFile($zh, $header['size']);
					continue;
				}

				// check if file is a directory
				if($header["typeflag"]=="5")
					$directory = $filename;
				else if (!strstr($filename, "/"))
					$directory = "";
				else
					$directory = dirname($filename);

				$this->checkDir($directory);

				// continue with the rest of the archive when it was a directory
				if($header["typeflag"]=="5") continue;

				// try to open destination file
				if(($fh = @fopen($filename, "wb")) == 0)
				{
					$this->skipFile($zh, $header['size']);
					continue;
				}

				// ----- Read data
				$n = floor($header["size"]/512);
				for ($i=0; $i<$n; $i++)
				{
					$content = gzread($zh, 512);
					fwrite($fh, $content, 512);
				}

				if (($header["size"] % 512) != 0)
				{
					$content = gzread($zh, 512);
					fwrite($fh, $content, ($header["size"] % 512));
				}
				fclose($fh);

				// ----- Change the file mode, mtime
				chmod($filename, 0664);
				touch($filename, $header["mtime"]);

				if(filesize($filename) != $header["size"]) throw new Exception("Extracted file $filename does not have the correct file size. Archive may be corrupted.");
			}
		}
		catch(Exception $e)
		{
			// make sure we close the file
			gzclose($zh);
			throw $e;
		}

		gzclose($zh);
	}

	function skipFile($zh, $size)
	{
		gzseek($zh, gztell($zh)+(ceil(($size/512))*512));
	}

  function readHeader($binaryData)
  {
		// default header
    $header=array();
		$header['filename'] = "";
		$header['status'] = "empty";

    // ----- Look for no more block
    if (strlen($binaryData)==0) return $header;

    // ----- Look for invalid block size
		$blockSize = strlen($binaryData); 
    if($blockSize != 512) throw new Exception("Invalid block size: $blockSize.");

    // ----- Calculate the checksum
		$checksum = $this->getChecksum($binaryData);

    // ----- Extract the values
    $data = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1typeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor", $binaryData);

    // ----- Extract the checksum for check
    $header["checksum"] = OctDec(trim($data["checksum"]));

		// ----- Look for last block (empty block)
		if(($checksum == 256) && ($header["checksum"] == 0)) return $header;

		// Checksum validation
    if($header["checksum"] != $checksum) throw new Exception("File checksum is invalid: $checksum calculated, {$header['checksum']} expected.");

    // ----- Extract the properties
    $header["filename"] = trim($data["filename"]);
    $header["mode"] = OctDec(trim($data["mode"]));
    $header["uid"] = OctDec(trim($data["uid"]));
    $header["gid"] = OctDec(trim($data["gid"]));
    $header["size"] = OctDec(trim($data["size"]));
    $header["mtime"] = OctDec(trim($data["mtime"]));

		// check if type is directory
    if (($header["typeflag"] = $data["typeflag"]) == "5") $header["size"] = 0;

    // ----- Set the status field
    $header["status"] = "ok";

    // ----- Return
    return $header;
  }

	private function getChecksum($binaryData)
	{
    $checksum = 0;

    // ..... First part of the header (from filename to mtime)
    for ($i=0; $i<148; $i++)
    {
      $checksum+=ord(substr($binaryData,$i,1));
    }
    // ..... Ignore the checksum value and replace it by ' ' (space)
    for ($i=148; $i<156; $i++)
    {
      $checksum += ord(' ');
    }
    // ..... Last part of the header (typeflag to devminor)
    for ($i=156; $i<512; $i++)
    {
      $checksum+=ord(substr($binaryData,$i,1));
    }
		return $checksum;
	}

  function checkDir($directory)
  {
    // ----- some php versions fail on creating direcotries that end with slash...
		$directory = rtrim($directory, '/');

    // ----- Check the directory availability
    if ((is_dir($directory)) || ($directory == "")) return;

    // ----- Extract parent directory
    $parent_dir = dirname($directory);

    // ----- Just a check
    if ($parent_dir && $parent_dir != $directory) $this->checkDir($parent_dir);

    // ----- Create the directory
    if (!@mkdir($directory, 0777)) throw new Exception("Unable to create direcotry $directory.");
		chmod($directory, 0755);
  }
}
?>
