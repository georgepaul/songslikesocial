<?php

/**
 * Default Controller & Auth
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class IndexController extends Zend_Controller_Action
{

	/**
	 * Main Feed / Login Page
	 */
	public function indexAction()
	{
		$request = $this->getRequest();
		
		$Connections = new Application_Model_Connections();
		
		$limit = Zend_Registry::get('config')->get('sidebar_max_users');
		
		// put addPost form on the front page
		$this->_helper->addPostFormLoader();
		
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->view->sidebar_myprofile = true;
		} else {
			$this->view->sidebar_login = true;
		}
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 2, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/myprofile.phtml');
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/loginregister.phtml');
		});
		
		// load initial posts
		$Posts = new Application_Model_Posts();
		// Add coment form
		$add_comment_form = new Application_Form_AddComment();
		$this->view->add_comment_form = $add_comment_form;
		
		// offset infinite scroll
		if ($this->view->post_page_number) {
			$Posts->page_number = $this->view->post_page_number;
		}
		
		$data = $Posts->getPosts();
		
		$this->view->posts_data = $data;
		$this->view->profile_type = 'feed';
		
		// continue to load posts with ajax
		if (count($data) >= Zend_Registry::get('config')->get('limit_posts')) {
			$this->view->php_loadPostURL = $this->_helper->url->url(array(
				'controller' => 'posts',
				'action' => 'load'
			), 'default', true);
		}
		
		// auto show image (shares)
		$image_uid = $request->getParam('showimage', 0);
		if ($image_uid) {
			
			if (!Zend_Auth::getInstance()->hasIdentity()) $this->redirect('');
			
			$Images = new Application_Model_Images();
			$image = $Images->getImageByUID($image_uid);
			
			if (isset($image)) {
				$this->view->auto_show_image = $image['id'];
				$this->view->auto_show_image_file_name = $image['file_name'];
			} else {
				Application_Plugin_Alerts::error($this->view->translate('Resource does not exists'), 'on');
				$this->redirect('');
			}
		}
	}

	/**
	 * Generic zend-form ajax validator
	 */
	public function validateformajaxAction()
	{
		$formName = $this->getRequest()->getParam('identifier', null);
		
		if (! $formName || ! is_string($formName)) {
			$this->getHelper('json')->sendJson('form validator error');
		}
		
		$form_class = 'Application_Form_' . $formName;
		
		if (! class_exists($form_class)) {
			$this->getHelper('json')->sendJson('form validator error');
		}
		
		$form = new $form_class();
		$form->isValid($this->_getAllParams());
		
		$json = array();
		
		if ($form->getElement('csrf')) {
			$form->getElement('csrf')->initCsrfToken();
			$form->getElement('csrf')->initCsrfValidator();
			$new_csrf_hash = $form->getElement('csrf')->getHash();
			
			$json['ajax_csrf'] = $new_csrf_hash;
		}
		
		$json['errors'] = $form->getMessages();
		
		$this->getHelper('json')->sendJson($json);
	}

	/**
	 * Activation link lands here to activate user account
	 */
	public function activateAction()
	{
		$this->_helper->_layout->setLayout('layout_wide');
		
		// flush if already logged in
		Zend_Auth::getInstance()->clearIdentity();
		
		$activateaccount_form = new Application_Form_ActivateAccount();
		$this->view->activateaccount_form = $activateaccount_form;
		
		$key = $this->getRequest()->getParam('key', false);
		$resend_username = $this->getRequest()->getParam('resend', false);
		
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$userData = $Profiles->getProfileByField('activationkey', $key);
		
		if (! $userData || $key == 'activated') {
			
			// try if this is a resend
			$userData = $Profiles->getProfile($resend_username);
			
			if (! $userData || $userData->activationkey == 'activated') {
				$this->redirect('');
			} else {
				
				$resend_lock = $ProfilesMeta->getMetaValue('resend_activation_lock', $userData->id);
				$hour_lock = date('H');
				
				// prevent too many attempts
				if ($resend_lock && $resend_lock == $hour_lock) {
					Application_Plugin_Alerts::info(Zend_Registry::get('Zend_Translate')->translate('Please Check your Inbox and come back after you activate your account.'), 'off');
					$this->redirect('');
				}
				
				$ret = Application_Plugin_Common::sendActivationEmail($userData->email, $userData->name, $userData->activationkey);
				
				// email has been sent, show success message
				if ($ret) {
					Application_Plugin_Alerts::info(Zend_Registry::get('Zend_Translate')->translate('Please Check your Inbox and come back after you activate your account.'), 'off');
					// once per day
					$ProfilesMeta->metaUpdate('resend_activation_lock', $hour_lock, $userData->id);
				} else {
					// show error message
					Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Something went wrong, email was not sent.'), 'off');
				}
				
				$this->redirect('');
				
			}
			
		}
		
		$request = $this->getRequest();
		
		if ($request->isPost() && isset($_POST['identifier']) && $_POST['identifier'] == 'ActivateAccount') {
			
			if ($activateaccount_form->isValid($_POST)) {
				
				if ($Profiles->activateAccount($key)) {
					// auto-login user and store identity
					$authAdapter = Application_Plugin_Common::getAuthAdapter();
					$authAdapter->setIdentity($userData->email)
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
					$this->redirect('');
				}
			}
		}
	}

	
	/**
	 * Login stand-alone page
	 */
	public function loginAction()
	{
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->redirect('');
		}
		
		$this->_helper->_layout->setLayout('layout_login');
		$this->_helper->viewRenderer->setNoRender(true);
	}

	/**
	 * Change language
	 */
	public function languageAction()
	{
		Application_Plugin_Common::redirectOnDemoAccount();
		
		$request = $this->getRequest();
		$session = new Zend_Session_Namespace('Default');
		
		$new_lang = $request->getParam('code');
		
		$translate = Zend_Registry::get('Zend_Translate');
		
		// change current language
		if ($new_lang && in_array($new_lang, $translate->getList())) {
			$session->language = $new_lang;
			
			if (Zend_Auth::getInstance()->hasIdentity()) {
				// update user's default language
				$Profiles = new Application_Model_Profiles();
				$Profiles->updateField(Zend_Auth::getInstance()->getIdentity()->name, 'language', $new_lang);
			}
		}
		
		$this->redirect('');
	}

	/**
	 * Logout & Destroy identity
	 */
	public function logoutAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		
		// flush url
		$this->redirect('');
	}

	/**
	 * Access denied page for logged in users
	 */
	public function denyAction()
	{}
}