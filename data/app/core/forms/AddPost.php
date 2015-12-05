<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_AddPost extends Application_Form_Main
{

	public $show_privacy = true;

	/**
	 *
	 * Add new post
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);

		// show privacy
		$show_privacy = new Zend_Form_Element_Hidden('show_privacy');
		$show_privacy
		->setDecorators(array('ViewHelper'))
		->setValue($this->show_privacy);
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/AddPost.phtml'))));

		// fields
		$text = new Zend_Form_Element_Textarea('content');
		$text
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setAttrib('class', 'form-control')
		->setAttrib('placeholder', $this->translator->translate('What is on your mind?'));

		$submit = new Zend_Form_Element_Submit('submitbutton');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Post'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($text, $submit));
		
		$this->postInit();
	}

}

