<?php
/**
 * Power lobby add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class LobbyClass
{
	protected $front;
	protected $view;
	protected $limit;
	protected $model;
	protected $translator;
	
	public $uid;

	public function __construct() {

		$this->front = Zend_Controller_Front::getInstance();
		$this->view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;
		$this->limit = (int)Zend_Registry::get('config')->get('sidebar_max_users');
		$this->model = new LobbyModel();
		$this->translator = Zend_Registry::get('Zend_Translate');
		$this->uid = Zend_Auth::getInstance()->hasIdentity() ? Zend_Session::getId() : md5('APPLICATION_PATH');
	}

	
	/**
	 *
	 * Show online users
	 */
	public function getOnlineUsers()
	{
		$profiles = $this->model->getOnlineUsers();
	
		$this->render('Online users', $profiles);
	
		return;
	}
	
	
	/**
	 *
	 * Show popular users
	 */
	public function getPopularUsers($limited = true)
	{
		// Get cache from registry
		$cache = Zend_Registry::get('cache');
		
		$profiles = false;
		
		if ( ($profiles = $cache->load('addon_powerlobby_PopularUsers_'.$this->uid)) === false ) {
			// cache missed
		
			if ($limited){
				// get profiles for sidebar
				$profiles = $this->model->getPopularProfiles('user', $this->limit);
			}else{
				$total_count = $this->model->getPopularProfiles('user', false, true);
				$this->model->page_number = $this->preparePagination($total_count);
				$profiles = $this->model->getPopularProfiles('user');
			}
			$cache->save($profiles);
		}
		
		$this->render('Popular users', $profiles);
	
		return;
	}
	

	/**
	 *
	 * Show popular groups
	 */
	public function getPopularGroups($limited = true)
	{
		// Get cache from registry
		$cache = Zend_Registry::get('cache');
		
		$profiles = false;
		
		if ( ($profiles = $cache->load('addon_powerlobby_PopularGroups_'.$this->uid)) === false ) {
			// cache missed
		
			if ($limited){
				// get profiles for sidebar
				$profiles = $this->model->getPopularProfiles('group', $this->limit);
			}else{
				$total_count = $this->model->getPopularProfiles('group', false, true);
				$this->model->page_number = $this->preparePagination($total_count);
				$profiles = $this->model->getPopularProfiles('group');
			}
			
			$cache->save($profiles);
		}

		$this->render('Popular groups', $profiles);

		return;

	}


	/**
	 *
	 * Show popular pages
	 */
	public function getPopularPages($limited = true)
	{
		// Get cache from registry
		$cache = Zend_Registry::get('cache');
		
		$profiles = false;
		
		if ( ($profiles = $cache->load('addon_powerlobby_PopularPages_'.$this->uid)) === false ) {
			// cache missed
		
			if ($limited){
				// get profiles for sidebar
				$profiles = $this->model->getPopularPages($this->limit);
			}else{
				$total_count = $this->model->getPopularPages(false, true);
				$this->model->page_number = $this->preparePagination($total_count);
				$profiles = $this->model->getPopularPages();
			}
				
			$cache->save($profiles);
		}
		
		$this->render('Popular pages', $profiles);

		return;
	}


	/**
	 *
	 * Get friend suggestions
	 */
	public function getFriendSuggestions($limited = true)
	{
		// Get cache from registry
		$cache = Zend_Registry::get('cache');
		
		$profiles = false;
		
		if ( ($profiles = $cache->load('addon_powerlobby_FriendSuggestions_'.$this->uid)) === false ) {
			// cache missed
			if ($limited){
				// get profiles for sidebar
				$profiles = $this->model->getFriendSuggestions($this->limit);
			}else{
				$total_count = $this->model->getFriendSuggestions();
				$this->model->page_number = $this->preparePagination($total_count);
				$profiles = $this->model->getFriendSuggestions();
			}
			
			$cache->save($profiles);		
		}
		
		$this->render('Friend suggestions', $profiles);
	
		return;
	}
	

	/**
	 *
	 * Prepare pagination
	 */
	public function preparePagination($total_count)
	{
		$request = $this->front->getRequest();

		$page = (int)$request->getParam('page');
		if ($page < 1) $page = 1;

		$this->view->pagination_last_page = (int)ceil($total_count / (int)Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;

		return $page;
	}


	/**
	 *
	 * Render widget box
	 */
	public function render($title, $profiles)
	{
		$translated_title = $this->view->translate($title);
		
		// do not show if no profiles
		if (!$profiles) return;
		
		switch ($title) {
			case 'Friend suggestions':
			case 'Online users':
			case 'Popular users':
				$url = $this->view->baseUrl() . '/search/users';
				$title_html = '<a href="'.$url.'">'.$translated_title.'</a>';
			break;
			
			case 'Popular groups':
				$url = $this->view->baseUrl() . '/search/groups';
				$title_html = '<a href="'.$url.'">'.$translated_title.'</a>';
				break;
					
			case 'Popular pages':
				$url = $this->view->baseUrl() . '/search/pages';
				$title_html = '<a href="'.$url.'">'.$translated_title.'</a>';
			break;
			
			default:
				 $title_html = '<span>'.$translated_title.'</span>';
			break;
		}

		$out = '<div class="well">
				<div class="sb-title">'.$title_html.'</div>';

		// we can call partials here
		$out .= $this->view->partial('/partial/profiles_sidebar.phtml', array('profiles' => $profiles));

		$out .= '</div>';

		echo $out;
	}

}