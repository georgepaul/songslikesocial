<?php

/**
 * Abstract Storage adapter
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_StorageAbstract
{

	/**
	 * get storage path
	 */
	public function getStoragePath($resource)
	{}

	/**
	 * get resource folder
	 */
	public function getResourceFolder($context)
	{
		switch ($context) {
			case 'posts':
				$destination_folder = '/storage/posts/';
				break;
			
			case 'avatar':
				$destination_folder = '/storage/avatars/';
				break;
			
			case 'cover':
				$destination_folder = '/storage/covers/';
				break;
			
			default:
				return false;
				break;
		}
		
		return $destination_folder;
	}

	/**
	 * move file from temp to storage
	 */
	public function moveFileToStorage($source_file_name, $context, $delete_tmp = true)
	{}

	/**
	 * delete file from storage
	 */
	public function deleteFileFromStorage($file_name, $context)
	{}

	/**
	 * get file from storage to tmp folder
	 */
	public function getFileFromStorage($source_file_name, $destination_file_name, $context)
	{}

	/**
	 * download file
	 */
	public function downloadFile($filename)
	{}

	/**
	 * delete old files from tmp folder, default older than 24h
	 */
	public function deleteOldTmpFiles($seconds_old = 86400, $prefix = '')
	{
		
		// delete old files inside tmp folder
		foreach (glob(TMP_PATH . '/' . $prefix . '*') as $file) {
			if (is_file($file) && filemtime($file) < time() - $seconds_old) {
				@unlink($file);
			}
		}
		
		return;
	}
}