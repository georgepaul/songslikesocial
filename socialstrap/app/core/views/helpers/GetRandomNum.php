<?php

class Zend_View_Helper_GetRandomNum extends Zend_View_Helper_Abstract
{

	public function GetRandomNum()
	{
		return Application_Plugin_Common::getRandomNum();
	}
}