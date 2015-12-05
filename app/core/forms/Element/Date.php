<?php
/**
 * Custom date form element
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Element_Date extends Zend_Form_Element_Xhtml
{
	public $helper = 'formDate';
	public $min_year = false;
	public $max_year = false;

	public function isValid ($value, $context = null)
	{
		if (!is_array($value)) return false;

		$result = checkdate((int)$value['month'], (int)$value['day'], (int)$value['year']);
		
		if ($result == false) $value = null;
		
		if (($this->max_year && (int)$value['year'] > $this->max_year) || ($this->min_year && (int)$value['year'] < $this->min_year)) $value = null;
		
		return parent::isValid($value, $context);
	}
	
	public function setYearSpan($min, $max)
	{
		$this->min_year = $min;
		$this->max_year = $max;
	}

}
