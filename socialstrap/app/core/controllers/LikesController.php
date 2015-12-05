<?php

/**
 * Likes Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class LikesController extends Zend_Controller_Action
{

	/**
	 * Like a resource (via ajax)
	 */
	public function togglelikeAction()
	{
		$resource_id = (int) $this->getRequest()->getParam('resource_id');
		$resource_type = $this->getRequest()->getParam('resource_type');
		
		$Likes = new Application_Model_Likes();
		
		$ret = $Likes->toggleLike($resource_id, $resource_type);
		
		if ($ret['state'] == 1)
			$ret['text'] = $this->view->translate('Unlike');
		else
			$ret['text'] = $this->view->translate('Like');
		
		$this->getHelper('json')->sendJson($ret);
	}

	/**
	 * Get all likes (via ajax)
	 */
	public function getallAction()
	{
		$resource_id = (int) $this->getRequest()->getParam('resource_id');
		$resource_type = $this->getRequest()->getParam('resource_type');
		
		$Likes = new Application_Model_Likes();
		
		$ret = $Likes->getUsersLiked($resource_type, $resource_id);
		
		$this->getHelper('json')->sendJson($ret);
	}

	/**
	 * Show resource with like - notification handler
	 */
	public function showAction()
	{
		$like_id = (int) $this->getRequest()->getParam('like');
		
		$Likes = new Application_Model_Likes();
		$like = $Likes->getLikeById($like_id);
		
		// for likes on comments show appropriate (parent) post or image
		if ($like->resource_type == 'comment') {
			$Comments = new Application_Model_Comments();
			$comment = $Comments->getComment($like->resource_id);
			
			// owerwrite resource_id with the one found in comment
			$like->resource_id = $comment['resource_id'];
			
			// overwrite resource_type
			if ($comment['resource_type'] == 'post') {
				$like->resource_type = 'post';
			} elseif ($comment['resource_type'] == 'image') {
				$like->resource_type = 'image';
			}
		}
		
		// redirect based on post/comment/image
		switch ($like->resource_type) {
			case 'post':
				$post_id = $like->resource_id;
				$Posts = new Application_Model_Posts();
				$profile = $Posts->getProfileDataByPostWall($post_id);
				$profile_name = $profile['name'];
				$this->redirect('/profiles/showpost/name/' . $profile_name . '/post/' . $post_id);
				break;
			
			case 'image':
				$Images = new Application_Model_Images();
				$image = $Images->getImage($like->resource_id);
				$this->redirect('/index/index/showimage/' . $image['data']['uid']);
				break;
			
			default:
				$this->redirect('');
				break;
		}
	}
}