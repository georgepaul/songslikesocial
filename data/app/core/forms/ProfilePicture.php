<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_ProfilePicture extends Application_Form_Main
{

	/**
	 *
	 * Change avatar form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/ProfilePicture.phtml'))));

		// fields

		$avatar_file = new Zend_Form_Element_File('avatarfile', array('onchange' => '$("html").addClass("busy"); submit();'));
		$avatar_file
		->setDecorators(array('File', 'Errors'))
		->setLabel($this->translator->translate('Choose Picture (jpg, png or gif)'))
		->addValidator('Count', false, 1) // ensure only 1 file
		->addValidator('Size', false,  Zend_Registry::get('config')->get('max_file_upload_size'))
		->addValidator('Extension', false, 'jpg,jpeg,png,gif');

		$submit = new Zend_Form_Element_Submit('next');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Next'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($avatar_file, $submit));

		$this->postInit();
	}

}

