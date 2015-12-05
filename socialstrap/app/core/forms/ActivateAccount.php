<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_ActivateAccount extends Application_Form_Main
{

	/**
	 *
	 * Activate account form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/ActivateAccount.phtml'))));

		// fields
		$confirm = new Zend_Form_Element_Checkbox('confirm');
		$confirm
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Accept Terms & Conditions'))
		->addValidator('GreaterThan', false, array(0))
		->setErrorMessages(array($this->translator->translate('Please Read and Agree to our Terms & Conditions')));

		$register = new Zend_Form_Element_Submit('activatesubmit');
		$register
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Create Account'))
		->setAttrib('class', 'submit btn btn-default');

		if (Zend_Registry::get('config')->get('recaptcha_active') == 1){
			$privateKey = Zend_Registry::get('config')->get('recaptcha_privatekey');
			$publicKey = Zend_Registry::get('config')->get('recaptcha_publickey');
			$recaptcha = new Zend_Service_ReCaptcha($publicKey, $privateKey, array('ssl' => true));
				
			$captcha = new Zend_Form_Element_Captcha(
					'captcha',
					array('label' => '',
							'captcha' => array(
									'captcha' => 'ReCaptcha',
									'service' => $recaptcha
							)));
			$captcha->setDecorators(array('ViewHelper', 'Errors'));
				
				
			$this->addElements(array($captcha, $confirm, $register));
		}else{
			$this->addElements(array($confirm, $register));
		}
		
		
		$this->postInit();
	}

}

