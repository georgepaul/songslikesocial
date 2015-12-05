<?php

/**
 * Connections
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Connections extends Zend_Db_Table_Abstract
{

	protected $_name = 'connections';

	protected $_rowClass = 'Application_Model_Connections_Row';
	
	// pagination
	public $page_number = 1;
	
	// include friends when dealing with followers/following
	public $mix_friends = false;

	/**
	 * Follow user toggle
	 */
	public function toggleFollowed($name)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return null;
		
		$Profiles = new Application_Model_Profiles();
		$Notifications = new Application_Model_Notifications();
		$translator = Zend_Registry::get('Zend_Translate');
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		$follow_profile = $Profiles->getProfile($name);
		
		// no or bad profile
		if (! $follow_profile || ! isset($follow_profile->id))
			return;
		
		if ($follow_profile->type === 'page')
			return;
		
		$is_group = ($follow_profile->type === 'group' ? true : false);
		
		$follow_id = $follow_profile->id;
		
		if ($this->isFollowing($user_id, $follow_id)) {
			
			if ($is_group) {
				
				// delete mutual connection
				$ret = $this->removeConnections($follow_id, $user_id);
				
				if ($ret == 2)
					Application_Plugin_Alerts::info(sprintf($translator->translate('You have left the group %s'), $follow_profile->screen_name));
				else
					Application_Plugin_Alerts::info($translator->translate('You request has been canceled'));
			} else {
				
				$Notifications->pushNotification(array(
					$follow_id
				), 6, 'profile', $user_id);
				
				$this->delete(array(
					'follow_id = ?' => (int) $follow_id,
					'user_id = ?' => (int) $user_id
				));
				
				Application_Plugin_Alerts::info(sprintf($translator->translate('You have stopped following %s'), $follow_profile->screen_name));
			}
			
			return;
		} else {
			
			if ($is_group) {
				
				$data = array(
					'user_id' => $user_id,
					'follow_id' => $follow_id,
					'created_on' => Application_Plugin_Common::now()
				);
				$ret = $this->insert($data);
				
				if ($follow_profile->profile_privacy === 'friends' && $follow_profile->owner != $user_id) {
					// admin will have to confirm this
					Application_Plugin_Alerts::success(sprintf($translator->translate('Your request to join this group has been sent to %s'), $follow_profile->screen_name));
					// notify group admin that new user has requested membership
					$Notifications->pushNotification(array(
						$follow_profile->owner
					), 12, 'profile', $follow_id);
				} else {
					// join the group by adding mutual follow
					$data = array(
						'user_id' => $follow_id,
						'follow_id' => $user_id,
						'created_on' => Application_Plugin_Common::now()
					);
					$this->insert($data);
					
					Application_Plugin_Alerts::success(sprintf($translator->translate('You have joined the group %s'), $follow_profile->screen_name));
				}
			} else {
				
				$data = array(
					'user_id' => $user_id,
					'follow_id' => $follow_id,
					'created_on' => Application_Plugin_Common::now()
				);
				$this->insert($data);
				
				if ($this->areFriends($user_id, $follow_id)) {
					// follow, areFriends
					
					// are friends now, notify user
					$Notifications->pushNotification(array(
						$follow_id
					), 4, 'profile', $user_id);
					
					if ($is_group) {
						Application_Plugin_Alerts::success(sprintf($translator->translate('You have joined the group %s'), $follow_profile->screen_name));
					} else {
						Application_Plugin_Alerts::success(sprintf($translator->translate('You are now friends with %s'), $follow_profile->screen_name));
					}
					
					return;
				}
				
				// new follower, notify user
				$Notifications->pushNotification(array(
					$follow_id
				), 3, 'profile', $user_id);
				
				Application_Plugin_Alerts::success(sprintf($translator->translate('You are now following %s'), $follow_profile->screen_name));
			}
			
			// follow
			return;
		}
		
		return false;
	}

	/**
	 * Approve connection
	 */
	public function approveConnection($user_id, $follow_id)
	{
		if ($this->areFriends($user_id, $follow_id) || $this->isFollowing($follow_id, $user_id))
			return false;
		
		$data = array(
			'user_id' => $follow_id,
			'follow_id' => $user_id,
			'created_on' => Application_Plugin_Common::now()
		);
		
		$ret = $this->insert($data);
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * Follow User
	 */
	public function followUser($user_id, $follow_id)
	{
		if ($this->areFriends($user_id, $follow_id) || $this->isFollowing($follow_id, $user_id))
			return false;
		
		$data = array(
			'user_id' => $user_id,
			'follow_id' => $follow_id,
			'created_on' => Application_Plugin_Common::now()
		);
		
		try {
			$ret = $this->insert($data);
		} catch (Zend_Db_Exception $e) {
			Application_Plugin_Common::log($e->getMessage());
		}
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * Accept group membership request
	 */
	public function acceptGroupMembership($user_id, $follow_id)
	{
		$ret = $this->approveConnection($user_id, $follow_id);
		
		// notify user who sent the request that his request is rejected
		$Notifications = new Application_Model_Notifications();
		$Notifications->pushNotification(array(
			$user_id
		), 10, 'profile', $follow_id);
		
		return $ret;
	}

	/**
	 * Reject group membership request
	 */
	public function rejectGroupMembership($user_id, $follow_id)
	{
		$ret = $this->removeConnections($user_id, $follow_id);
		
		// notify user who sent the request that his request is accepted
		$Notifications = new Application_Model_Notifications();
		$Notifications->pushNotification(array(
			$user_id
		), 11, 'profile', $follow_id);
		
		return $ret;
	}

	/**
	 * Remove all connections
	 */
	public function removeConnections($user1_id, $user2_id)
	{
		$ret1 = $this->delete(array(
			'follow_id = ?' => $user1_id,
			'user_id = ?' => $user2_id
		));
		
		$ret2 = $this->delete(array(
			'follow_id = ?' => $user2_id,
			'user_id = ?' => $user1_id
		));
		
		return $ret1 + $ret2;
	}

	/**
	 * Remove all user's connections
	 */
	public function removeUsersConnections($user_id)
	{
		$ret1 = $this->delete(array(
			'follow_id = ?' => $user_id
		));
		
		$ret2 = $this->delete(array(
			'user_id = ?' => $user_id
		));
		
		return $ret1 + $ret2;
	}

	/**
	 * Check if user1 is following user2
	 */
	public function isFollowing($user1_id, $user2_id)
	{
		$ret = $this->fetchRow($this->select()
			->where('user_id = ?', $user1_id)
			->where('follow_id = ?', $user2_id));
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * Check if current user is friend or follower with this user
	 */
	public function isFriendOrFollower($user_id)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return false;
		
		$user_id = (int) $user_id;
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		$sql = "
		SELECT u.user_id
		FROM connections u
		INNER JOIN connections f ON f.user_id = u.follow_id
		WHERE (u.user_id = {$user_id} AND u.follow_id = {$current_user_id}) OR (u.user_id = {$user_id} AND u.follow_id = {$current_user_id} AND f.user_id = {$current_user_id} AND f.follow_id = {$user_id})
		GROUP BY u.user_id
		";
		
		$ret = $this->getAdapter()->fetchRow($sql);
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * Check if users are friends (mutual followers) / members of a group
	 */
	public function areFriends($user_id_1, $user_id_2)
	{
		$user_id_1 = (int) $user_id_1;
		$user_id_2 = (int) $user_id_2;
		
		$sql = "
		SELECT u.user_id
		FROM connections u
		INNER JOIN connections f ON f.user_id = u.follow_id
		WHERE (u.user_id = {$user_id_1} and u.follow_id = {$user_id_2}) AND (f.user_id = {$user_id_2} and f.follow_id = {$user_id_1})
		";
		
		$ret = $this->getAdapter()->fetchRow($sql);
		
		if (! $ret)
			return false;
		
		return true;
	}

	/**
	 * Get followers for specific user
	 */
	public function getFollowers($user_id, $limit = false, $count_only = false)
	{
		$user_id = (int) $user_id;
		$limit = (int) $limit;
		
		if ($this->page_number < 1)
			$this->page_number = 1;
			
			// pagination limit
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		// custom limit
		if ($limit) {
			$limit_from = 0;
			$limit_to = (int) $limit;
		}
		
		$sql = "";
		
		if ($count_only) {
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}
		
		if ($this->mix_friends) {
			$sql .= "
			SELECT u.*
			FROM connections f
			INNER JOIN profiles u ON f.user_id = u.id
			WHERE f.follow_id = {$user_id}
			AND u.is_hidden = 0
			AND u.type = 'user'
			";
		} else {
			$sql .= "
			SELECT u.*
			FROM connections f
			INNER JOIN profiles u ON f.user_id = u.id
			LEFT JOIN connections c ON c.follow_id = f.user_id AND c.user_id = {$user_id}
			WHERE f.follow_id = {$user_id} AND c.user_id IS NULL
			AND u.is_hidden = 0
			AND u.type = 'user'
			";
		}
		
		if ($count_only) {
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		} else {
			$sql .= " LIMIT {$limit_from}, {$limit_to}";
		}
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get users this user is following - include friends
	 */
	public function getFollowing($user_id, $limit = false, $count_only = false)
	{
		$user_id = (int) $user_id;
		$limit = (int) $limit;
		
		if ($this->page_number < 1)
			$this->page_number = 1;
			
			// pagination limit
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		// custom limit
		if ($limit) {
			$limit_from = 0;
			$limit_to = (int) $limit;
		}
		
		$sql = "";
		
		if ($count_only) {
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}
		
		if ($this->mix_friends) {
			$sql .= "
			SELECT u.*
			FROM connections f
			INNER JOIN profiles u ON f.follow_id = u.id
			WHERE f.user_id = {$user_id}
			AND u.is_hidden = 0
			AND u.type = 'user'
			";
		} else {
			$sql .= "
			SELECT u.*
			FROM connections f
			INNER JOIN profiles u ON f.follow_id = u.id
			LEFT JOIN connections c ON  c.user_id = f.follow_id AND c.follow_id = {$user_id}
			WHERE f.user_id = {$user_id} AND c.user_id IS NULL
			AND u.is_hidden = 0
			AND u.type = 'user'
			";
		}
		
		if ($count_only) {
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		} else {
			$sql .= " LIMIT {$limit_from}, {$limit_to}";
		}
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get user's friends / group members
	 */
	public function getFriends($user_id, $limit = false, $count_only = false, $type = 'user')
	{
		$user_id = (int) $user_id;
		$limit = (int) $limit;
		$type = $this->getDefaultAdapter()->quote($type);
		
		if ($this->page_number < 1)
			$this->page_number = 1;
			
			// pagination limit
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		// custom limit
		if ($limit) {
			$limit_from = 0;
			$limit_to = (int) $limit;
		}
		
		$sql = "";
		
		if ($count_only) {
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}
		
		$sql .= "
		SELECT u.*
		FROM profiles u
		JOIN connections followers ON followers.user_id = u.id AND followers.follow_id = {$user_id}
		JOIN connections following ON following.follow_id = followers.user_id AND following.user_id = {$user_id}
		WHERE u.is_hidden = 0
		AND u.type = {$type}
		";
		
		if ($count_only) {
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		} else {
			$sql .= " LIMIT {$limit_from}, {$limit_to}";
		}
		
		return $this->getAdapter()->fetchAll($sql);
	}
}

class Application_Model_Connections_Row extends Zend_Db_Table_Row_Abstract
{
}