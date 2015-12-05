<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_ChangePassword extends Application_Form_Main
{

	/**
	 *
	 * Change password form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/ChangePassword.phtml'))));

		$Profiles = new Application_Model_Profiles();
		
		// fields
		$password_old = new Zend_Form_Element_Password('passwordold');
		$password_old
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('autocomplete', 'off')
		->setRequired(true)
		->setErrorMessages(array($this->translator->translate('Password is required')))
		->setLabel($this->translator->translate('Old Password:'))
		->setAttrib('class', 'form-control');
		
		// check if blank password (facebook-registered user) and remove old password field if so
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$profile = $Profiles->getProfile(Zend_Auth::getInstance()->getIdentity()->name);
			if ($profile->password == '') {
				$password_old->setAttrib('class', 'hidden');
				$password_old->setRequired(false);
				$password_old->setLabel('');
			}
		}
		
		$password1 = new Zend_Form_Element_Password('password1');
		$password1
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('autocomplete', 'off')
		->setRequired(true)
		->addValidator('StringLength', false, array(5))
		->setErrorMessages(array($this->translator->translate('Min 5 characters')))
		->setLabel($this->translator->translate('New Password:'))
		->setAttrib('class', 'form-control');
		
		$password2 = new Zend_Form_Element_Password('password2');
		$password2
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('autocomplete', 'off')
		->setRequired(true)
		->addValidator('Identical', false, array('token' => 'password1'))
		->setErrorMessages(array($this->translator->translate('The passwords do not match')))
		->setLabel($this->translator->translate('Confirm Password:'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('changepass');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Change Password'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($password_old, $password1, $password2, $submit));

		$this->postInit();
	}

}

