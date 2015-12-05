<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_CoverPicture extends Application_Form_Main
{

	/**
	 *
	 * Change cover form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/CoverPicture.phtml'))));

		// fields

		$cover_file = new Zend_Form_Element_File('coverfile', array('onchange' => '$("html").addClass("busy"); submit();'));
		$cover_file
		->setDecorators(array('File', 'Errors'))
		->setLabel($this->translator->translate('Choose Picture (jpg, png or gif)'))
		->addValidator('Count', false, 1) // ensure only 1 file
		->addValidator('Size', false, Zend_Registry::get('config')->get('max_file_upload_size'))
		->addValidator('Extension', false, 'jpg,jpeg,png,gif');


		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Next'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($cover_file, $submit));

		$this->postInit();
	}

}

