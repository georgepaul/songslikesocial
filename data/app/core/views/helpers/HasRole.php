<?php

class Zend_View_Helper_HasRole extends Zend_View_Helper_Abstract
{

	public function HasRole($role)
	{
		return (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->role === $role);
	}
}