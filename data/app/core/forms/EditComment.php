<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_EditComment extends Application_Form_Main
{

	/**
	 *
	 * Edit comment
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/EditComment.phtml'))));

		// fields

		$comment = new Zend_Form_Element_Textarea('comment');
		$comment
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setAttrib('class', 'form-control');


		$submit = new Zend_Form_Element_Submit('submitcomment');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($comment, $submit));

		$this->postInit();
	}

}

