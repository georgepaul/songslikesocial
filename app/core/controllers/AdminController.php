<?php

/**
 * Admin Controller
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class AdminController extends Zend_Controller_Action
{

	/**
	 * Build settings menu
	 */
	protected function buildSettingsMenu()
	{
		$items = array(
			$this->view->translate('General') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'general'
			),
			$this->view->translate('Email Setup') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'mail'
			),
			$this->view->translate('File Storage') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'storage'
			),
			$this->view->translate('Logo') => array(
				'controller' => 'admin',
				'action' => 'logo',
				'section' => 'logo'
			),
			$this->view->translate('SEO & Custom Head') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'markup'
			),
			$this->view->translate('Banners') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'banner'
			),
			$this->view->translate('Background') => array(
				'controller' => 'admin',
				'action' => 'background',
				'section' => 'background'
			),
			$this->view->translate('Themes & Style') => array(
				'controller' => 'admin',
				'action' => 'styles',
				'section' => 'styles'
			),
			'divider1' => 'divider',
			$this->view->translate('Script Info') => array(
				'controller' => 'admin',
				'action' => 'settings',
				'section' => 'info'
			),
			
		);
		
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$section = $this->getRequest()->getParam('section', 'general');
		
		// find current active item
		foreach ($items as $key => &$value) {
			
			if ($value == 'divider')
				continue;
			
			if ($section == $value['section']) {
				$this->view->sidebar_nav_menu_active_item = $key;
			}
			
			$value = $this->_helper->url->url($value, 'default', true);
		}
		
		$this->view->sidebar_nav_menu = $items;
		$this->view->sidebar_nav_menu_class = 'admin-menu';
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/navmenu.phtml');
		});
	}

	/**
	 * Build addons menu
	 */
	protected function buildAddonsMenu()
	{
		$items_top = array();
		$items_bottom = array();
		
		// add addons
		$addons = glob(ADDONS_PATH . '/*');
		foreach ($addons as $addon) {
			$plugin_name = basename($addon);
			
			if (file_exists($addon . '/init.php')) {
				
				require_once $addon . '/init.php';
				
				$add = array(
					'controller' => 'admin',
					'action' => 'addons',
					'section' => $plugin_name
				);
				
				if ($plugin_name[0] == '_') {
					$items_bottom['_' . $name] = $add;
				} else {
					$items_top[$name] = $add;
				}
			}
		}
		
		// merge parts & dividers
		$items = array_merge($items_top, (! empty($items_bottom) ? array(
			'divider2' => 'divider'
		) : array()), $items_bottom);
		
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$section = $this->getRequest()->getParam('section');
		
		// find current active item
		foreach ($items as $key => &$value) {
			
			if ($value == 'divider')
				continue;
			
			if ($section == $value['section']) {
				$this->view->sidebar_nav_menu_active_item = $key;
			}
			
			$value = $this->_helper->url->url($value, 'default', true);
		}
		
		$this->view->sidebar_nav_menu = $items;
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/navmenu.phtml');
		});
	}

	/**
	 * Site settings
	 */
	public function settingsAction()
	{
		$request = $this->getRequest();
		
		$section = $request->getUserParam('section', 'general');
		$get_callback = $request->getUserParam('checkforupdates');
		
		$this->buildSettingsMenu();
		
		switch ($section) {
			
			case 'general':
				$settings_form = new Application_Form_SettingsGeneral();
				break;
			
			case 'mail':
				$settings_form = new Application_Form_SettingsMail();
				break;
			
			case 'storage':
				$settings_form = new Application_Form_SettingsStorage();
				break;
			
			case 'markup':
				$settings_form = new Application_Form_SettingsMarkup();
				break;
			
			case 'logo':
				$settings_form = new Application_Form_SettingsLogo();
				break;
			
			case 'banner':
				$settings_form = new Application_Form_SettingsBanners();
				break;
			
			default:
				$settings_form = new Application_Form_Settings();
				
				$this->view->callback_info = '';
				if ($get_callback) {
					// check for updates
					$client = new Zend_Http_Client('http://www.socialstrap.net/callback/?license=' . Zend_Registry::get('config')->get('license_code') . '&schemaver=' . Zend_Registry::get('config')->get('schema_version') . '&appver=' . APP_VERSION, array(
						'timeout' => 5
					));
					$response = $client->request();
					if ($response->isSuccessful()) {
						$this->view->callback_info = '<hr />' . $response->getBody();
					}
				}
				
				break;
		}
		
		$this->view->settings_form = $settings_form;
		
		$AppOptions = new Application_Model_AppOptions();
		$AppOptions->getAllOptions();
		
		if ($request->isPost()) {
			// Form Submitted...
			if ($settings_form->isValid($_POST)) {
				$elements = $settings_form->getElements();
				
				foreach ($elements as $element) {
					
					$element_id = $element->getId();
					
					if ($element_id == 'submitbtn' || $element_id == 'identifier')
						continue;
					
					$AppOptions->updateOption($element_id, $element->getValue());
				}
				
				Application_Plugin_Alerts::success($this->view->translate('Settings updated'));
				
				// flush url
				$this->redirect('admin/settings/section/' . $section);
			}
		}
	}

	/**
	 * Addons settings
	 */
	public function addonsAction()
	{
		$request = $this->getRequest();
		
		$addon = $request->getUserParam('section');
		$this->buildAddonsMenu();
		
		$script_path = ADDONS_PATH . '/' . $addon;
		
		// redirect if not exists
		if ($addon && ! is_readable($script_path . '/init.php')) {
			$this->redirect('');
		}
		
		// disable addon
		if ($request->getUserParam('disable')) {
			$dir_name = basename($script_path);
			rename($script_path, ADDONS_PATH . '/_' . $dir_name);
			
			// flush url
			$this->redirect('admin/addons/section/_' . $addon);
		}
		
		// enable addon
		if ($request->getUserParam('enable')) {
			
			rename($script_path, ADDONS_PATH . '/' . substr($addon, 1));
			
			// flush url
			$this->redirect('admin/addons/section/' . substr($addon, 1));
		}
		
		if ($addon) {
			require ADDONS_PATH . '/' . $addon . '/init.php';
			
			$this->view->is_disabled = ($addon[0] == '_' ? true : false);
			$this->view->addon = $addon;
			$this->view->title = $name;
			$this->view->version = $version;
			$this->render('partial/addons', null, true);
			
			if (is_readable($script_path . '/settings.php')) {
				$this->_helper->viewRenderer->setViewSuffix('php');
				$this->view->addScriptPath($script_path);
				
				$this->render('settings', null, true);
			}
		}
	}

	/**
	 * Remove profile
	 */
	public function removeprofileAction()
	{
		
		$request = $this->getRequest();
		$profile_id = $request->getParam('id', null);
		
		$Profiles = new Application_Model_Profiles();
		$profile = $Profiles->getProfileByField('id', $profile_id);
		
		$this->view->sidebar_editprofile = $profile;
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
		});
		
		$request = $this->getRequest();
		
		$form = new Application_Form_Confirm();
		
		$this->view->form = $form;
		
		if ($request->isPost() && $form->isValid($_POST) && $profile) {
			$Profiles->removeProfile($profile_id);
			
			Application_Plugin_Alerts::success($this->view->translate('Profile updated'));
			
			// flush url
			$this->redirect('search/users');
		}
	}
	

	/**
	 * Edit user
	 */
	public function userAction()
	{
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		
		$request = $this->getRequest();
		$profile_id = $request->getParam('id', null);
		
		$profile = $Profiles->getProfileByField('id', $profile_id);
		
		$this->view->sidebar_editprofile = $profile;
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
		});
		
		$edit_user_form = new Application_Form_AdminUser();
		$this->view->edit_user_form = $edit_user_form;
		
		if ($request->isPost() && $profile_id && $edit_user_form->isValid($_POST)) {
			$elements = $edit_user_form->getElements();
			
			// standard db fields
			foreach ($elements as $element) {
				
				$element_id = $element->getId();
				
				// if column exists - save to main profiles table
				if (isset($profile->{$element_id})) {
					$profile->{$element_id} = $element->getValue();
				}
			}
			
			// specific fields
			if ($edit_user_form->getValue('password1')) {
				
				$hash = new Application_Plugin_Phpass();
				$profile->password = $hash->HashPassword($edit_user_form->getValue('password1'));
			}
			
			$profile->relogin_request = 1;
			$profile->save();
			
			// notifications
			$bulk_notifications = array();
			foreach ($elements as $element) {
				
				$element_id = $element->getId();
				
				if (strstr($element_id, 'notification_email') !== false) {
					$bulk_notifications[$element_id] = $element->getValue();
				}
			}
			$ProfilesMeta->metaUpdate('bulk_notifications', json_encode($bulk_notifications), $profile->id);
			
			
			// save all the rest to meta
			$elements = $edit_user_form->getElements();
				
			$system_elements = array(
				'identifier',
				'formsubmit',
				'profile_privacy',
				'default_privacy',
				'screen_name',
				'language',
				'password1',
				'password2',
				'activationkey',
				'is_hidden',
				'csrf',
				'role',
				'name',
				'email',
				'id',
			);
				
			// foreach meta elements
			foreach ($elements as $element) {
				
				
				$element_id = $element->getId();
				$element_value = $element->getValue();
				
				// skip system & readonly fields
				if (in_array($element_id, $system_elements)) continue;
				
				// skip notifications
				if (strstr($element_id, 'notification_email') !== false) continue;
				
				// custom date element?
				if ($element->helper == 'formDate') {
						
					if ($element_value) {
						$dateval = date("Y-m-d H:i:s", strtotime($element_value['day'] . '-' . $element_value['month'] . '-' . $element_value['year']));
						$ProfilesMeta->metaUpdate($element_id, $dateval, $profile->id);
					} else {
						$ProfilesMeta->deleteProfilesMetaKey($profile->id, $element_id);
					}
						
					continue;
				}
				$ProfilesMeta->metaUpdate($element_id, $element_value, $profile->id);
			}
			
			Application_Plugin_Alerts::success($this->view->translate('User updated'));
			
			// flush url
			$this->redirect('admin/user/id/' . $profile_id);
		}
	}

	/**
	 * Edit group
	 */
	public function groupAction()
	{
		$Profiles = new Application_Model_Profiles();
		
		$request = $this->getRequest();
		$profile_id = $request->getParam('id', null);
		
		$profile = $Profiles->getProfileByField('id', $profile_id);
		
		$this->view->sidebar_editprofile = $profile;
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
		});
		
		$edit_form = new Application_Form_AdminGroup();
		$this->view->edit_form = $edit_form;
		
		if ($request->isPost() && $profile_id && $edit_form->isValid($_POST)) {
			$owner_profile = $Profiles->getProfileByField('name', $edit_form->getValue('owner'));
			
			$profile->owner = $owner_profile->id;
			$profile->name = $edit_form->getValue('name');
			$profile->screen_name = $edit_form->getValue('screen_name');
			$profile->profile_privacy = $edit_form->getValue('profile_privacy');
			$profile->is_hidden = $edit_form->getValue('is_hidden');
			
			$profile->save();
			
			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $edit_form->getValue('description'), $profile_id);
			$ProfilesMeta->metaUpdate('badges', $edit_form->getValue('badges'), $profile_id);
			
			Application_Plugin_Alerts::success($this->view->translate('Group updated'));
			
			// flush url
			$this->redirect('admin/group/id/' . $profile_id);
		}
	}

	/**
	 * Edit page
	 */
	public function pageAction()
	{
		$Profiles = new Application_Model_Profiles();
		
		$request = $this->getRequest();
		$profile_id = $request->getParam('id', null);
		
		$profile = $Profiles->getProfileByField('id', $profile_id);
		
		$this->view->sidebar_editprofile = $profile;
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
		});
		
		$edit_form = new Application_Form_AdminPage();
		$this->view->edit_form = $edit_form;
		
		if ($request->isPost() && $profile_id && $edit_form->isValid($_POST)) {
			$owner_profile = $Profiles->getProfileByField('name', $edit_form->getValue('owner'));
			
			$profile->owner = $owner_profile->id;
			$profile->name = $edit_form->getValue('name');
			$profile->screen_name = $edit_form->getValue('screen_name');
			$profile->is_hidden = $edit_form->getValue('is_hidden');
			
			$profile->save();
			
			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $edit_form->getValue('description'), $profile_id);
			$ProfilesMeta->metaUpdate('badges', $edit_form->getValue('badges'), $profile_id);
			
			Application_Plugin_Alerts::success($this->view->translate('Page updated'));
			
			// flush url
			$this->redirect('admin/page/id/' . $profile_id);
		}
	}

	/**
	 * Change background picture
	 */
	public function backgroundAction()
	{
		$this->buildSettingsMenu();
		
		$request = $this->getRequest();
		
		$form = new Application_Form_SettingsBackground();
		
		$this->view->image = Application_Plugin_Common::getFullBaseUrl() . '/images/' . Zend_Registry::get('config')->get('background');
		$this->view->form = $form;
		
		$this->view->load_colorpicker = true;
		
		if ($request->isPost() && $form->isValid($_POST)) {
			$AppOptions = new Application_Model_AppOptions();
			$AppOptions->updateOption('background_color', $form->getValue('background_color'));
			$AppOptions->updateOption('background_repeat', $form->getValue('background_repeat'));
			$AppOptions->updateOption('background_scroll', $form->getValue('background_scroll'));
			$AppOptions->updateOption('background_stretch', $form->getValue('background_stretch'));
			$AppOptions->updateOption('background_noimage', $form->getValue('background_noimage'));
			
			Application_Plugin_Alerts::success($this->view->translate('Settings updated, please clear your browser cache'), 'off');
		}
		
		// image processing helper
		$this->_helper->imageProcessing('background', false, $form, 'background', false);
	}
	

	/**
	 * Change user's background picture
	 */
	public function usersbackgroundAction()
	{
		return $this->_forward('setbackgroundpicture', 'editprofile');
	}
	
	
	/**
	 * Change logo picture
	 */
	public function logoAction()
	{
		$this->buildSettingsMenu();
		
		$request = $this->getRequest();
		
		$form = new Application_Form_SettingsLogo();
		
		$this->view->image = Application_Plugin_Common::getFullBaseUrl() . '/images/' . Zend_Registry::get('config')->get('logo_image');
		$this->view->form = $form;
		
		// image processing helper
		$this->_helper->imageProcessing('logo_image', false, $form, 'logo_image', false);
		
		if ($request->isPost() && $form->isValid($_POST)) {
			if ($form->getValue('logo_noimage')) {
				$AppOptions = new Application_Model_AppOptions();
				$AppOptions->removeMeta('logo_image');
			}
			
			Application_Plugin_Alerts::success($this->view->translate('Settings updated, please clear your browser cache'), 'off');
			
			// flush url
			$this->redirect('admin/logo/section/logo/');
		}
	}

	/**
	 * Theme & style
	 */
	public function stylesAction()
	{
		$this->buildSettingsMenu();
		
		$request = $this->getRequest();
		
		$form = new Application_Form_SettingsStyle();
		$this->view->form = $form;
		
		if ($request->isPost() && $form->isValid($_POST)) {
			$AppOptions = new Application_Model_AppOptions();
			$AppOptions->updateOption('css_theme', $form->getValue('css_theme'));
			$AppOptions->updateOption('css_custom', $form->getValue('css_custom'));
			$AppOptions->updateOption('cover_ysize', $form->getValue('cover_ysize'));
			$AppOptions->updateOption('user_background', $form->getValue('user_background'));
			$AppOptions->updateOption('subscriber_background', $form->getValue('subscriber_background'));
			$AppOptions->updateOption('wide_layout', $form->getValue('wide_layout'));
			
			Application_Plugin_Alerts::success($this->view->translate('Settings updated, please clear your browser cache'), 'off');
			
			// flush url
			$this->redirect('admin/styles/section/styles/');
		}
	}
}