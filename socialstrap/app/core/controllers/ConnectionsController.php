<?php

/**
 * Connections Controller
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class ConnectionsController extends Zend_Controller_Action
{

	/**
	 * Toggle follow/unfolow
	 */
	public function togglefollowAction()
	{
		$profile_name = $this->getRequest()->getParam('name');
		
		$Connections = new Application_Model_Connections();
		$ret = $Connections->toggleFollowed($profile_name);
		
		// reload to profile page
		$this->redirect($profile_name);
	}

	/**
	 * Accept / Remove group membership
	 */
	public function membershipAction()
	{
		$user_name = $this->getRequest()->getParam('name');
		$group_name = $this->getRequest()->getParam('group');
		$action = $this->getRequest()->getParam('do');
		
		$Connections = new Application_Model_Connections();
		$Profiles = new Application_Model_Profiles();
		
		// get user
		$user_profile = $Profiles->getProfile($user_name);
		// get group and check ownership
		$group_profile = $Profiles->getProfile($group_name, false, true);
		
		if (! $group_profile || ! $user_profile)
			$this->redirect('');
		
		if ($action == 'join') {
			$Connections->acceptGroupMembership($user_profile->id, $group_profile->id);
		} else {
			$Connections->rejectGroupMembership($user_profile->id, $group_profile->id);
		}
		
		// reload to group page
		$this->redirect($group_name);
	}
}