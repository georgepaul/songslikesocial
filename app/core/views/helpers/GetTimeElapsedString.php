<?php

class Zend_View_Helper_GetTimeElapsedString extends Zend_View_Helper_Abstract
{

	public function GetTimeElapsedString($resource)
	{
		return Application_Plugin_Common::getTimeElapsedString(strtotime($resource));
	}
}