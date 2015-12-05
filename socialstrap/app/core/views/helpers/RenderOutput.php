<?php

class Zend_View_Helper_RenderOutput extends Zend_View_Helper_Abstract
{
	
	/*
	 * context: default, sidebar, message, comment, post
	 */
	public function RenderOutput($content, $context = 'default')
	{
		if ($context != 'post') {
			$filter = new Zend_Filter_StripTags();
			$content = $filter->filter($content);
		}
		
		$content = nl2br($content);
		
		// trigger addons
		$data = array(
			'content' => $content,
			'context' => $context
		);
		Zend_Registry::get('hooks')->trigger('hook_data_renderoutput', $data);
		
		return $data['content'];
	}
}