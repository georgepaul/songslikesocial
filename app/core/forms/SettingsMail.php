<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsMail extends Application_Form_Main
{

	/**
	 *
	 * Mail settings
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
	
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Settings.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields

		$mail_adapters = array('smtp' => 'smtp', 'mail' => 'php mail()');

		$mail_adapter = new Zend_Form_Element_Select('mail_adapter');
		$mail_adapter
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions($mail_adapters)
		->setLabel($this->translator->translate('Email adapter'))
		->setRequired(true)
		->setValue(isset($all_meta['mail_adapter']) ? $all_meta['mail_adapter'] : 'Zend_Mail_Transport_Smtp')
		->setAttrib('class', 'form-control');

		$mail_host = new Zend_Form_Element_Text('mail_host');
		$mail_host
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('(smtp) host'))
		->setValue(isset($all_meta['mail_host']) ? $all_meta['mail_host'] : 'example.com')
		->setAttrib('class', 'form-control');

		$mail_port = new Zend_Form_Element_Text('mail_port');
		$mail_port
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('(smtp) port (25/465/587)'))
		->setValidators(array('digits'))
		->setValue(isset($all_meta['mail_port']) ? $all_meta['mail_port'] : '465')
		->setAttrib('class', 'form-control');

		$mail_login = new Zend_Form_Element_Select('mail_login');
		$mail_login
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(array('login' => 'login', '' => 'open'))
		->setLabel($this->translator->translate('(smtp) auth method'))
		->setValue(isset($all_meta['mail_login']) ? $all_meta['mail_login'] : 'login')
		->setAttrib('class', 'form-control');

		$mail_security_methods = array('ssl' => 'ssl', 'tls' => 'tls', '' => 'none');

		$mail_security = new Zend_Form_Element_Select('mail_security');
		$mail_security
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions($mail_security_methods)
		->setLabel($this->translator->translate('(smtp) security'))
		->setValue(isset($all_meta['mail_security']) ? $all_meta['mail_security'] : 'ssl')
		->setAttrib('class', 'form-control');

		$mail_username = new Zend_Form_Element_Text('mail_username');
		$mail_username
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('name'))
		->setValue(isset($all_meta['mail_username']) ? $all_meta['mail_username'] : '')
		->setAttrib('class', 'form-control');

		$mail_password = new Zend_Form_Element_Password('mail_password');
		$mail_password
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('(smtp) auth password'))
		->setAttrib('autocomplete', 'off')
		->setRenderPassword(true)
		->setValue(isset($all_meta['mail_password']) ? $all_meta['mail_password'] : '')
		->setAttrib('class', 'form-control');

		$mail_username = new Zend_Form_Element_Text('mail_username');
		$mail_username
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('(smtp) auth username'))
		->setValue(isset($all_meta['mail_username']) ? $all_meta['mail_username'] : '')
		->setAttrib('class', 'form-control');

		$mail_from = new Zend_Form_Element_Text('mail_from');
		$mail_from
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('from'))
		->setValue(isset($all_meta['mail_from']) ? $all_meta['mail_from'] : '')
		->setAttrib('class', 'form-control');

		$mail_from_name = new Zend_Form_Element_Text('mail_from_name');
		$mail_from_name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('from name'))
		->setValue(isset($all_meta['mail_from_name']) ? $all_meta['mail_from_name'] : '')
		->setAttrib('class', 'form-control');


		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$mail_adapter,
				$mail_from,
				$mail_from_name,
				$mail_host,
				$mail_port,
				$mail_security,
				$mail_login,
				$mail_username,
				$mail_password,
				$submit));

		$this->postInit();
	}

}

