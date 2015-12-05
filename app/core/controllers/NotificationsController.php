<?php

/**
 * Notifications Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class NotificationsController extends Zend_Controller_Action
{

	/**
	 * Build menu
	 */
	protected function buildMenu()
	{
		$Messages = new Application_Model_Messages();
		$new_count = $Messages->getMessagesCount(false, true);
		
		if ($new_count == 0)
			$new_count = '';
		
		$items = array(
			$this->view->translate('Notifications') => array(
				'controller' => 'notifications',
				'action' => 'list'
			)
		);
		
		$akeys = array_keys($items);
		
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		
		// find current active item
		foreach ($items as $key => &$value) {
			
			if ($controller == $value['controller'] && $action == $value['action']) {
				$this->view->sidebar_nav_menu_active_item = $key;
			}
			
			$value = $this->_helper->url->url($value, 'default', true);
		}
		
		$this->view->sidebar_nav_menu = $items;
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/navmenu.phtml');
		});
	}

	/**
	 * View all notifications
	 */
	public function listAction()
	{
		$this->buildMenu();
		
		$request = $this->getRequest();
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;
		
		$Notifications = new Application_Model_Notifications();
		
		$total_counts = $Notifications->getNotificationsCount();
		
		// for pagination
		$Notifications->page_number = $page;
		$this->view->pagination_last_page = (int) ceil($total_counts / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;
		
		$all_notifications = $Notifications->getNotifications(false);

		$Notifications->clearNotifications();
		
		$this->view->sidebar_profile = true;
		
		$this->view->notifications = $all_notifications;
	}

	/**
	 * heartbeat actions (via continuous ajax call)
	 */
	public function heartbeatAction()
	{
		$Notifications = new Application_Model_Notifications();
		$notifications = $Notifications->getNotifications(true, 10);
		$notifications_count = $Notifications->getUnreadNotificationsCount();
		
		$this->view->notifications = $notifications;
		$notifications_html = $this->view->render('/partial/notifications_popover.phtml');
		
		// new messages count
		$Messages = new Application_Model_Messages();
		$new_messages_count = $Messages->getMessagesCount(false, true);
		
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		
		// we will use timestamp format here since it's easier to calculate diff with ANSI SQL
		$ProfilesMeta->metaUpdate('last_heartbeat', time());
		
		$out = array(
			'notification_count' => $notifications_count,
			'notification_html' => $notifications_html,
			'new_messages' => $new_messages_count
		);
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_heartbeat', $out);
		
		$this->_helper->json($out);
	}

	/**
	 * callback after next page load (aka Poor Man's Cron)
	 *
	 * prevents slowing down the page load
	 */
	public function callbackAction()
	{
		$Notifications = new Application_Model_Notifications();
		
		$out = $Notifications->getNotifications(false, false, true);
		
		// delete old tmp image files in 1%
		if (rand(1, 100) == 1) {
			$Storage = new Application_Model_Storage();
			$StorageAdapter = $Storage->getAdapter();
			$StorageAdapter->deleteOldTmpFiles();
		}
		
		// TODO: delete old notifications, gc etc
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_callback');
		
		$this->_helper->json($out);
	}

	/**
	 * clear notifications (via ajax)
	 */
	public function clearnotificationsAction()
	{
		$Notifications = new Application_Model_Notifications();
		$out = $Notifications->clearNotifications();
		$this->_helper->json($out);
	}

	/**
	 * clear message of the day (via ajax)
	 */
	public function clearmotdAction()
	{
		$session = new Zend_Session_Namespace('Default');
		$session->motd = serialize(Zend_Registry::get('config')->get('motd'));
		$this->_helper->json(true);
	}
}