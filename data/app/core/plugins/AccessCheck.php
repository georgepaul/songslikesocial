<?php

/**
 * ACL
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_AccessCheck extends Zend_Controller_Plugin_Abstract
{

	public $acl;

	public function __construct()
	{
		
		// Create ACL
		$this->acl = new Zend_Acl();
		$this->acl->

		/**
		 * Add resources.
		 * Each resource have format of Controller_Name/Action_Name
		 */
		
		addResource(new Zend_Acl_Resource('index'))
			->addResource(new Zend_Acl_Resource('index/login'), 'index')
			->addResource(new Zend_Acl_Resource('index/index'), 'index')
			->addResource(new Zend_Acl_Resource('index/language'), 'index')
			->addResource(new Zend_Acl_Resource('index/activate'), 'index')
			->addResource(new Zend_Acl_Resource('index/deny'), 'index')
			->addResource(new Zend_Acl_Resource('index/validateformajax'), 'index')
			->addResource(new Zend_Acl_Resource('index/logout'), 'index')
			->
		addResource(new Zend_Acl_Resource('addons'))
			->addResource(new Zend_Acl_Resource('addons/show'), 'addons')
			->
		addResource(new Zend_Acl_Resource('search'))
			->addResource(new Zend_Acl_Resource('search/users'), 'search')
			->addResource(new Zend_Acl_Resource('search/groups'), 'search')
			->addResource(new Zend_Acl_Resource('search/pages'), 'search')
			->addResource(new Zend_Acl_Resource('search/timeline'), 'search')
			->addResource(new Zend_Acl_Resource('search/posts'), 'search')
			->addResource(new Zend_Acl_Resource('search/mentions'), 'search')
			->
		addResource(new Zend_Acl_Resource('editprofile'))
			->addResource(new Zend_Acl_Resource('editprofile/changepassword'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/recoverpassword'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/setprofilepicture'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/setcoverpicture'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/setbackgroundpicture'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/changenotifications'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/defaultprivacy'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/closeaccount'), 'editprofile')
			->addResource(new Zend_Acl_Resource('editprofile/edit'), 'editprofile')
			->
		addResource(new Zend_Acl_Resource('managegroups'))
			->addResource(new Zend_Acl_Resource('editprofile/listgroups'), 'managegroups')
			->addResource(new Zend_Acl_Resource('editprofile/editgroup'), 'managegroups')
			->addResource(new Zend_Acl_Resource('editprofile/creategroup'), 'managegroups')
			->
		addResource(new Zend_Acl_Resource('managepages'))
			->addResource(new Zend_Acl_Resource('editprofile/listpages'), 'managepages')
			->addResource(new Zend_Acl_Resource('editprofile/editpage'), 'managepages')
			->addResource(new Zend_Acl_Resource('editprofile/createpage'), 'managepages')
			->			
		addResource(new Zend_Acl_Resource('images'))
			->addResource(new Zend_Acl_Resource('images/edit'), 'images')
			->addResource(new Zend_Acl_Resource('images/setprofilepicture'), 'images')
			->addResource(new Zend_Acl_Resource('images/setcoverpicture'), 'images')
			->addResource(new Zend_Acl_Resource('images/moveimage'), 'images')
			->addResource(new Zend_Acl_Resource('images/rotateimage'), 'images')
			->
		addResource(new Zend_Acl_Resource('fileupload'))
			->addResource(new Zend_Acl_Resource('fileupload/receivefile'), 'fileupload')
			->
		addResource(new Zend_Acl_Resource('info'))
			->addResource(new Zend_Acl_Resource('info/page'), 'info')
			->
		addResource(new Zend_Acl_Resource('posts'))
			->addResource(new Zend_Acl_Resource('posts/show'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/load'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/edit'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/delete'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/getlightboxdata'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/downloadimage'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/share'), 'posts')
			->addResource(new Zend_Acl_Resource('posts/repost'), 'posts')
			->
		addResource(new Zend_Acl_Resource('comments'))
			->addResource(new Zend_Acl_Resource('comments/postcomment'), 'comments')
			->addResource(new Zend_Acl_Resource('comments/delete'), 'comments')
			->addResource(new Zend_Acl_Resource('comments/edit'), 'comments')
			->
		addResource(new Zend_Acl_Resource('likes'))
			->addResource(new Zend_Acl_Resource('likes/togglelike'), 'likes')
			->addResource(new Zend_Acl_Resource('likes/show'), 'likes')
			->addResource(new Zend_Acl_Resource('likes/getall'), 'likes')
			->
		addResource(new Zend_Acl_Resource('notifications'))
			->addResource(new Zend_Acl_Resource('notifications/list'), 'notifications')
			->addResource(new Zend_Acl_Resource('notifications/callback'), 'notifications')
			->addResource(new Zend_Acl_Resource('notifications/heartbeat'), 'notifications')
			->addResource(new Zend_Acl_Resource('notifications/clearnotifications'), 'notifications')
			->addResource(new Zend_Acl_Resource('notifications/clearmotd'), 'notifications')
			->
		addResource(new Zend_Acl_Resource('profiles'))
			->addResource(new Zend_Acl_Resource('profiles/show'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/showpost'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/friends'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/followers'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/following'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/groups'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/members'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/images'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/albums'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/editalbum'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/createalbum'), 'profiles')
			->addResource(new Zend_Acl_Resource('profiles/deletealbum'), 'profiles')
			->
		addResource(new Zend_Acl_Resource('connections'))
			->addResource(new Zend_Acl_Resource('connections/togglefollow'), 'connections')
			->addResource(new Zend_Acl_Resource('connections/membership'), 'connections')
			->
		addResource(new Zend_Acl_Resource('messages'))
			->addResource(new Zend_Acl_Resource('messages/inbox'), 'messages')
			->addResource(new Zend_Acl_Resource('messages/new'), 'messages')
			->addResource(new Zend_Acl_Resource('messages/remove'), 'messages')
			->
		addResource(new Zend_Acl_Resource('reports'))
			->addResource(new Zend_Acl_Resource('reports/report'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/reviewprofiles'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/reviewposts'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/reviewcomments'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/reviewmessages'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/reviewimages'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/updatereported'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/editpost'), 'reports')
			->addResource(new Zend_Acl_Resource('reports/editcomment'), 'reports')
			->
		addResource(new Zend_Acl_Resource('admin'))
			->addResource(new Zend_Acl_Resource('admin/settings'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/addons'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/user'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/group'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/page'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/removeprofile'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/logo'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/background'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/usersbackground'), 'admin')
			->addResource(new Zend_Acl_Resource('admin/styles'), 'admin')
			->
		/**
		 * Roles.
		 */
		addRole(new Zend_Acl_Role('anonymous'))
			->addRole(new Zend_Acl_Role('guest'))
			->addRole(new Zend_Acl_Role('user'), 'guest')
			->addRole(new Zend_Acl_Role('subscriber'), 'user')
			->addRole(new Zend_Acl_Role('reviewer'), 'user')
			->addRole(new Zend_Acl_Role('admin'), 'reviewer')
			->
		/**
		 * Set privileges for each role
		 */
		allow('anonymous', array(
			'info',
			'index/login',
			'index/activate',
			'index/language',
			'index/validateformajax',
			'addons',
			'profiles/show',
			'profiles/showpost',
			'profiles/images',
			'profiles/albums',
			'posts/load',
			'posts/share',
			'posts/getlightboxdata',
			'posts/downloadimage',
			'likes/getall',
			'editprofile/recoverpassword',
			'notifications/clearmotd'
		))
			->
		allow('guest', array(
			'search',
			'index',
			'addons',
			'info',
			'profiles/show',
			'profiles/showpost',
			'profiles/friends',
			'profiles/following',
			'profiles/followers',
			'profiles/groups',
			'profiles/images',
			'profiles/albums',
			'posts/load',
			'posts/share',
			'posts/getlightboxdata',
			'posts/downloadimage',
			'likes/getall',
			'editprofile/recoverpassword',
			'notifications/clearmotd'
		))
			->
		allow('user', array(
			'info',
			'profiles',
			'connections',
			'images',
			'posts',
			'comments',
			'likes',
			'reports/report',
			'editprofile',
			'messages',
			'fileupload',
			'notifications'
		))
			->
		allow('reviewer', array(
			'managegroups',
			'managepages',
			'reports',
			'editprofile/setbackgroundpicture',
		))
			->
		allow('admin', array(
			'admin',
			'managegroups',
			'managepages',
			'editprofile/setbackgroundpicture',
		))
		;
		
		//
		// manage groups and pages?
		//
		if (! Zend_Registry::get('config')->disable_groups_pages) {
			$this->acl->allow('subscriber', array(
				'managegroups',
				'managepages',
			));
			$this->acl->allow('reviewer', array(
				'managegroups',
				'managepages',
			));
			if (Zend_Registry::get('config')->get('user_manage_groups')) {
				$this->acl->allow('user', array(
					'managegroups',
				));
			}
			if (Zend_Registry::get('config')->get('user_manage_pages')) {
				$this->acl->allow('user', array(
					'managepages',
				));
			}
		}
		
		//
		// custom background image
		//

		// allow to users?
		if (Zend_Registry::get('config')->get('user_background')) {
			$this->acl->allow('user', array(
				'editprofile/setbackgroundpicture',
			));
		} else {
			$this->acl->deny('user', array(
				'editprofile/setbackgroundpicture',
			));
		}
		
		// allow to subscribers?
		if (Zend_Registry::get('config')->get('subscriber_background')) {
			$this->acl->allow('subscriber', array(
				'editprofile/setbackgroundpicture',
			));
		} else {
			$this->acl->deny('subscriber', array(
				'editprofile/setbackgroundpicture',
			));
		}
		
	}

	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$auth = Zend_Auth::getInstance();
		$isAllowed = false;
		
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		
		// Generate the resource name
		$resourceName = $controller . '/' . $action;
		
		// Don't block errors
		if ($resourceName == 'error/error')
			return;
		
		$resources = $this->acl->getResources();
		
		if (! in_array($resourceName, $resources)) {
			$request->setControllerName('error')
				->setActionName('error')
				->setDispatched(true);
			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
			return;
		}
		
		// Check if user can access this resource or not
		$isAllowed = $this->acl->isAllowed(Zend_Registry::get('role'), $resourceName);
		
		// Forward user to access denied or login page if this is guest
		if (! $isAllowed) {
			
			if (! Zend_Auth::getInstance()->hasIdentity()) {
				
				$forwardAction = 'login';
			} else {
				$forwardAction = 'deny';
			}
			
			$request->setControllerName('index')
				->setActionName($forwardAction)
				->setDispatched(true);
		}
	}
}