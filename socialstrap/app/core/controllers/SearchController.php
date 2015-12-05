<?php

/**
 * Search Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class SearchController extends Zend_Controller_Action
{

	public $search_term = '';

	public $search_filter = '';

	public $page = 1;

	public function init()
	{
		$request = $this->getRequest();
		
		// action name based category
		$action = $request->getActionName();
		
		$this->page = (int) $request->getParam('page');
		if ($this->page < 1)
			$this->page = 1;
		
		$url_search_term = trim($this->getRequest()->getParam('term', false));
		
		if ($url_search_term !== false) {
			
			// filter search input
			$filter_st = new Zend_Filter_StripTags();
			$url_search_term = $filter_st->filter($url_search_term);

			$this->search_term = $url_search_term;
		}
		
		// minimum search string
		$min = 3;
		if ($url_search_term && strlen($this->search_term) < $min) {
			$this->search_term = '';
			Application_Plugin_Alerts::error($this->view->translate('Search query to short'), 'off');
		}
		
		// set global search form action & value
		$this->view->search_category = $action;
		$this->view->search_term = $this->search_term;
		
		// now that we have search_term we can build a menu
		$this->buildMenu();
	}

	/**
	 * Build menu
	 */
	protected function buildMenu()
	{
		$items = array(
			$this->view->translate('Users') => array(
				'controller' => 'search',
				'action' => 'users'
			),
			$this->view->translate('Groups') => array(
				'controller' => 'search',
				'action' => 'groups'
			),
			$this->view->translate('Pages') => array(
				'controller' => 'search',
				'action' => 'pages'
			),
			$this->view->translate('Timeline') => array(
				'controller' => 'search',
				'action' => 'timeline'
			),
			$this->view->translate('Mentions') => array(
				'controller' => 'search',
				'action' => 'mentions'
			),
			$this->view->translate('All Posts') => array(
				'controller' => 'search',
				'action' => 'posts'
			),
		);
		
		// do not show timeline link for guests
		if (! Zend_Auth::getInstance()->hasIdentity()) {
			unset($items[$this->view->translate('Timeline')]);
			unset($items[$this->view->translate('Mentions')]);
		}
		
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
	 * Fetch and prepare posts for view
	 */
	public function preparePosts($search_context)
	{
		$Posts = new Application_Model_Posts();
	
		// offset infinite scroll
		if ($this->view->post_page_number) {
			$Posts->page_number = $this->view->post_page_number;
		}
		
		// retrieve posts
		$this->view->posts_data = $Posts->getPosts(null, false, array(
			'term' => $this->search_term,
			'context' => $search_context
		));

		if (count($this->view->posts_data) > 0) {
			// Add comment form
			$add_comment_form = new Application_Form_AddComment();
			$this->view->add_comment_form = $add_comment_form;
		} else {
			Application_Plugin_Alerts::info($this->view->translate('Nothing found...'), 'off');
		}

		// continue to load posts with ajax (v1.5+)
		if (count($this->view->posts_data) >= Zend_Registry::get('config')->get('limit_posts')) {
			$this->view->php_loadPostURL = $this->_helper->url->url(array(
				'controller' => 'posts',
				'action' => 'load',
				'term' => $this->search_term,
				'context' => $search_context,
			), 'default', true);
		}
		
		$this->view->profile_type = 'feed';
		
		// set single view script
		$this->render('posts');
	}
	
	
	/**
	 * Fetch and prepare profiles for view
	 */
	public function prepareProfiles($type, $filters = false)
	{
		$Profiles = new Application_Model_Profiles();
		
		$count = $Profiles->searchProfiles($this->search_term, $filters, $type, true);
		$this->view->pagination_last_page = (int) ceil($count / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $this->page;
		$Profiles->page_number = $this->page;
		
		if ($count > 0) {
			$this->view->profiles = $Profiles->searchProfiles($this->search_term, $filters, $type);
		} else {
			Application_Plugin_Alerts::info($this->view->translate('Nothing found...'), 'off');
		}
		
		// set single view script
		$this->render('profiles');
	}

	/**
	 * Search users
	 */
	public function usersAction()
	{
		$search_filers_form = new Application_Form_FilterSearchUsers();
		
		$this->view->sidebar_search_filters = $search_filers_form;
		
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/searchfilters.phtml');
		});
		
		$filters = $search_filers_form->getValues();
		
		$this->prepareProfiles('user', $filters);
	}

	/**
	 * Search groups
	 */
	public function groupsAction()
	{
		$this->prepareProfiles('group');
	}

	/**
	 * Search pages
	 */
	public function pagesAction()
	{
		$this->prepareProfiles('page');
	}

	/**
	 * Search timeline posts
	 */
	public function timelineAction()
	{
		$this->preparePosts('timeline');
	}

	/**
	 * Search all posts
	 */
	public function postsAction()
	{
		$this->preparePosts('all');
	}
	
	/**
	 * Search all posts for mentions
	 */
	public function mentionsAction()
	{
		$url_search_term = $this->getRequest()->getParam('term', false);
		
		if ($url_search_term) {
			return $this->redirect('search/posts/?term='.$url_search_term);
		}
		
		$this->search_term = '@'.Zend_Auth::getInstance()->getIdentity()->name;
		$this->preparePosts('all');
	}
}