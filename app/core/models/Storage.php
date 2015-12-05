<?php

/**
 * Storage adapter
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Storage
{

	public function getAdapter()
	{
		$classname = Zend_Registry::get('storage_adapter');
		
		return new $classname();
	}
}