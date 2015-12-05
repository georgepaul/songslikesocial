<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_SettingsStorage extends Application_Form_Main
{

	/**
	 *
	 * General settings
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname));
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/Settings.phtml'))));

		// load settings
		$AppOptions = new Application_Model_AppOptions();
		$all_meta = $AppOptions->getAllOptions();

		// fields
		$php_post_max_size =  Application_Plugin_Common::returnBytes(ini_get('post_max_size'));
		$php_upload_max_filesize =  Application_Plugin_Common::returnBytes(ini_get('upload_max_filesize'));

		$info_class = '';
		if ($all_meta['max_file_upload_size'] > $php_post_max_size || $all_meta['max_file_upload_size'] > $php_upload_max_filesize){
			$info_class = 'warning';
		}
		$filesize_php_info = '<span class="'.$info_class.'">('.$this->translator->translate('php ini settings:').' post_max_size = '.$php_post_max_size.', upload_max_filesize = '.$php_upload_max_filesize.')</span>';

		$max_file_upload_size = new Zend_Form_Element_Text('max_file_upload_size');
		$max_file_upload_size
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max file upload size in bytes').' '.$filesize_php_info)
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_file_upload_size']) ? $all_meta['max_file_upload_size'] : '1048576')
		->setAttrib('class', 'form-control');

		$max_images_per_post = new Zend_Form_Element_Text('max_images_per_post');
		$max_images_per_post
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max images per post'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_images_per_post']) ? $all_meta['max_images_per_post'] : '5')
		->setAttrib('class', 'form-control');
		
		$max_files_per_user = new Zend_Form_Element_Text('max_files_per_user');
		$max_files_per_user
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max files per user'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_files_per_user']) ? $all_meta['max_files_per_user'] : '0')
		->setAttrib('class', 'form-control');
		
		$max_storage_per_user = new Zend_Form_Element_Text('max_storage_per_user');
		$max_storage_per_user
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Max storage space per user (in bytes)'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['max_storage_per_user']) ? $all_meta['max_storage_per_user'] : '0')
		->setAttrib('class', 'form-control');

		$resample_images = new Zend_Form_Element_Checkbox('resample_images');
		$resample_images
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['resample_images']) && $all_meta['resample_images'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Resample uploaded images'))
		->setCheckedValue("1")
		->setUncheckedValue("0");
		
		$keep_original = new Zend_Form_Element_Checkbox('keep_original');
		$keep_original
		->setDecorators(array('ViewHelper', 'Errors'))
		->setValue((isset($all_meta['keep_original']) && $all_meta['keep_original'] == 1 ? 1 : 0))
		->setLabel($this->translator->translate('Keep original file'))
		->setCheckedValue("1")
		->setUncheckedValue("0");

		$resample_maxwidth = new Zend_Form_Element_Text('resample_maxwidth');
		$resample_maxwidth
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Resample image max width'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['resample_maxwidth']) ? $all_meta['resample_maxwidth'] : '400')
		->setAttrib('class', 'form-control');

		$resample_maxheight = new Zend_Form_Element_Text('resample_maxheight');
		$resample_maxheight
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($this->translator->translate('Resample image max height'))
		->setValidators(array('digits'))
		->setRequired(true)
		->setValue(isset($all_meta['resample_maxwidth']) ? $all_meta['resample_maxheight'] : '400')
		->setAttrib('class', 'form-control');

		$submit = new Zend_Form_Element_Submit('submitbtn');
		$submit
		->setDecorators(array('ViewHelper'))
		->setLabel($this->translator->translate('Update'))
		->setAttrib('class', 'submit btn btn-default');

		$this->addElements(array(
				$max_file_upload_size,
				$max_images_per_post,
				$max_files_per_user,
				$max_storage_per_user,
				$resample_images,
				$keep_original,
				$resample_maxwidth,
				$resample_maxheight,
				$submit));

		$this->postInit();
	}

}

