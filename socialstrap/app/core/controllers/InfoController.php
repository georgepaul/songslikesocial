<?php

/**
 * Info Controller (static pages, language specific option)
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class InfoController extends Zend_Controller_Action
{

	public function pageAction()
	{
		$this->_helper->_layout->setLayout('layout_wide');
		$script_path = APPLICATION_PATH . '/views/info/';
		
		$page_name = $this->getRequest()->getParam('name');
		
		$current_language = Zend_Registry::get('locale');
		
		$script_name = false;
		
		// default version
		if (is_readable($script_path . $page_name . '.phtml')) {
			$script_name = $page_name;
		}
		
		// language specific override?
		if (is_readable($script_path . $page_name . '-' . $current_language . '.phtml')) {
			$script_name = $page_name . '-' . $current_language;
		}
		
		if ($script_name) {
			
			$this->view->addScriptPath($this->view->GetStaticContentPath('GetStaticContentPath'));
			
			$this->render($script_name, null, true);
		} else {
			$this->redirect('');
		}
	}
}