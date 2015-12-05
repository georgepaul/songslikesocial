<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Register extends Application_Form_Main
{

	/**
	 *
	 * Small register form on login page
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Register.phtml'))));


		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');

		// fields

		// lowercase, alnum without whitespaces
		$name = new Zend_Form_Element_Text('regname');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setRequired(true)
		->addFilter('StringToLower')
		->addValidator('alnum', false, array('allowWhiteSpace' => false))
		->addValidator('stringLength', false, array($username_minchars, $username_maxchars))
		->setErrorMessages(array(sprintf($this->translator->translate('Please choose a valid username between %d and %d characters'), $username_minchars, $username_maxchars)))
		->setAttrib('class', 'form-control alnum-only')
		->setLabel($this->translator->translate('Username'));

		$email = new Zend_Form_Element_Text('regemail');
		$email
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StringToLower')
		->setRequired(true)
		->addValidator('EmailAddress', true)
		->setLabel($this->translator->translate('Email'))
		->setAttrib('class', 'form-control')
		->setErrorMessages(array($this->translator->translate('Enter a valid email address')));

		$password = new Zend_Form_Element_Password('regpassword');
		$password
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Password'))
		->setErrorMessages(array($this->translator->translate('Password is required')))
		->setAttrib('class', 'form-control')
		->setAttrib('autocomplete', 'off')
		->setRequired(true);

		$register = new Zend_Form_Element_Submit('registerbtn');
		$register
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Create Account'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($name, $email, $password, $register));

		$this->postInit();
	}


	/**
	 *
	 * unique name / email validator
	 */
	public function isValid($data)
	{
		// return on false to see previous errors
		if (parent::isValid($data) == false) return false;
		
		$this->getElement('regname')
		->addValidator(
				'Db_NoRecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'name',
				)
		)
		->setErrorMessages(array($this->translator->translate('This username is not available')));

		$this->getElement('regemail')
		->addValidator(
				'Db_NoRecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'email',
				)
		)
		->setErrorMessages(array($this->translator->translate('This email is already in use')));

		return parent::isValid($data);
	}


}

