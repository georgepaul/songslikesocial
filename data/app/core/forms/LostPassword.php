<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_LostPassword extends Application_Form_Main
{

	/**
	 *
	 * Lost password form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/LostPassword.phtml'))));

		// fields
		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Username or email'))
		->setErrorMessages(array($this->translator->translate('Please enter your name or email')))
		->setRequired(true)
		->setAttrib('class', 'form-control');
			
		$login = new Zend_Form_Element_Submit('lostpasswordsubmit');
		$login
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Send me the link'))
		->setAttrib('class', 'submit btn btn-info');

		$this->addElements(array($name, $login));

		$this->postInit();
	}

}

