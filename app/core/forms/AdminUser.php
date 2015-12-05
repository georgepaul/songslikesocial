<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_AdminUser extends Application_Form_Main
{

	/**
	 *
	 * Edit User (admin only)
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/AdminUser.phtml'))));

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request_profile_id = $request->getParam('id');

		// get user from database
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$profile = $Profiles->getProfileByField('id', $request_profile_id);
		$all_meta = $ProfilesMeta->getMetaValues($profile->id);

		if (isset($all_meta['bulk_notifications'])){
			$notifications_meta = json_decode($all_meta['bulk_notifications'], true);
		}

		// fields

		$role = new Zend_Form_Element_Select('role');
		$role
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(array('user' => 'User', 'subscriber' => 'Subscriber', 'reviewer' => 'Reviewer', 'admin' => 'Admin'))
		->setErrorMessages(array($this->translator->translate('User Role is requiered')))
		->setLabel($this->translator->translate('User Role'))
		->setRequired(true)
		->setValue($profile->role)
		->setAttrib('class', 'form-control');

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
		->setLabel($this->translator->translate('Name'));

		$email = new Zend_Form_Element_Text('email');
		$email
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Email'))
		->setValue($profile->email)
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
		->setValue(isset($all_meta['description']) ? $all_meta['description'] : '')
		->setLabel($this->translator->translate('Description'))
		->setAttrib('class', 'form-control');

		$profile_privacy = new Zend_Form_Element_Select('profile_privacy');
		$profile_privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('profile_privacy_all'))
		->setErrorMessages(array($this->translator->translate('Select profile visibility')))
		->setLabel($this->translator->translate('Profile visibility'))
		->setRequired(true)
		->setValue($profile->profile_privacy)
		->setAttrib('class', 'form-control');

		$default_privacy = new Zend_Form_Element_Select('default_privacy');
		$default_privacy
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('post_privacy_array'))
		->setLabel($this->translator->translate('Default visibility'))
		->setRequired(true)
		->setValue($profile->default_privacy)
		->setAttrib('class', 'form-control');

		$language = new Zend_Form_Element_Select('language');
		$language
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions(Zend_Registry::get('languages_array'))
		->setLabel($this->translator->translate('Language'))
		->setRequired(true)
		->setValue($profile->language)
		->setAttrib('class', 'form-control');

		$birthday = new Application_Form_Element_Date('birthday');
		$birthday
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Date of birth'))
		->setErrorMessages(array($this->translator->translate('Please enter a valid date')));
		$birthday->setYearSpan(1920, date('Y')-1);
		if (isset($all_meta['birthday'])){
			$timestamp = strtotime($all_meta['birthday']);
			$birthday->setValue(array('day' => date('d', $timestamp), 'month' => date('m', $timestamp), 'year' => date('Y', $timestamp)));
		}

		$password1 = new Zend_Form_Element_Password('password1');
		$password1
		->setDecorators(array('ViewHelper', 'Errors'))
		->setAttrib('autocomplete', 'off')
		->setLabel($this->translator->translate('New Password:'))
		->setAttrib('class', 'form-control');

		$activation = new Zend_Form_Element_Text('activationkey');
		$activation
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Activation key (or "activated")'))
		->setValue($profile->activationkey)
		->setAttrib('class', 'form-control');

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

		$badges = new Zend_Form_Element_Text('badges');
		$badges
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Badges based on Glyphicon font separated by comma (e.g. "bullhorn,earphone")'))
		->setValue(isset($all_meta['badges']) ? $all_meta['badges'] : '')
		->setAttrib('class', 'form-control');


		$n1 = new Zend_Form_Element_Checkbox('notification_email_1');
		$n1
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_1']) && $notifications_meta['notification_email_1'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone posts a new comment'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n2 = new Zend_Form_Element_Checkbox('notification_email_2');
		$n2
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_2']) && $notifications_meta['notification_email_2'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone likes your post'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n3 = new Zend_Form_Element_Checkbox('notification_email_3');
		$n3
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_3']) && $notifications_meta['notification_email_3'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone follows you'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n4 = new Zend_Form_Element_Checkbox('notification_email_4');
		$n4
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_4']) && $notifications_meta['notification_email_4'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email on new friends'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n6 = new Zend_Form_Element_Checkbox('notification_email_6');
		$n6
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_6']) && $notifications_meta['notification_email_6'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when you lose a follower'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n7 = new Zend_Form_Element_Checkbox('notification_email_7');
		$n7
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_7']) && $notifications_meta['notification_email_7'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone posts on your wall'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$n8 = new Zend_Form_Element_Checkbox('notification_email_8');
		$n8
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($notifications_meta['notification_email_8']) && $notifications_meta['notification_email_8'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Email when someone sends you a private message'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

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
				$role,
				$name,
				$email,
				$screenname,
				$description,
				$profile_privacy,
				$default_privacy,
				$language,
				$gender,
				$online_status,
				$contact_privacy,
				$location,
				$website,
				$birthday,
				$password1,
				$activation,
				$badges,
				$n1,
				$n2,
				$n3,
				$n4,
				$n6,
				$n7,
				$n8,
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

		$this->getElement('email')
		->addValidator(
				'Db_NoRecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'email',
						'exclude' => array(
								'field' => 'id',
								'value' => $data['id']
						)
				)
		)
		->setErrorMessages(array($this->translator->translate('This email is already in use')));

		return parent::isValid($data);
	}

}

