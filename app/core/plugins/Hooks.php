<?php

/**
 * Hooks (Mediator Pattern) * 
 *
 * addon init (hooks.php):
 * $hooks->attach('hook_view_sidebar', 10, function($view) { echo "Hello"; });
 * $hooks->detach('hook_view_sidebar');
 *
 * app usage:
 * Zend_Registry::get('hooks')->trigger('hook_view_sidebar', $this);
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */
class Application_Plugin_Hooks extends Zend_Controller_Plugin_Abstract
{

	protected $events = array();

	/**
	 * Init hooks here so addons can have $request data prepared
	 */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		
		// include from addons
		if (Zend_Registry::get('config')->get('allow_addons') && APPLICATION_ENV != 'safe') {
			foreach (glob(ADDONS_PATH . '/*') as $addon) {
				$plugin_name = basename($addon);
				
				// do not include disabled
				if ($plugin_name[0] != '_' && file_exists($addon . '/hooks.php')) {
					require_once $addon . '/hooks.php';
				}
			}
		}
		
		// save to global storage registry
		Zend_Registry::set('hooks', $this);
		
		// run bootstrap hooks
		$front = Zend_Controller_Front::getInstance();
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
		if (null === $viewRenderer->view) {
			$viewRenderer->initView();
		}
		$view = $viewRenderer->view;
		
		$app = array(
			'front' => $front,
			'view' => $view
		);
		
		// trigger hooks
		Zend_Registry::get('hooks')->trigger('hook_app_init', $app);
	}

	/**
	 * attach event
	 */
	public function attach($eventName, $priority, $callback)
	{
		
		// create an array if this event was never used
		if (! isset($this->events[$eventName])) {
			$this->events[$eventName] = array();
		}
		
		$this->events[$eventName][] = array(
			'callback' => $callback,
			'priority' => $priority
		);
	}

	/**
	 * remove event
	 */
	public function detach($eventName)
	{
		if (isset($this->events[$eventName])) {
			unset($this->events[$eventName]);
		}
	}

	/**
	 * trigger all attached events
	 */
	public function trigger($eventName, &$data = null)
	{
		if (isset($this->events[$eventName]) && ! empty($this->events[$eventName])) {
			
			$fn_ordered = array();
			
			foreach ($this->events[$eventName] as $callback) {
				
				while (isset($fn_ordered[$callback['priority']])) {
					++ $callback['priority'];
				}
				
				$fn_ordered[$callback['priority']] = $callback['callback'];
			}
			
			// sort by priority
			ksort($fn_ordered);
			
			// run
			foreach ($fn_ordered as $callback) {
				
				$callback($data);
			}
		}
	}
}