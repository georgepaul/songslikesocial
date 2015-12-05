<?php
/**
 * Emotions pack add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

$this->attach('view_head', 10, function($view) {
	echo '<link rel="stylesheet" type="text/css" href="'.$view->baseUrl().'/addons/'.basename(__DIR__).'/style.css">';
});


$this->attach('hook_data_renderoutput', 9, function(&$data) {

	$content = &$data['content'];
	
	// do not show on code posts
	if (strpos($content, '<pre>') !== false) return $content;


	$em = array(":)", ":-)", ":]");
	$content = str_replace($em, '<span class="emotion e01"></span>', $content);

	$em = array(":(", ":-(");
	$content = str_replace($em, '<span class="emotion e02"></span>', $content);

	$em = array(":-P", ":P", ":p", ":-p");
	$content = str_replace($em, '<span class="emotion e03"></span>', $content);

	$em = array(":D", ":-D");
	$content = str_replace($em, '<span class="emotion e04"></span>', $content);

	$em = array(":0", ":-0", ":O", ":-O");
	$content = str_replace($em, '<span class="emotion e05"></span>', $content);

	$em = array(";)", ";-)");
	$content = str_replace($em, '<span class="emotion e06"></span>', $content);

	$em = array("8)", "8-)", "B)", "B-)");
	$content = str_replace($em, '<span class="emotion e07"></span>', $content);

	$em = array("8|", "8-|", "B|", "B-|");
	$content = str_replace($em, '<span class="emotion e08"></span>', $content);

	$em = array(":*", ":-*");
	$content = str_replace($em, '<span class="emotion e13"></span>', $content);

	$em = array("^_^");
	$content = str_replace($em, '<span class="emotion e16"></span>', $content);

	$em = array("o.O");
	$content = str_replace($em, '<span class="emotion e17"></span>', $content);
	
	$em = array(":v", ":V");
	$content = str_replace($em, '<span class="emotion e19"></span>', $content);

});