<?php

/**
 * Action Helper for image processing
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Zend_Controller_Action_Helper_ImageProcessing extends Zend_Controller_Action_Helper_Abstract
{

	public $request;

	public $translator;

	public $redirector;

	public $profile;

	public $image_type;

	public $form;

	public $file_element;

	public $is_requiered;


	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function direct($image_type, $profile_name, $form, $file_element, $is_requiered = true)
	{
		$this->request = $this->getFrontController()->getRequest();
		$this->translator = Zend_Registry::get('Zend_Translate');
		$this->redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		$this->profile_name = $profile_name;
		$this->image_type = $image_type;
		$this->form = $form;
		$this->file_element = $file_element;
		$this->is_requiered = $is_requiered;
		
		$this->setImage();
	}

	/**
	 */
	public function setImage()
	{
		// Form Submitted...
		if ($this->request->isPost() && $this->form->isValid($_POST)) {
			// file uploaded?
			if ($this->form->{$this->file_element}->isUploaded()) {
				
				$this->form->{$this->file_element}->receive(); // must have
				$receive_path = $this->form->{$this->file_element}->getFileName();
				$filename = $this->form->{$this->file_element}->getValue();
				$extension = strtolower(pathinfo($receive_path, PATHINFO_EXTENSION));
				
				if ($this->profile_name) {
					
					// delete old tmp image files
					$Storage = new Application_Model_Storage();
					$StorageAdapter = $Storage->getAdapter();
					
					$StorageAdapter->deleteOldTmpFiles(0, 'profileimage_' . $this->profile_name);
					
					$tmp_filename = 'profileimage_' . $this->profile_name . '.' . $extension;
					
					// move new file to tmp folder
					rename($receive_path, TMP_PATH . '/' . $tmp_filename);
					
					// check if valid image
					if (! Application_Plugin_ImageLib::isValidImage(TMP_PATH . '/' . $tmp_filename)) {
						unlink(TMP_PATH . '/' . $tmp_filename);
						Application_Plugin_Alerts::error($this->translator->translate('Server-side error'), 'off');
						$this->redirector->gotoUrl();
						return;
					}
					
					Application_Plugin_Alerts::success($this->translator->translate('You can adjust the picture here'), 'off');
					
					// go back to current page after editing
					$base_url = Application_Plugin_Common::getFullBaseUrl(false);
					$callback_url = $base_url . $this->request->getRequestUri().'/edit_done/1';
					
					// save params to session and redirect to edit page
					$session = new Zend_Session_Namespace('Default');
					$pass_params = array(
						'tmp_image' => $tmp_filename,
						'image_type' => $this->image_type,
						'callback' => $callback_url,
						'profile_name' => $this->profile_name
					);
					$session->pass_params = $pass_params;
					$this->redirector->gotoUrl('images/edit');
				} else {
					// here we store site settings images
					// i.e. network background image
					
					$this->form->{$this->file_element}->receive(); // must have
					$receive_path = $this->form->{$this->file_element}->getFileName();
					$filename = $this->form->{$this->file_element}->getValue();
					$extension = strtolower(pathinfo($receive_path, PATHINFO_EXTENSION));
					
					$file_name = $this->image_type . '.' . $extension;
					
					// move new file to public image folder
					rename($receive_path, PUBLIC_PATH . '/images/' . $file_name);
					
					// store to app settings & refresh
					$app_option_key = $this->image_type;
					
					$AppOptions = new Application_Model_AppOptions();
					$AppOptions->updateOption($app_option_key, $file_name);
					
					$current_config = Zend_Registry::get('config');
					$current_config->{$app_option_key} = $file_name;
					Zend_Registry::set('config', $current_config);
					
					Application_Plugin_Alerts::success($this->translator->translate('Image uploaded'), 'off');
					
					$base_url = Application_Plugin_Common::getFullBaseUrl(false);
					$callback_url = $base_url . $this->request->getRequestUri();
					
					// flush url
					$this->redirector->gotoUrl($callback_url);
				}
			} else {
				
				if ($this->is_requiered) {
					// nothing to upload
					Application_Plugin_Alerts::error($this->translator->translate('Please choose a picture'), 'off');
				}
			}
		}
		
		// somethig went wrong, image too big?
		if ($this->request->isPost() && ! $this->form->isValid($_POST)) {
			Application_Plugin_Alerts::error($this->translator->translate('File not allowed or too big'), 'off');			
		}
	}
}