<?php

/**
 * File Upload Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class FileUploadController extends Zend_Controller_Action
{

	/**
	 * Receive uploaded files (ajax/blueimp)
	 */
	public function receivefileAction()
	{
		$ret = Zend_Registry::get('Zend_Translate')->translate('Server-side error');
		
		if ($this->getRequest()->isPost()) {
			
			$Images = new Application_Model_Images();
			$adapter = new Zend_File_Transfer_Adapter_Http();
			$adapter->addValidator('Extension', false, 'jpg,jpeg,png,gif');
			$files = $adapter->getFileInfo();
			
			$receive_to = $this->getRequest()->getParam('to');
			$form_unique_key = (int) $this->getRequest()->getParam('form_unique_key');
			
			$current_user_id = Zend_Auth::getInstance()->getIdentity()->id;
			$current_user_role = Zend_Auth::getInstance()->getIdentity()->role;
			
			foreach ($files as $file => $info) {
				
				// file uploaded & is valid
				if (! $adapter->isUploaded($file))
					continue;
				if (! $adapter->isValid($file))
					continue;

				// check max file size
				if ($info['size'] > Zend_Registry::get('config')->get('max_file_upload_size'))
					continue;
				
				$filename = $adapter->getFileName($file);
				$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				
				$fileinfo = $adapter->getFileInfo($file);
				$filesize = $fileinfo[$file]['size'];
				
				$profilename = Zend_Auth::getInstance()->getIdentity()->name;
				$randomstring = Application_Plugin_Common::getRandomString();
				
				// generate tmp filename
				$tmp_filename = 'post_' . $profilename . '_' . $form_unique_key . '_' . $randomstring . '.' . $extension;
				$tmp_filename_full = TMP_PATH . '/' . $tmp_filename;
				
				// set to rename uploaded file upon receiving to tmp folder
				$adapter->setDestination(TMP_PATH);
				$adapter->addFilter('rename', $tmp_filename_full);
				
				// receive the files into the tmp directory, must have
				$adapter->receive($file);
				
				// check if valid image
				if (! Application_Plugin_ImageLib::isValidImage($tmp_filename_full)) {
					unlink($tmp_filename_full);
					continue;
				}
				
				// check storage limits
				$max_files_per_user = 0 + Zend_Registry::get('config')->get('max_files_per_user');
				$max_storage_per_user = 0 + Zend_Registry::get('config')->get('max_storage_per_user');
				if ($current_user_role == 'user' && ($max_files_per_user || $max_storage_per_user)) {
				
					$storage_usage = $Images->getStorageUsage($current_user_id);
				
					if (
					($max_files_per_user && $storage_usage['image_count'] > $max_files_per_user)
					||
					($max_storage_per_user && $storage_usage['image_size'] > $max_storage_per_user)
					) {
						$ret = Zend_Registry::get('Zend_Translate')->translate('Storage limits reached');
						unlink($tmp_filename_full);
						continue;
					}
				}
				
				if ($receive_to !== 'tmp') {
					
					// receive to album, check if user is an album owner
					if ($receive_to > 0) {
						
						$Albums = new Application_Model_Albums();
						$album = $Albums->getAlbum($receive_to);
						
						// exit on wrong album
						if (! $album || $album['user_id'] != $current_user_id) {
							$this->_helper->json(false);
							return;
						}
					}
					
					$Storage = new Application_Model_Storage();
					$StorageAdapter = $Storage->getAdapter();
					
					$original_filename = '';
					if (Zend_Registry::get('config')->get('resample_images')) {
							
						Application_Plugin_ImageLib::resample(TMP_PATH . '/' . $tmp_filename, TMP_PATH . '/thumb_' . $tmp_filename);
						$image_filename = $StorageAdapter->moveFileToStorage('thumb_' . $tmp_filename, 'posts');
							
						if (Zend_Registry::get('config')->get('keep_original')) {
							$original_filename = $StorageAdapter->moveFileToStorage($tmp_filename, 'posts');
						} else {
							$original_filename = '';
							unlink(TMP_PATH . '/' . $tmp_filename); // clean up
						}
					} else {
						$image_filename = $StorageAdapter->moveFileToStorage($tmp_filename, 'posts');
					}
					
					if ($image_filename) {
						$ret = $Images->addImage($image_filename, $filesize, $current_user_id, $current_user_id, 0, $receive_to, $original_filename);
					}
				}
				
				$ret = true;
			}
		}
		
		$this->_helper->json($ret);
	}
}