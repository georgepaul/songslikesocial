<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsStyle extends Application_Form_Main
{

	/**
	 *
	 * Themes & styles
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/SettingsStyles.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();
		
		// fields
		$themes_array = array('/bootstrap/css/bootstrap.min.css' => 'Bootstrap');
		$css_theme = new Zend_Form_Element_Select('css_theme');
		$css_theme
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions($themes_array)
		->setErrorMessages(array($this->translator->translate('Please select')))
		->setLabel($this->translator->translate('Choose css theme'))
		->setRequired(true)
		->setValue(isset($all_meta['css_theme']) ? $all_meta['css_theme'] : 'bootstrap')
		->setAttrib('class', 'form-control');

		$wide_layout = new Zend_Form_Element_Checkbox('wide_layout');
		$wide_layout
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['wide_layout']) && $all_meta['wide_layout'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Extra-wide layout on large screens'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$cover_ysize = new Zend_Form_Element_Text('cover_ysize');
		$cover_ysize
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Cover image height'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['cover_ysize']) ? $all_meta['cover_ysize'] : '220')
		->setAttrib('class', 'form-control');

		$user_background = new Zend_Form_Element_Checkbox('user_background');
		$user_background
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['user_background']) && $all_meta['user_background'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Users can have custom background image'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$subscriber_background = new Zend_Form_Element_Checkbox('subscriber_background');
		$subscriber_background
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['subscriber_background']) && $all_meta['subscriber_background'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Subscribers can have custom background image'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$custom_css = new Zend_Form_Element_Textarea('css_custom');
		$custom_css
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '15')
		->setValue(isset($all_meta['css_custom']) ? $all_meta['css_custom'] : '')
		->setLabel($this->translator->translate('Custom css'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$css_theme,
				$wide_layout,
				$cover_ysize,
				$user_background,
				$subscriber_background,
				$custom_css,
				$submit));
		
		$this->postInit();
	}

}

