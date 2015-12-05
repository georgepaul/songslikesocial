<?php

/**
 * PostsMeta
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_PostsMeta extends Zend_Db_Table_Abstract
{

	protected $_name = 'post_meta';

	protected $_rowClass = 'Application_Model_PostsMeta_Row';

	/**
	 * update meta data
	 */
	public function metaUpdate($post_id, $meta_key, $meta_value)
	{
		$exists = $this->getMetaValue($meta_key, $post_id);
		
		if ($exists !== false) {
			
			$data = array(
				'meta_value' => $meta_value
			);
			$where = array(
				'meta_key = ?' => $meta_key,
				'post_id = ?' => $post_id
			);
			
			$res = $this->update($data, $where);
		} else {
			// insert key if not exists
			$res = $this->insert(array(
				'post_id' => $post_id,
				'meta_key' => $meta_key,
				'meta_value' => $meta_value
			));
		}
		
		return $res;
	}

	/**
	 * remove meta data
	 */
	public function metaRemove($post_id)
	{
		return $this->delete(array(
			'post_id = ?' => $post_id
		));
	}

	/**
	 * get meta data for a post
	 */
	public function getMetaValue($meta_key, $post_id)
	{
		$select = $this->select()
			->where('post_id = ?', $post_id)
			->where('meta_key = ?', $meta_key);
		
		$result = $this->getAdapter()->fetchRow($select);
		
		if ($result === false && ! isset($result['meta_value']))
			return false;
		
		return $result['meta_value'];
	}
	
	/**
	 * get all meta data for a post
	 */
	public function getMetaValues($post_id)
	{
		$select = $this->select()
		->where('post_id = ?', $post_id);
	
		$result = $this->getAdapter()->fetchAll($select);

		return $result;
	}
}

class Application_Model_PostsMeta_Row extends Zend_Db_Table_Row_Abstract
{
}