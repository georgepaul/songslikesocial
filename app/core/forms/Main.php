<?php
/**
 * Main Abstract Form
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Application_Form_Main extends Zend_Form
{

	public $translator;
	public $formname;
	public $identifier;
	public $csrf;


	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct()
	{

		$this->translator = $this->getTranslator();

		parent::__construct();
	}


	/**
	 *
	 * PreInit
	 *
	 */
	public function preInit($name, $addIdentifier = true, $addCsrf = true)
	{

		$this->formname = $name;

		// hidden form identifier
		if ($addIdentifier){
			$this->identifier = new Zend_Form_Element_Hidden('identifier');
			$this->identifier
			->setDecorators(array('ViewHelper'))
			->setValue($this->formname);
				
			$this->addElement($this->identifier);
		}

		// add csrf protection
		if ($addCsrf){
			$this->csrf = new Zend_Form_Element_Hash('csrf');
			$this->csrf->setTimeout(14400); // 4h timeout
			$this->addElement($this->csrf);
		}
		

		
		$this->setName($this->formname);
		$this->setMethod('post');
		$this->setAction('');
	}


	/**
	 *
	 * PostInit
	 *
	 * Call addons here
	 *
	 */
	public function postInit()
	{
		Zend_Registry::get('hooks')->trigger('form_'.$this->formname, $this);
	}

}

