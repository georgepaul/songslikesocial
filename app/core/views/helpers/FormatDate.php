<?php

class Zend_View_Helper_FormatDate extends Zend_View_Helper_Abstract
{

	public function FormatDate($date)
	{
		$format = (Zend_Registry::get('Zend_Translate')->translate('date_format') ? Zend_Registry::get('Zend_Translate')->translate('date_format') : 'm/d/y');
		
		return date($format, strtotime($date));
	}
}