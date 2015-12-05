<?php

class Zend_View_Helper_GetCurrentUserData extends Zend_View_Helper_Abstract
{

	public function GetCurrentUserData($field)
	{
		return (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->{$field} ? Zend_Auth::getInstance()->getIdentity()->{$field} : false);
	}
}