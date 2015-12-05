<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_AdminGroup extends Application_Form_Main
{

	/**
	 *
	 * Edit Group form (admin only)
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/AdminGroup.phtml'))));

		// get group from database
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request_profile_id = $request->getParam('id');

		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();

		$profile = $Profiles->getProfileByField('id', $request_profile_id);
		$owners_profile = $Profiles->getProfileByField('id', $profile->owner);

		// fields

		$profile_id = new Zend_Form_Element_Text('id');
		$profile_id
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Id'))
		->setValue($profile->id)
		->setIgnore(true)
		->setAttrib('readonly',true)
		->setAttrib('class', 'form-control');

		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');

		// lowercase, alnum without whitespaces
		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setRequired(true)
		->addFilter('StringToLower')
		->addValidator('alnum', false, array('allowWhiteSpace' => false))
		->addValidator('stringLength', false, array($username_minchars, $username_maxchars))
		->setErrorMessages(array(sprintf($this->translator->translate('Please choose a valid username between %d and %d characters'), $username_minchars, $username_maxchars)))
		->setAttrib('class', 'form-control alnum-only')
		->setValue($profile->name)
		->setLabel($this->translator->translate('Username'));

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

		$owner_name = (isset($owners_profile->name) ? $owners_profile->name : '-');

		$owner = new Zend_Form_Element_Text('owner');
		$owner
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Owner').' ('.$this->translator->translate('Current').': '.$owner_name.')')
		->setValue($owner_name)
		->setRequired(true)
		->setAttrib('class', 'form-control');

		$badges = new Zend_Form_Element_Text('badges');
		$badges
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Badges based on Glyphicon font separated by comma (e.g. "bullhorn,earphone")'))
		->setValue($ProfilesMeta->getMetaValue('badges', $profile->id))
		->setAttrib('class', 'form-control');
		
		$description = new Zend_Form_Element_Textarea('description');
		$description
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('COLS', '')
		->setAttrib('ROWS', '4')
		->addFilter('StripTags')
		->setValue($ProfilesMeta->getMetaValue('description', $profile->id))
		->setLabel($this->translator->translate('About this group'))
		->setAttrib('class', 'form-control');

		$profile_privacy = new Zend_Form_Element_Select('profile_privacy');
		$profile_privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('group_privacy_all'))
		->setErrorMessages(array($this->translator->translate('Select group visibility')))
		->setLabel($this->translator->translate('Select group visibility'))
		->setRequired(true)
		->setValue($profile->profile_privacy)
		->setAttrib('class', 'form-control');

		$is_hidden = new Zend_Form_Element_Checkbox('is_hidden');
		$is_hidden
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($profile->is_hidden) && $profile->is_hidden == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Hide?'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$profile_id,
				$owner,
				$name,
				$screenname,
				$profile_privacy,
				$badges,
				$description,
				$is_hidden,
				$submit));


		$this->postInit();
	}


	/**
	 *
	 * unique validator
	 */
	public function isValid($data)
	{
		// return on false to see previous errors
		if (parent::isValid($data) == false) return false;

		

		$this->getElement('name')
		->addValidator(
				'Db_NoRecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'name',
						'exclude' => array(
								'field' => 'id',
								'value' => $data['id']
						)
				)
		)
		->setErrorMessages(array($this->translator->translate('This is already taken')));

		$this->getElement('owner')
		->addValidator(
				'Db_RecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'name',
				)
		)
		->setErrorMessages(array($this->translator->translate('Choose existing profile name')));

		return parent::isValid($data);
	}

}

