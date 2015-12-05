<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsMarkup extends Application_Form_Main
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
		$global_head = new Zend_Form_Element_Textarea('global_head');
		$global_head
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['global_head']) ? $all_meta['global_head'] : '')
		->setLabel($this->translator->translate('Additional html for head section - show on global pages'))
		->setAttrib('class', 'form-control');

		$profiles_head = new Zend_Form_Element_Textarea('profiles_head');
		$profiles_head
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['profiles_head']) ? $all_meta['profiles_head'] : '')
		->setLabel($this->translator->translate('Additional html for head section - show on profile pages (Tags: PROFILE_SCREEN_NAME, PROFILE_NAME, PROFILE_AVATAR, PROFILE_COVER, PROFILE_DESCRIPTION)'))
		->setAttrib('class', 'form-control');

		$common_head = new Zend_Form_Element_Textarea('common_head');
		$common_head
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['common_head']) ? $all_meta['common_head'] : '')
		->setLabel($this->translator->translate('Additional html for head section - always show on all pages (common analytics code etc)'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$global_head,
				$profiles_head,
				$common_head,
				$submit));

		$this->postInit();
	}

}

