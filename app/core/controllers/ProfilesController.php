<?php

/**
 * Profiles Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class ProfilesController extends Zend_Controller_Action
{

	public $profile = false;

	public function init()
	{
		$Profiles = new Application_Model_Profiles();
		$Connections = new Application_Model_Connections();
		
		$name = $this->getRequest()->getParam('name');
		
		$this->profile = $Profiles->getProfile($name);
		
		// check privacy
		if ($this->profile) {
			
			if (Zend_Auth::getInstance()->hasIdentity()) {
				$current_user_id = Zend_Auth::getInstance()->getIdentity()->id;
				$current_user_role = Zend_Auth::getInstance()->getIdentity()->role;
			} elseif (Zend_Registry::get('config')->get('allow_guests') || $this->profile->type == 'page') {
				$current_user_id = 0;
				$current_user_role = 'guest';
			} else {
				$this->redirect('');
			}
			
			// @formatter:off

			if (
					// anyone can see public profile, even guests
					$this->profile->profile_privacy == 'public'
					|| ($this->profile->profile_privacy == 'everyone' && $current_user_id > 0)
					// admins and reviewers can see all profiles
					|| ($current_user_role == 'admin' || $current_user_role == 'reviewer')
					// viewing own profile
					|| ($current_user_id == $this->profile->id)
					// curent user is a follower
					|| ($this->profile->profile_privacy == 'followers' && $Connections->isFollowing($current_user_id, $this->profile->id))
					|| ($this->profile->profile_privacy == 'friends' && $Connections->areFriends($current_user_id, $this->profile->id))
			){
				// ok, has privileges

			}else{
				// user doesn't have privileges to view this profile
				$this->forward('private');
			}
			
			// @formatter:on
		}
	}

	/**
	 * Prepare profile for cover view
	 */
	public function prepareProfile($profile)
	{
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$current_user = (int) Zend_Auth::getInstance()->getIdentity()->id;
		} else {
			$current_user = 0;
		}
		
		$Images = new Application_Model_Images();
		$Connections = new Application_Model_Connections();
		$Reports = new Application_Model_Reports();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		
		$meta_values = $ProfilesMeta->getMetaValues($profile->id);
		
		// user's data, object style
		$this->view->profile_data = $profile;
		$this->view->profile_data->meta_values = $meta_values;
		// attach sidebar box
		Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
		{
			echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/profileinfo.phtml');
		});
		
		$limit = (int) Zend_Registry::get('config')->get('sidebar_max_users');
		
		$is_following = $Connections->isFollowing($current_user, $profile->id);
		$is_friend = $Connections->areFriends($profile->id, $current_user);
		$is_reported = $Reports->isReported($profile->id, $profile->type);
		
		// @formatter:off
		
		// check privacy
		if (isset($profile) && (
				// is admin or reviewer
				(Zend_Auth::getInstance()->hasIdentity() && (Zend_Auth::getInstance()->getIdentity()->role == 'admin' || Zend_Auth::getInstance()->getIdentity()->role == 'reviewer'))
				// viewing own profile
				|| (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->id == $profile->id)
				|| ($profile->profile_privacy === 'friends' && $is_friend)
				|| ($profile->profile_privacy === 'followers' && $is_following)
				|| ($profile->profile_privacy === 'everyone' && Zend_Auth::getInstance()->hasIdentity())
				|| ($profile->profile_privacy === 'public')
		)) {
			if ($profile->type === 'group'){
	
				$this->view->sidebar_members = $Connections->getFriends($profile->id, $limit, false, 'user');
				$this->view->sidebar_members_count = $Connections->getFriends($profile->id, false, true);
	
				// attach sidebar box
				Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function() {
					echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/members.phtml');
				});
				
				// check if secret group and this is a group owner
				if ($current_user > 0 && $current_user == $profile->owner && $profile->profile_privacy === 'friends'){
	
					$Connections->mix_friends = false;
					$this->view->sidebar_approve_members = $Connections->getFollowers($profile->id);
					$this->view->sidebar_approve_members_count = $Connections->getFollowers($profile->id, false, true);
					
					// attach sidebar box
					Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function() {
						echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/approvemembers.phtml');
					});
				}
				
				
			}elseif ($profile->type === 'user'){
				// TODO: optiomize this to a single join call
				$this->view->sidebar_followers = $Connections->getFollowers($profile->id, $limit);
				$this->view->sidebar_following = $Connections->getFollowing($profile->id, $limit);
				$this->view->sidebar_friends = $Connections->getFriends($profile->id, $limit, false, 'user');
				$this->view->sidebar_followers_count = $Connections->getFollowers($profile->id, false, true);
				$this->view->sidebar_following_count = $Connections->getFollowing($profile->id, false, true);
				$this->view->sidebar_friends_count = $Connections->getFriends($profile->id, false, true);
	
				$this->view->sidebar_groups = $Connections->getFriends($profile->id, $limit, false, 'group');
				$this->view->sidebar_groups_count = $Connections->getFriends($profile->id, $limit, true, 'group');
				
				// attach sidebar box
				Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function() {
					echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/followers.phtml');
					echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/following.phtml');
					echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/friends.phtml');
					echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/groups.phtml');
				});
	
			}elseif ($profile->type === 'page'){
	
			}
	 
			// put images to sidebar
			// $this->view->sidebar_images_count = $Images->getImages($profile->id, false, true);
			// $this->view->sidebar_images = $Images->getImages($profile->id, false, false, $limit);
			
			Zend_Registry::get('hooks')->attach('hook_view_sidebar', 10, function() {
				echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/images.phtml');
			});
			
		}
		
		// @formatter:on
		
		// set view params
		$this->view->user_cover = true;
		
		$this->view->is_following = $is_following;
		$this->view->is_friend = $is_friend;
		$this->view->is_reported = $is_reported;
		
		// override <head> for profile pages
		if (Zend_Registry::get('config')->get('profiles_head')) {
			$content = Zend_Registry::get('config')->get('profiles_head');
			$this->view->custom_head = Application_Plugin_Common::parseProfileTags($content, $profile);
		}
		
		// view perspective
		$this->view->view_perspective = 'profile_view';
		
		return;
	}

	/**
	 * Show friends
	 */
	public function privateAction()
	{
		$this->prepareProfile($this->profile);
		
		$post_id = $this->getRequest()->getParam('post', false);
		
		// single public post on a private layout?
		if ($post_id) {
			$Posts = new Application_Model_Posts();
			
			$Posts->show_hidden_comments = true;
			$data = $Posts->getPosts(null, $post_id);
			
			if (! $data) {
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('This post is private or does not exists'), 'off');
			}
			
			$this->view->posts_data = $data;
			$this->view->profile_type = $this->profile->type;
			// render classic posts view
			$this->render('show');
			return;
		}
		
		$this->render('private');
	}

	/**
	 * Show user's profile
	 */
	public function showAction()
	{
		if (! Zend_Registry::get('config')->get('license_code')) {
			Application_Plugin_Alerts::error('Please visit settings and enter your item purchase code');
		}
		
		$name = $this->getRequest()->getParam('name');
		
		// important, flush if profile not found
		if (! $name || ! $this->profile)
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		// load addPost form
		if ($this->profile->type === 'user') {
			$show_privacy_btn = true;
		} else {
			$show_privacy_btn = false;
		}
		
		$this->_helper->addPostFormLoader($this->profile->name, $show_privacy_btn);
		
		// load initial posts
		$Posts = new Application_Model_Posts();
		// Add coment form
		$add_comment_form = new Application_Form_AddComment();
		$this->view->add_comment_form = $add_comment_form;
		
		// offset infinite scroll
		if ($this->view->post_page_number) {
			$Posts->page_number = $this->view->post_page_number;
		}
		
		$data = $Posts->getPosts($this->profile->id);
		
		$this->view->posts_data = $data;
		$this->view->profile_type = $this->profile->type;
		
		// continue to load posts with ajax
		if (count($data) >= Zend_Registry::get('config')->get('limit_posts')) {
			$this->view->php_loadPostURL = $this->_helper->url->url(array(
				'controller' => 'posts',
				'action' => 'load',
				'wall_id' => $this->profile->id
			), 'default', true);
		}
	}

	/**
	 * Show single post on profile's wall
	 */
	public function showpostAction()
	{
		$post_id = $this->getRequest()->getParam('post');
		
		// important, flush if profile not found
		if (! $this->profile)
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		// load addPost form
		if ($this->profile->type === 'user') {
			$show_privacy_btn = true;
		} else {
			$show_privacy_btn = false;
		}
		
		$this->_helper->addPostFormLoader($this->profile->name, $show_privacy_btn);
		
		// load single post
		$Posts = new Application_Model_Posts();
		// Add coment form
		$add_comment_form = new Application_Form_AddComment();
		$this->view->add_comment_form = $add_comment_form;
		
		$Posts->show_hidden_comments = true;
		$data = $Posts->getPosts(null, $post_id);
		
		if (! $data) {
			Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('This post is private or does not exists'), 'off');
		}
		
		$this->view->posts_data = $data;
		$this->view->profile_type = $this->profile->type;
		
		// render classic profile view
		$this->render('show');
	}

	/**
	 * Show friends
	 */
	public function friendsAction()
	{
		$Connections = new Application_Model_Connections();
		
		// flush if user not found
		if (! $this->profile || $this->profile->type !== 'user')
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		$Connections->page_number = $this->preparePagination($this->view->sidebar_friends_count);
		$this->view->users = $Connections->getFriends($this->profile->id);
		
		$this->render('userlist');
	}

	/**
	 * Show followers
	 */
	public function followersAction()
	{
		$Connections = new Application_Model_Connections();
		
		// flush if user not found
		if (! $this->profile || $this->profile->type !== 'user')
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		$Connections->page_number = $this->preparePagination($this->view->sidebar_followers_count);
		$this->view->users = $Connections->getFollowers($this->profile->id);
		
		$this->render('userlist');
	}

	/**
	 * Show following
	 */
	public function followingAction()
	{
		$Connections = new Application_Model_Connections();
		
		// flush if user not found
		if (! $this->profile || $this->profile->type !== 'user')
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		$Connections->page_number = $this->preparePagination($this->view->sidebar_following_count);
		$this->view->users = $Connections->getFollowing($this->profile->id);
		
		$this->render('userlist');
	}

	/**
	 * Show groups where user is a member
	 */
	public function groupsAction()
	{
		$Connections = new Application_Model_Connections();
		
		// flush if user not found
		if (! $this->profile || $this->profile->type !== 'user')
			$this->redirect('');
		
		$this->prepareProfile($this->profile);
		
		$Connections->page_number = $this->preparePagination($this->view->sidebar_groups_count);
		$this->view->users = $Connections->getFriends($this->profile->id, false, false, 'group');
		
		$this->render('userlist');
	}

	/**
	 * Show group members
	 */
	public function membersAction()
	{
		$Connections = new Application_Model_Connections();
		
		// flush if user not found
		if (! $this->profile || $this->profile->type !== 'group')
			$this->redirect('');
		
		$total_count = $Connections->getFriends($this->profile->id, false, true);
		$this->view->title = $this->view->translate('Members');
		$this->view->title_number = $total_count;
		$Connections->page_number = $this->preparePagination($total_count);
		$this->view->members = $Connections->getFriends($this->profile->id);
		
		$this->prepareProfile($this->profile);
		
		$this->render('memberslist');
	}

	/**
	 * Prepare pagination
	 */
	public function preparePagination($total_count)
	{
		$request = $this->getRequest();
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;
		
		$this->view->pagination_last_page = (int) ceil($total_count / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;
		
		return $page;
	}

	/**
	 * Get images/album count
	 */
	public function prepareImagesAlbumsCount()
	{
		$Images = new Application_Model_Images();
		$Albums = new Application_Model_Albums();
		
		$this->view->total_images = $Images->getImages($this->profile->id, false, true);
		$this->view->total_albums = $Albums->getAlbumsCount($this->profile->id);
	}

	/**
	 * Show images
	 */
	public function imagesAction()
	{
		$Images = new Application_Model_Images();
		$Albums = new Application_Model_Albums();
		
		$request = $this->getRequest();
		
		// flush if user not found
		if (! $this->profile)
			$this->redirect('');
		
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;
		
		$album_id = $request->getParam('album', false);
		
		$current_album_count = $Images->getImages($this->profile->id, $album_id, true);
		
		$Images->page_number = $page;
		$this->view->images = $Images->getImages($this->profile->id, $album_id);
		
		$this->view->pagination_last_page = (int) ceil($current_album_count / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;
		
		$this->prepareProfile($this->profile);
		$this->prepareImagesAlbumsCount();
		
		if (! $album_id) {
			$this->view->active_item = 'all';
			$this->view->context = 'images';
		} else {
			$album = $Albums->getAlbum($album_id);
			$this->view->active_item_id = $album['id'];
			$this->view->active_item = $album['name'] . ' (' . $current_album_count . ')';
			$this->view->context = 'album';
		}
		
		$this->render('images');
	}

	/**
	 * Show albums
	 */
	public function albumsAction()
	{
		$Albums = new Application_Model_Albums();
		
		$request = $this->getRequest();
		
		// flush if user not found
		if (! $this->profile)
			$this->redirect('');
		
		$page = (int) $request->getParam('page');
		if ($page < 1)
			$page = 1;
		
		$total_count = $Albums->getAlbumsCount($this->profile->id);
		
		$Albums->page_number = $page;
		$this->view->albums = $Albums->getAlbums($this->profile->id);
		
		$this->view->pagination_last_page = (int) ceil($total_count / (int) Zend_Registry::get('config')->get('pagination_limit'));
		$this->view->pagination_current_page = $page;
		
		$this->prepareProfile($this->profile);
		$this->prepareImagesAlbumsCount();
		
		$this->view->active_item = 'albums';
		$this->render('albums');
	}

	/**
	 * Edit album
	 */
	public function editalbumAction()
	{
		$request = $this->getRequest();
		
		$Albums = new Application_Model_Albums();
		
		$album_form = new Application_Form_EditAlbum();
		$this->view->album_form = $album_form;
		
		$total_count = $Albums->getAlbumsCount($this->profile->id);
		
		$album_id = $request->getParam('id');
		
		$this->prepareProfile($this->profile);
		$this->prepareImagesAlbumsCount();
		
		$album = $Albums->getAlbum($album_id);
		$this->view->active_item = $album['name'];
		
		if ($request->isPost() && $album_form->isValid($_POST)) {
			$album_name = $album_form->getValue('album_name');
			$album_description = $album_form->getValue('description');
			
			$result = $Albums->updateAlbum($album_id, $album_name, $album_description);
			
			if ($result) {
				Application_Plugin_Alerts::success($this->view->translate('Album updated'));
			}
			
			$this->redirect('profiles/editalbum/id/' . $album_id);
		}
	}

	/**
	 * Create an album
	 */
	public function createalbumAction()
	{
		$request = $this->getRequest();
		
		$Albums = new Application_Model_Albums();
		
		$album_form = new Application_Form_AddAlbum();
		$this->view->album_form = $album_form;
		
		$this->prepareProfile($this->profile);
		$this->prepareImagesAlbumsCount();
		
		if ($request->isPost() && $album_form->isValid($_POST)) {
			$album_name = $album_form->getValue('album_name');
			$description = $album_form->getValue('description');
			
			$Albums->createAlbum($album_name, $description);
			
			Application_Plugin_Alerts::success($this->view->translate('New album created'));
			$this->redirect('profiles/albums');
		}
	}

	/**
	 * Delete album (via ajax)
	 */
	public function deletealbumAction()
	{
		$request = $this->getRequest();
		$album_id = $request->getParam('album_id');
		
		$Albums = new Application_Model_Albums();
		
		$ret = $Albums->deleteAlbum($album_id);
		
		$this->getHelper('json')->sendJson($ret);
	}
}