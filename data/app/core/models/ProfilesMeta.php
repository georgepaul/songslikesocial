<?php

/**
 * ProfilesMeta
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_ProfilesMeta extends Zend_Db_Table_Abstract
{

	protected $_name = 'profile_meta';

	protected $_rowClass = 'Application_Model_ProfilesMeta_Row';

	/**
	 * updates meta data
	 */
	public function metaUpdate($meta_key, $meta_value, $profile_id = false)
	{
		if ($profile_id == false && Zend_Auth::getInstance()->hasIdentity()) {
			$profile_id = Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		$exists = $this->getMetaValue($meta_key, $profile_id);
		
		if ($exists !== false) {
			
			$data = array(
				'meta_value' => $meta_value
			);
			$where = array(
				'meta_key = ?' => $meta_key,
				'profile_id = ?' => $profile_id
			);
			
			$res = $this->update($data, $where);
		} else {
			// insert key if not exists
			$res = $this->insert(array(
				'profile_id' => $profile_id,
				'meta_key' => $meta_key,
				'meta_value' => $meta_value
			));
		}
		
		return $res;
	}

	/**
	 * get meta data for a profile
	 */
	public function getMetaValue($meta_key, $profile_id = false)
	{
		if ($profile_id == false && Zend_Auth::getInstance()->hasIdentity()) {
			$profile_id = Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		$select = $this->select()
			->where('profile_id = ?', $profile_id)
			->where('meta_key = ?', $meta_key);
		
		$result = $this->getAdapter()->fetchRow($select);
		
		if ($result === false && ! isset($result['meta_value']))
			return false;
		
		return $result['meta_value'];
	}

	/**
	 * get all meta data for a profile
	 */
	public function getMetaValues($profile_id = false)
	{
		if ($profile_id == false && Zend_Auth::getInstance()->hasIdentity()) {
			$profile_id = Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		$select = $this->select()->where('profile_id = ?', $profile_id);
		
		$result = $this->getAdapter()->fetchAll($select);
		
		if ($result === false)
			return false;
		
		$ret = array();
		
		// transform to key=>value pairs array
		foreach ($result as $row) {
			$ret[$row['meta_key']] = $row['meta_value'];
		}
		
		return $ret;
	}

	/**
	 * get profile_id by meta key/value pair
	 */
	public function getProfileId($meta_key, $meta_value)
	{
		$select = $this->select()
			->where('meta_key = ?', $meta_key)
			->where('meta_value = ?', $meta_value);
		
		$result = $this->getAdapter()->fetchRow($select);
		
		return $result['profile_id'];
	}

	/**
	 * delete key/value pair
	 */
	public function deletePair($meta_key, $meta_value)
	{
		return $this->delete(array(
			'meta_key = ?' => $meta_key,
			'meta_value = ?' => $meta_value
		));
	}

	/**
	 * delete meta key for a profile
	 */
	public function deleteProfilesMetaKey($profile_id, $meta_key)
	{
		return $this->delete(array(
			'meta_key = ?' => $meta_key,
			'profile_id = ?' => $profile_id
		));
	}

	/**
	 * delete all metas fror profile
	 */
	public function removeMetaForProfile($profile_id)
	{
		return $this->delete(array(
			'profile_id = ?' => $profile_id
		));
	}

	/**
	 * Get meta data for selected profiles
	 */
	public function getMetaForProfiles(array $profiles_ids)
	{
		if (empty($profiles_ids))
			return false;
		
		foreach ($profiles_ids as &$profile_id) {
			$profile_id = (int) $profile_id;
		}
		
		$profiles_in = implode(',', $profiles_ids);
		
		$sql = "
		SELECT
		*
		FROM profile_meta
		WHERE profile_id IN ({$profiles_in})
		";
		
		$result = $this->getAdapter()->fetchAll($sql);
		
		// transform array
		$ret_fixed = array();
		
		if (! empty($result)) {
			foreach ($result as $profile) {
				$ret_fixed[$profile['profile_id']][$profile['meta_key']] = $profile['meta_value'];
			}
		}
		
		return $ret_fixed;
	}
}

class Application_Model_ProfilesMeta_Row extends Zend_Db_Table_Row_Abstract
{
}