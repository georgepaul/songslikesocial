<?php

/**
 * Reports
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Reports extends Zend_Db_Table_Abstract
{

	protected $_name = 'reports';

	protected $_rowClass = 'Application_Model_Reports_Row';
	
	// pagination
	public $page_number = 1;

	/**
	 * Report resource
	 */
	public function report($resource_id, $resource_type, $reason)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return null;
		
		$user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		if ($this->isReported($resource_id, $resource_type))
			return false;
		
		$data = array(
			'user_id' => $user_id,
			'resource_type' => $resource_type,
			'resource_id' => $resource_id,
			'reason' => $reason,
			'created_on' => Application_Plugin_Common::now(),
			'reviewed_by' => 0,
			'is_accepted' => 0
		);
		
		// new report email notification
		if (Zend_Registry::get('config')->get('report_notify_email')) {
			
			$to = Zend_Registry::get('config')->get('report_notify_email');
			$subject = 'New report';
			
			// prepare phtml email template
			$mail_template_path = APPLICATION_PATH . '/views/emails/';
			$view = new Zend_View();
			$view->setScriptPath($mail_template_path);
			$body = $view->render('newreport.phtml');
			
			$ret = Application_Plugin_Common::sendEmail($to, $subject, $body, true);
		}
		
		return $this->insert($data);
	}

	/**
	 * Check if resource is reported (by the current user)
	 */
	public function isReported($resource_id, $resource_type, $check_user = true)
	{
		if (! Zend_Auth::getInstance()->hasIdentity())
			return false;
		
		$select = $this->select()
			->where('resource_type = ?', $resource_type)
			->where('resource_id = ?', $resource_id)
			->where('reviewed_by = 0');
		
		if ($check_user) {
			$select->where('user_id = ?', Zend_Auth::getInstance()->getIdentity()->id);
		}
		
		$ret = $this->fetchRow($select);
		
		if ($ret === null)
			return false;
		
		return true;
	}

	/**
	 * accept all reports for resource_id/resource_type pair
	 */
	public function clearReports($resource_id, $resource_type)
	{
		$ret = $this->update(array(
			'reviewed_by' => Zend_Auth::getInstance()->getIdentity()->id,
			'is_accepted' => 1
		), array(
			'resource_id = ?' => $resource_id,
			'resource_type = ?' => $resource_type
		));
		
		return $ret;
	}

	/**
	 * Remove all user's reports
	 */
	public function removeUsersReports($user_id)
	{
		return $this->delete(array(
			'user_id = ?' => $user_id
		));
	}

	/**
	 * Get reported users
	 */
	public function getReportedProfiles()
	{
		$limits = $this->setSqlLimits();
		
		$sql = "
		SELECT
		r.id AS id,
		u1.name AS reported_by_name,
		u1.screen_name AS reported_by_screen_name,
		u1.avatar AS reported_by_avatar,
		u2.name AS reported_name,
		u2.screen_name AS reported_screen_name,
		u2.avatar AS reported_avatar,
		r.reason AS reason,
		r.created_on AS report_date,
		r.user_id AS reported_by_id,
		r.resource_id AS resource_id
		FROM
		reports r
		JOIN profiles u1 ON u1.id = r.user_id
		JOIN profiles u2 ON u2.id = r.resource_id
		WHERE r.reviewed_by = 0 AND (r.resource_type = 'user' OR r.resource_type = 'group' OR r.resource_type = 'page')
		ORDER BY r.resource_id DESC, r.created_on ASC
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get reported images
	 */
	public function getReportedImages()
	{
		$limits = $this->setSqlLimits();
		
		$sql = "
		SELECT
		r.id AS id,
		u1.name AS reported_by_name,
		u1.screen_name AS reported_by_screen_name,
		u1.avatar AS reported_by_avatar,
		r.reason AS reason,
		r.created_on AS report_date,
		r.user_id AS reported_by_id,
		r.resource_id AS resource_id,
		i.file_name AS file_name,
		i.id AS image_id
		FROM
		reports r
		LEFT JOIN profiles u1 ON u1.id = r.user_id
		LEFT JOIN images i ON i.id = r.resource_id
		WHERE r.reviewed_by = 0 AND r.resource_type = 'image' AND i.id IS NOT NULL
		ORDER BY r.resource_id DESC, r.created_on ASC
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get reported posts
	 */
	public function getReportedPosts()
	{
		$limits = $this->setSqlLimits();
		
		$sql = "
		SELECT
		r.id AS id,
		u1.name AS reported_by_name,
		u1.screen_name AS reported_by_screen_name,
		u1.avatar AS reported_by_avatar,
		u2.name AS user_name,
		u2.screen_name AS user_screen_name,
		u2.avatar AS user_avatar,
		u3.id AS post_wall_id,
		u3.name AS post_wall_name,
		u3.screen_name AS post_wall_screen_name,
		u3.type AS post_wall_profile_type,
		p.id AS post_id,
		p.author_id AS author_id,
		p.content AS post_content,
		p.created_on AS post_created_on,
		p.privacy AS post_privacy,
		r.reason AS reason,
		r.created_on AS report_date,
		r.user_id AS reported_by_id,
		r.resource_id AS resource_id
		FROM
		reports r
		JOIN profiles u1 ON u1.id = r.user_id
		JOIN posts p ON p.id = r.resource_id
		JOIN profiles u2 ON u2.id = p.author_id
		JOIN profiles u3 ON u3.id = p.wall_id
		WHERE r.reviewed_by = 0 AND r.resource_type = 'post'
		ORDER BY r.resource_id DESC, r.created_on ASC
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get reported messages
	 */
	public function getReportedMessages()
	{
		$limits = $this->setSqlLimits();
		
		$sql = "
		SELECT
		r.id AS id,
		u1.name AS reported_by_name,
		u1.screen_name AS reported_by_screen_name,
		u1.avatar AS reported_by_avatar,

		u2.name AS from_name,
		u2.screen_name AS from_screen_name,
		u2.avatar AS from_user_avatar,

		u3.name AS to_name,
		u3.screen_name AS to_screen_name,
		u3.avatar AS to_user_avatar,

		m.content AS message_content,
		m.sent_on AS message_sent_on,

		r.reason AS reason,
		r.created_on AS report_date,
		r.user_id AS reported_by_id,
		r.resource_id AS resource_id

		FROM
		reports r
		JOIN profiles u1 ON u1.id = r.user_id
		JOIN messages m ON m.id = r.resource_id
		JOIN profiles u2 ON u2.id = m.from_user_id
		JOIN profiles u3 ON u3.id = m.to_user_id
		WHERE r.reviewed_by = 0 AND r.resource_type = 'message'
		ORDER BY r.resource_id DESC, r.created_on ASC
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get reported comments
	 */
	public function getReportedComments()
	{
		$limits = $this->setSqlLimits();
		
		$sql = "
		SELECT
		r.id AS id,
		u1.name AS reported_by_name,
		u1.screen_name AS reported_by_screen_name,
		u1.avatar AS reported_by_avatar,

		u2.name AS comment_name,
		u2.screen_name AS comment_screen_name,
		u2.avatar AS comment_avatar,
		
		c.content AS comment_content,
		c.created_on AS comment_created_on,
		c.resource_type AS comment_resource_type,
		c.resource_id AS comment_resource_id,

		r.reason AS reason,
		r.created_on AS report_date,
		r.user_id AS reported_by_id,
		r.resource_id AS resource_id
		

		FROM
		reports r
		JOIN profiles u1 ON u1.id = r.user_id
		JOIN comments c ON c.id = r.resource_id
		JOIN profiles u2 ON u2.id = c.author_id
		WHERE r.reviewed_by = 0 AND r.resource_type = 'comment'
		ORDER BY r.resource_id DESC, r.created_on ASC
		{$limits}
		";
		
		return $this->getAdapter()->fetchAll($sql);
	}

	/**
	 * Get resource total count
	 */
	public function getTotalCount()
	{
		$sql = "
		SELECT
		r.resource_type,
		count(*) AS resource_count,
		i.id,
		m.id,
		c.id,
		pr.id,
		po.id
		FROM
		reports r
		LEFT JOIN images i ON i.id = r.resource_id AND r.resource_type = 'image'
		LEFT JOIN messages m ON m.id = r.resource_id AND r.resource_type = 'message'
		LEFT JOIN comments c ON c.id = r.resource_id AND r.resource_type = 'comment'
		LEFT JOIN profiles pr ON pr.id = r.resource_id AND r.resource_type = 'user'
		LEFT JOIN posts po ON po.id = r.resource_id AND r.resource_type = 'post'
		WHERE r.reviewed_by = 0 
		AND (i.id IS NOT NULL OR m.id IS NOT NULL OR c.id IS NOT NULL OR pr.id  IS NOT NULL OR po.id IS NOT NULL)
		GROUP BY r.resource_type
		";
		
		$result = $this->getAdapter()->fetchAssoc($sql);
		
		if (! isset($result['post']['resource_count']))
			$result['post']['resource_count'] = '';
		if (! isset($result['comment']['resource_count']))
			$result['comment']['resource_count'] = '';
		if (! isset($result['message']['resource_count']))
			$result['message']['resource_count'] = '';
		if (! isset($result['image']['resource_count']))
			$result['image']['resource_count'] = '';
		
		if (! isset($result['user']['resource_count']))
			$result['user']['resource_count'] = 0;
		if (! isset($result['group']['resource_count']))
			$result['group']['resource_count'] = 0;
		if (! isset($result['page']['resource_count']))
			$result['page']['resource_count'] = 0;
		
		$result['profiles']['resource_count'] = $result['user']['resource_count'] + $result['group']['resource_count'] + $result['page']['resource_count'];
		
		if (! $result['profiles']['resource_count'])
			$result['profiles']['resource_count'] = '';
		
		return $result;
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
	 * Get a single report
	 */
	public function getReport($report_id)
	{
		$select = $this->select();
		$select->where('id = ?', $report_id);
		
		return $this->getAdapter()->fetchRow($select);
	}

	/**
	 * Update report
	 */
	public function updateReport($id, $is_accepted)
	{
		$rows_updated = $this->update(array(
			'reviewed_by' => Zend_Auth::getInstance()->getIdentity()->id,
			'is_accepted' => $is_accepted
		), array(
			'id = ?' => $id
		));
		
		return ($rows_updated == 1 ? true : false);
	}
}

class Application_Model_Reports_Row extends Zend_Db_Table_Row_Abstract
{
}