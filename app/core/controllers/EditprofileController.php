<?php

/**
 * Edit Profile Controller
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class EditProfileController extends Zend_Controller_Action
{

	/**
	 * Build menu
	 */
	protected function buildMenu($group_pages = false)
	{
		$acl = new Application_Plugin_AccessCheck();
		
		$items = array();
		
		if (!$group_pages) {
			
			$items = array(
				$this->view->translate('General Info') => array(
					'controller' => 'editprofile',
					'action' => 'edit'
				),
				$this->view->translate('Change Profile Picture') => array(
					'controller' => 'editprofile',
					'action' => 'setprofilepicture'
				),
				$this->view->translate('Change Cover Picture') => array(
					'controller' => 'editprofile',
					'action' => 'setcoverpicture'
				),
			);
			
			
			if ($acl->acl->isAllowed(Zend_Registry::get('role'), 'editprofile/setbackgroundpicture')) {
				$items = array_merge($items, array(
					$this->view->translate('Background') => array(
						'controller' => 'editprofile',
						'action' => 'setbackgroundpicture'
					),
				));
			}
			
			
			$items = array_merge($items, array(
				$this->view->translate('Change Password') => array(
					'controller' => 'editprofile',
					'action' => 'changepassword'
				),
				$this->view->translate('Notifications') => array(
					'controller' => 'editprofile',
					'action' => 'changenotifications'
				)
			));

			if ($acl->acl->isAllowed(Zend_Registry::get('role'), 'managegroups') || $acl->acl->isAllowed(Zend_Registry::get('role'), 'managepages')) {
				$items = array_merge($items, array(
					'divider1' => 'divider',
				));
			}
	
		}

		if ($acl->acl->isAllowed(Zend_Registry::get('role'), 'managegroups')) {
			$items = array_merge($items, array(
					$this->view->translate('My Groups') => array(
							'controller' => 'editprofile',
							'action' => 'listgroups'
					),
			));
		}
		
		if ($acl->acl->isAllowed(Zend_Registry::get('role'), 'managepages')) {
			$items = array_merge($items, array(
				$this->view->translate('My Pages') => array(
					'controller' => 'editprofile',
					'action' => 'listpages'
				)
			));
		}

		if (!$group_pages) {
			
			$items = array_merge($items, array(
				'divider2' => 'divider',
				$this->view->translate('Close Account') => array(
					'controller' => 'editprofile',
					'action' => 'closeaccount'
				)
			));
		}


		$akeys = array_keys($items);

		$is_group_action = $this->getRequest()->getParam('groupaction');
		$is_page_action = $this->getRequest()->getParam('pageaction');

		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();

		// find current active item
		foreach ($items as $key => &$value) {

			if ($value === 'divider')
				continue;

			if ($is_group_action) {
				$this->view->sidebar_nav_menu_active_item = $this->view->translate('My Groups');
				$value = $this->_helper->url->url($value, 'default', true);
				continue;
			}

			if ($is_page_action) {
				$this->view->sidebar_nav_menu_active_item = $this->view->translate('My Pages');
				$value = $this->_helper->url->url($value, 'default', true);
				continue;
			}

			if ($controller == $value['controller'] && $action == $value['action']) {
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
	 * Edit profile
	 */
	public function editAction()
	{
		$Profiles = new Application_Model_Profiles();

		$this->buildMenu();

		$profile_form = new Application_Form_Profile();
		$this->view->profile_form = $profile_form;

		$request = $this->getRequest();

		if ($request->isPost() && $profile_form->isValid($_POST)) {
			Application_Plugin_Common::redirectOnDemoAccount();

			$profile = $Profiles->getProfileRow();

			// do not foreach this!
			$profile->screen_name = $profile_form->getValue('screen_name');
			$profile->profile_privacy = $profile_form->getValue('profile_privacy');
			$profile->save();

			$ProfilesMeta = new Application_Model_ProfilesMeta();
			
			$elements = $profile_form->getElements();
			
			$system_elements = array(
				'identifier',
				'formsubmit',
				'profile_privacy',
				'screen_name',
				'csrf',
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
			
			Application_Plugin_Alerts::success($this->view->translate('Profile updated'));

			// refresh user session
			Zend_Auth::getInstance()->getStorage()->write($Profiles->getProfileRowObject());

			// flush url
			$this->redirect('editprofile/edit');
		}
	}

	/**
	 * Change password
	 */
	public function changepasswordAction()
	{
		$request = $this->getRequest();

		$Profiles = new Application_Model_Profiles();

		if (Zend_Auth::getInstance()->hasIdentity()) {
			$profile = $Profiles->getProfileByField('id', Zend_Auth::getInstance()->getIdentity()->id);
		}

		// Redirect if bad or no user
		if (! isset($profile) || ! $profile) {
			$this->redirect('');
		}

		$this->buildMenu();

		$changepassword_form = new Application_Form_ChangePassword();
		$this->view->changepassword_form = $changepassword_form;

		// Form Submitted...
		if ($request->isPost() && $changepassword_form->isValid($_POST)) {

			Application_Plugin_Common::redirectOnDemoAccount();

			// if regular pw update check for old pw
			$hash = new Application_Plugin_Phpass();
			$old_password = $changepassword_form->getValue('passwordold');

			// old password checks
			$check = false;

			// pass when old password is blank (user from facebook registration)
			if ($profile->password == '') {
				$check = true;
			}
			
			// try with md5
			if (is_string($old_password) && md5($old_password) == $profile->password) {
				$check = true;
			}

			// Check that hash value is correct
			if (is_string($old_password) && $hash->CheckPassword($old_password, $profile->password)) {
				$check = true;
			}

			if (! $check) {
				$changepassword_form->getElement('passwordold')->setErrors(array(
						Zend_Registry::get('Zend_Translate')->translate('Enter your password')
				));
				return;
			}

			// old password is ok, proceed...

			$newpassword = $changepassword_form->getValue('password2');

			$hash = new Application_Plugin_Phpass();
			$hashed_password = $hash->HashPassword($newpassword);

			$Profiles->updateField($profile->name, 'password', $hashed_password);

			Application_Plugin_Alerts::success($this->view->translate('Password updated'));
			
			// prepare phtml email template
			$mail_template_path = APPLICATION_PATH . '/views/emails/';
			$view = new Zend_View();
			$view->setScriptPath($mail_template_path);
			$body = $view->render('passwordnotice.phtml');
			
			// send email as a security measure
			$ret = Application_Plugin_Common::sendEmail($profile->email, $this->view->translate('Password updated'), $body, true);
		}
	}


	/**
	 * Change password with recover key
	 */
	public function recoverpasswordAction()
	{
		$this->_helper->_layout->setLayout('layout_wide');
		
		$request = $this->getRequest();

		// Get password change key if any
		$key = $request->getParam('key', false);

		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();

		if ($key) {

			$form = new Application_Form_ChangeForgottenPassword();

			$profile_id = $ProfilesMeta->getProfileId('password_reset', $key);

			if ($profile_id) {
				$profile = $Profiles->getProfileByField('id', $profile_id);
			}
		}

		// Redirect if bad or no user
		if (! $key || ! isset($profile) || ! $profile) {
			$this->redirect('');
		}

		$this->view->form = $form;

		// Form Submitted...
		if ($request->isPost() && $form->isValid($_POST)) {

			Application_Plugin_Common::redirectOnDemoAccount();

			$newpassword = $form->getValue('password2');

			$hash = new Application_Plugin_Phpass();
			$hashed_password = $hash->HashPassword($newpassword);

			// update password
			$Profiles->updateField($profile->name, 'password', $hashed_password);
			
			// remove password reset key
			$ProfilesMeta->deletePair('password_reset', $key);

			Application_Plugin_Alerts::success($this->view->translate('Password updated'));

			// prepare phtml email template
			$mail_template_path = APPLICATION_PATH . '/views/emails/';
			$view = new Zend_View();
			$view->setScriptPath($mail_template_path);
			$body = $view->render('passwordnotice.phtml');

			// send email as a security measure
			$ret = Application_Plugin_Common::sendEmail($profile->email, $this->view->translate('Password updated'), $body, true);

			$this->redirect('');
		}
	}


	/**
	 * Change notifications
	 */
	public function changenotificationsAction()
	{
		$this->buildMenu();

		$Notifications = new Application_Model_Notifications();

		$notifications_form = new Application_Form_Notifications();
		$this->view->notifications_form = $notifications_form;

		$request = $this->getRequest();

		$ProfilesMeta = new Application_Model_ProfilesMeta();

		if ($request->isPost()) {
			// Form Submitted...
			if ($notifications_form->isValid($_POST)) {
				Application_Plugin_Common::redirectOnDemoAccount();

				// clear email notifications up to now
				$Notifications->clearEmailNotifications();

				$elements = $notifications_form->getElements();
				$bulk_notifications = array();

				foreach ($elements as $element) {

					$element_id = $element->getId();

					if ($element_id == 'submitbtn' || $element_id == 'identifier')
						continue;

					$bulk_notifications[$element_id] = $element->getValue();
				}

				$ProfilesMeta->metaUpdate('bulk_notifications', json_encode($bulk_notifications));

				Application_Plugin_Alerts::success($this->view->translate('Notifications updated'));
			}
		}
	}

	/**
	 * Close account
	 */
	public function closeaccountAction()
	{
		$this->buildMenu();

		$form = new Application_Form_Confirm();
		$this->view->form = $form;
		
		$request = $this->getRequest();
		
		// Form Submitted...
		if ($request->isPost() && $form->isValid($_POST)) {
			
			Application_Plugin_Common::redirectOnDemoAccount();

			$Profiles = new Application_Model_Profiles();
			$Profiles->updateField(Zend_Auth::getInstance()->getIdentity()->name, 'is_hidden', 1);

			Application_Plugin_Alerts::success($this->view->translate('Your account is now closed'), 'off');

			// redirect to logout
			$this->redirect('index/logout');
		}

	}
	

	/**
	 * Change profile picture
	 */
	public function setprofilepictureAction()
	{
		$request = $this->getRequest();
		
		if ($request->getParam('edit_done') && $request->getParam('groupaction')) {
			$this->redirect('editprofile/listgroups');
		}
		
		if ($request->getParam('edit_done') && $request->getParam('pageaction')) {
			$this->redirect('editprofile/listpages');
		}

		if ($request->getParam('groupaction') || $request->getParam('pageaction')) {
			$this->buildMenu(true);
		} else {
			$this->buildMenu();
		}
		
		return $this->_forward('setprofilepicture', 'images');
	}
	

	/**
	 * Change cover picture
	 */
	public function setcoverpictureAction()
	{
		$request = $this->getRequest();
		
		if ($request->getParam('edit_done') && $request->getParam('groupaction')) {
			$this->redirect('editprofile/listgroups');
		}
		
		if ($request->getParam('edit_done') && $request->getParam('pageaction')) {
			$this->redirect('editprofile/listpages');
		}

		if ($request->getParam('groupaction') || $request->getParam('pageaction')) {
			$this->buildMenu(true);
		} else {
			$this->buildMenu();
		}
		
		return $this->_forward('setcoverpicture', 'images');
	}
	
	
	/**
	 * Custom background
	 */
	public function setbackgroundpictureAction()
	{
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
	
		$request = $this->getRequest();
		
		$request_profile_id = $request->getParam('id', false);
		$profile = $Profiles->getProfileByField('id', $request_profile_id);
		
		if (Zend_Auth::getInstance()->getIdentity()->role == 'admin' && $request_profile_id) {
			
			// admin edit
			$profile_id = $request_profile_id;
			$this->view->sidebar_editprofile = $profile;
			// attach sidebar box
			Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
			{
				echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
			});
			
		} elseif ($request_profile_id && $Profiles->getProfile($profile->name, false, true)) {

			// users pages & groups
			$this->buildMenu(true);
			$profile_id = $request_profile_id;
			
		} else {
			
			// user profile
			$this->buildMenu();
			$profile_id = Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		$profile_name = Zend_Auth::getInstance()->getIdentity()->name;
	
		$form = new Application_Form_CustomBackground();
	
		$current_background_file = $ProfilesMeta->getMetaValue('background_file', $profile_id);
		
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();

		if ($request->isPost() && $form->isValid($_POST)) {
			
			// file uploaded?
			if ($form->background->isUploaded()) {
			
				$form->background->receive(); // must have
				$receive_path = $form->background->getFileName();
				$filename = $form->background->getValue();
				$extension = strtolower(pathinfo($receive_path, PATHINFO_EXTENSION));
				$tmp_filename = 'profileimage_' . $profile_name . '.' . $extension;
			
				// delete old tmp image files
				$StorageAdapter->deleteOldTmpFiles(0, 'profileimage_' . $profile_name);

				// move new file to tmp folder
				rename($receive_path, TMP_PATH . '/' . $tmp_filename);
				
				// check if valid image
				if (! Application_Plugin_ImageLib::isValidImage(TMP_PATH . '/' . $tmp_filename)) {
					unlink(TMP_PATH . '/' . $tmp_filename);
					Application_Plugin_Alerts::error($this->view->translate('Server-side error'), 'off');
					$this->redirect();
					return;
				}
				
				// delete old file
				$StorageAdapter->deleteFileFromStorage($current_background_file, 'cover');

				// move uploaded file to permanent location
				$current_background_file = $StorageAdapter->moveFileToStorage($tmp_filename, 'cover');
				
				// update db
				$ProfilesMeta->metaUpdate('background_file', $current_background_file, $profile_id);
			}
			
			$ProfilesMeta->metaUpdate('background_repeat', $form->getValue('background_repeat'), $profile_id);
			$ProfilesMeta->metaUpdate('background_scroll', $form->getValue('background_scroll'), $profile_id);
			$ProfilesMeta->metaUpdate('background_stretch', $form->getValue('background_stretch'), $profile_id);
			$ProfilesMeta->metaUpdate('background_noimage', $form->getValue('background_noimage'), $profile_id);
				
			Application_Plugin_Alerts::success($this->view->translate('Settings updated, please clear your browser cache'), 'off');
		}
		
		$this->view->image = $current_background_file ? $StorageAdapter->getStoragePath('cover').$current_background_file : false;
		$this->view->form = $form;
		
		$this->view->load_colorpicker = true;

	}
	
	/**
	 * Change default privacy (via ajax)
	 */
	public function defaultprivacyAction()
	{
		$default_privacy = $this->getRequest()->getParam('privacy');

		$privacy_levels = Zend_Registry::get('post_privacy_array');

		$ret = false;

		if (array_key_exists($default_privacy, $privacy_levels)) {
			$Profiles = new Application_Model_Profiles();
			$ret = $Profiles->updateField(Zend_Auth::getInstance()->getIdentity()->name, 'default_privacy', $default_privacy);
			Zend_Registry::set('default_privacy', $default_privacy);
		}

		$this->getHelper('json')->sendJson($ret);
	}

	/**
	 * List all user groups
	 */
	public function listgroupsAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;

		$user_id = Zend_Auth::getInstance()->getIdentity()->id;

		$Profiles = new Application_Model_Profiles();
		$profiles_count = $Profiles->getProfilesCount(false, 'group', $user_id);
		$Profiles->page_number = $page;
		$groups = $Profiles->getProfiles(false, 'group', $user_id);

		$this->view->pagination_last_page = (int) ceil($profiles_count / (int) Zend_Registry::get('config')->pagination_limit);
		$this->view->pagination_current_page = $page;

		$Profiles->page_number = $page;

		$this->view->groups = $groups;
	}

	/**
	 * Edit group
	 */
	public function editgroupAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();

		$Profiles = new Application_Model_Profiles();

		$profile_form = new Application_Form_EditGroup();
		$this->view->profile_form = $profile_form;

		if ($request->isPost() && $profile_form->isValid($_POST)) {
			$profile_name = $profile_form->getValue('name');
			$profile = $Profiles->getProfileRow($profile_name, false, true);

			$profile->screen_name = $profile_form->getValue('screen_name');
			$profile->profile_privacy = $profile_form->getValue('profile_privacy');
			$profile->is_hidden = $profile_form->getValue('is_hidden');

			$ret = $profile->save();

			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $profile_form->getValue('description'), $profile->id);

			Application_Plugin_Alerts::success($this->view->translate('Group updated'));

			$this->redirect('editprofile/listgroups');
		}
	}

	/**
	 * Create a group
	 */
	public function creategroupAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();

		$Profiles = new Application_Model_Profiles();

		$profile_form = new Application_Form_AddGroup();
		$this->view->profile_form = $profile_form;

		if ($request->isPost() && $profile_form->isValid($_POST)) {
			
			if ($Profiles->getProfile($profile_form->getValue('name'), true)) {
				
				
				$profile_form->getElement('name')->setErrors(array(
					Zend_Registry::get('Zend_Translate')->translate('This username is not available')
				));
				return;
			}
			
			$profile = $Profiles->createRow();
			$profile->owner = Zend_Auth::getInstance()->getIdentity()->id;
			$profile->name = $profile_form->getValue('name');
			$profile->screen_name = $profile_form->getValue('screen_name');
			$profile->profile_privacy = $profile_form->getValue('profile_privacy');

			$Profiles->createNewGroup($profile);

			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $profile_form->getValue('description'), $profile->id);

			Application_Plugin_Alerts::success($this->view->translate('New group created'));
			$this->redirect('editprofile/listgroups');
		}
	}

	/**
	 * List all user pages
	 */
	public function listpagesAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;

		$user_id = Zend_Auth::getInstance()->getIdentity()->id;

		$Profiles = new Application_Model_Profiles();
		$profiles_count = $Profiles->getProfilesCount(false, 'page', $user_id);
		$Profiles->page_number = $page;
		$pages = $Profiles->getProfiles(false, 'page', $user_id);

		$this->view->pagination_last_page = (int) ceil($profiles_count / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;

		$Profiles->page_number = $page;

		$this->view->pages = $pages;
	}

	/**
	 * Edit page
	 */
	public function editpageAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();

		$Profiles = new Application_Model_Profiles();

		$profile_form = new Application_Form_EditPage();
		$this->view->profile_form = $profile_form;

		if ($request->isPost() && $profile_form->isValid($_POST)) {
			$profile_name = $profile_form->getValue('name');
			$profile = $Profiles->getProfileRow($profile_name, false, true);

			$profile->screen_name = $profile_form->getValue('screen_name');
			$profile->is_hidden = $profile_form->getValue('is_hidden');

			$ret = $profile->save();

			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $profile_form->getValue('description'), $profile->id);

			Application_Plugin_Alerts::success($this->view->translate('Page updated'));

			$this->redirect('editprofile/listpages');
		}
	}

	/**
	 * Create a page
	 */
	public function createpageAction()
	{
		$this->buildMenu(true);

		$request = $this->getRequest();

		$Profiles = new Application_Model_Profiles();

		$profile_form = new Application_Form_AddPage();
		$this->view->profile_form = $profile_form;

		if ($request->isPost() && $profile_form->isValid($_POST)) {
			
			if ($Profiles->getProfile($profile_form->getValue('name'), true)) {
				$profile_form->getElement('name')->setErrors(array(
					Zend_Registry::get('Zend_Translate')->translate('This username is not available')
				));
				return;
			}
			
			$profile = $Profiles->createRow();
			$profile->owner = Zend_Auth::getInstance()->getIdentity()->id;
			$profile->name = $profile_form->getValue('name');
			$profile->screen_name = $profile_form->getValue('screen_name');
			$profile->profile_privacy = 'public';

			$Profiles->createNewPage($profile);

			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('description', $profile_form->getValue('description'), $profile->id);

			Application_Plugin_Alerts::success($this->view->translate('New page created'));
			$this->redirect('editprofile/listpages');
		}
	}
}