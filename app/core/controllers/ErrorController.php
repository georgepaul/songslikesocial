<?php

/**
 * Default Error Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class ErrorController extends Zend_Controller_Action
{

	public function errorAction()
	{
		
		$this->_helper->_layout->setLayout('layout_errors');
		
		$this->_helper->viewRenderer->setNoRender(true);
		
		// default application error
		$this->getResponse()->setHttpResponseCode(500);
		$this->view->message = $this->view->translate('Application error');
		
		// log errors
		$logtext = "\n------------------------------------------------------------\n";
		
		$errors = $this->_getParam('error_handler');
		
		if (isset($errors->type)) {
			switch ($errors->type) {
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
					// 404 error -- controller or action not found
					$this->getResponse()->setHttpResponseCode(404);
					$this->view->message = $this->view->translate('Error 404 - Page not found');
					break;
			}
		}
		
		$logtext .= $this->view->message;
		$logtext .= "\n";
		
		if (isset($errors->exception)) {
			$logtext .= (isset($errors->exception->information) ? $errors->exception->information : '');
			$logtext .= "\n";
			$logtext .= $errors->exception->getMessage();
			$logtext .= "\n";
			$logtext .= $errors->exception->getTraceAsString();
		}
		
		// conditionally display exceptions
		if (APPLICATION_ENV != 'production' && isset($errors->exception) && $this->getResponse()->getHttpResponseCode() != 404) {
			$this->view->exception = $errors->exception;
		}
		
		if (APPLICATION_ENV != 'production' && isset($errors->request) && $this->getResponse()->getHttpResponseCode() != 404) {
			$this->view->request = $errors->request;
		}
		
		if (isset($errors->request)) {
			$logtext .= var_export($errors->request->getParams(), true);
			$logtext .= "\n";
		} else {
			$this->view->request = '';
		}
		
		// log errors but not 404s
		if ($this->getResponse()->getHttpResponseCode() != 404) {
			Application_Plugin_Common::log($logtext);
		}
	}
}

