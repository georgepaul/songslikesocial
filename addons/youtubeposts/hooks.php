<?php
/**
 * Youtube posts add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */

$this->attach('view_head', 10, function($view) {

	echo '
	<script type="text/javascript">
			
		// postsLoaded event subscribe
		$(document).on("postsLoaded", playYoutubeVideo);
		
		// postsLoaded event handler
		function playYoutubeVideo(e) {
			
			/**
			 * Play video
			 */
			$(".play a").unbind().click(function(e){
				
				var play_url = $(this).attr("data-play-url");	
				$(this).html(play_url);
			});

		}

	</script>
	';

});


$this->attach('hook_data_presavepost', 10, function(&$post) {

	$content = $post['content'];

	$content = preg_replace_callback('~
        # Match non-linked youtube URL in the wild. (Rev:20130823)
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube         # or youtube.com or
          (?:-nocookie)?  # youtube-nocookie.com
          \.com           # followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\s-]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w.-]*    # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w.-]*        # Consume any URL (query) remainder.
        ~ix',
        function($matches) use (&$post) {
        	
        	// accept only first video
        	if (isset($post['meta']['rich_content'])) {
        		return;
        	}

        	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        	$video_url = $protocol.'www.youtube.com/watch?v='.$matches[1];
        	$oembedURL = $protocol.'www.youtube.com/oembed?url=' . urlencode($video_url) . '&format=json';
	
			if ($protocol == 'https://') {
				$oembedURL .= '&scheme=https';
			}
		
			$client = new Zend_Http_Client($oembedURL, array('timeout' => 5));
		
			try {
				$response = $client->request();
		
				if ($response->isSuccessful()){
					// return html with iframe
					$ret = $response->getBody();
		
					$rich_content = array(
							'type' => 'youtube',
							'data' => $ret,
					);
		
					// update meta
					$post['meta'] = array('rich_content' => json_encode($rich_content));
					return;
				}
			}
			catch (Zend_Http_Client_Adapter_Exception $e) {
				Application_Plugin_Common::log(array($e->getMessage()));
			}
        	 
        },
        
        $content);
	
});



$this->attach('hook_data_postcontent', 10, function(&$post) {

	// fix rich data
	if (isset($post['rich_content_json'])){

		$rich_content = json_decode($post['rich_content_json']);

		if ($rich_content->type == 'youtube' && !empty($rich_content->data)) {

			$youtube_data = json_decode($rich_content->data);
			
			// add autoplay to src
			$youtube_data->html = preg_replace('#\<iframe(.*?)\ssrc\=\"(.*?)\"(.*?)\>#i',
					'<iframe$1 src="$2&autoplay=1"$3>',  $youtube_data->html);

			$play_url = htmlentities((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? str_replace("http://","https://",$youtube_data->html) : $youtube_data->html);

			$youtube_html = '
			<div class="youtube-video-box">
			<div>
			<h4>'.$youtube_data->provider_name .' - '. $youtube_data->title.'</h4>
			</div>
			
			<div class="play">
			<a data-play-url="'.$play_url.'">
				<i></i>
				<img class="img-responsive" src="'.$youtube_data->thumbnail_url.'">
			</a>
			</div>
			</div>
			';
			
			// remove new lines from template since it's gonna be converted with nl2br() later on
			$youtube_html = trim(preg_replace('/\s+/', ' ', $youtube_html));

			if (strpos($post['post_content'], 'youtube-video-box') === false) {
				$post['post_content'] = $post['post_content'] . $youtube_html;
			}

		}
	}

});