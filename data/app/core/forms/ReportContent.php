<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_ReportContent extends Application_Form_Main
{

	/**
	 *
	 * Report content
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
		
		// hidden resource type identifier
		$resource_type = new Zend_Form_Element_Hidden('resource_type');
		$resource_type
		->setDecorators(array('ViewHelper'));

		// hidden resource id identifier
		$resource_id = new Zend_Form_Element_Hidden('resource_id');
		$resource_id
		->setDecorators(array('ViewHelper'));

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/ReportContent.phtml'))));

		// GET action
		$this->setAction(Zend_Controller_Front::getInstance()->getBaseUrl() . '/reports/report');

		// fields

		$reason = new Zend_Form_Element_Textarea('reason');
		$reason
		->setAttrib('COLS', '26')
		->setAttrib('ROWS', '3')
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StripTags')
		->setAttrib('placeholder', $this->translator->translate('Write a reason'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitreport');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Submit'))
		->setAttrib('class', 'submit btn btn-default pull-right');

		$this->addElements(array($resource_type, $resource_id, $reason, $submit));

		$this->postInit();
	}

}

