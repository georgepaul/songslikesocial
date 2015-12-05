<?php

/**
 * Referral
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_Referral extends Zend_Controller_Plugin_Abstract
{

	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$profile_id = (int) $request->getParam('ref');
		
		if ($profile_id) {
			
			$Profiles = new Application_Model_Profiles();
			$profile = $Profiles->getProfileByField('id', $profile_id);
			
			if ($profile && ! isset($_COOKIE["ref"])) {
				$expire_time = time() + (3600 * 24 * 365); // 1 year
				setcookie('ref', base64_encode($profile_id), $expire_time, '/');
			}
		}
	}
}