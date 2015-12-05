<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_CustomBackground extends Application_Form_Main
{

	/**
	 *
	 * Change network background
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
	
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/CustomBackground.phtml'))));

		// load settings
		$Profiles = new Application_Model_Profiles();
		$ProfilesMeta = new Application_Model_ProfilesMeta();
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request_profile_id = $request->getParam('id', false);
		$profile = $Profiles->getProfileByField('id', $request_profile_id);
		
		if ((Zend_Auth::getInstance()->getIdentity()->role == 'admin' && $request_profile_id)
			|| ($request_profile_id && $Profiles->getProfile($profile->name, false, true))) {
			// admin or own group & page
			$profile_id = $request_profile_id;
		} else {
			// editing profile
			$profile_id = Zend_Auth::getInstance()->getIdentity()->id;
		}
		
		$all_meta = $ProfilesMeta->getMetaValues($profile_id);

		// fields

		$background_image = new Zend_Form_Element_File('background');
		$background_image
		->setDecorators(array('File', 'Errors'))
		->setLabel($this->translator->translate('Choose Picture (jpg, png or gif)'))
		->addValidator('Count', false, 1) // ensure only 1 file
		->addValidator('Size', false, Zend_Registry::get('config')->get('max_file_upload_size'))
		->addValidator('Extension', false, 'jpg,jpeg,png,gif');
		
		$background_image->getValidator('Count')->setMessage($this->translator->translate('File not allowed or too big'));
		$background_image->getValidator('Size')->setMessage($this->translator->translate('File not allowed or too big'));
		$background_image->getValidator('Extension')->setMessage($this->translator->translate('File not allowed or too big'));
		;
		
		$background_repeat = new Zend_Form_Element_Checkbox('background_repeat');
		$background_repeat
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_repeat']) && $all_meta['background_repeat'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Repeat background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$background_scroll = new Zend_Form_Element_Checkbox('background_scroll');
		$background_scroll
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_scroll']) && $all_meta['background_scroll'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Scroll background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$background_stretch = new Zend_Form_Element_Checkbox('background_stretch');
		$background_stretch
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_stretch']) && $all_meta['background_stretch'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Stretch background'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$disable_image = new Zend_Form_Element_Checkbox('background_noimage');
		$disable_image
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['background_noimage']) && $all_meta['background_noimage'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Disable custom image'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$submit = new Zend_Form_Element_Submit('formsubmit');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Save'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$background_image,
				$background_repeat,
				$background_scroll,
				$background_stretch,
				$disable_image,
				$submit));

		$this->postInit();
	}

}

