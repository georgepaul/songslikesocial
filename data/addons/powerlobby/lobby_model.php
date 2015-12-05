<?php
/**
 * Power lobby add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class LobbyModel extends Zend_Db_Table_Abstract
{
	protected $_name = 'connections';

	// pagination
	public $page_number = 1;



	/**
	 *
	 * Return friend suggestions
	 *
	 * 50% - friend's friends
	 * 50% - users following the same users
	 */
	public function getFriendSuggestions($limit = 10)
	{
		if (!Zend_Auth::getInstance()->hasIdentity()) return false;

		// pagination difficult to implement

		$suggestions = $more_suggestions = array();

		// 50:50 ratio
		$limit_friendsfriends = (int)($limit / 2);
		$limit_usersfollowingsameusers = $limit - $limit_friendsfriends;

		// first let's try with friend's friends
		$suggestions = $this->getFriendsFriends($limit_friendsfriends);

		// skip found profiles
		$skip = array();
		if (!empty($suggestions)){
			foreach ($suggestions as $suggested_user)

				$skip[] = $suggested_user['id'];
		}

		// fill up remaining limit with users following the same users
		$remaining_space = $limit_friendsfriends - count($suggestions);

		$more_suggestions = $this->getUsersFollowingSameUsers($limit_usersfollowingsameusers + $remaining_space, $skip);

		return array_merge($suggestions, $more_suggestions);

	}


	/**
	 *
	 * Return all close friends of current user's close friends (not yet followed)
	 */
	public function getFriendsFriends($limit = 10)
	{
		$user_id = (int)Zend_Auth::getInstance()->getIdentity()->id;

		$sql = "
		SELECT
		
		u2.id,
		u2.name,
		u2.screen_name,
		u2.avatar,
		u2.cover
		
		FROM profiles u
		JOIN connections followers ON followers.user_id = u.id AND followers.follow_id = {$user_id}
		JOIN connections following ON following.follow_id = followers.user_id AND following.user_id = {$user_id}
			
		JOIN profiles nongroups ON nongroups.id = following.follow_id AND nongroups.type = 'user'
			
		JOIN connections ffollowers ON ffollowers.user_id = nongroups.id AND ffollowers.follow_id <> {$user_id}
		JOIN connections ffollowing ON ffollowing.follow_id = ffollowers.user_id AND ffollowing.user_id = ffollowers.follow_id

		LEFT JOIN connections iamfollowing ON iamfollowing.follow_id = ffollowing.user_id AND iamfollowing.user_id = {$user_id}

		JOIN profiles u2 ON u2.id = ffollowing.user_id
		
		WHERE iamfollowing.user_id IS NULL
		AND u2.is_hidden = 0
		AND u2.type = 'user'

		GROUP BY u2.id
		LIMIT {$limit}
		";

		return $this->getAdapter()->fetchAll($sql);
	}



	/**
	 *
	 * Return popular users with most followers
	 * if user is logged in then skip users already followed
	 * skip users/groups with 'friends' security settings
	 */
	public function getPopularProfiles($type, $limit = false, $count_only = false)
	{
		$type = $this->getDefaultAdapter()->quote($type);
		$limit = (int)$limit;

		if ($this->page_number < 1) $this->page_number = 1;

		// pagination limit
		$limit_from = ((int)$this->page_number - 1 ) * (int)Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int)Zend_Registry::get('config')->get('pagination_limit');

		// custom limit
		if ($limit){
			$limit_from = 0;
			$limit_to = (int)$limit;
		}

		if (Zend_Auth::getInstance()->hasIdentity()){
			$nouser_id = (int)Zend_Auth::getInstance()->getIdentity()->id;
		}else{
			$nouser_id = 0;
		}

		$sql = "";

		if ($count_only){
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}
		$sql .= "
		SELECT
		
		u.id,
		u.name,
		u.screen_name,
		u.avatar,
		u.cover,
		count(fs.follow_id) AS no_of_followers, 
		MAX(fs.created_on) AS last_friend_stamp

		FROM profiles u
		LEFT JOIN connections f ON f.follow_id = u.id AND f.user_id = {$nouser_id}
		LEFT JOIN connections fs ON fs.follow_id = u.id
		
		WHERE u.id <> {$nouser_id} AND f.user_id IS NULL
		AND u.is_hidden = 0
		AND u.type = {$type}
		AND u.profile_privacy <> 'friends'

		GROUP BY u.id
		ORDER BY no_of_followers DESC, last_friend_stamp DESC
		";

		if ($count_only){
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		}else{
			$sql .= " LIMIT {$limit_from}, {$limit_to}";
		}

		return $this->getAdapter()->fetchAll($sql);
	}


	/**
	 *
	 * Return friend suggestions
	 * return unfolowed ppl who are following the same ppl as the curent user
	 */
	public function getUsersFollowingSameUsers($limit = 10, $skip_user_ids = false)
	{
		$user_id = (int)Zend_Auth::getInstance()->getIdentity()->id;
		$limit = (int)$limit;
		$skip_sql = "";

		if ($skip_user_ids && is_array($skip_user_ids) && !empty($skip_user_ids)){
			$skip_sql = " AND u2.id NOT IN (".implode(",", $skip_user_ids).") ";
		}

		$sql = "
		SELECT
		
		u2.id,
		u2.name,
		u2.screen_name,
		u2.avatar,
		u2.cover
		
		FROM profiles u
		LEFT JOIN connections potential ON potential.follow_id = u.id AND potential.user_id = {$user_id}
		LEFT JOIN connections followers ON followers.follow_id = u.id
		LEFT JOIN connections iamfollowing ON iamfollowing.follow_id = followers.user_id AND iamfollowing.user_id = {$user_id}
		LEFT JOIN profiles u2 ON followers.user_id = u2.id
		
		WHERE u2.id <> {$user_id} AND potential.user_id IS NOT NULL AND iamfollowing.user_id IS NULL
		{$skip_sql}
		AND u2.is_hidden = 0
		AND u2.type = 'user' AND u.type = 'user'

		GROUP BY u2.id
		LIMIT {$limit}
		";

		return $this->getAdapter()->fetchAll($sql);
	}


	/**
	 *
	 * Return popular pages
	 */
	public function getPopularPages($limit = false, $count_only = false)
	{
		$limit = (int)$limit;

		if ($this->page_number < 1) $this->page_number = 1;

		// pagination limit
		$limit_from = ((int)$this->page_number - 1 ) * (int)Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int)Zend_Registry::get('config')->get('pagination_limit');

		// custom limit
		if ($limit){
			$limit_from = 0;
			$limit_to = (int)$limit;
		}

		$sql = "";

		if ($count_only){
			$sql .= "SELECT count(*) AS total_rows FROM (";
		}

		$sql .= "
				SELECT
			
				u.id,
				u.name,
				u.screen_name,
				u.avatar,
				u.cover,
				count(l.user_id) AS total_likes

				FROM profiles u
				LEFT JOIN likes l ON l.resource_id = u.id AND l.resource_type = 'page'
				WHERE u.is_hidden = 0
				AND u.type = 'page'

				GROUP BY u.id
				ORDER BY total_likes DESC
				";

		if ($count_only){
			$sql .= ") AS count_temp";
			return $this->getAdapter()->fetchOne($sql);
		}else{
			$sql .= " LIMIT {$limit_from}, {$limit_to}";
		}

		return $this->getAdapter()->fetchAll($sql);
	}


	/**
	 *
	 * Return online users
	 */
	public function getOnlineUsers()
	{
		// user is online based on last heartbeat
		$active_timespan = time() - Zend_Registry::get('config')->get('heartbeatfreq') - 1;
		$limit = Zend_Registry::get('config')->get('sidebar_max_users');

		$sql = "
		SELECT
		
		p.id,
		p.name,
		p.screen_name,
		p.avatar,
		p.cover
		
		FROM profiles p
		JOIN profile_meta pm ON pm.profile_id = p.id AND pm.meta_key = 'show_online_status' AND pm.meta_value = 's'
		JOIN profile_meta pmt ON pmt.profile_id = pm.profile_id AND pmt.meta_key = 'last_heartbeat'

		WHERE p.is_hidden = 0
		AND p.type = 'user'
		AND pmt.meta_value > {$active_timespan}
		LIMIT {$limit}
		";
		
		return $this->getAdapter()->fetchAll($sql);

	}

}