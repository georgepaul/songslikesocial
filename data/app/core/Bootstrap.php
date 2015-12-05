<?php
/**
 * Zend Bootstrap
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	private $_view;
	private $_appConfig;
	
	public function __construct($application)
	{
		parent::__construct($application);
		
		// get view resources
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');

		$this->_view = $layout->getView();

		// add view script path
		$this->_view->setScriptPath(APPLICATION_PATH . '/views/layout/');
		
		// add custom view helpers paths
		$this->_view->addHelperPath(APPLICATION_PATH . '/views/helpers/');

		// setup database for boostrap use
		$db = $this->getPluginResource('db')->getDbAdapter();
		
		// Firebug DB Profiler
		if (isset($_GET['profiler']) && $_GET['profiler'] == 'firebug') {
			$profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
			$profiler->setEnabled(true);
			$db->setProfiler($profiler);
		}
		
		// Force the initial connection to handle error relating to caching etc.
		try {
			$db->getConnection();
		} catch (Zend_Exception $e) {
			echo 'ERROR: Cannot connect to database.<br />';
			echo $e->getMessage();
			die;
		}

		Zend_Db_Table::setDefaultAdapter($db);
		
		// Set default storage adapter
		Zend_Registry::set('storage_adapter', 'Application_Model_StorageFilesystem');
		
		// Strip magic quotes if enabled
		if (get_magic_quotes_gpc()) {
			function magicQuotes_awStripslashes(&$value, $key) {$value = stripslashes($value);}
			$gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
			array_walk_recursive($gpc, 'magicQuotes_awStripslashes');
		}

	}


	/**
	 * Write combined config to registry
	 */
	protected function _initConfig()
	{
		$AppOptions = new Application_Model_AppOptions();
		
		
		try {
			$app_options = $AppOptions->getAllOptions();
		}  catch (Zend_Exception $e) {
			echo 'ERROR: App options cannot be loaded. Make sure your database has been imported. If you wish to run installer again please remove config.php file.<br />';
			echo $e->getMessage();
			die;
		}
		
		
		foreach ($app_options as &$app_option){
			$app_option = str_replace ("PUBLIC_PATH", PUBLIC_PATH, $app_option);
			$app_option = str_replace ("APPLICATION_PATH", APPLICATION_PATH, $app_option);
		}

		$zend_options = $this->getOptions();

		$config = new Zend_Config(array_merge($app_options, $zend_options), true);

		Zend_Registry::set('config', $config);

		$this->_appConfig = $config;

		return;
	}

	
	/**
	 * Init main Cache mechanism
	 */
	protected function _initCache()
	{
		if (!defined('CACHE_PATH')) {
			die ("Error: Cache directory not defined, check index.php file.");
		}
		
		if ($this->_appConfig->cache_frontend_options) {
			$frontendOptions = json_decode($this->_appConfig->cache_frontend_options, true);
		} else {
			$frontendOptions = array(
				'automatic_serialization' => true,
				'lifetime' => 600, // default = 3600
			);
		}
		
		$backend_fallback = 'File';
		$backendOptions_fallback = array('cache_dir' => CACHE_PATH);
		
		if ($this->_appConfig->cache_backend) {
			$backend = $this->_appConfig->cache_backend;
			$backendOptions = json_decode($this->_appConfig->cache_backend_options, true);
		} else {
			$backend = $backend_fallback;
			$backendOptions = $backendOptions_fallback;
		}
		
		try {
			$cache = Zend_Cache::factory('Core', $backend, $frontendOptions, $backendOptions);
		} catch (Zend_Exception $e) {
			$message = 'ERROR: Cannot start cache - '.$e->getMessage();
			Application_Plugin_Common::log($message);
			
			// fallback cache
			try {
				$cache = Zend_Cache::factory('Core', $backend_fallback, $frontendOptions, $backendOptions_fallback);
			} catch (Zend_Exception $e) {
				$message = 'ERROR: Cannot start fallback cache - '.$e->getMessage();
				Application_Plugin_Common::log($message);
				die($message);
			}
		}
		
		// Set the cache to be used with all table objects
		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
		
		// Save all-purpose cache to the registry
		Zend_Registry::set('cache', $cache);
	}
	
	
	/**
	 * Init session
	 */
	protected function _initSession()
	{
		if (!$this->_appConfig->session_lifetime) return;
		
		// session storage db table
		$config = array(
				'name'           => 'sessions',    
				'primary'        => 'id', 
				'modifiedColumn' => 'modified',
				'dataColumn'     => 'data',
				'lifetimeColumn' => 'lifetime'
		);
		
		$saveHandler = new Zend_Session_SaveHandler_DbTable($config);
		
		// run garbage collector in 1%
		if (rand(1, 100) == 1){
			$saveHandler->gc(1);
		}
		
		// make the session persist for x seconds
		$saveHandler->setLifetime($this->_appConfig->session_lifetime, $this->_appConfig->session_lifetime);
		
		Zend_Session::setSaveHandler($saveHandler);
		Zend_Session::start(array('cookie_lifetime' => $this->_appConfig->session_lifetime));
	}
	

	/**
	 * Init, security etc.
	 */
	protected function _initAutoload() {

		// load action helpers
		Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH .'/controllers/helpers');

		$controller = Zend_Controller_Front::getInstance();

		// load ACL security
		$controller->registerPlugin(new Application_Plugin_AccessCheck());

		// load hooks
		$controller->registerPlugin(new Application_Plugin_Hooks());
		
		// load global notifications
		$controller->registerPlugin(new Application_Plugin_GlobalDisplay());
		
		// load referral
		$controller->registerPlugin(new Application_Plugin_Referral());
		
		
	}


	/**
	 * Translations & languages
	 */
	protected function _initSetTranslations(){

		$session = new Zend_Session_Namespace('Default');
		$available_languages = array();

		// load all php files from languages folder
		foreach (glob(APPLICATION_PATH .'/languages/*.php') as $file) {

			unset($data, $init);
			require_once $file;

			if (!isset($translate)){
				$translate = new Zend_Translate('array', $data, $init['short']);
			}
			{
				$translate->addTranslation($data, $init['short']);
			}

			$available_languages[$init['short']] = $init['name'];
		}

		if (empty($available_languages)) {
			die('Error: language file is missing '.APPLICATION_PATH .'/languages/*.php');
		}
		
		// init Zend_Locale
		$locale = new Zend_Locale();

		// set default language from options
		$default_language = Zend_Registry::get('config')->get('default_language');
		if (!in_array($default_language, $translate->getList())){
			// in case someone forgot to update settings -> general
			reset($available_languages);
			$default_language = key($available_languages);
		}
		
		$translate->setLocale($default_language);
		$locale->setLocale($default_language);
		Zend_Registry::set('locale', $default_language);

		// set current language based on user session
		if ($session->language && in_array($session->language, $translate->getList())){
			$translate->setLocale($session->language);
			$locale->setLocale($session->language);
			Zend_Registry::set('locale', $session->language);
		}

		// push translator to registry
		Zend_Registry::set('Zend_Translate', $translate);
		// push translator to views
		$this->_view->translate = $translate;
		// push translator to forms
		Zend_Form::setDefaultTranslator($translate);

		// sort default as first & save array for forms
		$available_languages_sorted = array();
		if (isset($available_languages[$default_language])){
			$available_languages_sorted = array($default_language => $available_languages[$default_language]);
			unset($available_languages[$default_language]);
		}
		$available_languages_sorted = array_merge($available_languages_sorted, $available_languages);

		Zend_Registry::set('languages_array', $available_languages_sorted);
	}


	/**
	 * logged in user actions on each page load, username row db load
	 */
	protected function _initUserPreloads()
	{
		// return on guests
		if (!Zend_Auth::getInstance()->hasIdentity()) return;
		
		// check if cookie is ok
		if (! isset(Zend_Auth::getInstance()->getIdentity()->name) || ! isset(Zend_Auth::getInstance()->getIdentity()->id)) {
			Zend_Auth::getInstance()->clearIdentity();
			return;
		}

		// load user from db
		$Profiles = new Application_Model_Profiles();
		$current_user = $Profiles->getProfile(Zend_Auth::getInstance()->getIdentity()->name, true);

		// load users meta from db
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$current_user_meta = $ProfilesMeta->getMetaValues(Zend_Auth::getInstance()->getIdentity()->id);

		if (!$current_user){
			Zend_Auth::getInstance()->clearIdentity();
			return;
		}

		// re-login requests, when user has to re-login for some reason
		if ($current_user->relogin_request == 1){
			$Profiles->updateField($current_user->name, 'relogin_request', 0);
			Zend_Auth::getInstance()->clearIdentity();
		}

		// set default post privacy
		Zend_Registry::set('default_privacy', $current_user->default_privacy);
		// save all profile's meta data
		Zend_Registry::set('current_user_meta', $current_user_meta);

		return;
	}


	/**
	 * Set privacy groups & global arrays
	 */
	protected function _initPrivacyArrays()
	{
		$translator = Zend_Registry::get('Zend_Translate');

		$group_privacy_array = array(
				'friends' => $translator->translate('Secret Group'), // only members see who is in and posts, requires admin confirmation to join
				'followers' => $translator->translate('Private Group'), // anyone can see the group members but only members can see posts
				'everyone' => $translator->translate('Open Group'), // all users can see the group, members and posts
				'public' => $translator->translate('Public Group'), // everyone on the internet can see the group, members and posts
		);

		$profile_privacy_array = array(
				'friends' => $translator->translate('Private profile (friends only)'),
				'followers' => $translator->translate('Semi-private profile (friends and followers)'),
				'everyone' => $translator->translate('Open profile (all users)'),
				'public' => $translator->translate('Public profile (everyone on the internet)')
		);

		$post_privacy_array = array(
				'friends' => $translator->translate('Friends only'),
				'followers' => $translator->translate('Friends & Followers'),
				'everyone' => $translator->translate('Everyone'),
				'public' => $translator->translate('Public')
		);

		Zend_Registry::set('group_privacy_all', $group_privacy_array);
		Zend_Registry::set('profile_privacy_all', $profile_privacy_array);
		Zend_Registry::set('post_privacy_all', $post_privacy_array);


		// Set user's 'role' as a global
		if(Zend_Auth::getInstance()->hasIdentity())
		{
			Zend_Registry::set('role', Zend_Auth::getInstance()->getStorage()->read()->role);

			// we don't need public anymore since noone will be able to see public posts
			if (!Zend_Registry::get('config')->get('allow_guests')){
				unset($group_privacy_array['public']);
				unset($profile_privacy_array['public']);
				unset($post_privacy_array['public']);

				// privacy fallback to everyone when allow_guests set to off
				if (Zend_Registry::get('default_privacy') == 'public'){
					Zend_Registry::set('default_privacy', 'everyone');
				}
			}

		}
		elseif (Zend_Registry::get('config')->get('allow_guests'))
		{
			Zend_Registry::set('role', 'guest');

		}else
		{
			Zend_Registry::set('role', 'anonymous');
		}

		Zend_Registry::set('group_privacy_array', $group_privacy_array);
		Zend_Registry::set('profile_privacy_array', $profile_privacy_array);
		Zend_Registry::set('post_privacy_array', $post_privacy_array);
	}


	/**
	 * Set global arrays
	 */
	protected function _initGlobalArrays()
	{
		$translator = Zend_Registry::get('Zend_Translate');

		$show_online_status = array(
				's' => $translator->translate('Show as online'),
				'h' => $translator->translate('Hide me'));

		$genders_array = array(
				'void' => $translator->translate('Not Specified'),
				'male' => $translator->translate('Male'),
				'female' => $translator->translate('Female'));
		
		$contacyprivacy_status = array(
				'e' => $translator->translate('Everyone'),
				'f' => $translator->translate('Friends only'));


		Zend_Registry::set('onlinestatus_array', $show_online_status);
		Zend_Registry::set('genders_array', $genders_array);
		Zend_Registry::set('contactprivacy_array', $contacyprivacy_status);

	}


	/**
	 * Init main router
	 */
	protected function _initRouter() {
		$router = Zend_Controller_Front::getInstance()->getRouter();

		
		$router->addRoute('user', new Zend_Controller_Router_Route(
				':name',
				array(
						'controller' => 'profiles',
						'action' => 'show',
						'name' => '',
				),
				array(
						1 => 'name',
				)
		));
		
		
		$router->addRoute('mainpage', new Zend_Controller_Router_Route(
				'',
				array(
						'controller' => 'index',
						'action' => 'index'
				)
		));
		
		
		$router->addRoute('addons', new Zend_Controller_Router_Route(
				'addons/:name',
				array(
						'controller' => 'addons',
						'action' => 'show',
						'name' => '',
				),
				array(
						1 => 'name',
				)
		));

	}
	
	protected function getBaseURL(){
		
		return sprintf(
				"%s://%s/%s",
				isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
				$_SERVER['HTTP_HOST'],
				basename(PUBLIC_PATH)
		);
	}
	
}

