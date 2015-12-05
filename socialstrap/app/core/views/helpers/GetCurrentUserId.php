<?php

class Zend_View_Helper_GetCurrentUserId extends Zend_View_Helper_Abstract
{

	public function GetCurrentUserId()
	{
		return (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->id ? Zend_Auth::getInstance()->getIdentity()->id : false);
	}
}