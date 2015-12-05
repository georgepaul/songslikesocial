<?php

/**
 * Likes
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Likes extends Zend_Db_Table_Abstract
{

	protected $_name = 'likes';

	protected $_rowClass = 'Application_Model_Likes_Row';

	/**
	 * Like toggle
	 */
	public function toggleLike($resource_id, $resource_type)
	{
		if (! Zend_Auth::getInstance()->hasIdentity() || ! $resource_id || ! $resource_type)
			return null;
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		if ($this->isLiked($resource_id, $resource_type)) {
			
			$result = $this->delete(array(
				'resource_id = ?' => (int) $resource_id,
				'resource_type = ?' => $resource_type,
				'user_id = ?' => (int) $user_id
			));
			
			$state = 0;
		} else {
			
			$data = array(
				'user_id' => (int) $user_id,
				'resource_type' => $resource_type,
				'resource_id' => (int) $resource_id,
				'created_on' => Application_Plugin_Common::now()
			);
			
			$ret = $this->insert($data);
			
			$state = 1;
		}
		
		$likes_count = $this->getLikesCount($resource_id, $resource_type);
		
		// notify author
		$Notifications = new Application_Model_Notifications();
		
		if ($state == 1) {
			
			// find resource author
			switch ($resource_type) {
				case 'post':
					$Posts = new Application_Model_Posts();
					$resource_author = array(
						$Posts->getPostAuthorId($resource_id)
					);
					break;
				case 'comment':
					$Comments = new Application_Model_Comments();
					$resource_author = array(
						$Comments->getCommentAuthorId($resource_id)
					);
					break;
				case 'image':
					$Images = new Application_Model_Images();
					$resource_author = array(
						$Images->getImageOwnerId($resource_id)
					);
					break;
				default:
					$resource_author = false;
					break;
			}
			
			if ($resource_author) {
				// notify resource owner
				$Notifications->pushNotification($resource_author, 2, 'like', $ret);
			}
		}
		
		return array(
			'count' => $likes_count,
			'state' => $state
		);
	}

	/**
	 * Remove all user's likes
	 */
	public function removeUsersLikes($user_id)
	{
		return $this->delete(array(
			'user_id = ?' => $user_id
		));
	}

	/**
	 * Remove page likes
	 */
	public function removePageLikes($page_profile_id)
	{
		return $this->delete(array(
			'resource_type = ?' => 'page',
			'resource_id = ?' => $page_profile_id
		));
	}

	/**
	 * Get likes count for specific resource
	 */
	public function getLikesCount($resource_id, $resource_type)
	{
		$select = $this->select();
		$select->from('likes', new Zend_Db_Expr('COUNT(*)'));
		$select->where('resource_id = ?', $resource_id)->where('resource_type = ?', $resource_type);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Check if current user has liked this
	 */
	public function isLiked($resource_id, $resource_type)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return false;
		
		$current_user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$ret = $this->fetchRow($this->select()
			->where('user_id = ?', $current_user_id)
			->where('resource_type = ?', $resource_type)
			->where('resource_id = ?', $resource_id));
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * Get like by id
	 */
	public function getLikeById($id)
	{
		$ret = $this->fetchRow($this->select()
			->where('id = ?', $id));
		
		if ($ret === null)
			return false;
		
		return $ret;
	}

	/**
	 * Retrive all users liked this
	 */
	public function getUsersLiked($resource_type, $resource_id, $limit = 10)
	{
		$resource_type = $this->getDefaultAdapter()->quote($resource_type);
		$resource_id = (int) $resource_id;
		$limit = (int) $limit;
		$limit_sql = "";
		
		if ($limit > 0) {
			$limit_sql = " LIMIT " . $limit;
		}
		
		$sql = "
		SELECT
		u.name AS author_name,
		u.screen_name AS author_screen_name
		FROM likes l
		JOIN profiles u ON u.id = l.user_id
		WHERE l.resource_type = {$resource_type} AND l.resource_id = {$resource_id}
		AND u.is_hidden = 0
		ORDER BY l.id DESC
		{$limit_sql}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get likes for selected resources
	 */
	public function getLikesForPosts(array $post_ids)
	{
		if (empty($post_ids))
			return false;
		
		$user_id = 0;
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		foreach ($post_ids as &$post_id) {
			$post_id = (int) $post_id;
		}
		
		$posts_in = implode(',', $post_ids);
		
		$sql = "
		SELECT
		p.id AS post_id,
		COUNT(l.user_id) AS likes_count,
		sum(ls.user_id) AS is_liked
		
		FROM likes l
		
		JOIN posts p ON p.id = l.resource_id AND l.resource_type = 'post'
		LEFT JOIN likes ls ON ls.id = l.id AND ls.user_id = {$user_id}
		
		WHERE l.resource_id IN ({$posts_in})
		GROUP BY p.id
		";
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		// transform array
		$likes_fixed = array();
		
		if (! empty($result)) {
			foreach ($result as $like) {
				$likes_fixed[$like['post_id']] = array(
					'likes_count' => $like['likes_count'],
					'is_liked' => ($like['is_liked'] ? 1 : 0)
				);
			}
		}
		
		return $likes_fixed;
	}
}

class Application_Model_Likes_Row extends Zend_Db_Table_Row_Abstract
{
}