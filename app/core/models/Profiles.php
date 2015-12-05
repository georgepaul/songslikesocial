<?php

/**
 * Profiles
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Profiles extends Zend_Db_Table_Abstract
{

	protected $_name = 'profiles';

	protected $_rowClass = 'Application_Model_Profiles_Row';
	
	// pagination
	public $page_number = 1;

	/**
	 * Create new user - add defaults & save
	 */
	public function createNewUser(Application_Model_Profiles_Row $profile, $origin = null)
	{
		$session = new Zend_Session_Namespace('Default');
		$language = ($session->language ? $session->language : Zend_Registry::get('config')->get('default_language'));
		
		$profile->role = 'user';
		$profile->screen_name = $profile->name;
		$profile->type = 'user';
		$profile->avatar = 'default/generic.jpg';
		$profile->cover = 'default/' . rand(1, 3) . '.jpg';
		$profile->is_hidden = 0;
		$profile->owner = 0;
		$profile->default_privacy = 'everyone';
		$profile->profile_privacy = 'everyone';
		$profile->language = $language;
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_preadduser', $profile);
		
		try {
			$created_id = $profile->save();
		} catch (Zend_Db_Exception $e) {
			Application_Plugin_Common::log($e->getMessage());
		}
		
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$ProfilesMeta->metaUpdate('date_created', Application_Plugin_Common::now(), $created_id);
		
		// referral user cookie
		if (isset($_COOKIE['ref'])) {
			$ref_by_user = $this->getProfileByField('id', base64_decode($_COOKIE['ref']));
			if ($ref_by_user) {
				$ProfilesMeta->metaUpdate('referred_by', $ref_by_user->id, $profile->id);
			}
		}
		
		if ($origin) {
			$ProfilesMeta->metaUpdate('registration_origin', $origin, $profile->id);
		}
		
		// new user notification
		if (Zend_Registry::get('config')->get('newuser_notify_email')) {
			
			$to = Zend_Registry::get('config')->get('newuser_notify_email');
			$subject = 'New user - ' . $profile->name;
			
			// prepare phtml email template
			$mail_template_path = APPLICATION_PATH . '/views/emails/';
			$view = new Zend_View();
			$view->setScriptPath($mail_template_path);
			$view->assign('user_name', $profile->name);
			$body = $view->render('newuser.phtml');
			
			Application_Plugin_Common::sendEmail($to, $subject, $body);
		}
		
		// auto follow users
		if (Zend_Registry::get('config')->get('auto_follow_users')) {
			
			$Connections = new Application_Model_Connections();
			$users = explode(",", Zend_Registry::get('config')->get('auto_follow_users'));
			
			foreach ($users as $user) {
				
				$follow = $this->getProfileByField('name', trim($user));
				
				if ($follow && $follow->type == 'user') {
					$Connections->followUser($profile->id, $follow->id);
				} elseif ($follow && $follow->type == 'group') {
					$Connections->followUser($profile->id, $follow->id);
					$Connections->approveConnection($profile->id, $follow->id);
				}
			}
		}
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_postadduser', $profile);
		
		return $profile;
	}

	/**
	 * Create new group - add defaults & save
	 */
	public function createNewGroup(Application_Model_Profiles_Row $profile)
	{
		$profile->type = 'group';
		$profile->avatar = 'default/groups.jpg';
		$profile->cover = 'default/' . rand(1, 3) . '.jpg';
		$profile->is_hidden = 0;
		
		try {
			$created_id = $profile->save();
		} catch (Zend_Db_Exception $e) {
			Application_Plugin_Common::log($e->getMessage());
		}
		
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$ProfilesMeta->metaUpdate('date_created', Application_Plugin_Common::now(), $created_id);
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		// add curent user to the group
		$Connections = new Application_Model_Connections();
		$Connections->approveConnection($user_id, $created_id);
		$Connections->approveConnection($created_id, $user_id);
		
		return $profile;
	}

	/**
	 * Create new page - add defaults & save
	 */
	public function createNewPage(Application_Model_Profiles_Row $profile)
	{
		$profile->type = 'page';
		$profile->avatar = 'default/pages.jpg';
		$profile->cover = 'default/' . rand(1, 3) . '.jpg';
		$profile->is_hidden = 0;
		
		try {
			$created_id = $profile->save();
		} catch (Zend_Db_Exception $e) {
			Application_Plugin_Common::log($e->getMessage());
		}
		
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$ProfilesMeta->metaUpdate('date_created', Application_Plugin_Common::now(), $created_id);
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		return $profile;
	}

	/**
	 * Update selected user field
	 */
	public function updateField($name, $field, $value)
	{
		$data = array(
			$field => $value
		);
		$where = $this->getAdapter()->quoteInto('name = ?', $name);
		
		$ret = $this->update($data, $where);
		
		if ($ret == 0)
			return false;
			
			// return true on success
		return true;
	}

	/**
	 * Check if user has activated his account
	 */
	public function isActivated($name)
	{
		if (Zend_Registry::get('config')->get('user_activation_disabled')) return true;
		
		$select = $this->select();
		$select->where('name = ?', $name);
		$select->where('activationkey = "activated"');
		
		if ($this->getAdapter()->fetchRow($select))
			return true;
		
		return false;
	}

	/**
	 * Activated user account
	 */
	public function activateAccount($key)
	{
		if (ctype_alnum($key) == false)
			return false;
		
		$data = array(
			'activationkey' => 'activated'
		);
		$where = $this->getAdapter()->quoteInto('activationkey = ?', $key);
		
		$ret = $this->update($data, $where);
		
		if ($ret == 0)
			return false;
			
			// return true on success
		return true;
	}

	/**
	 * Get user data by searching certain fieldname
	 */
	public function getProfileByField($field, $value)
	{
		if (! $value)
			return;
		
		$select = $this->select();
		$select->where("{$field} = ?", $value);
		
		return $this->fetchRow($select);
	}

	/**
	 * Get user/group data
	 */
	public function getProfile($name = null, $get_hidden = false, $check_ownership = false)
	{
		if ($name == null && Zend_Auth::getInstance()->hasIdentity()) {
			$name = Zend_Auth::getInstance()->getIdentity()->name;
		}
		
		$name = $this->getDefaultAdapter()->quote($name);
		
		$sql = "
		SELECT
		*
		FROM profiles p
		WHERE name = {$name}
		";
		
		// show hidden users for admin
		if (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->role === 'admin') {
			$get_hidden = true;
		}
		
		if (! $get_hidden) {
			$sql .= " AND is_hidden = 0 ";
		}
		
		$result = $this->getDefaultAdapter()->fetchRow($sql, array(), Zend_Db::FETCH_OBJ);
		
		// profile does not exitst
		if (! $result) {
			return false;
		}
		
		// check ownership
		if ($check_ownership && ! Zend_Auth::getInstance()->hasIdentity() || ($check_ownership && Zend_Auth::getInstance()->getIdentity()->id != $result->owner && $check_ownership && Zend_Auth::getInstance()->getIdentity()->id != $result->id && $check_ownership && Zend_Auth::getInstance()->getIdentity()->role !== 'admin')) {
			
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			
			Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Error - not permitted'), 'off');
			
			$redirector->gotoSimple('index', 'index');
			return false;
		}
		
		if ($result->type === 'page') {
			$Likes = new Application_Model_Likes();
			$result->is_liked = $Likes->isLiked($result->id, 'page');
			$result->likes_count = $Likes->getLikesCount($result->id, 'page');
		}
		
		return $result;
	}

	/**
	 * Get user/group data
	 */
	public function getProfileRow($name = null, $get_hidden = false, $check_ownership = false)
	{
		if ($name == null && Zend_Auth::getInstance()->hasIdentity()) {
			$name = Zend_Auth::getInstance()->getIdentity()->name;
		}
		
		$select = $this->select();
		$select->where("name = ?", $name);
		
		if (! $get_hidden) {
			$select->where('is_hidden = 0');
		}
		
		$result = $this->fetchRow($select);
		
		// profile does not exitst
		if (! $result) {
			return false;
		}
		
		// check ownership
		if ($check_ownership && ! Zend_Auth::getInstance()->hasIdentity() || ($check_ownership && Zend_Auth::getInstance()->getIdentity()->id != $result->owner && $check_ownership && Zend_Auth::getInstance()->getIdentity()->id != $result->id && $check_ownership && Zend_Auth::getInstance()->getIdentity()->role !== 'admin')) {
			return false;
		}
		
		return $result;
	}

	/**
	 * Get all users/groups
	 */
	public function getProfiles($get_hidden = false, $type = 'user', $owner = false)
	{
		if ($this->page_number < 1)
			$this->page_number = 1;
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		$type = $this->getDefaultAdapter()->quote($type);
		
		$sql = "
		SELECT
		p.*,
		o.id AS owner_id,
		o.name AS owner_name,
		o.screen_name AS owner_screen_name
		FROM profiles p
		LEFT JOIN profiles o ON o.id = p.owner
		WHERE p.type = {$type}
		";
		
		if (! $get_hidden) {
			$sql .= " AND p.is_hidden = 0 ";
		}
		
		if ($owner) {
			$owner = $this->getDefaultAdapter()->quote($owner);
			$sql .= " AND p.owner = {$owner} ";
		}
		
		$sql .= " ORDER BY p.is_hidden, p.type, p.role, p.profile_privacy ";
		$sql .= " LIMIT {$limit_from}, {$limit_to}	";
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		return $result;
	}

	/**
	 * Search profiles
	 */
	public function searchProfiles($search_term, $filters = false, $profile_type = 'user', $count_only = false)
	{
		// TODO: show friends first
		if ($this->page_number < 1)
			$this->page_number = 1;
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		$profile_type = $this->getDefaultAdapter()->quote($profile_type);
		
		// important security quote
		$search_term = str_replace('%', '', $search_term);
		$search_term = $this->getDefaultAdapter()->quote('%' . $search_term . '%');
		
		$sql = "";
		
		// count wrap start
		if ($count_only) {
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}
		
		// search hidden for admin
		if (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->role === 'admin') {
			$sql_hidden = "";
		} else {
			$sql_hidden = " AND p.is_hidden = 0 ";
		}
		
		$sql_filter = '';
		if ($filters && ! empty($filters)) {
			
			foreach ($filters as $filter => $value) {
				
				if (! $value)
					continue;
				
				$value = $this->getAdapter()->quote($value);
				
				switch ($filter) {
					case 'search_filter_gender':
						$sql_filter = " JOIN profile_meta pm ON pm.profile_id = p.id AND pm.meta_key = 'gender' AND pm.meta_value = {$value} ";
						break;
					
					default:
						;
						break;
				}
			}
		}
		
		$sql .= "
		SELECT
		p.*,
		o.id AS owner_id,
		o.name AS owner_name,
		o.screen_name AS owner_screen_name
		FROM profiles p
		LEFT JOIN profiles o ON o.id = p.owner
		{$sql_filter}
		WHERE p.type = {$profile_type}
		AND (p.name like {$search_term} OR p.screen_name like {$search_term})
		{$sql_hidden}

		ORDER BY p.type, p.id DESC
		";
		
		// count wrap end or limits
		if ($count_only) {
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		} else {
			$sql .= " LIMIT {$limit_from}, {$limit_to}	";
		}
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		return $result;
	}

	/**
	 * Get total users/groups count
	 */
	public function getProfilesCount($get_hidden = false, $type = 'user', $owner = false)
	{
		$select = $this->select();
		$select->from('profiles', new Zend_Db_Expr('COUNT(*)'));
		
		if (! $get_hidden) {
			$select->where('is_hidden = 0');
		}
		
		if ($owner) {
			$select->where('owner = ?', $owner);
		}
		
		$select->where('type = ?', $type);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Generate activation key
	 */
	public function generateActivationKey($hash)
	{
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		
		do {
			$generatedKey = sha1(mt_rand(10000, 99999) . time() . $hash);
		} while ($ProfilesMeta->getProfileId('activationkey', $generatedKey) || $ProfilesMeta->getProfileId('password_reset', $generatedKey));
		
		return $generatedKey;
	}

	/**
	 * Get current user data
	 *
	 * Converts Zend_Db_Table_Row into plain stdClass object with properties matching
	 * the actual table row.
	 * For usage with auth storage ( Zend_Auth_Adapter->getResultRowObject )
	 *
	 * @return stdClass
	 */
	public function getProfileRowObject()
	{
		$id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$select = $this->select();
		$select->where("id = ?", $id);
		
		$user = $this->fetchRow($select)->toArray();
		
		$usrObj = new stdClass();
		foreach ($user as $key => $value) {
			$usrObj->{$key} = $value;
		}
		
		return $usrObj;
	}

	/**
	 * Mark as hidden
	 */
	public function markHidden($id)
	{
		$data = array(
			'is_hidden' => 1,
			'relogin_request' => 1
		);
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		
		$rows_updated = $this->update($data, $where);
		
		return ($rows_updated == 1 ? true : false);
	}

	/**
	 * Permanently remove profile and all data
	 */
	public function removeProfile($profile_id)
	{
		// get groups this user owns and remove all
		$connected_groups = $this->getProfiles(true, 'group', $profile_id);
		
		if ($connected_groups) {
			foreach ($connected_groups as $profile) {
				$this->removeAllProfilesData($profile['id']);
				$ret = $this->delete(array(
					'id = ?' => $profile['id']
				));
			}
		}
		
		// get pages this user owns and remove all
		$connected_pages = $this->getProfiles(true, 'page', $profile_id);
		
		if ($connected_pages) {
			foreach ($connected_pages as $profile) {
				
				// remove likes on this page
				$Likes = new Application_Model_Likes();
				$Likes->removePageLikes($profile_id);
				
				$this->removeAllProfilesData($profile['id']);
				$ret = $this->delete(array(
					'id = ?' => $profile['id']
				));
			}
		}
		
		// remove all user's data
		$this->removeAllProfilesData($profile_id);
		
		// remove user itself
		$ret = $this->delete(array(
			'id = ?' => $profile_id
		));
		
		return $ret;
	}

	/**
	 * Permanently remove all profile's associated data
	 */
	public function removeAllProfilesData($profile_id)
	{
		// check if exists
		$profile = $this->getProfileByField('id', $profile_id);
		if (! $profile)
			return false;
		
		$Images = new Application_Model_Images();
		$Images->removeUsersImages($profile_id);
		
		$Albums = new Application_Model_Albums();
		$Albums->deleteAlbums($profile_id);
		
		$Comments = new Application_Model_Comments();
		$Comments->deleteComments($profile_id);
		
		$Connections = new Application_Model_Connections();
		$Connections->removeUsersConnections($profile_id);
		
		$Likes = new Application_Model_Likes();
		$Likes->removeUsersLikes($profile_id);
		
		$Notifications = new Application_Model_Notifications();
		$Notifications->removeUsersNotifications($profile_id);
		
		$Reports = new Application_Model_Reports();
		$Reports->removeUsersReports($profile_id);
		
		$Posts = new Application_Model_Posts();
		$Posts->removeUsersPosts($profile_id);
		
		$Messages = new Application_Model_Messages();
		$Messages->removeUsersMessages($profile_id);
		
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$ProfilesMeta->removeMetaForProfile($profile_id);
		
		return true;
	}
}

class Application_Model_Profiles_Row extends Zend_Db_Table_Row_Abstract
{
}