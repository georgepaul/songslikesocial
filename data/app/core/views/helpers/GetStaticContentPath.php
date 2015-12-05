<?php

class Zend_View_Helper_GetStaticContentPath extends Zend_View_Helper_Abstract
{

	public function GetStaticContentPath()
	{
		return APPLICATION_PATH . '/views/info/';
	}
}