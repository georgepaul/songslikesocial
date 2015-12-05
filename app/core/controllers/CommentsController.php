<?php

/**
 * Comments Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class CommentsController extends Zend_Controller_Action
{

	/**
	 * Post comment (via ajax)
	 */
	public function postcommentAction()
	{
		$add_comment_form = new Application_Form_AddComment();
		
		$json = array();
		
		$request = $this->getRequest();
		if ($request->isPost() && $add_comment_form->isValid($_POST)) {
			
			$comment_content = $add_comment_form->getValue('comment');
			$comment_content = Application_Plugin_Common::prepareComment($comment_content);
			
			// drop on false
			if ($comment_content === false) {
				$this->getHelper('json')->sendJson(false);
			}
			
			$resource_type = $add_comment_form->getValue('resource_type');
			$resource_id = (int) $add_comment_form->getValue('resource_id');
			
			$Comments = new Application_Model_Comments();
			$Comments->addComment($comment_content, $resource_id, $resource_type);
			
			$new_comments_data = $Comments->getCommentsForResources(array(
				$resource_id
			), $resource_type);
			
			if (isset($new_comments_data[$resource_id])) {
				
				$add_comment_form->reset();
				$this->view->comments = $new_comments_data[$resource_id];
				$this->view->resource_type = $resource_type;
				$this->view->resource_id = $resource_id;
				$this->view->add_comment_form = $add_comment_form;
				
				$comments_html = $this->view->render('/partial/comments.phtml');
				
				$json['html'] = $comments_html;
			}
		}
		
		$json['errors'] = $add_comment_form->getMessages();
		
		$this->getHelper('json')->sendJson($json);
	}

	/**
	 * delete comment (via ajax)
	 */
	public function deleteAction()
	{
		$add_comment_form = new Application_Form_AddComment();
		
		$comment_id = (int) $this->getRequest()->getParam('comment_id');
		$resource_id = (int) $this->getRequest()->getParam('resource_id');
		$resource_type = $this->getRequest()->getParam('resource_type');
		
		$Comments = new Application_Model_Comments();
		
		$Comments->deleteComment($comment_id);
		$new_comments_data = $Comments->getCommentsForResources(array(
			$resource_id
		), $resource_type);
		
		$this->view->comments = (isset($new_comments_data[$resource_id]) ? $new_comments_data[$resource_id] : false);
		$this->view->resource_type = $resource_type;
		$this->view->resource_id = $resource_id;
		
		$this->view->add_comment_form = $add_comment_form;
		
		$comments_html = $this->view->render('/partial/comments.phtml');
		
		$json = array();
		$json['html'] = $comments_html;
		$this->getHelper('json')->sendJson($json);
	}
	
	
	/**
	 * Edit comment (ajax)
	 */
	public function editAction()
	{
		$request = $this->getRequest();
	
		$user_role = Zend_Auth::getInstance()->getIdentity()->role;
		
		$comment_id = (int) $request->getParam('id', false);
	
		$Comments = new Application_Model_Comments();
		$comment = $Comments->getComment($comment_id);
		
		if (! $comment && ! isset($comment['content'])) {
			$this->getHelper('json')->sendJson($this->view->translate('Resource not available'));
			return;
		}
			
		// check if my comment or an admin
		if ($Comments->getCommentAuthorId($comment_id) != Zend_Auth::getInstance()->getIdentity()->id && ($user_role != 'admin' && $user_role != 'reviewer')) {
			$this->getHelper('json')->sendJson($this->view->translate('Error - not permitted'));
			return;
		}
	
		// load and fill up form
		$edit_comment_form = new Application_Form_EditComment();
		$edit_comment_form->getElement('comment')->setValue($comment['content']);
	
		// get and render form only
		if ($request->isPost() && $request->getParam('form_render')) {
			$edit_comment_form->setAction(Zend_Controller_Front::getInstance()->getBaseUrl() . '/comments/edit/id/'.$comment_id);
			$this->getHelper('json')->sendJson($edit_comment_form->render());
			return;
		}
		
		if ($request->isPost() && $edit_comment_form->isValid($_POST)) {
				
			$comment_content = $edit_comment_form->getElement('comment')->getValue();
			$comment_content = Application_Plugin_Common::prepareComment($comment_content);
				
			// drop on false
			if ($comment_content === false) {
				$this->getHelper('json')->sendJson($this->view->translate('Error - not permitted'));
				return;
			}
				
			$ret = $Comments->updateComment($comment_id, $comment_content);
			
			$this->getHelper('json')->sendJson($this->view->RenderOutput($comment_content, 'comment'));
			return;
		}
		
		$this->getHelper('json')->sendJson($this->view->translate('Error - not permitted'));
		return;
	}
	
}