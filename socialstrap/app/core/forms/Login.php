<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Login extends Application_Form_Main
{

	/**
	 *
	 * Login page form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Login.phtml'))));

		// fields
		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Username or email'))
		->addFilter('StringToLower')
		->setErrorMessages(array($this->translator->translate('Enter your username or email')))
		->setAttrib('class', 'form-control')
		->setRequired(true);

		$password = new Zend_Form_Element_Password('password');
		$password
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Password'))
		->setErrorMessages(array($this->translator->translate('Enter your password')))
		->setAttrib('class', 'form-control')
		->setAttrib('autocomplete', 'off')
		->setRequired(true);

		$remember = new Zend_Form_Element_Checkbox('remember_me');
		$remember
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue('1')
		->setLabel($this->translator->translate('Remember me'));

		$login = new Zend_Form_Element_Submit('loginbtn');
		$login
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Sign In'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($name, $password, $login, $remember));

		$this->postInit();
	}

}

