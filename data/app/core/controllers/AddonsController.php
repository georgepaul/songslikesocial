<?php

/**
 * Addons Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class AddonsController extends Zend_Controller_Action
{

	public function init()
	{
		if (! Zend_Registry::get('config')->get('allow_addons')) {
			$this->redirect('');
		}
	}

	/**
	 * Show addon as a page
	 */
	public function showAction()
	{
		$request = $this->getRequest();
		
		$addon_name = $request->getParam('name');
		
		$script_path = ADDONS_PATH . '/' . $addon_name . '/';
		
		$this->_helper->_layout->setLayout('layout_wide');
		
		// redirect if not exists
		if (! is_readable($script_path . 'page.php')) {
			$this->redirect('');
		}
		
		$this->_helper->viewRenderer->setViewSuffix('php');
		
		$this->view->addScriptPath($script_path);
		$this->view->addScriptPath($this->view->GetStaticContentPath('GetStaticContentPath'));
		
		$this->render('page', null, true);
	}
}