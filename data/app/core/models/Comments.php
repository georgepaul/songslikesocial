<?php

/**
 * Comments
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Comments extends Zend_Db_Table_Abstract
{

	protected $_name = 'comments';

	protected $_rowClass = 'Application_Model_Comments_Row';

	/**
	 * Get comments of selected resources
	 */
	public function getCommentsForResources(array $resource_ids, $resource_type, $show_hidden = false)
	{
		$user_id = 0;
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		foreach ($resource_ids as &$resource_id) {
			$resource_id = (int) $resource_id;
		}
		
		$resources_in = implode(',', $resource_ids);
		
		$resource_type = $this->getDefaultAdapter()->quote($resource_type);
		
		$hidden_comments_sql = ($show_hidden ? "" : "AND c.is_hidden = 0");
		
		$sql = "
		SELECT * FROM (
			SELECT
			c.id AS comment_id,
			c.created_on AS comment_created_on,
			c.content AS comment_content,
			c.resource_id AS comment_resource_id,
			u.id AS comment_author_id,
			u.name AS comment_name,
			u.screen_name AS comment_screen_name,
			u.avatar AS comment_avatar,
			lm.user_id AS is_liked,
			count(l.user_id) AS likes_count,
			r.user_id AS is_reported,
			pp.id AS post_wall_id,
			pp.name AS post_wall_name,
			pp.avatar AS post_wall_avatar,
			pp.type AS post_wall_type,
			pp.owner AS post_wall_owner
			
			FROM comments c
			LEFT JOIN profiles u ON u.id = c.author_id
			LEFT JOIN likes l ON l.resource_id = c.id AND l.resource_type = 'comment'
			LEFT JOIN likes lm ON lm.resource_id = c.id AND lm.resource_type = 'comment' AND lm.user_id = {$user_id}
			LEFT JOIN reports r ON r.resource_id = c.id AND r.resource_type = 'comment' AND r.user_id = {$user_id}
			
			LEFT JOIN posts p ON p.id = c.resource_id AND c.resource_type = 'post'
			LEFT JOIN profiles pp ON pp.id = p.wall_id
			
			WHERE u.is_hidden = 0 
			{$hidden_comments_sql}
			AND c.resource_type = {$resource_type}
			AND c.resource_id IN ($resources_in)
			
			GROUP BY c.id
			
			ORDER BY c.created_on DESC
		
		) d ORDER BY comment_created_on ASC
		";

		$result = $this->getAdapter()->fetchAll($sql);
		
		return $this->fixData($result);
	}

	/**
	 * transform comments array
	 */
	public function fixData($data)
	{
		$post_comments_fixed = array();
		
		foreach ($data as $post_comment) {
			$post_comment['comment_created_on'] = Application_Plugin_Common::getTimeElapsedString(strtotime($post_comment['comment_created_on']));
			$post_comments_fixed[$post_comment['comment_resource_id']][] = $post_comment;
		}
		
		return $post_comments_fixed;
	}

	/**
	 * Add comment
	 */
	public function addComment($content, $resource_id, $resource_type)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return false;
		if (! is_string($content) || ! is_string($resource_type) || strlen($content) < 1)
			return false;
		
		$content = Application_Plugin_Common::limitInput($content);
		
		$author_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		// find resource author
		switch ($resource_type) {
			case 'post':
				$Posts = new Application_Model_Posts();
				$resource_author = $Posts->getPostAuthorId($resource_id);
				
				$resource_wall = $Posts->getPostsWallProfileData($resource_id);

				// for page comments written by page admin switch owner to be a page itself
				if ($resource_wall['type'] == 'page' && $resource_wall['owner'] == $author_id) {
					$author_id = $resource_wall['id'];
					$resource_author = $author_id;
				}
				
				break;
			case 'image':
				$Images = new Application_Model_Images();
				$image = $Images->getImage($resource_id);
				$resource_author = $image['data']['uploaded_by'];
				break;
			default:
				$resource_author = 0;
				break;
		}
		
		$ret = $this->insert(array(
			'author_id' => $author_id,
			'resource_type' => $resource_type,
			'resource_id' => $resource_id,
			'created_on' => Application_Plugin_Common::now(),
			'content' => $content,
			'is_hidden' => 0
		));
		
		$this->markOldAsHidden($resource_type, $resource_id);
		
		$Notifications = new Application_Model_Notifications();
		
		// notify all users involved in comment discussion
		$notify_users = $this->getUsersCommented($resource_type, $resource_id, true);
		// notify resource author if not already on the list
		if (array_search($resource_author, $notify_users) === false) {
			$notify_users[] = $resource_author;
		}
		
		$Notifications->pushNotification($notify_users, 1, 'comment', $ret);
		
		// trigger hooks
		$data = array('comment_id' => $ret, 'content' => $content);
		Zend_Registry::get('hooks')->trigger('hook_data_aftersavecomment', $data);
		
		return $ret;
	}

	/**
	 * Retrive all users taking place in the resource discussion (comment authors)
	 */
	public function getUsersCommented($resource_type, $resource_id, $exclude_current_user = false)
	{
		$select = $this->select();
		$select->distinct()
			->from(array(
			'c' => 'comments'
		), 'author_id')
			->columns('author_id')
			->where('resource_type = ?', $resource_type)
			->where('resource_id = ?', $resource_id)
			->where('is_hidden = 0');
		
		$result = $this->getAdapter()->fetchAll($select);
		
		$ret = array();
		
		// consolidate
		if (! empty($result)) {
			foreach ($result as $row) {
				
				if ($exclude_current_user && $row['author_id'] == Zend_Auth::getInstance()->getIdentity()->id)
					continue;
				
				$ret[] = $row['author_id'];
			}
		}
		
		return $ret;
	}

	/**
	 * Get comment's author
	 */
	public function getCommentAuthorId($comment_id)
	{
		$select = $this->select();
		$select->from('comments', 'author_id');
		$select->where('id = ?', $comment_id);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Delete comment
	 */
	public function deleteComment($comment_id)
	{
		$comment_id = (int) $comment_id;
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		$current_user_role = Zend_Auth::getInstance()->getIdentity()->role;
		
		$sql = "
			DELETE c
			FROM comments c
			LEFT JOIN posts p ON p.id = c.resource_id AND c.resource_type = 'post'
			LEFT JOIN profiles pp ON pp.id = p.wall_id
			WHERE c.id = {$comment_id} 
			";
		
		// if not admin or reviewer add owner's restrictions
		// c.author_id = commet's author 
		// pp.id = user wall
		// pp.owner = page or group owner
		if ($current_user_role != 'admin' && $current_user_role != 'reviewer') {
			$sql .= " AND (c.author_id = {$current_user_id} OR pp.id = {$current_user_id} OR pp.owner = {$current_user_id})";
		}
		
		return $this->getAdapter()->query($sql);
	}

	/**
	 * Delete all user's comments
	 */
	public function deleteComments($author_id)
	{
		return $this->delete(array(
			'author_id = ?' => $author_id
		));
	}

	/**
	 * Delete all comments by resorce type
	 */
	public function deleteCommentsByResource($resource_type, $resource_id)
	{
		return $this->delete(array(
			'resource_type = ?' => $resource_type,
			'resource_id = ?' => $resource_id
		));
	}

	
	/**
	 * Mark old (10+) comments as hidden
	 */
	public function markOldAsHidden($resource_type, $resource_id, $maxlimit = 10)
	{
		$maxlimit = (int)$maxlimit;
		$resource_id = (int)$resource_id;
		$resource_type = $this->getDefaultAdapter()->quote($resource_type);
		
		$sql = "
			UPDATE comments SET is_hidden = 1
			 WHERE id IN (
			     SELECT id FROM (
			         SELECT * FROM comments
			         WHERE resource_type = {$resource_type}
			         AND resource_id = {$resource_id}
			         ORDER BY id  DESC
			         LIMIT {$maxlimit}, 100000000
			     ) tmp
			 );
			";

		$result = $this->getAdapter()->query($sql);
		
		return $result;
	}
	
	
	/**
	 * Mark as hidden
	 */
	public function markHidden($id)
	{
		$data = array(
			'is_hidden' => 1
		);
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$rows_updated = $this->update($data, $where);
		
		return ($rows_updated == 1 ? true : false);
	}

	/**
	 * Get single comment
	 */
	public function getComment($comment_id)
	{
		$select = $this->select();
		$select->where('id = ?', $comment_id);
		
		return $this->getAdapter()->fetchRow($select);
	}

	/**
	 * Update single comment
	 */
	public function updateComment($comment_id, $content)
	{	
		$user_role = Zend_Auth::getInstance()->getIdentity()->role;
		
		// check if my comment or an admin
		if ($this->getCommentAuthorId($comment_id) != Zend_Auth::getInstance()->getIdentity()->id && ($user_role != 'admin' && $user_role != 'reviewer')) {
			return false;
		}
		
		$content = Application_Plugin_Common::limitInput($content);
		
		$data = array(
			'content' => $content
		);
		
		$where = $this->getAdapter()->quoteInto('id = ?', $comment_id);
		
		$rows_updated = $this->update($data, $where);
		
		return ($rows_updated == 1 ? true : false);
	}
}

class Application_Model_Comments_Row extends Zend_Db_Table_Row_Abstract
{
}