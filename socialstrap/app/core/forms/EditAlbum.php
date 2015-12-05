<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_EditAlbum extends Application_Form_Main
{

	/**
	 *
	 * Edit Album form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/EditAlbum.phtml'))));

		// get group from database
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$album_id = $request->getParam('id');

		$Albums = new Application_Model_Albums();

		$album = $Albums->getAlbum($album_id);

		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');
		
		// fields

		$id = new Zend_Form_Element_Hidden('id');
		$id
		->setValue($album);

		$album_name = new Zend_Form_Element_Text('album_name');
		$album_name
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StringTrim')
		->addValidator('alnum', false, array('allowWhiteSpace' => true))
		->addValidator('stringLength', false, array($username_minchars, $username_maxchars))
		->setErrorMessages(array(sprintf($this->translator->translate('Please choose a valid name between %d and %d characters'), $username_minchars, $username_maxchars)))
		->setLabel($this->translator->translate('Album Name'))
		->setRequired(true)
		->setValue($album['name'])
		->setAttrib('class', 'form-control');

		$description = new Zend_Form_Element_Textarea('description');
		$description
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setValue($album['description'])
		->setLabel($this->translator->translate('About this album'))
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$id,
				$album_name,
				$description,
				$submit));

		$this->postInit();
	}

}

