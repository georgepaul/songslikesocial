<?php

class Zend_View_Helper_GetRandomString extends Zend_View_Helper_Abstract
{

	public function GetRandomString()
	{
		return Application_Plugin_Common::getRandomString();
	}
}