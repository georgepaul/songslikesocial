<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsLogo extends Application_Form_Main
{

	/**
	 *
	 * Change network logo
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
	
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/SettingsLogo.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields

		$logo_image = new Zend_Form_Element_File('logo_image');
		$logo_image
		->setDecorators(array('File', 'Errors'))
		->setLabel($this->translator->translate('Choose Picture (jpg, png or gif)'))
		->addValidator('Extension', false, 'jpg,jpeg,png,gif');

		$disable_image = new Zend_Form_Element_Checkbox('logo_noimage');
		$disable_image
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['logo_noimage']) && $all_meta['logo_noimage'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable custom image'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$logo_image,
				$disable_image,
				$submit));

		$this->postInit();
	}

}

