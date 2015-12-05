<?php

/**
 * Images
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Images extends Zend_Db_Table_Abstract
{

	protected $_name = 'images';

	protected $_rowClass = 'Application_Model_Images_Row';
	
	public $show_hidden_comments = false;
	
	// pagination
	public $page_number = 1;

	/**
	 * add image
	 *
	 * albums: 1 - posts
	 */
	public function addImage($file_name, $file_size, $owner_id, $uploaded_by, $post_id, $album_id, $original = '')
	{
		$random = Application_Plugin_Common::getRandomString();
		
		$data = array(
			'uid' => $random,
			'file_name' => $file_name,
			'original' => $original,
			'owner_id' => $owner_id,
			'uploaded_by' => $uploaded_by,
			'post_id' => $post_id,
			'album_id' => $album_id,
			'size' => $file_size,
			'created_on' => Application_Plugin_Common::now(),
			'is_hidden' => 0
		);
		
		$ret = $this->insert($data);
		
		return $ret;
	}

	/**
	 * get all images for posts
	 */
	public function getPostsImages(array $posts)
	{
		foreach ($posts as &$post_id)
			$post_id = (int) $post_id;
		
		$posts_in = implode(',', $posts);
		
		$sql = "
		SELECT
		img.id,
		img.post_id,
		img.file_name,
		img.size

		FROM images img
		WHERE is_hidden = 0
		AND img.post_id IN ($posts_in)
		";
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		$post_images = array();
		
		// transform to array
		if ($result){
			foreach ($result as $row) {
				$post_images[$row['post_id']][] = array(
					'image_id' => $row['id'],
					'file_name' => $row['file_name'],
					'size' => $row['size'],
				);
			}
		}
		
		return $post_images;
	}

	/**
	 * get image and previous/next
	 */
	public function getImage($image_id, $context = false)
	{
		$image_id_clean = $this->getDefaultAdapter()->quote($image_id);
		
		$prev_img_id = $image_id;
		$next_img_id = $image_id;
		
		// find post / album
		$sql = "
		SELECT
		i.*,
		a.name AS album_name,
		a.id AS album_id,
		p.name AS uploaded_by_name,
		p.avatar AS uploaded_by_avatar,
		p.screen_name AS uploaded_by_screen_name
		
		FROM images i
		LEFT JOIN albums a ON a.id = i.album_id
		LEFT JOIN profiles p ON p.id = i.uploaded_by AND i.owner_id > 0
		
		WHERE i.is_hidden = 0
		AND i.id = {$image_id_clean}
		";
		
		$current_image = $this->getAdapter()->fetchRow($sql);
		
		if (empty($current_image))
			return false;
		
		switch ($context) {
			case 'post':
				$post_id = $current_image['post_id'];
				$where = " AND post_id = {$post_id} ";
				break;
			
			case 'album':
				$album_id = $current_image['album_id'];
				$where = " AND album_id = {$album_id} ";
				break;
			
			case 'images':
				$owner_id = $current_image['owner_id'];
				$where = " AND owner_id = {$owner_id} ";
				break;
			
			default:
				$where = "";
				break;
		}
		
		$sql = "
		SELECT
		*
		FROM images
		WHERE is_hidden = 0
		{$where}
		ORDER BY id
		";
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		$prev_img_id = '';
		$next_img_id = '';
		$i = 0;
		
		if ($context != 'single') {
			foreach ($result as $img) {
				++ $i;
				if ($img['id'] == $image_id) {
					$next_img_id = (isset($result[$i]) ? $result[$i]['id'] : $img['id']);
					break;
				}
				
				$prev_img_id = $img['id'];
			}
		}
		
		if ($prev_img_id == $current_image['id'])
			$prev_img_id = '';
		if ($next_img_id == $current_image['id'])
			$next_img_id = '';
		
		return array(
			'data' => $current_image,
			'prev' => $prev_img_id,
			'next' => $next_img_id
		);
	}

	/**
	 * get image by unique id
	 */
	public function getImageByUID($image_uid)
	{
		$image_uid_clean = $this->getDefaultAdapter()->quote($image_uid);
		
		$sql = "
		SELECT
		i.*,
		a.name AS album_name,
		a.id AS album_id,
		p.name AS uploaded_by_name,
		p.avatar AS uploaded_by_avatar,
		p.screen_name AS uploaded_by_screen_name
			
		FROM images i
		LEFT JOIN albums a ON a.id = i.album_id
		LEFT JOIN profiles p ON p.id = i.uploaded_by AND i.owner_id > 0
	
		WHERE i.is_hidden = 0
		AND i.uid = {$image_uid_clean}
		";
		
		$current_image = $this->getAdapter()->fetchRow($sql);
		
		if (empty($current_image))
			return false;
		
		return $current_image;
	}

	/**
	 * get images
	 */
	public function getImages($owner_id, $album_id = false, $count_only = false, $limit = false)
	{
		$where_user = ' AND ' . $this->getDefaultAdapter()->quoteInto('owner_id = ?', $owner_id);
		
		if ($album_id) {
			$where_album = ' AND ' . $this->getDefaultAdapter()->quoteInto('album_id = ?', $album_id);
		} else {
			$where_album = '';
		}
		
		if ($limit == - 1) {
			$limits = ''; // no limit
		} elseif ($limit) {
			$limits = ' LIMIT ' . (int) $limit . ' '; // custom limit
		} else {
			$limits = $this->setSqlLimits(); // pagination limit
		}
		
		if ($count_only) {
			
			$sql = "
			SELECT
			count(*)
			FROM images
			WHERE is_hidden = 0
			{$where_user}
			{$where_album}
			";
			
			return $this->getAdapter()->fetchOne($sql);
		}
		
		$sql = "
		SELECT
		*
		FROM images
		WHERE is_hidden = 0
		{$where_user}
		{$where_album}
		ORDER BY id
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}
	
	/**
	 * get per user storage usage
	 */
	public function getStorageUsage($profile_id)
	{

		$profile_id = (int)$profile_id;
		
		$sql = "
		SELECT
		count(*) AS image_count,
		sum(size) AS image_size
		FROM images
		WHERE uploaded_by = {$profile_id}
		";
	
		$ret = $this->getAdapter()->fetchAll($sql);

		return $ret[0];
	}

	/**
	 * Get image owner
	 */
	public function getImageOwnerId($image_id)
	{
		$select = $this->select();
		$select->from('images', 'uploaded_by');
		$select->where('id = ?', $image_id);
		
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Set SQL limits
	 */
	public function setSqlLimits()
	{
		if ($this->page_number < 1)
			$this->page_number = 1;
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		return " LIMIT {$limit_from}, {$limit_to} ";
	}

	/**
	 * Update selected field
	 */
	public function updateField($where_id, $field, $value, $check_ownership = true)
	{
		$data = array(
			$field => $value
		);
		
		$where = array();
		$where[] = $this->getAdapter()->quoteInto('id = ?', $where_id);
		
		if ($check_ownership) {
			$where[] = $this->getAdapter()->quoteInto('uploaded_by = ?', Zend_Auth::getInstance()->getIdentity()->id);
		}
		
		$ret = $this->update($data, $where);
		
		if ($ret == 0)
			return false;
			
			// return true on success
		return true;
	}

	/**
	 * Rotate image
	 */
	public function rotateImage($image_id)
	{
		$image = $this->getImage($image_id);
		
		// check if image exists and this is the owner
		if (! $image || ! Zend_Auth::getInstance()->hasIdentity() || $image['data']['uploaded_by'] != Zend_Auth::getInstance()->getIdentity()->id) {
			return false;
		}
		
		$file_name = $image['data']['file_name'];
		$tmp_file_name = 'edit_' . $file_name;
		
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();
		
		$StorageAdapter->getFileFromStorage($file_name, $tmp_file_name, 'posts');
		
		$ret = Application_Plugin_ImageLib::rotate(TMP_PATH . '/' . $tmp_file_name);
		
		if ($ret) {
			$StorageAdapter->deleteFileFromStorage($file_name, 'posts');
			
			$new_filename = $StorageAdapter->moveFileToStorage($tmp_file_name, 'posts');
			$this->updateField($image['data']['id'], 'file_name', $new_filename);
		}
		
		return $new_filename;
	}

	/**
	 * Delete image
	 */
	public function deleteImage($image_id, $resource)
	{
		$image = $this->getImage($image_id);
		
		// check if owner or an admin
		if ($this->getImageOwnerId($image_id) != Zend_Auth::getInstance()->getIdentity()->id && Zend_Auth::getInstance()->getIdentity()->role !== 'admin') {
			return false;
		}
		
		// delete connected comments, likes and reports
		$Posts = new Application_Model_Posts();
		$Posts->deleteConnectedResourcesData('image', $image_id);
		
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();
		$StorageAdapter->deleteFileFromStorage($image['data']['file_name'], $resource);
		
		if ($image['data']['original']) {
			$StorageAdapter->deleteFileFromStorage($image['data']['original'], $resource);
		}
		
		$result = $this->delete(array(
			'id = ?' => $image_id
		));
		
		return $result;
	}

	/**
	 * Delete all post's images
	 */
	public function deletePostImages($post_id)
	{
		$images = $this->getPostsImages(array(
			$post_id
		));
		
		if (isset($images[$post_id])) {
			$post_images = $images[$post_id];
			
			foreach ($post_images as $image) {
				$this->deleteImage($image['image_id'], 'posts');
			}
		}
		
		return;
	}

	/**
	 * Delete all user's images
	 */
	public function removeUsersImages($user_id)
	{
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$profile = $Profiles->getProfileByField('id', $user_id);
		
		if (! $profile) {
			return false;
		}
		
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();
		
		$user_id = (int) $user_id;
		
		$sql = "
		SELECT
		*
		FROM images
		WHERE uploaded_by = {$user_id}
		";
		
		$images = $this->getAdapter()->fetchAll($sql);
		
		if (! empty($images)) {
			foreach ($images as $image) {
				
				$StorageAdapter->deleteFileFromStorage($image['file_name'], 'posts');
				
				if ($image['original']) {
					$StorageAdapter->deleteFileFromStorage($image['original'], 'posts');
				}
				
				$result = $this->delete(array(
					'id = ?' => $image['id']
				));
			}
		}
		
		// remove user avatar, cover and background
		$background_file = $ProfilesMeta->getMetaValue('background_file', $user_id);
		if ($background_file) {
			$ret = $StorageAdapter->deleteFileFromStorage($background_file, 'cover');
		}
		
		$avatar_file = $profile->avatar;
		if (strpos($avatar_file, 'default') === false) {
			$ret = $StorageAdapter->deleteFileFromStorage($avatar_file, 'avatar');
		}
		
		$cover_file = $profile->cover;
		if (strpos($cover_file, 'default') === false) {
			$ret = $StorageAdapter->deleteFileFromStorage($cover_file, 'cover');
		}

		return;
	}
}


class Application_Model_Images_Row extends Zend_Db_Table_Row_Abstract
{
}