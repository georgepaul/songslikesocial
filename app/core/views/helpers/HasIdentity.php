<?php

class Zend_View_Helper_HasIdentity extends Zend_View_Helper_Abstract
{

	public function HasIdentity()
	{
		return Zend_Auth::getInstance()->hasIdentity();
	}
}