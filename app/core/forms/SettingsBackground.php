<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsBackground extends Application_Form_Main
{

	/**
	 *
	 * Change network background
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
	
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/SettingsBackground.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields

		$background_image = new Zend_Form_Element_File('background');
		$background_image
		->setDecorators(array('File', 'Errors'))
		->setLabel($this->translator->translate('Choose Picture (jpg, png or gif)'))
		->addValidator('Extension', false, 'jpg,jpeg,png,gif');

		$background_color = new Zend_Form_Element_Text('background_color');
		$background_color
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StringTrim')
		->setValue(isset($all_meta['background_color']) ? $all_meta['background_color'] : 'ff0000')
		->setErrorMessages(array($this->translator->translate('Please pick a color')))
		->setLabel($this->translator->translate('Background Color'))
		->setRequired(true)
		->setAttrib('class', 'form-control colorpicker-input');

			
		$background_repeat = new Zend_Form_Element_Checkbox('background_repeat');
		$background_repeat
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_repeat']) && $all_meta['background_repeat'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Repeat background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$background_scroll = new Zend_Form_Element_Checkbox('background_scroll');
		$background_scroll
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_scroll']) && $all_meta['background_scroll'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Scroll background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$background_stretch = new Zend_Form_Element_Checkbox('background_stretch');
		$background_stretch
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_stretch']) && $all_meta['background_stretch'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Stretch background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$disable_image = new Zend_Form_Element_Checkbox('background_noimage');
		$disable_image
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_noimage']) && $all_meta['background_noimage'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable custom image'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$background_image,
				$background_repeat,
				$background_scroll,
				$background_stretch,
				$disable_image,
				$background_color,
				$submit));

		$this->postInit();
	}

}

