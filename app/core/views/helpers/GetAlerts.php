<?php

class Zend_View_Helper_GetAlerts extends Zend_View_Helper_Abstract
{

	public function GetAlerts()
	{
		return Application_Plugin_Alerts::getMessages();
	}
}