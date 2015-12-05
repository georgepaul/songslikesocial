<?php

/**
 * Messages
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Messages extends Zend_Db_Table_Abstract
{

	protected $_name = 'messages';

	protected $_rowClass = 'Application_Model_Messages_Row';
	
	// pagination
	public $page_number = 1;

	/**
	 * Send message
	 */
	public function sendMessage($to_user_id, $content, $message_type = 'pm')
	{
		if (! Zend_Auth::getInstance()->hasIdentity() || strlen($content) < 1)
			return false;
		
		$from_user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		if (!$to_user_id || $from_user_id == $to_user_id)
			return false;
		
		$ret = $this->insert(array(
			'type' => $message_type,
			'from_user_id' => $from_user_id,
			'to_user_id' => $to_user_id,
			'content' => $content,
			'is_new' => 1,
			'is_hidden' => 0,
			'sent_on' => Application_Plugin_Common::now()
		));
		
		$Notifications = new Application_Model_Notifications();
		$Notifications->pushNotification(array(
			$to_user_id
		), 8, 'profile', $from_user_id, false);
		
		return $ret;
	}

	/**
	 * Get all messages by single user
	 * 
	 */
	public function getMessages($user_id, $offset = false)
	{
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		$user_id = (int) $user_id;
		
		$offset_sql = "";
		
		if ($offset) {
			$offset = (int) $offset;
			$offset_sql = " m.id > {$offset} AND ";
		}
		
		$sql = "
		SELECT
		m.id AS message_id,
		m.content AS message_content,
		m.is_new AS is_new,
		m.sent_on AS message_sent_on,
		p.name AS from_name,
		p.screen_name AS from_screen_name,
		p.avatar AS from_user_avatar,
		r.user_id AS is_reported
		
		FROM messages m
		
		JOIN profiles p ON p.id = m.from_user_id
		LEFT JOIN reports r ON r.resource_id = m.id AND r.resource_type = 'message' AND r.user_id = {$current_user_id}
		
		WHERE 
		
		{$offset_sql}
		
		(
			(m.from_user_id = {$current_user_id} AND m.to_user_id = {$user_id} AND (m.is_hidden = 0 OR m.is_hidden = 2)) 
				OR 
			(m.from_user_id = {$user_id} AND m.to_user_id = {$current_user_id} AND (m.is_hidden = 0 OR m.is_hidden = 4))
		)
		
		ORDER BY sent_on
		";

		
		$result = $this->getAdapter()->fetchAll($sql);
		
		return $result;
	}
	
	
	/**
	 * Get all time participants
	 *
	 */
	public function getParticipants()
	{
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
	
		$sql = "
		SELECT 
		
		SUM(m.is_new) AS is_new,

		pf.id AS pf_id,
		pt.id AS pt_id,
		
		pf.name AS pf_name,
		pt.name AS pt_name,

		pf.screen_name AS pf_screen_name,
		pt.screen_name AS pt_screen_name,

		pf.avatar AS pf_avatar,
		pt.avatar AS pt_avatar, 
		
		MAX(m.sent_on) AS last_msg
		
		FROM messages m
		JOIN profiles pf ON pf.id = m.from_user_id
		JOIN profiles pt ON pt.id = m.to_user_id
		
		WHERE 
			(m.from_user_id = {$current_user_id} AND (m.is_hidden = 0 OR m.is_hidden = 2))
				OR
			(m.to_user_id = {$current_user_id} AND (m.is_hidden = 0 OR m.is_hidden = 4))
		GROUP BY pf.id, pt.id
		ORDER BY last_msg DESC
		LIMIT 100
		";
		
		// LIMIT 100 means any max number between 50 and 100 participants, depending on mutual or single conversations
		// since grouped by profile_id there is no fear for limit going below 50

		$result = $this->getAdapter()->fetchAll($sql);
	
		$result_fixed = false;
		
		// remove dupes, dirty hack but very fast
		foreach ($result as $row) {
			
			$key = $row['pf_id'] == $current_user_id ? 'pt_' : 'pf_';

			$is_new = 0;
			
			// fix, set as new only if other party replied
			if ((isset($result_fixed[$row[$key.'id']]['is_new']) && $result_fixed[$row[$key.'id']]['is_new'] > 0)
			||
			($row['pt_id'] == $current_user_id && $row['is_new'] > 0)) {
				$is_new = 1;
			}
			
			// find the latest time between two participants
			$last_msg = $row['last_msg'];
			if (isset($result_fixed[$row[$key.'id']])) {
				$last_msg = $result_fixed[$row[$key.'id']]['last_msg'];
			}
			
			$result_fixed[$row[$key.'id']] = array(
				'is_new' => $is_new,
				'id' => $row[$key.'id'],
				'name' => $row[$key.'name'],
				'screen_name' => $row[$key.'screen_name'],
				'avatar' => $row[$key.'avatar'],
				'last_msg' => $last_msg,
			);
		}
		
		// flush older non-read messages that can't fit to sidebar
		if (isset($row['last_msg'])) {
			$last_visible = $row['last_msg'];
			$this->markAsRead(false, $row['last_msg']);
		}
		
		return $result_fixed;
	}
	

	/**
	 * Get total message count
	 *
	 * include messages from hidden users since other party may still want to see those messages
	 */
	public function getMessagesCount($sent_only = false, $new_only = false)
	{
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		if ($sent_only) {
			$where_sql = " AND m.from_user_id = {$current_user_id} AND (m.is_hidden = 0 OR m.is_hidden = 2) ";
		} else {
			$where_sql = " AND m.to_user_id = {$current_user_id} AND (m.is_hidden = 0 OR m.is_hidden = 4) ";
		}
		
		if ($new_only) {
			$where_sql .= " AND m.is_new = 1 ";
		}
		
		$sql = "
		SELECT
		count(m.id) AS message_count
		FROM messages m
		WHERE m.type = 'pm'
		{$where_sql}
		";
		
		return $this->getAdapter()->fetchOne($sql);
	}


	/**
	 * Set all messages from user as read
	 */
	public function markAsRead($user_id = false, $date_offset = false)
	{
		$data = array(
			'is_new' => 0
		);
				
		$where = array(
			'to_user_id = ?' => (int) Zend_Auth::getInstance()->getIdentity()->id,
		);
		
		if ($user_id) {
			$where['from_user_id = ?'] = (int) $user_id;
		}
		
		if ($date_offset) {
			$where['sent_on < ?'] = $date_offset;
		}
		
		$this->update($data, $where);

		return true;
	}
	

	/**
	 * Mark as hidden
	 */
	public function markHidden($id, $datetime = false)
	{
		$data = array(
			'is_hidden' => 1
		);
		
		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		
		$rows_updated = $this->update($data, $where);
		
		return ($rows_updated == 1 ? true : false);
	}
	
	
	/**
	 * Remove a single message: current_user -> message_id
	 */
	public function removeMessage($id)
	{
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
	
		$select = $this->select();
		$select->where("id = ?", $id);

		$message = $this->getAdapter()->fetchRow($select);
	
		if (! $message) return false;
		
		// security, only messages where current user is involved
		if ($message['from_user_id'] != $current_user_id && $message['to_user_id'] != $current_user_id) return false;
		
		if ($message['to_user_id'] == $current_user_id ) {
			
			// remove his/her message from my part
			$data = array(
				'is_hidden' => 2 | $message['is_hidden']
			);
		} else {
			
			// remove my message from my part
			$data = array(
				'is_hidden' => 4 | $message['is_hidden']
			);
		}

		$where = $this->getAdapter()->quoteInto('id = ?', $id);
		$rows_updated = $this->update($data, $where);
	
		return ($rows_updated == 1 ? true : false);
	}
	
	
	/**
	 * Remove all messages: curent_user -> single_user
	 */
	public function removeAllMessagesWithUser($username)
	{
		$Profiles = new Application_Model_Profiles();
		$user = $Profiles->getProfile($username);
		
		if (!$user) return false;
		
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		$with_user_id = (int) $user->id;
		
		$sql = "
			UPDATE messages
			SET is_hidden = 2 | is_hidden
			WHERE 
			(from_user_id = {$with_user_id} AND to_user_id = {$current_user_id})
			";
		
		$this->getAdapter()->query($sql);
		
		$sql = "
		UPDATE messages
		SET is_hidden = 4 | is_hidden
		WHERE
		(from_user_id = {$current_user_id} AND to_user_id = {$with_user_id})
		";
		
		$this->getAdapter()->query($sql);
		
		return true;
	}
	

	/**
	 * delete all messages for profile
	 */
	public function removeUsersMessages($profile_id)
	{
		$this->delete(array(
			'from_user_id = ?' => $profile_id
		));
		$this->delete(array(
			'to_user_id = ?' => $profile_id
		));
		
		return;
	}
}

class Application_Model_Messages_Row extends Zend_Db_Table_Row_Abstract
{
}