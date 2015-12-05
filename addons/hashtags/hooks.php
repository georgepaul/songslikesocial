<?php
/**
 * Hashtags / usertags add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */


$this->attach('hook_data_renderoutput', 10, function(&$data) {

	$content = &$data['content'];
	
	$base_url = Application_Plugin_Common::getFullBaseUrl();
	$search_url = $base_url . '/search/posts/?term=%23';

	
	// hashtags
	$content = preg_replace("/(^|[\t\r\n\s])#([\p{L}\p{N}\-_]+)/u", " <a href='{$search_url}$2'>#$2</a> ", $content);
	
	// usertags
	$content = preg_replace("/(^|[\t\r\n\s])@(\w+)/u", " <a href='{$base_url}/$2'>@$2</a>", $content);
	
});


// push post mention notifications
$this->attach('hook_data_aftersavepost', 10, function($data) {
	
	$post_id = $data['post_id'];
	
	$Posts = new Application_Model_Posts();
	$post = $Posts->getPost($post_id);
	
	$content = $data['content']['content'];
	$users = preg_replace_callback("/(^|[\t\r\n\s])@(\w+)/u", 
		function($match) use ($post_id) {
	
			$Profiles = new Application_Model_Profiles();
			$profile = $Profiles->getProfile($match[2]);
		
			if ($profile && $profile->type == 'user') {
				$Notifications = new Application_Model_Notifications();
				$Notifications->pushNotification(array($profile->id), 1001, 'post', $post_id);
			}
		},
		$content);
});


// push comment mention notifications
$this->attach('hook_data_aftersavecomment', 10, function($data) {
	
	$comment_id = $data['comment_id'];
	
	$Comments = new Application_Model_Comments();
	$comment = $Comments->getComment($comment_id);
	
	$content = $data['content'];
	$users = preg_replace_callback("/(^|[\t\r\n\s])@(\w+)/u", 
		function($match) use ($comment_id) {
		
			$Profiles = new Application_Model_Profiles();
			$profile = $Profiles->getProfile($match[2]);
		
			if ($profile && $profile->type == 'user') {
				$Notifications = new Application_Model_Notifications();
				$Notifications->pushNotification(array($profile->id), 1001, 'comment', $comment_id);
			}
		}
		, $content);
});
	
	
// notifications
$this->attach('hook_data_notificationsfix', 10, function(&$data) {
	
		$baseURL = Application_Plugin_Common::getFullBaseUrl();
		$transl = Zend_Registry::get('Zend_Translate');
	
		foreach ($data as &$row) {
	
			// user mentioned inside post/comment
			if ($row['notification_type'] == 1001) {
	
				$row['do_send_email'] = false;
					
				if ($row['commented_post_id']) {
					
					$row['html_link'] = '<a href="' . $baseURL . '/profiles/showpost/name/' . $row['commented_post_on_wall'] . '/post/' . $row['commented_post_id'] . '">';
					
					$row['view_from_name'] = $row['comment_author_name'];
					$row['view_from_screen_name'] = $row['comment_author_screen_name'];
					$row['view_from_avatar'] = $row['comment_author_avatar'];
					
					$row['html_link'] .= sprintf($transl->translate('%s has mentioned you in a comment'), $row['comment_author_screen_name']);
					
				} elseif ($row['post_id']) {
					
					$row['html_link'] = '<a href="' . $baseURL . '/profiles/showpost/name/' . $row['post_author_name'] . '/post/' . $row['post_id'] . '">';
					
					$row['view_from_name'] = $row['post_author_name'];
					$row['view_from_screen_name'] = $row['post_author_screen_name'];
					$row['view_from_avatar'] = $row['post_author_avatar'];
					
					$row['html_link'] .= sprintf($transl->translate('%s has mentioned you in a post'), $row['post_author_screen_name']);
							
				} else {
					$row['html_link'] = $transl->translate('Resource not available');
					$row['view_from_avatar'] = '/default/generic.jpg';
					break;
				}
					
				
				$row['html_link'] .= '</a>';
					

	
			}
		}
});
