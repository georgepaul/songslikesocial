<?php
/**
 * Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_FilterSearchUsers extends Application_Form_Main
{

	/**
	 *
	 * Search Filter for Users
	 *
	 */
	public function init()
	{
		$cname = explode('_', get_class()); $this->preInit(end($cname), false);
		
		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'forms/SearchFilters.phtml'))));

		// GET - requiered for pagination to work
		$this->setMethod('get');
		$this->setAction(Zend_Controller_Front::getInstance()->getBaseUrl().'/search/users/');

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request_gender = $request->getParam('search_filter_gender');

		// fields
		$genders_array = Zend_Registry::get('genders_array');

		// append "All" at the top
		$genders_array = array_reverse($genders_array, true);
		$genders_array[''] = $this->translator->translate('All');
		$genders_array = array_reverse($genders_array, true);

		$gender = new Zend_Form_Element_Select('search_filter_gender', array('onchange' => 'submit();'));
		$gender
		->setDecorators(array('ViewHelper', 'Errors'))
		->setMultiOptions($genders_array)
		->setErrorMessages(array($this->translator->translate('Please select')))
		->setLabel($this->translator->translate('Gender Filter'))
		->setValue($request_gender)
		->setAttrib('class', 'form-control');

		$this->addElements(array($gender));
		
		$this->postInit();
	}

}

