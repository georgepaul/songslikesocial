<?php

/**
 * Posts Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class PostsController extends Zend_Controller_Action
{

	/**
	 * delete post (via ajax)
	 */
	public function deleteAction()
	{
		$post_id = (int) $this->getRequest()->getParam('post_id');
		
		$Posts = new Application_Model_Posts();
		$post = $Posts->getPosts(null, $post_id);
		
		if (! $post) {
			$this->_helper->json(false);
		}
		
		$ret = $Posts->deletePost($post_id);
		
		$this->_helper->json($ret);
	}

	/**
	 * loading posts after initial load (via ajax)
	 *
	 * pages 2, 3...
	 */
	public function loadAction()
	{
		$wall_id = (int) $this->getRequest()->getParam('wall_id');
		$search_term = $this->getRequest()->getParam('term');
		$search_context = $this->getRequest()->getParam('context');
		
		$Posts = new Application_Model_Posts();
		$Profiles = new Application_Model_Profiles();
		
		$profile_type = 'feed';
		
		if ($wall_id > 0) {
			$wall_profile = $Profiles->getProfileByField('id', $wall_id);
			$profile_type = $wall_profile->type;
		}
		
		if ($this->getRequest()->getParam('post_page_number'))
			$Posts->page_number = (int) $this->getRequest()->getParam('post_page_number');
		else
			$Posts->page_number = 2;

		if ($search_context) {
			// retrieve posts on search context
			$data = $Posts->getPosts(null, false, array(
				'term' => $search_term,
				'context' => $search_context
			));
			
		} else {
			// plain posts on wall
			$data = $Posts->getPosts($wall_id);
		}
		
		$this->view->posts_data = $data;
		$this->view->profile_type = $profile_type;
		
		// stop load if there are no more posts
		if (count($data) >= Zend_Registry::get('config')->get('limit_posts')) {
			$stop_loading = false;
		} else {
			$stop_loading = true;
		}
		
		// Add coment form
		$add_comment_form = new Application_Form_AddComment();
		$this->view->add_comment_form = $add_comment_form;
		
		$page_number = $Posts->page_number + 1;
		$posts = $this->view->render('/partial/posts.phtml');
		
		$out = array(
			'posts' => $posts,
			'post_page_number' => $page_number,
			'stop' => $stop_loading
		);
		
		$this->_helper->json($out);
	}

	/**
	 * get lightbox data (via ajax)
	 */
	public function getlightboxdataAction()
	{
		$Comments = new Application_Model_Comments();
		$Images = new Application_Model_Images();
		$Likes = new Application_Model_Likes();
		$Reports = new Application_Model_Reports();
		$Albums = new Application_Model_Albums();
		$add_comment_form = new Application_Form_AddComment();
		
		$request = $this->getRequest();
		
		$resource_id = $request->getParam('resource_id', 0);
		$context = $request->getParam('context');
		
		$image = $Images->getImage($resource_id, $context);
		
		if (! $image) {
			$this->getHelper('json')->sendJson(false);
			return;
		}
		
		$resource_type = 'image';
		$this->view->resource_type = $resource_type;
		$this->view->resource_id = $resource_id;
		$this->view->context = $context;
		
		$dropdown_options = array();
		$this->view->can_rotate = false;
		
		if (Zend_Auth::getInstance()->hasIdentity()) {
			
			// if owner is viewing, add albums for moving
			if ($image['data']['owner_id'] == Zend_Auth::getInstance()->getIdentity()->id) {
				$albums = $Albums->getAlbums(Zend_Auth::getInstance()->getIdentity()->id, false);
				if (! empty($albums))
					foreach ($albums as $album) {
						$dropdown_options[] = array(
							'id' => $album['id'],
							'name' => Zend_Registry::get('Zend_Translate')->translate('Move to ') . $album['name']
						);
					}
			}
			
			// add move to cover / profile options
			if (! empty($dropdown_options)) {
				$dropdown_options[] = array(
					'id' => 'divider'
				);
			}
			
			$dropdown_options[] = array(
				'id' => 'avatar',
				'name' => Zend_Registry::get('Zend_Translate')->translate('Set as profile picture')
			);
			$dropdown_options[] = array(
				'id' => 'cover',
				'name' => Zend_Registry::get('Zend_Translate')->translate('Set as cover picture')
			);
			
			// if owner, admin or reviewer - add trash link
			if ($image['data']['uploaded_by'] == Zend_Auth::getInstance()->getIdentity()->id || Zend_Auth::getInstance()->getIdentity()->role == 'admin' || Zend_Auth::getInstance()->getIdentity()->role == 'reviewer') {
				// add trash
				$dropdown_options[] = array(
					'id' => 'divider'
				);
				$dropdown_options[] = array(
					'id' => 'trash',
					'name' => Zend_Registry::get('Zend_Translate')->translate('Delete Image')
				);
			}
			
			// if owner - add rotate link
			if ($image['data']['uploaded_by'] == Zend_Auth::getInstance()->getIdentity()->id) {
				$this->view->can_rotate = true;
			}
		}
		
		$this->view->dropdown_options = $dropdown_options;
		
		// comments
		$show_hidden_comments = ($context == 'single' ? true : false);
		$new_comments_data = $Comments->getCommentsForResources(array(
			$resource_id
		), $resource_type, $show_hidden_comments);
		$add_comment_form->reset();
		$this->view->comments = (isset($new_comments_data[$resource_id]) ? $new_comments_data[$resource_id] : array());
		$this->view->add_comment_form = $add_comment_form;
		
		// likes
		$this->view->is_liked = $Likes->isLiked($resource_id, $resource_type);
		$this->view->likes_count = $Likes->getLikesCount($resource_id, $resource_type);
		
		// reports
		$this->view->is_reported = $Reports->isReported($resource_id, $resource_type);
		$this->view->resource_owner_name = 'not-used';
		$this->view->btn_title = Zend_Registry::get('Zend_Translate')->translate('Report');
		$this->view->class = 'btn btn-default btn-xs';
		
		$this->view->image = $image;
		
		$html = $this->view->render('/partial/lightbox.phtml');
		
		$this->getHelper('json')->sendJson($html);
	}

	/**
	 * download original image
	 */
	public function downloadimageAction()
	{
		$request = $this->getRequest();
		$image_id = $request->getParam('resource_id');
		
		$Images = new Application_Model_Images();
		$image = $Images->getImage($image_id);
		
		if (! isset($image['data']['file_name']) || empty($image['data']['file_name']))
			$this->redirect('');
		
		if ($image['data']['original']) {
			$filename = $image['data']['original'];
		} else {
			$filename = $image['data']['file_name'];
		}
		
		$Storage = new Application_Model_Storage();
		$StorageAdapter = $Storage->getAdapter();
		$StorageAdapter->downloadFile($filename);
		
		die();
	}

	/**
	 * Edit post (ajax)
	 */
	public function editAction()
	{
		$request = $this->getRequest();
		
		$post_id = (int) $request->getParam('post');
		
		$Posts = new Application_Model_Posts();
		$post = $Posts->getPost($post_id, true);
		$posts_wall_profile = $Posts->getPostsWallProfileData($post_id);
		
		if (! $post) {
			$this->getHelper('json')->sendJson($this->view->translate('Resource not available'));
			return;
		}
		
		// load and fill up form
		$edit_post_form = new Application_Form_EditPost();
		$edit_post_form->getElement('content')->setValue(html_entity_decode($post['content']));
		$edit_post_form->getElement('privacy')->setValue($post['privacy']);
		
		// no privacy edit for groups & pages
		if ($posts_wall_profile['type'] == 'group' || $posts_wall_profile['type'] == 'page') {
			$edit_post_form->removeElement('privacy');
		}
		
		// get and render form only
		if ($request->isPost() && $request->getParam('form_render')) {
			$edit_post_form->setAction(Zend_Controller_Front::getInstance()->getBaseUrl() . '/posts/edit/post/'.$post_id);
			$this->getHelper('json')->sendJson($edit_post_form->render());
			return;
		}
		
		if ($request->isPost() && $edit_post_form->isValid($_POST)) {
			
			$content = $edit_post_form->getElement('content')->getValue();
			
			if ($edit_post_form->getElement('privacy') && $edit_post_form->getElement('privacy')->getValue()) {
				$privacy = $edit_post_form->getElement('privacy')->getValue();
			} else {
				$privacy = $post['privacy'];
			}
			
			$new_post_content = Application_Plugin_Common::preparePost($content);
			
			$Posts->updatePost($post_id, $new_post_content, $privacy);
			
			$this->getHelper('json')->sendJson($this->view->RenderOutput($new_post_content['content'], 'post'));
			return;
		}
		
		$this->getHelper('json')->sendJson($this->view->translate('Error - not permitted'));
		return;
	}

	
	/**
	 * Repost (copy post/image on your wall)
	 */
	public function repostAction()
	{
		$request = $this->getRequest();
	
		$post_id = (int) $request->getParam('post_id', false);
		$image_id = (int) $request->getParam('image_id', false);
	
		if ($post_id) {
			$Posts = new Application_Model_Posts();
			$Posts->sharePostToWall($post_id);
		}
		
		// flush to wall
		$this->redirect('');
	}
	
	
	/**
	 * get share modal content (via ajax)
	 */
	public function shareAction()
	{
		$request = $this->getRequest();
		
		$resource_type = $request->getParam('resource_type', 0);
		$resource_id = $request->getParam('resource_id', 0);
		
		$base_link = Application_Plugin_Common::getFullBaseUrl();
		
		$repost_link = false;
		
		switch ($resource_type) {
			case 'post':
				$Posts = new Application_Model_Posts();
				$post = $Posts->getPost($resource_id);
				$profile = $Posts->getProfileDataByPostWall($resource_id);
				$profile_name = $profile['name'];
				
				$direct_link = $base_link . '/profiles/showpost/name/' . $profile_name . '/post/' . $resource_id;
				$repost_link = $base_link . '/posts/repost/post_id/' . $resource_id;

				break;
			
			case 'profile':
				$direct_link = $base_link . '/' . $resource_id;

				break;
			
			case 'image':
				$Images = new Application_Model_Images();
				$image = $Images->getImage($resource_id);
				$direct_link = $base_link . '/index/index/showimage/' . $image['data']['uid'];
				break;
			
			default:
				$direct_link = $base_link;
				break;
		}
		
		// drop repost link if not logged in
		if (! Zend_Auth::getInstance()->hasIdentity()) {
			$repost_link = false;
		}
		
		$this->view->repost_link = $repost_link;
		$this->view->direct_link = $direct_link;
	
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_share', $this);
		
		$html = $this->view->render('/partial/share_modal_content.phtml');
		
		$this->getHelper('json')->sendJson($html);
	}
}