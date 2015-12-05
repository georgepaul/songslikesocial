<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_AddComment extends Application_Form_Main
{

	/**
	 *
	 * Add comment
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), true, false);
	
		// hidden resource type identifier
		$resource_type = new Zend_Form_Element_Hidden('resource_type');
		$resource_type
		->setDecorators(array('ViewHelper'));

		// hidden resource id identifier
		$resource_id = new Zend_Form_Element_Hidden('resource_id');
		$resource_id
		->setDecorators(array('ViewHelper'));

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/AddComment.phtml'))));

		$this->setAction(Zend_Controller_Front::getInstance()->getBaseUrl() . '/comments/postcomment');

		// fields

		$comment = new Zend_Form_Element_Text('comment');
		$comment
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StripTags')
		->setRequired(true)
		->setAttrib('autocomplete', 'off')
		->setAttrib('class', 'form-control')
		->setAttrib('placeholder', $this->translator->translate('Write a comment...'));


		$submit = new Zend_Form_Element_Submit('submitcomment');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Post'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array($resource_type, $resource_id, $comment, $submit));

		$this->postInit();
	}

}

