<?php

class Zend_View_Helper_GetStorageUrl extends Zend_View_Helper_Abstract
{

	public function GetStorageUrl($resource)
	{
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();
		
		return $StorageAdapter->getStoragePath($resource);
	}
}