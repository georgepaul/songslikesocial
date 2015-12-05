<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Settings extends Application_Form_Main
{

	/**
	 *
	 * General settings
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Settings.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields
		$network_name = new Zend_Form_Element_Text('network_name');
		$network_name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Network name'))
		->setValue(isset($all_meta['network_name']) ? $all_meta['network_name'] : 'MyNetwork')
		->setAttrib('class', 'form-control');

		$description = new Zend_Form_Element_Textarea('network_description');
		$description
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setValue(isset($all_meta['network_description']) ? $all_meta['network_description'] : '')
		->setLabel($this->translator->translate('Description'))
		->setAttrib('class', 'form-control');
		
		$license_code = new Zend_Form_Element_Text('license_code');
		$license_code
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Item Purchase Code'))
		->setValue(isset($all_meta['license_code']) ? $all_meta['license_code'] : '')
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$network_name,
				$description,
				$license_code,
				$submit));

		$this->postInit();
	}

}


