<?php

/**
 * Filesystem Storage adapter
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_StorageFilesystem extends Application_Model_StorageAbstract
{

	/**
	 * get storage path
	 */
	public function getStoragePath($resource)
	{
		$ret = Application_Plugin_Common::getFullBaseUrl();
		
		$folder = $this->getResourceFolder($resource);
		
		return $ret . $folder;
	}

	/**
	 * move file from temp to storage
	 */
	public function moveFileToStorage($source_file_name, $context, $delete_tmp = true)
	{
		$extension = strtolower(pathinfo($source_file_name, PATHINFO_EXTENSION));
		$random_string = Application_Plugin_Common::getRandomString();
		$new_filename = $random_string . '.' . $extension;
		
		$source = TMP_PATH . '/' . $source_file_name;
		
		$folder = $this->getResourceFolder($context);
		$destination = $folder . $new_filename;
		
		if ($delete_tmp) {
			rename($source, PUBLIC_PATH . $destination);
		} else {
			copy($source, PUBLIC_PATH . $destination);
		}
		
		return $new_filename;
	}

	/**
	 * delete file from storage
	 */
	public function deleteFileFromStorage($file_name, $context)
	{
		$folder = $this->getResourceFolder($context);
		
		$ret = @unlink(PUBLIC_PATH . $folder . $file_name);
		
		return $ret;
	}

	/**
	 * get file from storage to tmp folder
	 */
	public function getFileFromStorage($source_file_name, $destination_file_name, $context)
	{
		$folder = $this->getResourceFolder($context);
		
		$destination = TMP_PATH . '/' . $destination_file_name;
		
		copy(PUBLIC_PATH . $folder . $source_file_name, $destination);
		
		return;
	}

	/**
	 * download file in chunks
	 */
	public function downloadFile($filename)
	{
		$storege_path = PUBLIC_PATH . $this->getResourceFolder('posts');
		$full_path = $storege_path . $filename;
		
		// Set headers
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Content-Type: application/octet-stream");
		header("Content-Transfer-Encoding: binary");
		
		// output file in chunks
		set_time_limit(0);
		$file = @fopen($full_path, "rb");
		while (! feof($file)) {
			print(@fread($file, 1024 * 8));
			ob_flush();
			flush();
		}
		
		die();
	}
}