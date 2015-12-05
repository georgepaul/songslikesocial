<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_EditPost extends Application_Form_Main
{

	/**
	 *
	 * Edit post
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/EditPost.phtml'))));

		// fields

		$text = new Zend_Form_Element_Textarea('content');
		$text
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '8')
		->addFilter('StripTags')
		->setAttrib('class', 'form-control');

		$privacy = new Zend_Form_Element_Select('privacy');
		$privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('post_privacy_array'))
		->setErrorMessages(array($this->translator->translate('Select post privacy')))
		->setLabel($this->translator->translate('Privacy'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitbutton');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($text, $privacy, $submit));

		$this->postInit();
	}

}

