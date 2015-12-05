<?php

/**
 * Action Helper for loading forms
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Zend_Controller_Action_Helper_AddPostFormLoader extends Zend_Controller_Action_Helper_Abstract
{

	public $front;

	public $request;

	public $show_privacy;

	public $callbackurl = '';


	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function direct($callbackurl = '', $show_privacy_btn = true)
	{
		$this->front = $this->getActionController();
		$this->request = $this->front->getRequest();
		$this->show_privacy = $show_privacy_btn;
		
		$this->callbackurl = $callbackurl;
		
		return $this->addPostFormLoader();
	}

	/**
	 * Load a form with the provided options
	 */
	public function addPostFormLoader()
	{
		// do now show for guests
		if (! Zend_Auth::getInstance()->hasIdentity())
			return false;
		
		$Profiles = new Application_Model_Profiles();
		
		$wall_name = $this->request->getParam('name');
		
		if ($wall_name) {
			
			$profile = $Profiles->getProfileByField('name', $wall_name);
			
			if (! isset($profile->id))
				return false;
			
			$current_name = Zend_Auth::getInstance()->getIdentity()->name;
			$current_user_id = Zend_Auth::getInstance()->getIdentity()->id;
			
			if (! $this->canPostHere($current_user_id, $profile->type, $profile->id, $profile->owner)) {
				return false;
			}
		}
		
		// AddPost form
		$add_post_form = new Application_Form_AddPost();
		$add_post_form->show_privacy = $this->show_privacy;
		
		$this->front->view->add_post_form = $add_post_form;
		
		if ($this->request->isPost() && isset($_POST['identifier']) && $_POST['identifier'] == 'AddPost') {
			$add_post_form = $this->submitAddPostForm($add_post_form);
		}
		
		return true;
	}

	/**
	 * Add post submit
	 */
	public function submitAddPostForm($form)
	{
		$Profiles = new Application_Model_Profiles();
		
		$current_user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		// default user wall
		$profile = Zend_Auth::getInstance()->getIdentity();
		// writing on other user wall?
		if ($this->request->getParam('name')) {
			$profile = $Profiles->getProfile($this->request->getParam('name'));
		}
		
		if (! $this->canPostHere($current_user_id, $profile->type, $profile->id, $profile->owner)) {
			return false;
		}
		
		// submit?
		if (isset($_POST['identifier']) && $_POST['identifier'] == 'AddPost' && $form->isValid($_POST)) {
			
			$content = $form->getValue('content');
			$content = Application_Plugin_Common::preparePost($content);
			
			$Posts = new Application_Model_Posts();
			
			// save received filename to session form_unique_key
			$form_unique_key = (int) $_POST['form_unique_key'];
			
			$attached_files = @glob(TMP_PATH . '/post_' . Zend_Auth::getInstance()->getIdentity()->name . '_' . $form_unique_key . '*');
			
			if ($this->show_privacy) {
				$Posts->addPost($content, $profile->id, Zend_Registry::get('default_privacy'), $attached_files);
			} else {
				// most restrictive, for groups and pages privacy is controlled when fetching posts
				$Posts->addPost($content, $profile->id, 'friends', $attached_files);
			}
			
			// flush content
			$form->getElement('content')->setValue('');
			
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
			$redirector->gotoUrl($this->callbackurl);
		}
		
		return $form;
	}

	/**
	 * can post here?
	 */
	public function canPostHere($user_id, $profile_type, $profile_id, $profile_owner)
	{
		$Connections = new Application_Model_Connections();
		
		// only account owner and friends can post to each other walls.
		if ($profile_type == 'user' && $user_id != $profile_id && ! $Connections->areFriends($profile_id, $user_id)) {
			return false;
		}
		
		// only group members can post to a group
		if ($profile_type == 'group' && ! $Connections->areFriends($profile_id, $user_id)) {
			return false;
		}
		
		// only page owner can write to a page
		if ($profile_type == 'page' && $user_id !== $profile_owner) {
			return false;
		}
		
		return true;
	}
}