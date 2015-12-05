<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsGeneral extends Application_Form_Main
{

	/**
	 *
	 * General settings
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Settings.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields
		$limit_posts = new Zend_Form_Element_Text('limit_posts');
		$limit_posts
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max posts per page'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['limit_posts']) ? $all_meta['limit_posts'] : '5')
		->setAttrib('class', 'form-control');
		
		$max_post_length = new Zend_Form_Element_Text('max_post_length');
		$max_post_length
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max post length'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_post_length']) ? $all_meta['max_post_length'] : '2000')
		->setAttrib('class', 'form-control');

		$limit_comments = new Zend_Form_Element_Text('limit_comments');
		$limit_comments
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Number of visible comments per resource'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['limit_comments']) ? $all_meta['limit_comments'] : '3')
		->setAttrib('class', 'form-control');

		$pagination_limit = new Zend_Form_Element_Text('pagination_limit');
		$pagination_limit
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Pagination limit'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['pagination_limit']) ? $all_meta['pagination_limit'] : '10')
		->setAttrib('class', 'form-control');

		$max_scroll_fetches = new Zend_Form_Element_Text('max_scroll_fetches');
		$max_scroll_fetches
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Maximum fetches on infinite scroll (0 = no limit)'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_scroll_fetches']) ? $all_meta['max_scroll_fetches'] : '0')
		->setAttrib('class', 'form-control');
		
		$languages_array = array_merge(array('' => ''), Zend_Registry::get('languages_array'));
		$default_language = new Zend_Form_Element_Select('default_language');
		$default_language
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions($languages_array)
		->setLabel($this->translator->translate('Choose default language'))
		->setRequired(true)
		->setValue(isset($all_meta['default_language']) ? $all_meta['default_language'] : '')
		->setAttrib('class', 'form-control');

		$username_minchars = new Zend_Form_Element_Text('username_minchars');
		$username_minchars
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Min chars for username'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['username_minchars']) ? $all_meta['username_minchars'] : '5')
		->setAttrib('class', 'form-control');

		$username_maxchars = new Zend_Form_Element_Text('username_maxchars');
		$username_maxchars
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max chars for username'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['username_maxchars']) ? $all_meta['username_maxchars'] : '20')
		->setAttrib('class', 'form-control');

		$sidebar_max_users = new Zend_Form_Element_Text('sidebar_max_users');
		$sidebar_max_users
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max users to show on sidebar boxes'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['sidebar_max_users']) ? $all_meta['sidebar_max_users'] : '3')
		->setAttrib('class', 'form-control');

		$user_manage_groups = new Zend_Form_Element_Checkbox('user_manage_groups');
		$user_manage_groups
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['user_manage_groups']) && $all_meta['user_manage_groups'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('User can manage groups'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$user_manage_pages = new Zend_Form_Element_Checkbox('user_manage_pages');
		$user_manage_pages
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['user_manage_pages']) && $all_meta['user_manage_pages'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('User can manage pages'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$disable_groups_pages = new Zend_Form_Element_Checkbox('disable_groups_pages');
		$disable_groups_pages
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['disable_groups_pages']) && $all_meta['disable_groups_pages'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable groups and pages feature (only admin can manage them)'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$user_activation_disabled = new Zend_Form_Element_Checkbox('user_activation_disabled');
		$user_activation_disabled
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['user_activation_disabled']) && $all_meta['user_activation_disabled'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable user activation after registration'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$allow_addons = new Zend_Form_Element_Checkbox('allow_addons');
		$allow_addons
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['allow_addons']) && $all_meta['allow_addons'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Load addons'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$disable_ajax_validator = new Zend_Form_Element_Checkbox('disable_ajax_validator');
		$disable_ajax_validator
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['disable_ajax_validator']) && $all_meta['disable_ajax_validator'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable Ajax form validation'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$newuser_notify_email = new Zend_Form_Element_Text('newuser_notify_email');
		$newuser_notify_email
		->addValidator('EmailAddress', true)
		->addFilter('StringToLower')
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('When new user signs up send notification to this email'))
		->setValue(isset($all_meta['newuser_notify_email']) ? $all_meta['newuser_notify_email'] : '')
		->setAttrib('class', 'form-control');
		
		$report_notify_email = new Zend_Form_Element_Text('report_notify_email');
		$report_notify_email
		->addValidator('EmailAddress', true)
		->addFilter('StringToLower')
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('When resource is reported send notification to this email'))
		->setValue(isset($all_meta['report_notify_email']) ? $all_meta['report_notify_email'] : '')
		->setAttrib('class', 'form-control');
		
		$auto_follow_users = new Zend_Form_Element_Text('auto_follow_users');
		$auto_follow_users
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Upon registration follow these users (usernames separated by comma)'))
		->setValue(isset($all_meta['auto_follow_users']) ? $all_meta['auto_follow_users'] : '')
		->setAttrib('class', 'form-control');

		$heartbeatfreq = new Zend_Form_Element_Text('heartbeatfreq');
		$heartbeatfreq
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Notification freqency in seconds (heartbeat)'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['heartbeatfreq']) ? $all_meta['heartbeatfreq'] : '5')
		->setAttrib('class', 'form-control');
		
		$session_lifetime = new Zend_Form_Element_Text('session_lifetime');
		$session_lifetime
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Session lifetime (Remember me) time in seconds. Set to 0 to use php server settings.'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['session_lifetime']) ? $all_meta['session_lifetime'] : '0')
		->setAttrib('class', 'form-control');

		$recaptcha_active = new Zend_Form_Element_Checkbox('recaptcha_active');
		$recaptcha_active
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['recaptcha_active']) && $all_meta['recaptcha_active'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Use ReCaptcha'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$recaptcha_privatekey = new Zend_Form_Element_Text('recaptcha_privatekey');
		$recaptcha_privatekey
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('ReCaptcha Private Key'))
		->setValue(isset($all_meta['recaptcha_privatekey']) ? $all_meta['recaptcha_privatekey'] : '123456789')
		->setAttrib('class', 'form-control');

		$recaptcha_publickey = new Zend_Form_Element_Text('recaptcha_publickey');
		$recaptcha_publickey
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('ReCaptcha Public Key'))
		->setValue(isset($all_meta['recaptcha_publickey']) ? $all_meta['recaptcha_publickey'] : '123456789')
		->setAttrib('class', 'form-control');

		$facebook_appid = new Zend_Form_Element_Text('facebook_appid');
		$facebook_appid
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Facebook app id'))
		->setValue(isset($all_meta['facebook_appid']) ? $all_meta['facebook_appid'] : '123456789')
		->setAttrib('class', 'form-control');

		$facebook_secret = new Zend_Form_Element_Text('facebook_secret');
		$facebook_secret
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Facebook secret'))
		->setValue(isset($all_meta['facebook_secret']) ? $all_meta['facebook_secret'] : '123456789')
		->setAttrib('class', 'form-control');

		$allow_guests = new Zend_Form_Element_Checkbox('allow_guests');
		$allow_guests
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['allow_guests']) && $all_meta['allow_guests'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Allow public posts so the guests can explore the site'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$default_language,
				$facebook_appid,
				$facebook_secret,
				$allow_addons,
				$user_manage_groups,
				$user_manage_pages,
				$disable_groups_pages,
				$user_activation_disabled,
				$allow_guests,
				$disable_ajax_validator,
				$limit_posts,
				$max_post_length,
				$limit_comments,
				$pagination_limit,
				$max_scroll_fetches,
				$newuser_notify_email,
				$report_notify_email,
				$auto_follow_users,
				$username_minchars,
				$username_maxchars,
				$sidebar_max_users,
				$heartbeatfreq,
				$session_lifetime,
				$recaptcha_active,
				$recaptcha_publickey,
				$recaptcha_privatekey,
				$submit));

		$this->postInit();
	}
	
}


