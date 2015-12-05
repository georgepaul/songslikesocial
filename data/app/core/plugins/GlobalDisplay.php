<?php

/**
 * Autoload on every page
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_GlobalDisplay extends Zend_Controller_Plugin_Abstract
{

	private $view;

	/**
	 * Init $this->view
	 */
	public function __construct()
	{
		$this->view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;
	}

	/**
	 * Create standard display vars for each view
	 */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$controller_name = $request->getControllerName();
		$action_name = $request->getActionName();
		
		// redirect to facebook after share callback
		$url = $request->getRequestUri();
		if (strpos($url, '/fb-redirect') !== false) {
			$r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$r->gotoUrl('http://facebook.com')->redirectAndExit();
		}
		
		// add <head> code for all global pages
		$this->view->custom_head = (Zend_Registry::get('config')->get('global_head') ? Zend_Registry::get('config')->get('global_head') : '');
		
		// global javascript vars
		$this->view->php_controller = $controller_name;
		$this->view->php_action = $action_name;
		
		// view perspective
		$this->view->view_perspective = 'global_view';
		
		// offset infinite scroll - for bots crawling without js
		if (isset($_GET['scroll_offset'])) {
			$scroll_offset = (int) $_GET['scroll_offset'];
			$this->view->post_page_number = $scroll_offset + 1;
		}
		
		// background image
		$this->view->app_background_image = Zend_Registry::get('config')->background;
		
		// for logged in users
		if (Zend_Auth::getInstance()->hasIdentity()) {
			
			// notifications
			$Notifications = new Application_Model_Notifications();
			$notifications_count = $Notifications->getUnreadNotificationsCount();
			
			$this->view->notifications_count = $notifications_count;
			$this->view->addScriptPath(APPLICATION_PATH . '/views/scripts/');
			$notifications_html = $this->view->render('/partial/notifications_popover.phtml');
			$this->view->notifications_html = $notifications_html;
			
			// new messages count
			$Messages = new Application_Model_Messages();
			$new_messages_count = $Messages->getMessagesCount(false, true);
			$this->view->new_messages_count = $new_messages_count;
		} else {
			
			// skip on ajax validator
			if ($action_name == 'validateformajax')
				return;
			
			$this->loginFormsLoader($request);
		}
		
		// attach app sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 20, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/apps.phtml');
		});
		
		return;
	}

	/**
	 * Load login form
	 */
	public function loginFormsLoader($request)
	{
		// login form
		$login_form = new Application_Form_Login();
		$this->view->login_form = $login_form;
		
		$modal_login_form = new Application_Form_Login();
		$modal_login_form->setName('modal_login');
		$this->view->modal_login_form = $modal_login_form;
		
		if ($request->isPost() && isset($_POST['identifier']) && $_POST['identifier'] == 'Login') {
			$login_form = $this->submitLoginForm($login_form);
		}
		
		// register form
		$register_form = new Application_Form_Register();
		$this->view->register_form = $register_form;
		
		$modal_register_form = new Application_Form_Register();
		$modal_register_form->setName('modal_register');
		$this->view->modal_register_form = $modal_register_form;
		
		if ($request->isPost() && isset($_POST['identifier']) && $_POST['identifier'] == 'Register') {
			$register_form = $this->submitRegisterForm($register_form);
		}
		
		// lost password form
		$lostpassword_form = new Application_Form_LostPassword();
		$this->view->lostpassword_form = $lostpassword_form;
		
		$modal_lostpassword_form = new Application_Form_LostPassword();
		$modal_lostpassword_form->setName('modal_lostpassword');
		$this->view->modal_lostpassword_form = $modal_lostpassword_form;
		
		if ($request->isPost() && isset($_POST['identifier']) && $_POST['identifier'] == 'LostPassword') {
			$lostpassword_form = $this->submitLostPasswordForm($lostpassword_form);
		}
	}

	/**
	 * Login submit
	 */
	public function submitLoginForm($form)
	{
		if ($form->isValid($_POST)) {
			$Profiles = new Application_Model_Profiles();
			
			$name_input = $form->getValue('name');
			
			$password = $form->getValue('password');
			
			$remember_me = $form->getValue('remember_me');
			
			if ($remember_me == '0') {
				Zend_Session::ForgetMe();
			}
			
			$user_test = $Profiles->getProfileByField('email', $name_input);
			
			// no user, try with name instead of email
			if (! isset($user_test)) {
				$user_test = $Profiles->getProfileByField('name', $name_input);
			}
			
			if (isset($user_test)) {
				$name = $user_test->name;
				$email = $user_test->email;
			} else {
				// show as alert to cover login modal error
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Invalid username or password'), 'on');
				return;
			}
			
			if ($user_test->type != 'user' || ! $email) {
				// show as alert to cover login modal error
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Invalid username or password'), 'on');
				return;
			}
			
			$authAdapter = Application_Plugin_Common::getAuthAdapter();
			
			$authAdapter->setIdentity($email)->setCredential($password);
			
			$auth = Zend_Auth::getInstance();
			$authStorage = $auth->getStorage();
			$result = $auth->authenticate($authAdapter);
			
			if ($result->isValid()) {
				// check if account is activated
				if (! $Profiles->isActivated($name)) {
					Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Please activate your account first'), 'off');
					// build url
					$base_url = Application_Plugin_Common::getFullBaseUrl();
					$resendactivation_link = $base_url . '/index/activate/resend/'.$user_test->name;
					Application_Plugin_Alerts::info('<a href="'.$resendactivation_link.'">'.Zend_Registry::get('Zend_Translate')->translate('Click here to resend the activation email').'</a>', 'off', false);
					// clear identity - force logout
					Zend_Auth::getInstance()->clearIdentity();
				} elseif ($user_test->is_hidden) {
					
					Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('This account has been deleted or suspended'), 'off');
					
					// clear identity - force logout
					Zend_Auth::getInstance()->clearIdentity();
				} else {
					// everything ok, login user
					$user_data = $authAdapter->getResultRowObject();
					
					Application_Plugin_Common::loginUser($user_data, $authAdapter, $authStorage);
					
					// flush url
					Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
				}
			} else {
				
				// show as alert to cover login modal error
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Invalid username or password'), 'on');
			}
		}
		
		return $form;
	}

	/**
	 * Register submit
	 */
	public function submitRegisterForm($form)
	{
		if ($form->isValid($_POST)) {
			$Profiles = new Application_Model_Profiles();
			
			$name = $form->getValue('regname');
			$email = $form->getValue('regemail');
			
			$hash = new Application_Plugin_Phpass();
			$password = $hash->HashPassword($form->getValue('regpassword'));
			
			$user = $Profiles->createRow();
			$user->name = $name;
			$user->email = $email;
			$user->password = $password;
			
			if (Zend_Registry::get('config')->get('user_activation_disabled')) {

				// create new user withot activation & login
				$user->activationkey = 'activated';
				$new_profile = $Profiles->createNewUser($user);
				
				// auto-login user and store identity
				$authAdapter = Application_Plugin_Common::getAuthAdapter();
				$authAdapter->setIdentity($new_profile->email)
				->setCredential('whatever')
				->setCredentialTreatment('autologin');
					
				$auth = Zend_Auth::getInstance();
				$auth->authenticate($authAdapter);
					
				$identity = $authAdapter->getResultRowObject();
				$authStorage = $auth->getStorage();
				$authStorage->write($identity);
					
				// update last login date
				$ProfilesMeta = new Application_Model_ProfilesMeta();
				$ProfilesMeta->metaUpdate('last_login', Application_Plugin_Common::now(), $identity->id);
					
				// show welcome message
				Application_Plugin_Alerts::success($this->view->translate('Welcome to the network.'), 'on');
				
			} else {
				
				// create activation key and sent it to user email

				$key = $Profiles->generateActivationKey($email);
				$user->activationkey = $key;
				
				$ret = Application_Plugin_Common::sendActivationEmail($email, $name, $key);
					
				// email has been sent, proceed
				if ($ret) {
					// show success message
					Application_Plugin_Alerts::info(Zend_Registry::get('Zend_Translate')->translate('Please Check your Inbox and come back after you activate your account.'), 'off');
					// build url
					$base_url = Application_Plugin_Common::getFullBaseUrl();
					$resendactivation_link = $base_url . '/index/activate/resend/'.$user->name;
					Application_Plugin_Alerts::info('<a href="'.$resendactivation_link.'">'.Zend_Registry::get('Zend_Translate')->translate('Click here to resend the activation email').'</a>', 'off', false);
					
					// create new user
					$new_profile = $Profiles->createNewUser($user);
						
				} else {
					// show error message
					Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Something went wrong, email was not sent.'), 'off');
					Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
					return;
				}
				
				
			}

			// flush url
			Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
		}
		
		return $form;
	}

	/**
	 * Lost password
	 */
	public function submitLostPasswordForm($form)
	{
		$front = Zend_Controller_Front::getInstance();
		
		if ($form->isValid($_POST)) {
			$name = $form->getValue('name');
			
			$Profiles = new Application_Model_Profiles();
			
			$nameRow = $Profiles->getProfileByField('name', $name);
			
			// maybe user is entering email?
			$nameRow_byEmail = $Profiles->getProfileByField('email', $name);
			if ($nameRow_byEmail) {
				$nameRow = $Profiles->getProfileByField('name', $nameRow_byEmail->name);
			}
			
			if ($nameRow && $Profiles->isActivated($nameRow->name) && $nameRow->is_hidden == 0) {
				$resetPasswordKey = $Profiles->generateActivationKey($nameRow->email);
				
				$ProfilesMeta = new Application_Model_ProfilesMeta();
				$profile = $ProfilesMeta->metaUpdate('password_reset', $resetPasswordKey, $nameRow->id);
				
				// password recovery email
				$ret = Application_Plugin_Common::sendRecoveryEmail($nameRow->email, $name, $resetPasswordKey);
				
				// show info message
				if ($ret) {
					Application_Plugin_Alerts::success(Zend_Registry::get('Zend_Translate')->translate('We have sent an email to your registered email address. Follow the instructions and you will be able to enter a new password.'), 'off');
				}
				
				// flush url
				Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
			} else {
				sleep(2);
				$form->getElement('name')->setErrors(array(
					Zend_Registry::get('Zend_Translate')->translate('Username does not exists')
				));
			}
		}
		
		return $form;
	}
	
}