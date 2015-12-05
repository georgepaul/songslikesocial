<?php

/**
 * Notifications
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

/**
 * types:
 * 1 - new comment on post/image (inform all users included in this discussion)
 * 2 - new like on post/comment/image
 * 3 - new follower
 * 4 - new friend
 * 5 - (not used)
 * 6 - lost a follower
 * 7 - posted on your wall
 * 8 - new message (send email to notify)
 * 9 - (not used)
 * 10 - group membership accepted
 * 11 - group membership rejected
 * 12 - request for membership
 */
class Application_Model_Notifications extends Zend_Db_Table_Abstract
{

	protected $_name = 'notifications';

	protected $_rowClass = 'Application_Model_Notifications_Row';
	
	// pagination
	public $page_number = 1;
	
	// TODO: unsubcribe from notification feed, require additional table
	
	/**
	 * Add notification
	 */
	public function pushNotification(array $to_users, $notification_type, $resource_type, $resource_id, $set_as_new = true)
	{
		// prevent self-notify
		if (Zend_Auth::getInstance()->hasIdentity() && in_array(Zend_Auth::getInstance()->getIdentity()->id, $to_users)) {
			$key = array_search(Zend_Auth::getInstance()->getIdentity()->id, $to_users);
			unset($to_users[$key]);
		}
		
		if (! empty($to_users)) {
			foreach ($to_users as $user_id) {
				$data = array(
					'type' => $notification_type,
					'to_user' => (int) $user_id,
					'resource_type' => $resource_type,
					'resource_id' => $resource_id,
					'is_new' => ($set_as_new ? 1 : 0), // i.e. receives a message - no need to double notify icons
					'email_sent' => 0,
					'created_on' => Application_Plugin_Common::now()
				);
				
				try {
					$result = $this->insert($data);
				} catch (Zend_Db_Exception $e) {
					Application_Plugin_Common::log($e->getMessage());
				}
			}
		}
		
		return;
	}

	/**
	 * Retrive all unreaded notifications
	 */
	public function getNotifications($only_new = false, $fixed_limit = false, $send_emails = false)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return;
		
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		// application now time
		$now = Application_Plugin_Common::now();
		
		if ($this->page_number < 1)
			$this->page_number = 1;
		$limit_from = ((int) $this->page_number - 1) * (int) Zend_Registry::get('config')->get('pagination_limit');
		$limit_to = (int) Zend_Registry::get('config')->get('pagination_limit');
		
		$sql = "
		SELECT

		n.id AS notification_id,
		n.type AS notification_type,
		n.resource_type AS notification_resource_type,
		n.resource_id AS notification_resource_id,
		n.created_on AS notification_date,
				
		t.email AS to_email,
		t.name AS to_name,
		t.screen_name AS to_screen_name,

		c.resource_type AS comment_resource_type,
		c_authors.name AS comment_author_name,
		c_authors.screen_name AS comment_author_screen_name,
		c_authors.avatar AS comment_author_avatar,
		c_posts.id AS commented_post_id,
		c.content AS comment_content,
		c_images.uid AS commented_image_uid,
		c_profile_wall.name AS commented_post_on_wall,

		l.id AS like_id,
		l.resource_type AS like_resource_type,
		l_users.name AS like_user_name,
		l_users.screen_name AS like_user_screen_name,
		l_users.avatar AS like_user_avatar,

		p.id AS profile_id,
		p.name AS profile_name,
		p.screen_name AS profile_screen_name,
		p.avatar AS profile_avatar,

		po.id AS post_id,
		po.content AS post_content,
		po_authors.name AS post_author_name,
		po_authors.screen_name AS post_author_screen_name,
		po_authors.avatar AS post_author_avatar,

		p_meta.meta_value AS bulk_notifications

		FROM notifications n

		LEFT JOIN profiles t ON t.id = n.to_user
				
		LEFT JOIN comments c ON c.id = n.resource_id AND n.resource_type = 'comment'
		LEFT JOIN profiles c_authors ON c_authors.id = c.author_id
		LEFT JOIN posts c_posts ON c_posts.id = c.resource_id AND c.resource_type = 'post'
		LEFT JOIN profiles c_profile_wall ON c_profile_wall.id = c_posts.wall_id
		LEFT JOIN images c_images ON c_images.id = c.resource_id AND c.resource_type = 'image'

