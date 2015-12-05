<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsBanners extends Application_Form_Main
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
		$motd = new Zend_Form_Element_Textarea('motd');
		$motd
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['motd']) ? $all_meta['motd'] : '')
		->setLabel($this->translator->translate('Message of the day'))
		->setAttrib('class', 'form-control');
		
		$top_banner = new Zend_Form_Element_Textarea('top_banner'); 
		$top_banner
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['top_banner']) ? $all_meta['top_banner'] : '')
		->setLabel($this->translator->translate('Top Banner html'))
		->setAttrib('class', 'form-control');

		$sidebar_banner = new Zend_Form_Element_Textarea('sidebar_banner');
		$sidebar_banner
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['sidebar_banner']) ? $all_meta['sidebar_banner'] : '')
		->setLabel($this->translator->translate('Sidebar Banner html'))
		->setAttrib('class', 'form-control');

		// fields
		$middle_banner = new Zend_Form_Element_Textarea('middle_banner');
		$middle_banner
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->setValue(isset($all_meta['middle_banner']) ? $all_meta['middle_banner'] : '')
		->setLabel($this->translator->translate('Middle Banner html'))
		->setAttrib('class', 'form-control');


		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$motd,
				$top_banner,
				$sidebar_banner,
				$middle_banner,
				$submit));

		$this->postInit();
	}

}

