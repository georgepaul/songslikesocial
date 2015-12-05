<?php

/**
 * Albums
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Albums extends Zend_Db_Table_Abstract
{

	protected $_name = 'albums';

	protected $_rowClass = 'Application_Model_Albums_Row';
	
	// pagination
	public $page_number = 1;

	/**
	 * Create an album
	 */
	public function createAlbum($album_name, $description)
	{
		// protected names
		if ($album_name == 'cover' || $album_name == 'avatar')
			return false;
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$data = array(
			'user_id' => $user_id,
			'name' => $album_name,
			'description' => $description,
			'cover_image' => '',
			'created_on' => Application_Plugin_Common::now()
		);
		
		return $this->insert($data);
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
	 * Get list of albums
	 */
	public function getAlbums($user_id, $use_limits = true)
	{
		$user_id = $this->getDefaultAdapter()->quote($user_id);
		
		if ($use_limits) {
			$limits_sql = $this->setSqlLimits();
		} else {
			$limits_sql = '';
		}
		
		$sql = "
		SELECT
		a.*,
		count(i.id) AS image_count
		FROM albums a
		LEFT JOIN images i ON i.album_id = a.id AND i.is_hidden = 0
		WHERE a.user_id = {$user_id}
		GROUP BY a.id
		{$limits_sql}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get total albums count
	 */
	public function getAlbumsCount($user_id)
	{
		$user_id = $this->getDefaultAdapter()->quote($user_id);
		
		$where_sql = " AND a.user_id = {$user_id} ";
		
		$sql = "
		SELECT
		count(a.id) AS albums_count
		FROM albums a
		WHERE 1
		{$where_sql}
		";
		
		return $this->getAdapter()->fetchOne($sql);
	}

	/**
	 * Get single album
	 */
	public function getAlbum($id)
	{
		$select = $this->select();
		$select->where('id = ?', (int) $id);
		
		return $this->getAdapter()->fetchRow($select);
	}

	/**
	 * Update album
	 */
	public function updateAlbum($id, $name, $description)
	{
		// for security reasons
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$data = array(
			'name' => $name,
			'description' => $description
		);
		
		$where = array(
			$this->getAdapter()->quoteInto('id = ?', $id),
			$this->getAdapter()->quoteInto('user_id = ?', $user_id)
		);
		
		$rows_updated = $this->update($data, $where);
		
		return ($rows_updated == 1 ? true : false);
	}

	/**
	 * Delete album
	 */
	public function deleteAlbum($album_id)
	{
		$album_id = (int) $album_id;
		
		// check if owner or an admin
		if ($this->getAuthorId($album_id) != Zend_Auth::getInstance()->getIdentity()->id && Zend_Auth::getInstance()->getIdentity()->role !== 'admin') {
			return false;
		}
		
		// disconnect all images from this album
		$sql = "
		UPDATE images
		SET album_id = 0
		WHERE album_id = {$album_id}
		";
		
		$result = $this->getAdapter()->query($sql);
		
		// delete album
		if ($result) {
			$result = $this->delete(array(
				'id = ?' => $album_id
			));
		}
		
		return $result;
	}
	
	/**
	 * Get album's author id
	 */
	public function getAuthorId($album_id)
	{
		$select = $this->select();
		$select->from('albums', 'user_id');
		$select->where('id = ?', $album_id);
	
		return $this->getAdapter()->fetchOne($select);
	}

	/**
	 * Delete all user's albums
	 */
	public function deleteAlbums($user_id)
	{
		$albums = $this->getAlbums($user_id, false);
		
		if (! empty($albums)) {
			foreach ($albums as $album) {
				$result = $this->deleteAlbum($album['id']);
			}
		}
		
		return;
	}
}

class Application_Model_Albums_Row extends Zend_Db_Table_Row_Abstract
{
}