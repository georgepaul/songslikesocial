<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_EditPage extends Application_Form_Main
{

	/**
	 *
	 * Edit Page form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/EditPage.phtml'))));

		// get group from database
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$group = $request->getParam('name');

		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();

		$profile = $Profiles->getProfile($group, false, true);
		$owners_profile = $Profiles->getProfileByField('id', $profile->owner);

		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');
		
		// fields

		$id = new Zend_Form_Element_Hidden('id');
		$id
		->setValue($profile->id);

		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Page Name'))
		->setValue($profile->name)
		->setIgnore(true)
		->setAttrib('readonly',true)
		->setAttrib('class', 'form-control');

		$screenname = new Zend_Form_Element_Text('screen_name');
		$screenname
		->setDecorators(array('ViewHelper', 'Errors'))
		->addFilter('StringTrim')
		->setValue($profile->screen_name)
		->addValidator('alnum', false, array('allowWhiteSpace' => true))
		->addValidator('stringLength', false, array($username_minchars, $username_maxchars))
		->setErrorMessages(array(sprintf($this->translator->translate('Please choose a valid name between %d and %d characters'), $username_minchars, $username_maxchars)))
		->setLabel($this->translator->translate('Screen Name'))
		->setRequired(true)
		->setAttrib('class', 'form-control');

		$description = new Zend_Form_Element_Textarea('description');
		$description
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setValue($ProfilesMeta->getMetaValue('description', $profile->id))
		->setLabel($this->translator->translate('About this page'))
		->setAttrib('class', 'form-control');

		$is_hidden = new Zend_Form_Element_Checkbox('is_hidden');
		$is_hidden
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($profile->is_hidden) && $profile->is_hidden == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Remove?'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$id,
				$name,
				$screenname,
				$description,
				$is_hidden,
				$submit));

		$this->postInit();
	}

}

