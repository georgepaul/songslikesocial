<?php

/**
 * Reports Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class ReportsController extends Zend_Controller_Action
{

	/**
	 * Build menu
	 */
	protected function buildMenu($counts)
	{
		$items = array(
			$this->view->translate('Review Posts') . ' <span class="badge pull-right">' . $counts['post']['resource_count'] . '</span>' => array(
				'controller' => 'reports',
				'action' => 'reviewposts'
			),
			$this->view->translate('Review Comments') . ' <span class="badge pull-right">' . $counts['comment']['resource_count'] . '</span>' => array(
				'controller' => 'reports',
				'action' => 'reviewcomments'
			),
			$this->view->translate('Review Messages') . ' <span class="badge pull-right">' . $counts['message']['resource_count'] . '</span>' => array(
				'controller' => 'reports',
				'action' => 'reviewmessages'
			),
			$this->view->translate('Review Profiles') . ' <span class="badge pull-right">' . $counts['profiles']['resource_count'] . '</span>' => array(
				'controller' => 'reports',
				'action' => 'reviewprofiles'
			),
			$this->view->translate('Review Images') . ' <span class="badge pull-right">' . $counts['image']['resource_count'] . '</span>' => array(
				'controller' => 'reports',
				'action' => 'reviewimages'
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
	 * Report resource (via ajax)
	 */
	public function reportAction()
	{
		$report_content_form = new Application_Form_ReportContent();
		
		$request = $this->getRequest();
		
		// get and render form only
		if ($request->isPost() && $request->getParam('form_render')) {
			$resource_type = $request->getParam('resource_type');
			$resource_id = (int) $request->getParam('resource_id');
			
			$report_content_form->getElement('resource_type')->setValue($resource_type);
			$report_content_form->getElement('resource_id')->setValue($resource_id);
			
			$this->getHelper('json')->sendJson($report_content_form->render());
		}

		// post a report
		if ($request->isPost() && $report_content_form->isValid($_POST)) {
			
			$reason = $report_content_form->getValue('reason');
			$resource_type = $report_content_form->getValue('resource_type');
			$resource_id = (int) $report_content_form->getValue('resource_id');
			
			$Reports = new Application_Model_Reports();
			$ret = $Reports->report($resource_id, $resource_type, $reason);
		}
		
		$json = $report_content_form->getMessages();
		$this->getHelper('json')->sendJson($json);
		
		return;
	}

	/**
	 * Update reported resource (via ajax)
	 */
	public function updatereportedAction()
	{
		$report_id = (int) $this->getRequest()->getParam('report_id');
		$mark_reported = (int) $this->getRequest()->getParam('mark_reported');
		
		$Reports = new Application_Model_Reports();
		$report = $Reports->getReport($report_id);
		
		$ret = false;
		
		if ($mark_reported == 1)
			switch ($report['resource_type']) {
				case 'post':
					// posts
					$Posts = new Application_Model_Posts();
					$ret = $Posts->markHidden($report['resource_id']);
					break;
				
				case 'user':
				case 'group':
				case 'page':
					// profiles
					$Profiles = new Application_Model_Profiles();
					$ret = $Profiles->markHidden($report['resource_id']);
					break;
				
				case 'message':
					// messages
					$Messages = new Application_Model_Messages();
					$ret = $Messages->markHidden($report['resource_id']);
					break;
				
				case 'comment':
					// comments
					$Comments = new Application_Model_Comments();
					$ret = $Comments->deleteComment($report['resource_id']);
					break;
				
				case 'image':
					// images
					$Images = new Application_Model_Images();
					$ret = $Images->deleteImage($report['resource_id'], 'posts');
					$Reports->clearReports($report['resource_id'], 'image');
					break;
				
				default:
					;
					break;
			}
		
		$Reports->updateReport($report_id, $mark_reported);
		
		$this->getHelper('json')->sendJson($ret);
	}

	/**
	 * Prepare reviews
	 */
	public function prepareReviews($resource_type, $fn)
	{
		$request = $this->getRequest();
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;
		
		$Reports = new Application_Model_Reports();
		$total_counts = $Reports->getTotalCount();
		$this->buildMenu($total_counts);
		
		// for pagination
		$Reports->page_number = $page;
		$this->view->pagination_last_page = (int) ceil($total_counts[$resource_type]['resource_count'] / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;
		
		$this->view->resources = $Reports->{$fn}();
	}

	/**
	 * Review reported posts
	 */
	public function reviewpostsAction()
	{
		$this->prepareReviews('post', 'getReportedPosts');
	}

	/**
	 * Review reported users
	 */
	public function reviewprofilesAction()
	{
		$this->prepareReviews('user', 'getReportedProfiles');
	}

	/**
	 * Review reported messages
	 */
	public function reviewmessagesAction()
	{
		$this->prepareReviews('message', 'getReportedMessages');
	}

	/**
	 * Review reported comments
	 */
	public function reviewcommentsAction()
	{
		$this->prepareReviews('comment', 'getReportedComments');
	}

	/**
	 * Review reported images
	 */
	public function reviewimagesAction()
	{
		$this->prepareReviews('image', 'getReportedImages');
	}

	/**
	 * Edit reported post
	 */
	public function editpostAction()
	{
		$Reports = new Application_Model_Reports();
		$total_counts = $Reports->getTotalCount();
		$this->buildMenu($total_counts);
		
		$request = $this->getRequest();
		
		$page = (int) $request->getParam('page');
		$post_id = (int) $request->getParam('post');
		
		$Posts = new Application_Model_Posts();
		$post = $Posts->getPost($post_id);
		
		// load and fill up form
		$edit_post_form = new Application_Form_EditPost();
		$edit_post_form->getElement('content')->setValue($post['content']);
		$edit_post_form->getElement('privacy')->setValue($post['privacy']);
		$this->view->edit_post_form = $edit_post_form;
		
		if ($request->isPost() && $edit_post_form->isValid($_POST)) {
			
			$content = $edit_post_form->getElement('content')->getValue();
			$privacy = $edit_post_form->getElement('privacy')->getValue();
			
			$new_post_content = Application_Plugin_Common::preparePost($content);
			
			$Posts->updatePost($post_id, $new_post_content, $privacy);
			
			Application_Plugin_Alerts::success($this->view->translate('Post updated'));
			
			if ($page > 0)
				$this->redirect('reports/reviewposts/page/' . $page);
		}
	}

	/**
	 * Edit comment
	 */
	public function editcommentAction()
	{
		$Reports = new Application_Model_Reports();
		$total_counts = $Reports->getTotalCount();
		$this->buildMenu($total_counts);
		
		$request = $this->getRequest();
		
		$page = (int) $request->getParam('page');
		$comment_id = (int) $request->getParam('comment');
		
		$Comments = new Application_Model_Comments();
		$comment = $Comments->getComment($comment_id);
		
		// load and fill up form
		$edit_comment_form = new Application_Form_EditComment();
		$edit_comment_form->getElement('comment')->setValue($comment['content']);
		
		$this->view->edit_comment_form = $edit_comment_form;
		
		if ($request->isPost() && $edit_comment_form->isValid($_POST)) {
			
			$comment_content = $edit_comment_form->getElement('comment')->getValue();
			$comment_content = Application_Plugin_Common::prepareComment($comment_content);
			
			// drop on false
			if ($comment_content === false) {
				return;
			}
			
			$Comments->updateComment($comment_id, $comment_content);
			
			Application_Plugin_Alerts::success($this->view->translate('Comment updated'));
			
			if ($page > 0)
				$this->redirect('reports/reviewcomments/page/' . $page);
		}
	}

}