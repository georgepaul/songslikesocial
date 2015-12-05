<?php

/**
 * Image editor Controller
 * 
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class ImagesController extends Zend_Controller_Action
{

	public $image_name;

	public $image_type;

	public $profile_name;

	public $callback;

	public $target_x;

	public $target_y;

	public $view_span;

	public function init()
	{
		// init only edit actions
		if ($this->getFrontController()
			->getRequest()
			->getActionName() !== 'edit')
			return;
		
		$this->_helper->_layout->setLayout('layout_wide');
		$this->view->jcrop = true;
		
		// load params from session
		$session = new Zend_Session_Namespace('Default');
		
		if (! isset($session->pass_params) || ! $session->pass_params)
			$this->redirect('');
		
		$pass_params = $session->pass_params;
		
		$this->image_type = $pass_params['image_type'];
		$this->profile_name = $pass_params['profile_name'];
		$this->callback = $pass_params['callback'];
		$this->image_name = $pass_params['tmp_image'];
		
		switch ($this->image_type) {
			case 'avatar':
				$this->db_field = 'avatar';
				$this->target_x = 64;
				$this->target_y = 64;
				$this->view->php_jcropSetAspectRatio = 1;
				$this->view->preview_span = 9;
				$this->view->preview_class = "";
				break;
			
			case 'cover':
				$this->db_field = 'cover';
				$this->target_x = 940;
				$this->target_y = Zend_Registry::get('config')->get('cover_ysize');
				$this->view->php_jcropSetAspectRatio = $this->target_x / $this->target_y;
				$this->view->preview_span = 11;
				$this->view->preview_class = "cover";
				break;
		}
		
		// send image name to view
		$this->view->image = $this->image_name;
	}

	/**
	 * Edit image
	 */
	public function editAction()
	{
		$request = $this->getRequest();
		$do_rotate = $request->getParam('rotate');
		$do_skip = $request->getParam('skip');
		
		$Profiles = new Application_Model_Profiles();
		$profile = $Profiles->getProfileRow($this->profile_name, true, true);
		
		if (! $profile) $this->redirect('');
		
		$extension = strtolower(pathinfo(TMP_PATH . '/' . $this->image_name, PATHINFO_EXTENSION));
		
		if ($request->isPost() || $do_skip) {
			if ($do_skip) {
				// skip editing and use the full image
				Application_Plugin_ImageLib::resample(TMP_PATH . '/' . $this->image_name, TMP_PATH . '/' . $this->image_name, $this->target_x, $this->target_y, false);
			} else {
				$x = intval($_POST['x']);
				$y = intval($_POST['y']);
				$w = intval($_POST['w']);
				$h = intval($_POST['h']);
				
				if ($x + $y + $w + $h == 0)
					$this->redirect('');
				
				Application_Plugin_ImageLib::crop(TMP_PATH . '/' . $this->image_name, $x, $y, $w, $h, $this->target_x, $this->target_y);
			}
			
			$Storage = new Application_Model_Storage();
			$StorageAdapter = $Storage->getAdapter();
			
			// delete old file
			if (strstr($profile->{$this->db_field}, 'default') === false) {
				
				$StorageAdapter->deleteFileFromStorage($profile->{$this->db_field}, $this->image_type);
			}
			
			$new_filename = $StorageAdapter->moveFileToStorage($this->view->image, $this->image_type);
			
			$profile->{$this->db_field} = $new_filename;
			$profile->save();
			
			Application_Plugin_Alerts::success($this->view->translate('Image saved'));
			
			// kill tmp session
			$session = new Zend_Session_Namespace('Default');
			$session->pass_params = false;
			
			// refresh user session in case profile picture is updated
			Zend_Auth::getInstance()->getStorage()->write($Profiles->getProfileRowObject());
			
			// go back
			$this->redirect($this->callback);
		} elseif ($do_rotate) {
			
			Application_Plugin_ImageLib::rotate(TMP_PATH . '/' . $this->image_name);
		}
	}

	/**
	 * Change profile picture
	 */
	public function setprofilepictureAction()
	{
		$request = $this->getRequest();
		$profile_name = $request->getParam('name', null);
		
		$Profiles = new Application_Model_Profiles();
		$profile = $Profiles->getProfile($profile_name, true, true);
		
		if (! $profile) $this->redirect('');
		
		if (! isset($this->view->sidebar_nav_menu)) {
			$this->view->sidebar_editprofile = $profile;
			
			Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
			{
				echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
			});
		}
		
		$profilepicture_form = new Application_Form_ProfilePicture();
		$this->view->avatar = $profile->avatar;
		$this->view->profilepicture_form = $profilepicture_form;
		
		// image processing helper
		$this->_helper->imageProcessing('avatar', $profile->name, $profilepicture_form, 'avatarfile');
	}

	/**
	 * Change cover picture
	 */
	public function setcoverpictureAction()
	{
		$request = $this->getRequest();
		$profile_name = $request->getParam('name', null);
		
		$Profiles = new Application_Model_Profiles();
		$profile = $Profiles->getProfile($profile_name, true, true);
		
		if (! $profile) $this->redirect('');
		
		if (! isset($this->view->sidebar_nav_menu)) {
			$this->view->sidebar_editprofile = $profile;
			
			Zend_Registry::get('hooks')->attach('hook_view_sidebar', 5, function ()
			{
				echo Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view->render('/_sidebar/editprofile.phtml');
			});
		}
		
		$coverpicture_form = new Application_Form_CoverPicture();
		$this->view->cover = $profile->cover;
		$this->view->coverpicture_form = $coverpicture_form;
		
		// image processing helper
		$this->_helper->imageProcessing('cover', $profile->name, $coverpicture_form, 'coverfile');
	}

	/**
	 * move image to album (via ajax)
	 */
	public function moveimageAction()
	{
		$Images = new Application_Model_Images();
		$Albums = new Application_Model_Albums();
		
		$current_user = Zend_Auth::getInstance()->getIdentity();
		$request = $this->getRequest();
		
		$image_id = $request->getParam('resource_id');
		$album_id = $request->getParam('album_id');
		
		// do some basic checks
		if (! $image_id || ! $album_id) {
			$this->getHelper('json')->sendJson(false);
		}
		
		// see if this is a delete
		if ($album_id == 'trash') {
			$ret = $Images->deleteImage($image_id, 'posts');
			$this->getHelper('json')->sendJson($ret);
			return;
		}
		
		// see if this is "set as profile picture"
		if ($album_id == 'avatar' || $album_id == 'cover') {
			
			$image = $Images->getImage($image_id);
			$file_name = $image['data']['file_name'];
			$tmp_file_name = 'setas_' . $file_name;
			
			$Storage = new Application_Model_Storage();
			$StorageAdapter = $Storage->getAdapter();
			$StorageAdapter->getFileFromStorage($file_name, $tmp_file_name, 'posts');
			
			// save params to session and redirect to edit page
			$session = new Zend_Session_Namespace('Default');
			$pass_params = array(
				'tmp_image' => $tmp_file_name,
				'image_type' => $album_id,
				'callback' => '',
				'profile_name' => $current_user->name
			);
			$session->pass_params = $pass_params;
			
			$this->getHelper('json')->sendJson(true);
			return;
		}
		
		$album = $Albums->getAlbum($album_id);
		
		// see if this album belongs to the current user
		if (! isset($album['user_id']) || $album['user_id'] != $current_user->id) {
			$this->getHelper('json')->sendJson(false);
		}
		
		$ret = $Images->updateField($image_id, 'album_id', $album_id);
		
		if ($album['name']) {
			$ret = $album['name'];
		}
		
		$this->getHelper('json')->sendJson($ret);
	}

	/**
	 * rotate image (via ajax)
	 */
	public function rotateimageAction()
	{
		$request = $this->getRequest();
		$image_id = $request->getParam('resource_id');
		
		$Images = new Application_Model_Images();
		$ret = $Images->rotateImage($image_id);
		
		$this->getHelper('json')->sendJson($ret);
	}
}