		LEFT JOIN likes l ON l.id = n.resource_id AND n.resource_type = 'like'
		LEFT JOIN profiles l_users ON l_users.id = l.user_id

		LEFT JOIN profiles p ON p.id = n.resource_id AND n.resource_type = 'profile'

		LEFT JOIN posts po ON po.id = n.resource_id AND n.resource_type = 'post'
		LEFT JOIN profiles po_authors ON po_authors.id = po.author_id

		LEFT JOIN profile_meta p_meta ON p_meta.profile_id = n.to_user AND p_meta.meta_key = 'bulk_notifications'
				
		WHERE 1

		AND (n.type <> 2 OR l.id IS NOT NULL)
			
		";
		
		// 
		if ($send_emails) {
			$sql .= "
					AND n.email_sent = 0
					AND n.created_on < '{$now}' - INTERVAL 5 MINUTE
					AND n.created_on > DATE(DATE_SUB('{$now}', INTERVAL 1 DAY))
					";
		} else {
			$sql .= " AND n.to_user = {$current_user_id} ";
		}
		
		if ($only_new == true) {
			$sql .= " AND n.is_new = 1 ";
		}
		
		$sql .= " ORDER BY n.created_on DESC ";
		
		if ($fixed_limit) {
			// fixed limit, for notification box
			$sql .= " LIMIT " . (int) $fixed_limit;
		} elseif (! $send_emails) {
			// go with pagination
			$sql .= " LIMIT {$limit_from}, {$limit_to} ";
		}

		$result = $this->getAdapter()->fetchAll($sql);

		$transl = Zend_Registry::get('Zend_Translate');
		
		// save locale since we might change it below
		$locale_saved = $transl->getLocale();
		
		$result_rows = $this->fixData($result, $send_emails);
		
		// send emails
		if ($send_emails) {

			// set default language to network default
			$transl->setLocale(Zend_Registry::get('config')->get('default_language'));
			
			foreach ($result_rows as $row) {
				
				// update this notification to email_sent = 1, never mind if it wan't be really sent later on
				$data = array(
					'email_sent' => '1'
				);
				$where = array(
					'id = ?' => $row['notification_id']
				);
				$result = $this->update($data, $where);
				
				$notification_key = 'notification_email_' . $row['notification_type'];
				
				// if row is not updated then email was probably already sent
				// also, check if this user has enabled this notification
				if ($result == 1 && $row['do_send_email'] && $row['bulk_notifications'][$notification_key]) {
					
					$to = $row['to_email'];
					$subject = $row['subject_email'];
					
					// prepare phtml email template
					$mail_template_path = APPLICATION_PATH . '/views/emails/';
					$view = new Zend_View();
					$view->setScriptPath($mail_template_path);
					$view->assign('top', sprintf($transl->translate('Hello %s'), $row['to_screen_name']));
					$view->assign('message', '<p>' . $row['html_link'] . '</p>');
					$view->assign('footer', $transl->translate('Thank you'));
					$body = $view->render('notifications.phtml');
					
					Application_Plugin_Common::sendEmail($to, $subject, $body);
					
					$row['view_from_name'] = $row['profile_name'];
					$row['view_from_screen_name'] = $row['profile_screen_name'];
					$row['view_from_avatar'] = $row['profile_avatar'];
				}
			}
			
			return 1;
		}
		
		// restore locale
		$transl->setLocale($locale_saved);
		
