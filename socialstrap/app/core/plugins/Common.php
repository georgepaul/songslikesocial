<?php

/**
 * Common, application-wide methods
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_Common extends Zend_Controller_Plugin_Abstract
{

	/**
	 * Login auth adapter: Database driven
	 */
	static public function getAuthAdapter()
	{
		$authAdapter = new Application_Plugin_AuthAdapter();
		
		$authAdapter->setTableName('profiles')
			->setIdentityColumn('email')
			->setCredentialColumn('password');
		
		return $authAdapter;
	}

	/**
	 * Auth with email only, no password
	 */
	static public function getEmailAuthAdapter($email)
	{
		$authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
		$authAdapter->setTableName('profiles')
			->setIdentityColumn('email')
			->setIdentity($email)
			->setCredentialColumn('password')
			->setCredential('whatever')
			->setCredentialTreatment('? or 1 = 1');
		
		return $authAdapter;
	}

	/**
	 * Login user
	 */
	static public function loginUser($user_data, $authAdapter, $authStorage)
	{
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		
		// everything ok, login user
		$user_data = $authAdapter->getResultRowObject();
		
		// update fields
		$Profiles->updateField($user_data->name, 'relogin_request', 0);
		
		$authStorage->write($user_data);
		
		// update last login date
		$ProfilesMeta->metaUpdate('last_login', Application_Plugin_Common::now(), $user_data->id);
		
		// set user specific language after login
		$session = new Zend_Session_Namespace('Default');
		$session->language = $user_data->language;
		
		return;
	}

	/**
	 * generate random unique string
	 */
	static public function getRandomString()
	{
		return sha1(uniqid('', true));
	}

	/**
	 * generate random number
	 */
	static public function getRandomNum($min = 100000, $max = 999999)
	{
		return (int) mt_rand($min, $max) + date('s');
	}

	/**
	 * Send email
	 *
	 * TODO: add language specific template based on recepient default language
	 */
	static public function sendEmail($to, $subject, $body, $show_errors = false)
	{
		if (Zend_Registry::get('config')->get('mail_adapter') == 'smtp') {
			$smtp_config = array(
				'ssl' => Zend_Registry::get('config')->get('mail_security'),
				'port' => Zend_Registry::get('config')->get('mail_port'),
				'auth' => Zend_Registry::get('config')->get('mail_login'),
				'username' => Zend_Registry::get('config')->get('mail_username'),
				'password' => Zend_Registry::get('config')->get('mail_password')
			);
			
			$tr = new Zend_Mail_Transport_Smtp(Zend_Registry::get('config')->get('mail_host'), $smtp_config);
		} else {
			$tr = new Zend_Mail_Transport_Sendmail();
		}
		
		Zend_Mail::setDefaultTransport($tr);
		
		$mail = new Zend_Mail('utf8');
		$mail->setBodyHtml($body);
		
		$mail->setFrom(Zend_Registry::get('config')->get('mail_from'), Zend_Registry::get('config')->get('mail_from_name'));
		$mail->addTo($to);
		$mail->setSubject($subject);
		
		try {
			$mail->send($tr);
		} catch (Zend_Mail_Exception $e) {
			
			if (method_exists($tr, 'getConnection') && method_exists($tr->getConnection(), 'getLog')) {
				Application_Plugin_Common::log(array(
					$e->getMessage(),
					$tr->getConnection()->getLog()
				));
			} else {
				Application_Plugin_Common::log(array(
					$e->getMessage(),
					'error sending mail'
				));
			}
			
			if ($show_errors) {
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Something went wrong, email was not sent.'), 'off');
			}
			
			return false;
		}
		
		return true;
	}

	/**
	 * get full base url (http://www.example.com/socialstrap)
	 */
	static public function getFullBaseUrl($include_base = true)
	{
		$front = Zend_Controller_Front::getInstance();
		$view = new Zend_View();
		
		if ($include_base) {
			$base_url = $view->baseUrl();
		} else {
			$base_url = '';
		}
		
		$url = $front->getRequest()->getScheme() . '://' . $front->getRequest()->getHttpHost() . $base_url;
		
		return $url;
	}

	
	/**
	 * get safe uri (http://www.example.com/socialstrap/something/something)
	 */
	static public function getSafeUri()
	{
		$front = Zend_Controller_Front::getInstance();
		$request = $front->getRequest();
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		
		$url = Application_Plugin_Common::getFullBaseUrl().'/'.$controller.'/'.$action.'/';
		
		$params = Zend_Controller_Front::getInstance()->getRequest()->getParams();
		foreach ($params as $key => $val){
			if ($key === 'module' || $key === 'controller' || $key === 'action' || ! is_string($val) || ! is_string($val)) continue;
			$url .= htmlentities(strip_tags($key)) . '/' . htmlentities(strip_tags($val)) . '/';
		}

		return $url;
	}
	
	
	/**
	 * Validate and filter data (not used)
	 */
	static public function cleanData($data)
	{
		if (! is_array($data)) {
			$data = array(
				$data
			);
		}
		
		$filters = array(
			'*' => array(
				'StringTrim',
				'HtmlEntities',
				'StripTags',
				'Alnum'
			)
		);
		$validators = array(
			'*' => array(
				'NotEmpty',
				'presence' => 'required'
			)
		);
		
		$input = new Zend_Filter_Input($filters, $validators, $data);
		
		if ($input->isValid()) {
			
			// return filtered and escaped data
			$ret = $input->getEscaped();
			return $ret[0];
		}
		
		return false;
	}

	/**
	 * Return time elapsed
	 */
	static public function getTimeElapsedString($timestamp)
	{
		$etime = time() - $timestamp;
		
		$translator = Zend_Registry::get('Zend_Translate');
		
		if ($etime < 1) {
			return $translator->translate('a moment ago');
		}
		
		$a = array(
			12 * 30 * 24 * 60 * 60 => 'year',
			30 * 24 * 60 * 60 => 'month',
			24 * 60 * 60 => 'day',
			60 * 60 => 'hour',
			60 => 'minute',
			1 => 'second'
		);
		
		foreach ($a as $secs => $str) {
			$d = $etime / $secs;
			if ($d >= 1) {
				$r = round($d);
				
				switch ($str) {
					case 'year':
						$ret = ($r > 1 ? sprintf($translator->translate('x years ago'), $r) : sprintf($translator->translate('1 year ago'), $r));
						break;
					case 'month':
						$ret = ($r > 1 ? sprintf($translator->translate('x months ago'), $r) : sprintf($translator->translate('1 month ago'), $r));
						break;
					case 'day':
						$ret = ($r > 1 ? sprintf($translator->translate('x days ago'), $r) : sprintf($translator->translate('1 day ago'), $r));
						break;
					case 'hour':
						$ret = ($r > 1 ? sprintf($translator->translate('x hours ago'), $r) : sprintf($translator->translate('1 hour ago'), $r));
						break;
					case 'minute':
						$ret = ($r > 1 ? sprintf($translator->translate('x minutes ago'), $r) : sprintf($translator->translate('1 minute ago'), $r));
						break;
					case 'second':
						$ret = ($r > 1 ? sprintf($translator->translate('x seconds ago'), $r) : sprintf($translator->translate('1 second ago'), $r));
						break;
					default:
						;
						break;
				}
				return $ret;
			}
		}
	}

	/**
	 * log error messages to file
	 */
	public static function log($messages)
	{
		$writer = new Zend_Log_Writer_Stream(APPLICATION_LOG);
		$log = new Zend_Log($writer);
		
		$backTrace = debug_backtrace();
		if (isset($backTrace[2]['class'])) {
			$class_method = $backTrace[2]['class'] . "::" . $backTrace[2]['function'] . "()";
		} else {
			$class_method = "";
		}
		
		if (is_array($messages)) {
			foreach ($messages as $message) {
				$log->log($message, Zend_Log::ERR, array(
					'timestamp' => Application_Plugin_Common::now(),
					'class_method' => $class_method
				));
			}
		} else {
			$log->log($messages, Zend_Log::ERR, array(
				'timestamp' => Application_Plugin_Common::now(),
				'class_method' => $class_method
			));
		}
	}

	/**
	 * slugify function (not used)
	 */
	public static function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
		
		// trim
		$text = trim($text, '-');
		
		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		
		// lowercase
		$text = strtolower($text);
		
		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		
		if (empty($text)) {
			return 'n-a';
		}
		
		return $text;
	}

	/**
	 * shorten number (not used)
	 */
	public static function numberShort($number)
	{
		if (floatval($number) >= 1.0E+09) {
			$number = strval(floor(floatval($number) / 1.0E+09)) . "b";
		} elseif (floatval($number) >= 1.0E+06) {
			$number = strval(floor(floatval($number) / 1.0E+06)) . "m";
		} elseif (floatval($number) >= 1.0E+03) {
			$number = strval(floor(floatval($number) / 1.0E+03)) . "k";
		} else {
			$number = strval($number);
		}
		
		return $number;
	}

	/**
	 * Returns mysql datetime for current time
	 */
	public static function now()
	{
		return date('Y-m-d H:i:s', time());
	}

	/**
	 * prepare post before saving
	 */
	static function preparePost($content)
	{
		$data = array(
			'content' => $content,
			'meta' => array()
		);
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_data_presavepost', $data);
		
		return $data;
	}

	/**
	 * prepare comment before saving
	 */
	static function prepareComment($content)
	{
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_data_presavecomment', $content);
		
		return $content;
	}


	/**
	 * escape content between html tags with htmlentities
	 */
	static function escapeContentBetweenTags($content, $tagname)
	{
		if (! is_string($content))
			return '';
		
		preg_match_all("/\<{$tagname}\>(.+?)\<\/{$tagname}\>/s", $content, $matches);
		
		if (! isset($matches[1]) || empty($matches[1]))
			return $content;
		
		foreach ($matches[1] as $match) {
			$content = str_replace($match, htmlentities($match), $content);
		}
		
		return $content;
	}

	/**
	 * Helper for reading php.ini settings
	 */
	public static function returnBytes($val)
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
	 * Disable editing of demo accounts
	 */
	public static function redirectOnDemoAccount()
	{
		$demo_account_name = 'user1';
		
		if (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->name == $demo_account_name) {
			Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Cannot edit demo user'));
			
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
			$redirector->gotoUrl('');
		}
	}
	
	
	/**
	 * Multy byte substring
	 */
	static function mbsubstr($string, $start, $length = null, $encoding = 'utf-8')
	{
		if (function_exists('mb_substr')) {
			return mb_substr($string, $start, $length, $encoding);
		} elseif ($length) {
			return substr($string, $start, $length);
		} else {
			return substr($string, $start);
		}
	}
	

	/**
	 * limit input to max posts
	 */
	static function limitInput($data)
	{
		if (Zend_Registry::get('config')->get('max_post_length') > 0 && strlen($data) > Zend_Registry::get('config')->get('max_post_length')) {
			$data = Application_Plugin_Common::mbsubstr($data, 0, Zend_Registry::get('config')->get('max_post_length'), 'utf-8');
		}
		
		return $data;
	}
		
	/**
	 * parse profile tags (used for settings)
	 */
	static function parseProfileTags($content, $profile)
	{
		$content = str_replace('PROFILE_NAME', $profile->name, $content);
		$content = str_replace('PROFILE_SCREEN_NAME', $profile->screen_name, $content);
		$content = str_replace('PROFILE_AVATAR', $profile->avatar, $content);
		$content = str_replace('PROFILE_COVER', $profile->cover, $content);
		
		if (isset($profile->meta_values['description'])) {
			$content = str_replace('PROFILE_DESCRIPTION', $profile->meta_values['description'], $content);
		}
		
		return $content;
	}
	

	/**
	 * Send activation email
	 */
	public static function sendActivationEmail($email, $name, $key)
	{
		// controller for activation, full url
		$base_url = Application_Plugin_Common::getFullBaseUrl();
		$activation_link = $base_url . '/index/activate/key/' . $key;
	
		// send activation email
		$subject = Zend_Registry::get('Zend_Translate')->translate('Activate account');
			
		// prepare phtml email template
		$mail_template_path = APPLICATION_PATH . '/views/emails/';
		$view = new Zend_View();
		$view->setScriptPath($mail_template_path);
		$view->assign('activation_link', $activation_link);
		$view->assign('screen_name', $name);
		$body = $view->render('activation.phtml');
			
		$ret = Application_Plugin_Common::sendEmail($email, $subject, $body, true);
		
		return $ret;	
	}
	
	/**
	 * Send recovery email
	 */
	public static function sendRecoveryEmail($email, $name, $key)
	{	
		// password recovery email
		$subject = Zend_Registry::get('Zend_Translate')->translate('New Password:');
		
		// password recovery full url
		$base_url = Application_Plugin_Common::getFullBaseUrl();
		$pw_recovery_url = $base_url . '/editprofile/recoverpassword/key/' . $key;
		
		// prepare phtml email template
		$mail_template_path = APPLICATION_PATH . '/views/emails/';
		$view = new Zend_View();
		$view->setScriptPath($mail_template_path);
		$view->assign('recovery_link', $pw_recovery_url);
		$body = $view->render('resetpassword.phtml');
		
		$ret = Application_Plugin_Common::sendEmail($email, $subject, $body, true);
	
		return $ret;
	}
}