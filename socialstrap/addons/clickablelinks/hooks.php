<?php
/**
 * Create clickable links add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

$this->attach('hook_data_renderoutput', 10, function(&$data) {

	$content = &$data['content'];
	
	$content = ' ' . $content;
	
	//simple: $content = preg_replace("#([\t\r\n ])([a-z0-9]+?){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i", '\1<a target="_blank" href="\2://\3">\3</a>', $content);
	$content = preg_replace_callback("#([\t\r\n ])([a-z0-9]+?){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i",
			function($matches) {
			
				$baseUrl = Application_Plugin_Common::getFullBaseUrl();
				$matched_url = $matches[2].'://'.$matches[3];
				$new_windown = '';
				
				// open in new window if the target is outsite this domain
				if (strpos($matched_url, $baseUrl) === false) {
					$new_windown = 'target="_blank"';
				}
				
		 		return $matches[1].'<a '.$new_windown.' href="'.$matched_url.'">'.$matched_url.'</a>';
	 	
			},
			$content);
	
		
	//simple: $content = preg_replace("#([\t\r\n ])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i", '\1<a target="_blank" href="http://\2.\3">\2.\3</a>', $content);
	$content = preg_replace_callback("#([\t\r\n ])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i",
			function($matches) {
					
				$baseUrl = Application_Plugin_Common::getFullBaseUrl();
				$matched_url = 'http://'.$matches[2].'.'.$matches[3];
				$new_windown = '';
		
				// open in new window if the target is outsite this domain
				if (strpos($matched_url, $baseUrl) === false) {
					$new_windown = 'target="_blank"';
				}
		
				return $matches[1].'<a '.$new_windown.' href="'.$matched_url.'">'.$matched_url.'</a>';
				
			},
			$content);
	
	$content = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a target=\"_blank\" href=\"mailto:\\2@\\3\">\\2@\\3</a>", $content);
	$content = substr($content, 1);
});