<?php

/**
 * Posts
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Posts extends Zend_Db_Table_Abstract
{

	protected $_name = 'posts';

	protected $_rowClass = 'Application_Model_Posts_Row';
	
	public $show_hidden_comments = false;
	
	// pagination
	public $page_number = 1;

	/**
	 * Get posts
	 */
	public function getPosts($wall_id = null, $single_post = false, $search = false, $recursion = false)
	{
		$is_group = false;
		$is_page = false;
		
		// check if we need posts for group, disable privacy later
		if ($wall_id) {
			$Frofiles = new Application_Model_Profiles();
			$profile = $Frofiles->getProfileByField('id', $wall_id);
			$profile_type = $profile->type;
			
			if ($profile->type === 'group') {
				$is_group = true;
			}
			
			if ($profile->type === 'page') {
				$is_page = true;
			}
		}
		
		$wall_sql = '';
		$single_sql = '';
		$wall_id = (int) $wall_id;
		
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$current_user = (int) Zend_Auth::getInstance()->getIdentity()->id;
			$current_user_role = Zend_Auth::getInstance()->getIdentity()->role;
		} else {
			$current_user = 0;
			$current_user_role = 0;
		}
		
		// viewing walls
		// include own posts and posts on this wall by friends
		// do not show pages posts on users profile
		if ($wall_id > 0) {
			$wall_sql = " AND u2.type <> 'page' AND (p.author_id = {$wall_id} OR (p.wall_id = {$wall_id})) ";
		}
		
		// reset to show all posts on page wall
		if ($is_page) {
			$wall_sql = " AND p.wall_id = {$wall_id} ";
		}
		
		// logged in and viewing timeline or search within timeline
		// include own posts and posts from users current user is following
		// fgb table connect post from groups user is a part of
		// do not show page posts unless user likes this page
		if ($current_user > 0 && $wall_id == 0) {
			
			$wall_sql = " AND (	
								(u2.type <> 'page' AND  (f.user_id = {$current_user} OR p.author_id = {$current_user} OR fgb.user_id IS NOT NULL))
									OR
								(u2.type = 'page' AND l.id IS NOT NULL)
							  )
			";
		}
		
		// show single post only, clear follow and wall restrictions (privacy will apply later on)
		if ($single_post) {
			$wall_sql = "";
			$single_post = (int) $single_post;
			$single_sql = " AND p.id = {$single_post} ";
		}
		
		if ($this->page_number < 1)
			$this->page_number = 1;
		
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('limit_posts');
		$limit_to = (int) Zend_Registry::get('config')->get('limit_posts');
		
		// limit max number of ifinitive scroll loads
		if (Zend_Registry::get('config')->get('max_scroll_fetches') && ($this->page_number - 1) > Zend_Registry::get('config')->get('max_scroll_fetches')) {
			$limit_to = $limit_from = 0;
		}
		
		// simple one limits for a single post
		if ($single_post) {
			$limit_from = 0;
			$limit_to = 1;
		}
		
		// search for posts that match your feed or all posts
		if ($search && is_array($search)) {
			
			if ($search['context'] == 'all') {
				// reset to see all
				$wall_sql = "";
			}
			
			// important security quote
			$search_string = str_replace('%', '', $search['term']);
			$search_string = $this->getDefaultAdapter()->quote('%' . $search_string . '%');
			
			$search_sql = " AND p.content like {$search_string} ";

		} else {
			$search_sql = "";
		}
		
		// disable privacy for admin and reviewer
		// disable privacy for groups and pages since privacy is applied before this fn is called
		if ($current_user_role === 'admin' || $current_user_role === 'reviewer' || $is_group || $is_page) {
			$privacy_sql = "";
		} else {
			$privacy_sql = " AND (p.privacy = 'public' OR (u2.type = 'page') ";
			
			if ($current_user > 0 && $current_user != $wall_id) {
				$privacy_sql .= "
				OR (p.author_id = {$current_user})
				OR (p.privacy = 'everyone')
				OR (p.privacy = 'followers' AND f.user_id = {$current_user})
				OR (p.privacy = 'friends' AND f.user_id = {$current_user} AND fa.follow_id = {$current_user} AND u2.type != 'group')
				OR (p.privacy = 'friends' AND fg.user_id = {$current_user} AND fgb.follow_id = {$current_user})
				";
			} elseif ($current_user > 0 && $current_user == $wall_id) {
				$privacy_sql .= "
				OR (p.author_id = {$current_user})
				OR (p.privacy = 'everyone')
				OR (p.privacy = 'followers' AND f.user_id = {$current_user})
				OR (p.privacy = 'friends' AND p.wall_id = {$current_user})
				OR (p.privacy = 'friends' AND u2.profile_privacy = 'everyone')
				OR (p.privacy = 'friends' AND fg.user_id = {$current_user} AND fgb.follow_id = {$current_user})
				";
				}
			
			if ($current_user) {
				$privacy_sql .= " OR (u2.type = 'group' AND (u2.profile_privacy = 'public' OR u2.profile_privacy = 'everyone')) ";
			} else {
				$privacy_sql .= " OR (u2.type = 'group' AND u2.profile_privacy = 'public') ";
			}
			
				$privacy_sql .= " ) ";
			
			
		}
		
		// selective join optimization for large databases
		if (! $current_user && ! $wall_id) {
			$connections_sql = "";
		} elseif ($wall_id || $single_post) {
			$connections_sql = "
			LEFT JOIN connections f ON f.follow_id = p.author_id AND f.user_id = {$current_user}
			LEFT JOIN connections fa ON fa.user_id = p.author_id AND fa.follow_id = {$current_user}
			LEFT JOIN connections fg ON fg.follow_id = p.wall_id AND fg.user_id = {$current_user}
			LEFT JOIN connections fgb ON fgb.user_id = fg.follow_id AND fgb.follow_id = {$current_user}
			";
		} elseif ($current_user && ! $wall_id) {
			$connections_sql = "
			LEFT JOIN connections f ON f.follow_id = p.author_id AND f.user_id = {$current_user}
			LEFT JOIN connections fa ON fa.user_id = p.author_id AND fa.user_id = {$current_user}
			LEFT JOIN connections fg ON fg.follow_id = p.wall_id AND fg.user_id = {$current_user}
			LEFT JOIN connections fgb ON fgb.user_id = fg.follow_id AND fgb.follow_id = {$current_user}
			LEFT JOIN likes l ON l.resource_id = p.wall_id AND l.resource_type = 'page' AND l.user_id = {$current_user}
			";
		}
		
		$sql = '';
		
		$sql .= "
		SELECT
		u1.screen_name AS user_screen_name,
		u1.id AS author_id,
		p.wall_id AS post_wall_id,
		u2.name AS post_wall_name,
		u2.screen_name AS post_wall_screen_name,
		u2.avatar AS post_wall_avatar,
		u2.type AS post_wall_profile_type,
		u2.owner AS post_wall_profile_owner,
		u1.name AS user_name,
		u1.avatar AS user_avatar,
		p.id AS post_id,
		p.content AS post_content,
		p.created_on AS post_created_on,
		p.privacy AS post_privacy,
		pm.meta_value AS rich_content_json

		FROM posts p
		LEFT JOIN post_meta pm ON p.id = pm.post_id AND pm.meta_key = 'rich_content'
		JOIN profiles u1 ON u1.id = p.author_id
		JOIN profiles u2 ON u2.id = p.wall_id

		{$connections_sql}

		WHERE p.is_hidden = 0 AND u1.is_hidden = 0 AND u2.is_hidden = 0

		{$privacy_sql}
		{$wall_sql}
		{$single_sql}
		{$search_sql}

		GROUP BY p.id
		ORDER BY p.id DESC

		LIMIT {$limit_from}, {$limit_to}
		";

		$result = $this->getAdapter()->fetchAll($sql);
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_data_prefixfetchposts', $result);
		
		$data = $this->fixData($result);
		
		// trigger hooks
		if (! $recursion)
		Zend_Registry::get('hooks')->trigger('hook_data_postfetchposts', $data);
		
		return $data;
		
	}

	/**
	 * Fix posts data
	 */
	public function fixData($data)
	{
		if (! is_array($data) || empty($data))
			return;
		
		$all_post_ids = $all_post_authors = array();
		
		// find all post id's and authors
		foreach ($data as $tmp) {
			$all_post_ids[] = $tmp['post_id'];
			$all_post_authors[] = $tmp['author_id'];
		}
		
		// retrieve comments for these posts
		$Comments = new Application_Model_Comments();
		$post_comments = $Comments->getCommentsForResources($all_post_ids, 'post', $this->show_hidden_comments);
		
		// retrieve attached images for these posts
		$Images = new Application_Model_Images();
		$post_images = $Images->getPostsImages($all_post_ids);
		
		// retrieve likes for these posts
		$Likes = new Application_Model_Likes();
		$post_likes = $Likes->getLikesForPosts($all_post_ids, 'post');
		
		// retrieve authors meta data
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$profiles_meta = $ProfilesMeta->getMetaForProfiles($all_post_authors);
		
		// fix posts
		foreach ($data as &$post) {
			
			// depreciated
			$post['is_reported'] = 0;
			
			// blend in comments
			if (! empty($post_comments[$post['post_id']])) {
				
				$comments = $post_comments[$post['post_id']];
				// trigger comments hooks
				Zend_Registry::get('hooks')->trigger('hook_data_comments', $comments);
				$post['comments'] = $comments;
			}
			
			// blend in images
			if (! empty($post_images[$post['post_id']])) {
				$post['post_images'] = $post_images[$post['post_id']];
			}
			
			// blend in likes
			if (! empty($post_likes[$post['post_id']])) {
				$post['likes_count'] = $post_likes[$post['post_id']]['likes_count'];
				$post['is_liked'] = $post_likes[$post['post_id']]['is_liked'];
			} else {
				$post['likes_count'] = 0;
				$post['is_liked'] = 0;
			}
			
			// blend in author meta_data
			if (! empty($profiles_meta[$post['author_id']])) {
				$post['author_meta'] = $profiles_meta[$post['author_id']];
			}
			
			// shares
			if (isset($post['rich_content_json'])){
			
				$rich_content = json_decode($post['rich_content_json']);
			
				if ($rich_content->type == 'share') {
			
					$original_post_id = (int) $rich_content->data->post;

					if ($original_post_id) {
						
						$original_post = $this->getPosts(null, $original_post_id, false, true);

						if ($original_post !== null) {
							// copy from original
							$copycat = $post;
							$post = $original_post[0];
							$post['copycat'] = $copycat;
						} else {
							// original content was removed
							$translator = Zend_Registry::get('Zend_Translate');
							$post['post_content'] = $translator->translate('Resource not available');
							
						}

					}
				}
			}
			
			// trigger post hooks
			Zend_Registry::get('hooks')->trigger('hook_data_postcontent', $post);
		}
		
		return $data;
	}

	/**
	 * Add new post
	 */
	public function addPost(array $content, $wall_id, $privacy, $attached_files)
	{
		if (! Zend_Auth::getInstance()->hasIdentity() || (strlen($content['content']) < 1 && empty($attached_files)))
			return false;
		
		$content['content'] = Application_Plugin_Common::limitInput($content['content']);
		
		$Connections = new Application_Model_Connections();
		$Profiles = new Application_Model_Profiles();
		$Images = new Application_Model_Images();
		$PostsMeta = new Application_Model_PostsMeta();
		
		$wall_profile = $Profiles->getProfileByField('id', $wall_id);
		
		$author_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$insert_id = $this->insert(array(
			'author_id' => $author_id,
			'wall_id' => $wall_id,
			'created_on' => Application_Plugin_Common::now(),
			'content' => $content['content'],
			'is_hidden' => 0,
			'privacy' => $privacy
		));
		
		// write post's meta data
		if (isset($content['meta'])) {
			foreach ($content['meta'] as $metakey => $metavalue) {
				$ret = $PostsMeta->metaUpdate($insert_id, $metakey, $metavalue);
			}
		}
		
		// move tmp file to posts folder and add meta data to post
		if (! empty($attached_files)) {
			
			$i = 0;
			foreach ($attached_files as $file) {
				
				++ $i;
				
				$file_data = array(
					'name' => basename($file),
					'size' => filesize($file)
				);
				
				// check max images per post
				if ($i > Zend_Registry::get('config')->get('max_images_per_post'))
					break;
				
				$Storage = new Application_Model_Storage();
				$StorageAdapter = $Storage->getAdapter();
				
				$original_filename = '';
				if (Zend_Registry::get('config')->get('resample_images')) {
					
					Application_Plugin_ImageLib::resample(TMP_PATH . '/' . $file_data['name'], TMP_PATH . '/thumb_' . $file_data['name']);
					$filename = $StorageAdapter->moveFileToStorage('thumb_' . $file_data['name'], 'posts');
					
					if (Zend_Registry::get('config')->get('keep_original')) {
						$original_filename = $StorageAdapter->moveFileToStorage($file_data['name'], 'posts');
					} else {
						$original_filename = '';
						unlink(TMP_PATH . '/' . $file_data['name']); // clean up
					}
				} else {
					$filename = $StorageAdapter->moveFileToStorage($file_data['name'], 'posts');
					
				}
				
				// in case this is not a user's wall - image owner will become the network
				// (image owner could become the wall owner but that's a bad idea)
				if ($wall_profile['id'] != $author_id) {
					$owner = 0;
				} else {
					$owner = $author_id;
				}
				
				$Images->addImage($filename, $file_data['size'], $owner, $author_id, $insert_id, 0, $original_filename);
			}
		}
		
		// post on someone else's wall, notify wall owner
		if ($wall_profile['type'] === 'user' && $wall_id != $author_id) {
			$Notifications = new Application_Model_Notifications();
			$Notifications->pushNotification(array(
				$wall_id
			), 7, 'post', $insert_id);
		}
		
		// trigger hooks
		$data = array('post_id' => $insert_id, 'content' => $content);
		Zend_Registry::get('hooks')->trigger('hook_data_aftersavepost', $data);
		
		return true;
	}

	/**
	 * Get post's author id
	 */
	public function getPostAuthorId($post_id)
	{
		$select = $this->select();
		$select->from('posts', 'author_id');
		$select->where('id = ?', $post_id);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Get post's author datra
	 */
	public function getPostsWallProfileData($post_id)
	{
		$post_id = (int) $post_id;
		
		$sql = "
				SELECT a.*
				FROM posts p
				JOIN profiles a ON a.id = p.wall_id
				WHERE p.id = {$post_id}
				";
		
		return $this->getAdapter()->fetchRow($sql);
	}

	/**
	 * Get post's author data
	 */
	public function getProfileDataByPostWall($post_id)
	{
		$post = $this->getPost($post_id);
		$wall_id = $post['wall_id'];
		
		$Profiles = new Application_Model_Profiles();
		$profile = $Profiles->getProfileByField('id', $wall_id);
		
		return $profile;
	}

	/**
	 * Get post's wall id
	 */
	public function getPostWallId($post_id)
	{
		$select = $this->select();
		$select->from('posts', 'wall_id');
		$select->where('id = ?', $post_id);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Get all posts by author
	 */
	public function getPostsByAuthor($author_id)
	{
		$select = $this->select();
		$select->from('posts');
		$select->where('author_id = ?', $author_id);
		
		return $this->getAdapter()->fetchAll($select);
	}

	/**
	 * Get all posts on specific wall
	 */
	public function getPostsOnWall($wall_id)
	{
		$select = $this->select();
		$select->from('posts');
		$select->where('wall_id = ?', $wall_id);
		
		return $this->getAdapter()->fetchAll($select);
	}

	/**
	 * Delete all posts by author
	 */
	public function removeUsersPosts($author_id)
	{
		$user_posts = $this->getPostsByAuthor($author_id);
		
		if (! empty($user_posts)) {
			foreach ($user_posts as $post) {
				$ret = $this->deletePost($post['id']);
			}
		}
		
		$wall_posts = $this->getPostsOnWall($author_id);
		
		if (! empty($wall_posts)) {
			foreach ($wall_posts as $post) {
				$ret = $this->deletePost($post['id']);
			}
		}
		
		return;
	}

	/**
	 * Delete post
	 */
	public function deletePost($post_id)
	{
		$post_wall_data = $this->getPostsWallProfileData($post_id);

		// check if my post or on my wall
		if ($this->getPostAuthorId($post_id) != Zend_Auth::getInstance()->getIdentity()->id && $post_wall_data['owner'] != Zend_Auth::getInstance()->getIdentity()->id && $this->getPostWallId($post_id) != Zend_Auth::getInstance()->getIdentity()->id && Zend_Auth::getInstance()->getIdentity()->role !== 'admin' && Zend_Auth::getInstance()->getIdentity()->role !== 'reviewer') {
			return false;
		}
		
		// delete post's images
		$Images = new Application_Model_Images();
		$Images->deletePostImages($post_id);
		
		// delete post's meta data
		$PostsMeta = new Application_Model_PostsMeta();
		$PostsMeta->metaRemove($post_id);
		
		// delete connected comments, likes and reports
		$this->deleteConnectedResourcesData('post', $post_id);
		
		// delete post and return
		return $this->delete(array(
			'id = ?' => $post_id
		));
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
	 * Get single post row
	 */
	public function getPost($post_id, $check_ownership = false)
	{
		$select = $this->select();
		$select->where('id = ?', $post_id);
		
		$row = $this->getAdapter()->fetchRow($select);
		
		if ($row && $check_ownership) {
			
			if (! Zend_Auth::getInstance()->hasIdentity())
				return false;
			
			$current_user = Zend_Auth::getInstance()->getIdentity();
			
			if ($current_user->role == 'admin' || $current_user->role == 'reviewer')
				return $row;
			
			if ($current_user->id != $row['author_id'])
				return false;
		}
		
		return $row;
	}

	/**
	 * Update single post row
	 */
	public function updatePost($post_id, array $content, $privacy)
	{
		$PostsMeta = new Application_Model_PostsMeta();
		
		$content['content'] = Application_Plugin_Common::limitInput($content['content']);
		
		$data = array(
			'content' => $content['content'],
			'privacy' => $privacy
		);
		
		$where = $this->getAdapter()->quoteInto('id = ?', $post_id);
		
		$rows_updated = $this->update($data, $where);
		
		// write post's meta data
		if (isset($content['meta'])) {
			foreach ($content['meta'] as $metakey => $metavalue) {
				$PostsMeta->metaUpdate($post_id, $metakey, $metavalue);
			}
		} else {
			$PostsMeta->metaRemove($post_id);
		}
		
		return ($rows_updated == 1 ? true : false);
	}

	/**
	 * Delete all data related to $resource_type/$resource_id
	 */
	public function deleteConnectedResourcesData($resource_type, $resource_id)
	{
		$resource_type = $this->getDefaultAdapter()->quote($resource_type);
		$resource_id = (int) $resource_id;
		
		// delete related comments
		$sql = "
		DELETE FROM comments WHERE
		resource_type = $resource_type AND resource_id = {$resource_id}
		";
		$this->getAdapter()->query($sql);
		
		// delete related likes
		$sql = "
		DELETE FROM likes WHERE
		resource_type = $resource_type AND resource_id = {$resource_id}
		";
		$this->getAdapter()->query($sql);
		
		// delete related reports
		$sql = "
		DELETE FROM reports	WHERE
		resource_type = $resource_type AND resource_id = {$resource_id}
		";
		$this->getAdapter()->query($sql);
		
		return;
	}

	
	/**
	 * Share post to users wall
	 */
	public function sharePostToWall($post_id)
	{
		if (! Zend_Auth::getInstance()->hasIdentity()) return false;
		
		$post_id = (int) $post_id;
		
		$post = $this->getPost($post_id);
		
		$author_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$insert_id = $this->insert(array(
			'author_id' => $author_id,
			'wall_id' => $author_id,
			'created_on' => Application_Plugin_Common::now(),
			'content' => '',
			'is_hidden' => 0,
			'privacy' => $post['privacy'],
		));
		
		// write post's meta data
		$PostsMeta = new Application_Model_PostsMeta();
		
		$json = json_encode(array(
			'type' => 'share', 
			'data' => array('post' => $post_id)));
		
		$PostsMeta->metaUpdate($insert_id, 'rich_content', $json);

		return;
	}
}

class Application_Model_Posts_Row extends Zend_Db_Table_Row_Abstract
{
}