		return $result_rows;
	}

	public function fixData($data, $override_language = false)
	{
		$baseURL = Application_Plugin_Common::getFullBaseUrl();
		$transl = Zend_Registry::get('Zend_Translate');
		
		// set default language to network default
		$transl_default = Zend_Registry::get('Zend_Translate');
		
		if ($override_language) {
			$transl_default->setLocale(Zend_Registry::get('config')->get('default_language'));
		}
		
		foreach ($data as &$row) {
			
			$row['bulk_notifications'] = json_decode($row['bulk_notifications'], true);
			
			$row['html_link'] = '';
			$row['do_send_email'] = true;
			
			// default, can be overriden
			$row['view_from_name'] = $row['profile_name'];
			$row['view_from_screen_name'] = $row['profile_screen_name'];
			$row['view_from_avatar'] = $row['profile_avatar'];
			
			switch ($row['notification_type']) {
				
				// new comment on post/image (inform all users included in this discussion)
				case 1:
					
					$row['subject'] = $transl->translate('New comment');
					$row['subject_email'] = $transl_default->translate('New comment');
					
					if ($row['comment_resource_type'] == 'post') {
						$row['html_link'] .= '<a href="' . $baseURL . '/profiles/showpost/name/' . $row['commented_post_on_wall'] . '/post/' . $row['commented_post_id'] . '">';
					} elseif ($row['comment_resource_type'] == 'image') {
						$row['html_link'] .= '<a href="' . $baseURL . '/index/index/showimage/' . $row['commented_image_uid'] . '">';
					} else {
						$row['html_link'] .= $transl->translate('Resource not available');
						$row['view_from_avatar'] = 'default/generic.jpg';
						break;
					}
					
					$row['html_link'] .= sprintf($transl->translate('%s posted a new comment'), $row['comment_author_screen_name']);
					$row['html_link'] .= '</a>';
					
					$row['html_link'] .= '<p>';
					$row['html_link'] .= strlen($row['comment_content']) > 150 ? Application_Plugin_Common::mbsubstr($row['comment_content'], 0, 150, 'utf-8') : $row['comment_content'];
					$row['html_link'] .= '</p>';
					
					$row['view_from_name'] = $row['comment_author_name'];
					$row['view_from_screen_name'] = $row['comment_author_screen_name'];
					$row['view_from_avatar'] = $row['comment_author_avatar'];
					
					break;
				
				// 2 - new like on post/comment/image
				case 2:
					
					$row['subject'] = $transl->translate('New like');
					$row['subject_email'] = $transl_default->translate('New like');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/likes/show/like/' . $row['like_id'] . '">';
					$row['html_link'] .= sprintf($transl->translate('%s likes your %s'), $row['like_user_screen_name'], $transl->translate($row['like_resource_type']));
					
					$row['html_link'] .= '</a>';
					
					$row['view_from_name'] = $row['like_user_name'];
					$row['view_from_screen_name'] = $row['like_user_screen_name'];
					$row['view_from_avatar'] = $row['like_user_avatar'];
					
					break;
				
				// 3 - new follower
				case 3:
					$row['subject'] = $transl->translate('You have new followers');
					$row['subject_email'] = $transl_default->translate('You have new followers');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= sprintf($transl->translate('%s is now following you'), $row['profile_screen_name']);
					$row['html_link'] .= '</a>';
					break;
				
				// 4 - new friend
				case 4:
					$row['subject'] = $transl->translate('New comment');
					$row['subject_email'] = $transl_default->translate('New comment');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= sprintf($transl->translate('%s and you are now friends'), $row['profile_screen_name']);
					$row['html_link'] .= '</a>';
					break;
				
				// 6 - lost a follower
				case 6:
					$row['subject'] = $transl->translate('You have lost a follower');
					$row['subject_email'] = $transl_default->translate('You have lost a follower');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= sprintf($transl->translate('%s has stopped following you'), $row['profile_screen_name']);
					$row['html_link'] .= '</a>';
					break;
				
				// 7 - posted on your wall
				case 7:
					
					if (! $row['post_author_name']) {
						$row['html_link'] .= $transl->translate('Resource not available');
						$row['view_from_avatar'] = 'default/generic.jpg';
						break;
					}
					
					$row['subject'] = $transl->translate('New post on your wall');
					$row['subject_email'] = $transl_default->translate('New post on your wall');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/profiles/showpost/name/' . $row['to_name'] . '/post/' . $row['post_id'] . '">';
					$row['html_link'] .= sprintf($transl->translate('%s posted on your wall'), $row['post_author_screen_name']);
					$row['html_link'] .= '</a>';
					
					$row['html_link'] .= '<p>';
					$row['html_link'] .= strlen($row['post_content']) > 150 ? Application_Plugin_Common::mbsubstr($row['comment_content'], 0, 150, 'utf-8') : $row['post_content'];
					$row['html_link'] .= '</p>';
					
					$row['view_from_name'] = $row['post_author_name'];
					$row['view_from_screen_name'] = $row['post_author_screen_name'];
					$row['view_from_avatar'] = $row['post_author_avatar'];
					break;
				
				// 8 - new message (send email to notify)
				case 8:
					$row['subject'] = $transl->translate('You have a new private message');
					$row['subject_email'] = $transl_default->translate('You have a new private message');
					
					$row['html_link'] .= '<a href="' . $baseURL . '/messages/inbox/user/'.$row['profile_name'].'">';
					$row['html_link'] .= sprintf($transl->translate('%s sent you a new private message'), $row['profile_screen_name']);
					$row['html_link'] .= '</a>';
					break;
				
				// 10 - group membership accepted
				case 10:
					$row['do_send_email'] = false;
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= $transl->translate('Group membership accepted');
					$row['html_link'] .= '</a>';
					break;
				
				// 11 - group membership rejected
				case 11: // no email
					$row['do_send_email'] = false;
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= $transl->translate('Group membership rejected');
					$row['html_link'] .= '</a>';
					break;
				
				// 12 - request for group membership sent
				case 12:
					$row['do_send_email'] = false;
					
					$row['html_link'] .= '<a href="' . $baseURL . '/' . $row['profile_name'] . '">';
					$row['html_link'] .= $transl->translate('New group membership request');
					$row['html_link'] .= '</a>';
					break;
				
				default:
					;
					break;
			}
			
		}

		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_data_notificationsfix', $data);
		
		return $data;
	}

	/**
	 * Get total notifications count
	 */
	public function getNotificationsCount($ignore_messages = true)
	{
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		$sql = "
		SELECT
		count(n.id) AS notification_count
		FROM
		notifications n
		WHERE 1
		";
		
		if ($ignore_messages) {
			$sql .= " AND n.type <> 8 ";
		}
		
		$sql .= " AND n.to_user = {$current_user_id}";

		return $this->getAdapter()->fetchOne($sql);
	}

	/**
	 * Get unread notifications count
	 */
	public function getUnreadNotificationsCount()
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return;
		
		$current_user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		$sql = "
		SELECT
		count(n.id) AS notification_count
		FROM
		notifications n
		WHERE n.to_user = {$current_user_id} AND n.is_new = 1
		";
		
		return $this->getAdapter()->fetchOne($sql);
	}

	/**
	 * Mark notifications as readed
	 */
	public function clearNotifications()
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return;
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$data = array(
			'is_new' => '0'
		);
		$where = array(
			'to_user = ?' => $user_id
		);
		
		$this->update($data, $where);
		
		// delete 1 week old notifications in 1% (garbage collector)
		if (rand(1, 100) == 1) {
			$this->delete(array('created_on < ?' => new Zend_Db_Expr("CURRENT_DATE - INTERVAL '1' WEEK")));
		}
		
		return;
	}

	/**
	 * Remove all user's notifications
	 */
	public function removeUsersNotifications($user_id)
	{
		$this->delete(array(
			'to_user = ?' => $user_id
		));
		
		$this->delete(array(
			'resource_id = ?' => $user_id,
			'resource_type = ?' => 'profile'
		));
		
		return;
	}

	/**
	 * Mark all email notifications as sent for the current user
	 */
	public function clearEmailNotifications($type = null)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return;
		
		$user_id = (int) Zend_Auth::getInstance()->getIdentity()->id;
		
		$data = array(
			'is_new' => '0',
			'email_sent' => '1',
		);
		
		$where = array(
			'to_user = ?' => $user_id
		);
		
		if ($type) {
			$where['type = ?'] = (int) $type;
		};
		
		return $this->update($data, $where);
	}
}

class Application_Model_Notifications_Row extends Zend_Db_Table_Row_Abstract
{
}