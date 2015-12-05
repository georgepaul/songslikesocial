<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Profile extends Application_Form_Main
{

	/**
	 *
	 * Edit Profile form
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Profile.phtml'))));

		// get user from database
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();

		$profile = $Profiles->getProfile(Zend_Auth::getInstance()->getIdentity()->name, true);
		$all_meta = $ProfilesMeta->getMetaValues($profile->id);

		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');
		
		// fields

		$id = new Zend_Form_Element_Hidden('id');
		$id
		->setValue($profile->id);

		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Username'))
		->setValue($profile->name)
		->setIgnore(true)
		->setAttrib('readonly',true)
		->setAttrib('class', 'form-control');

		$email = new Zend_Form_Element_Text('email');
		$email
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Email'))
		->setValue($profile->email)
		->setIgnore(true)
		->setAttrib('readonly', true)
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
		->setValue(isset($all_meta['description']) ? $all_meta['description'] : '')
		->setLabel($this->translator->translate('About you'))
		->setAttrib('class', 'form-control');

		$profile_privacy = new Zend_Form_Element_Select('profile_privacy');
		$profile_privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('profile_privacy_array'))
		->setErrorMessages(array($this->translator->translate('Select profile visibility')))
		->setLabel($this->translator->translate('Profile visibility'))
		->setRequired(true)
		->setValue($profile->profile_privacy)
		->setAttrib('class', 'form-control');

		$birthday = new Application_Form_Element_Date('birthday');
		$birthday
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('class', 'form-control')
		->setLabel($this->translator->translate('Date of birth'))
		->setErrorMessages(array($this->translator->translate('Please enter a valid date')))
		->setYearSpan(1920, date('Y')-1);
		if (isset($all_meta['birthday'])){
			$timestamp = strtotime($all_meta['birthday']);
			$birthday->setValue(array('day' => date('d', $timestamp), 'month' => date('m', $timestamp), 'year' => date('Y', $timestamp)));
		}

		$gender = new Zend_Form_Element_Select('gender');
		$gender
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('genders_array'))
		->setErrorMessages(array($this->translator->translate('Please select something')))
		->setLabel($this->translator->translate('Gender'))
		->setRequired(true)
		->setValue(isset($all_meta['gender']) ? $all_meta['gender'] : '')
		->setAttrib('class', 'form-control');

		$online_status = new Zend_Form_Element_Select('show_online_status');
		$online_status
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('onlinestatus_array'))
		->setErrorMessages(array($this->translator->translate('Select profile visibility')))
		->setLabel($this->translator->translate('Online Status'))
		->setRequired(true)
		->setValue(isset($all_meta['show_online_status']) ? $all_meta['show_online_status'] : 's')
		->setAttrib('class', 'form-control');
		
		$contact_privacy = new Zend_Form_Element_Select('contact_privacy');
		$contact_privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('contactprivacy_array'))
		->setErrorMessages(array($this->translator->translate('Please select something')))
		->setLabel($this->translator->translate('Who can contact me?'))
		->setRequired(true)
		->setValue(isset($all_meta['contact_privacy']) ? $all_meta['contact_privacy'] : 'e')
		->setAttrib('class', 'form-control');

		$location = new Zend_Form_Element_Text('location');
		$location
		->setDecorators(array('ViewHelper', 'Errors'))
		->setRequired(false)
		->setLabel($this->translator->translate('Location'))
		->setAttrib('class', 'form-control')
		->addFilter('StripTags')
		->setValue(isset($all_meta['location']) ? $all_meta['location'] : '')
		->setErrorMessages(array($this->translator->translate('Enter a valid location')));

		$website = new Zend_Form_Element_Text('website');
		$website
		->setDecorators(array('ViewHelper', 'Errors'))
		->setRequired(false)
		->setLabel($this->translator->translate('Website'))
		->setAttrib('class', 'form-control')
		->addFilter('StripTags')
		->setValue(isset($all_meta['website']) ? $all_meta['website'] : '')
		->setErrorMessages(array($this->translator->translate('Enter a valid website')));

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default form-control');

		$this->addElements(array(
				$id,
				$name,
				$email,
				$screenname,
				$gender,
				$profile_privacy,
				$online_status,
				$contact_privacy,
				$description,
				$location,
				$website,
				$birthday,
				$submit));
		
		$this->postInit();
	}

}

