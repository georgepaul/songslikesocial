<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Message extends Application_Form_Main
{

	/**
	 *
	 * Internal message form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Message.phtml'))));
		
		// action
		$this->setAction(Zend_Controller_Front::getInstance()->getBaseUrl() . '/messages/new');

		// fields
		$content = new Zend_Form_Element_Text('content');
		$content
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StripTags')
		->setRequired(true)
		->setAttrib('autocomplete', 'off')
		->setAttrib('class', 'form-control')
		->setAttrib('placeholder', $this->translator->translate('Write a message...'));

		$submit = new Zend_Form_Element_Submit('submitmessage');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Send'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($content, $submit));

		$this->postInit();
	}

}

