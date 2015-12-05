<?php

/**
 * Messages Controller
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class MessagesController extends Zend_Controller_Action
{

	/**
	 * Build menu
	 */
	protected function buildMenu($active_user = false)
	{
		$Messages = new Application_Model_Messages();
		$participants = $Messages->getParticipants();

		$this->view->participants = $participants;
		$this->view->active = $active_user;
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/messagesmenu.phtml');
		});
	}

	/**
	 * Show inbox
	 */
	public function inboxAction()
	{
		$current_user = Zend_Auth::getInstance()->getIdentity();
		
		$Messages = new Application_Model_Messages();
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$Connections = new Application_Model_Connections();
		
		$request = $this->getRequest();
		$user_name = $request->getParam('user', false);
		
		$messages = $user = $offset = false;
		
		if ($user_name) {
			$user = $Profiles->getProfile($user_name);
			
			if (!$user || $user->type != 'user') {
				$this->redirect('messages/inbox');
			}
			
			$users_meta = $ProfilesMeta->getMetaValues($user->id);
			
			// check private message privacy
			if ($current_user->role != 'admin' && $current_user->role != 'reviewer'
				&& isset($users_meta['contact_privacy']) && $users_meta['contact_privacy'] == 'f'
					&& ! $Connections->areFriends($current_user->id, $user->id)) {
				Application_Plugin_Alerts::error($this->view->translate('Private profile (friends only)'));
				$user = false;
			}
				
			$messages = $Messages->getMessages($user->id);
			$Messages->markAsRead($user->id);
			
			// send last visible message
			$last = end($messages);
			$offset = $last['message_id'];
		}
		
		$this->buildMenu($user_name);
		
		$this->view->user = $user;
		$this->view->messages = $messages;
		$this->view->offset = $offset;
		
		$message_form = new Application_Form_Message();
		$this->view->message_form = $message_form;
	}


	/**
	 * Read / Compose a new message (via ajax)
	 */
	public function newAction()
	{
		$current_user = Zend_Auth::getInstance()->getIdentity();
		
		$request = $this->getRequest();
		$to_user = $request->getParam('to', false);
		$offset = $request->getParam('offset', false);
		
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$Connections = new Application_Model_Connections();
		$Messages = new Application_Model_Messages();
		$Notifications = new Application_Model_Notifications();
		
		$message_form = new Application_Form_Message();
		$this->view->message_form = $message_form;
		
		$user = $Profiles->getProfile($to_user);
		
		$json_ret = array(
			'errors' => '',
			'html' => '',
			'offset' => '',
		);
		
		if (! $user || ! isset($user->id) || $user->type != 'user') {
			$json_ret['errors'] = $this->view->translate('This user does not exist');
			// exit
			$this->getHelper('json')->sendJson($json_ret);
		}

		$users_meta = $ProfilesMeta->getMetaValues($user->id);
		
		// check private message privacy
		if ($current_user->role != 'admin' && $current_user->role != 'reviewer' 
			&& isset($users_meta['contact_privacy']) && $users_meta['contact_privacy'] == 'f' 
				&& ! $Connections->areFriends($current_user->id, $user->id)) {
			$json_ret['errors'] = $this->view->translate('Private profile (friends only)');
			// exit
			$this->getHelper('json')->sendJson($json_ret);
		}
		
		$this->view->to_screen_name = $user->screen_name;

		if ($request->isPost() && $message_form->isValid($_POST)) {
			$content = $message_form->getValue('content');
			
			$result = $Messages->sendMessage($user->id, $content);
			
			if (!$result) {
				$json_ret['errors'] = $this->view->translate('Server-side error');
				// exit
				$this->getHelper('json')->sendJson($json_ret);
			}
			
			// mark as read
			$Messages->markAsRead($user->id);
		}

		// get new messages
		$messages = $Messages->getMessages($user->id, $offset);
		
		// clear email notifications since you are looking at them right now
		$Notifications->clearEmailNotifications(8);
		
		if (!empty($messages)) {
		
			// send last visible message
			$last = end($messages);
			$json_ret['offset'] = $last['message_id'];
				
			foreach($messages as $message) {
				$this->view->message = $message;
				$json_ret['html'] .= $this->view->render('/partial/message.phtml');
			}
				
		}
		
		
		$this->getHelper('json')->sendJson($json_ret);
	}
	
	
	/**
	 * Remove message (via ajax)
	 */
	public function removeAction()
	{
		$message_id = (int) $this->getRequest()->getParam('message_id', false);
		$user = $this->getRequest()->getParam('user', false);
	
		$Messages = new Application_Model_Messages();
	
		$ret = false;
		
		if ($message_id)
			$ret = $Messages->removeMessage($message_id);
		
		if ($user)
			$ret = $Messages->removeAllMessagesWithUser($user);
	
		$this->getHelper('json')->sendJson($ret);
	}
	
}