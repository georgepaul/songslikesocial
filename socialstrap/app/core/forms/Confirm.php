<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Confirm extends Application_Form_Main
{

	/**
	 *
	 * Confirm form / button
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Confirm.phtml'))));


		// fields

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Confirm'))
		->setAttrib('class', 'submit btn btn-warning form-confirmation');

		$this->addElements(array($submit));

		$this->postInit();
	}

}

