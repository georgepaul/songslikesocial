<?php

/**
 * Addons
 *
 * This should serve as a storage for all addons
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Model_Addons extends Zend_Db_Table_Abstract
{

	protected $_name = 'addons';

	protected $_rowClass = 'Application_Model_Addons_Row';
}

class Application_Model_Addons_Row extends Zend_Db_Table_Row_Abstract
{
}