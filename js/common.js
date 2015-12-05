/**
 * Common JavaScript
 *
 * @package SocialStrap
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

var enable_posts_load = true;
var timer = null;
var likers_box = '';
var notificationtimer;
var waiting_for_response;
var message_offset = 0;

function startWaiting(){
	waiting_for_response = true;
  	$('html').addClass('busy');
  	return;
}

function stopWaiting(){
	waiting_for_response = false;
	$('html').removeClass('busy');
	return;
}


function postsScroll() {
	$(window).scroll(
		function() {
			if (!enable_posts_load)
				return false;
			if ($(window).scrollTop() >= $(document).height() - $(window).height() - 300) {
				enable_posts_load = false;
				loadPosts();
			}
		});
}


function chatScroll() {
	var height = ($(window).height())-280;
	
	if (height < 200) height = 200;
	
	if ($(".message-box-inner").length == 0) return;
	
	$('.message-box-inner').css({'height':(height)+'px'});
	$('.message-box-inner').scrollTop($(".message-box-inner")[0].scrollHeight);
	$('.add-message-from #content').focus();
	
	// iOS FIX
	var iOS_hack = document.createElement("div");
	iOS_hack.style.height = "101%";
	document.body.appendChild(iOS_hack);
	setTimeout(function(){
	    document.body.removeChild(iOS_hack);
	    iOS_hack = null;
	}, 0);
}


function chatRefresh(submit) {

	var chat_form = $('.add-message-from');
	
	// init message offset
	if (message_offset == 0) {
		message_offset = $(chat_form).closest('.message-submit').attr('data-offset');
	}
	
	var to_user = $(chat_form).closest('.message-submit').attr('data-user');
	var url = $(chat_form).attr('action') + '/to/'+to_user+'/offset/'+message_offset;
	var data = {};
	
	if (waiting_for_response == true) return false;
	
	if (submit) {
		data = $(chat_form).serialize();
		// clear form
		$(chat_form).find("input[type=text], textarea").val("");
		startWaiting();
	}
	
	$.post(url, data, function(response) {
		if (response['errors'] == ''){
			$(chat_form).closest('.message-box').find('.message-box-inner').append(response['html']);
			attachEvents();
		} else {
			$(chat_form).closest('.message-box-inner').append('errors: ' + response['errors']);
		}
		
		// update offset
		if (response['offset'] != ''){
			message_offset = response['offset'];
			chatScroll();
		}
		
		if (submit) {
			stopWaiting();
		}
		
	}, 'json');
}

function loadPosts() {
	
	var data = {};
	data['post_page_number'] = post_page_number;
	
	if (waiting_for_response == true) return;
  	startWaiting();
  	
	$.post(php_loadPostURL, data, function(response) {

		if (response['posts']) {
			// set the last page counter
			post_page_number = response['post_page_number']; 
			// remove old "more button"
			$(".load-more-btn").remove();
			// append new posts
			$("#posts-wrapper").append(response['posts']);
		}
		
		// check if there are more posts
		if (response['stop'] == true) {
			enable_posts_load = false;
			$(".load-more-btn").remove();
		}else{
			enable_posts_load = true;
		}
		
		attachEvents();
		stopWaiting();
		
	}, 'json');
	
	return;
}

function doHeartbeat() {
	
	url = php_baseURL + '/notifications/heartbeat/';
	var data = {};
	
	$.post(url, data, function(response) {
		// notifications?
		notifications['count'] = response['notification_count'];
		notifications['html'] = response['notification_html'];
		$('.notifications .label').html(notifications['count']);
		if (notifications['count'] > 0){
			$('.notifications .label').addClass('label-danger');
			$('.notifications .label').removeClass('label-default');
		}else{
			$('.notifications .label').removeClass('label-danger');
			$('.notifications .label').addClass('label-default');
		}
		
		// new messages?
		notifications['new_messages'] = response['new_messages'];
		$('.new-messages-count').html(notifications['new_messages']);
		if (notifications['new_messages'] > 0){
			$('.new-messages-count').addClass('label-danger');
			$('.new-messages-count').removeClass('label-default');
			$('.inbox-count').html(notifications['new_messages']);
		}else{
			$('.new-messages-count').removeClass('label-danger');
			$('.new-messages-count').addClass('label-default');
			$('.inbox-count').html('');
		}
		
		/**
		 * Addons can subscribe here
		 */
		$.event.trigger({
			type: "postHeartbeat"
		}, response);
		

	}, 'json');
	
	return;
}

function heartbeatLoop() {
	
	if (php_heartbeatFreq > 0){
		setTimeout(function(){
			doHeartbeat();
			heartbeatLoop();
	    }, php_heartbeatFreq);
	}
	
}

function chatLoop() {
	
	setTimeout(function(){
		chatRefresh(false);
		chatLoop();
	}, 5000);
	
}

function doValidation(form, field, submit) {
	
	if (php_disableAjaxValidator > 0) {
		if (submit == true) {
			document.getElementById(form.attr('id')).submit();
		} 
		return;
	}
	
	var data = {};

	var url = php_baseURL + '/index/validateformajax/';
	var inputFields = $(form).find('input');
	var inputSelects = $(form).find('select');
	var fieldName = $(field).attr('name');
	var fieldType = $(field).attr('type');
	
	// do nothing on blur submit
	if (fieldType == 'submit' && submit == false){
		return;
	}

	$(inputFields).each(function() {
		data[$(this).attr('name')] = $(this).val();
		
		// checkbox fix
		if ($(this).attr('type') == 'checkbox' && $(this).prop('checked')){
			data[$(this).attr('name')] = 1;			
		}else if ($(this).attr('type') == 'checkbox'){
			data[$(this).attr('name')] = 0;
		}

	});
	
	$(inputSelects).each(function() {
		data[$(this).attr('name')] = $(this).val();
	});
	
	if (waiting_for_response == true) return;
  	startWaiting();
  	
	$.post(url, data, function(response) {
		
		stopWaiting();
		
		if (response['ajax_csrf'] != '') {
			$('#csrf').val(response['ajax_csrf']);
		}
		
		if (submit == true && response['errors'] == '') {
			// no errors, do submit! 
			// jquery would loop, use dom instead
			document.getElementById(form.attr('id')).submit();
		} else if (fieldType == 'submit'){
			// there are errors on submit
			$(inputFields).each(function() {
	
				var errors = getErrorHtml(response['errors'][$(this).attr('name')]);
				
				if (errors != ''){
					
					$(this).popover({
						offset: 10,
						placement : 'top',
						html: 'true',
						content : function(){
							return errors;
					 	}
					}).popover('show');
				}

			});
			
			$(inputSelects).each(function() {

				// transform multi-selects i.e. birthday[day] to birthday
				var s = $(this).attr('name');
				s = s.substring(0, s.indexOf('['));
				if (s == '') s = $(this).attr('name');
				
				var errors = getErrorHtml(response['errors'][s]);
				
				if (errors != ''){
	
					// find last select in the row and display errors there
					$(this).parent().find('select').last().popover({
						offset: 10,
						placement : 'top',
						html: 'true',
						content : function(){
							return errors;
						}
					}).popover('show');
				}
			});

		} else{
			
			var errors = getErrorHtml(response['errors'][fieldName]);

			if (errors != ''){
				$(field).popover({
					offset: 10,
					placement : 'top',
					html: 'true',
					content : function(){
						return errors;
					}
				}).popover('show');
			}

		}
	}, 'json');

}

function getErrorHtml(errors, id) {
	
	if (typeof(errors) === 'undefined' || errors == '') return '';
	
	var o = '<div class="popover-errors"><ul>';
	for (errorKey in errors) {
		o += '<li>' + errors[errorKey] + '</li>';
	}
	o += '</ul></div>';
	return o;
}

//transform errors to popovers
function transformErrors(selector){
	
	var errors = $(selector);

	$(errors).each(function() {
		
		var prev_el = $(this).prev();
		var content = [];
		
		$(this).find('li').each(function() {
			content.push($(this).html());
		    })
		
			$(prev_el).popover({
				  offset: 10,
			      placement : 'top',
			      html: 'true',
			      content : function(){
			    	     return getErrorHtml(content);
			      }
			}).popover('show');
	});
	
	return;

}

function attachEvents() {
	
	/**
	 * login requiered - show modal
	 */
	$(".login-requiered").unbind().click(function() {
		// close other modals so they don't overlap
		$('.modal').modal('hide');
		
		$('#login-modal').modal('show');	
	});	
	
	/**
	 * comments
	 */			
	$(".add-comment-btn").unbind().click(function() {
		var resource_type = $(this).attr('data-resource-type');
		var resource_id = $(this).attr('data-resource-id');
		var commentbox = $(".comments[data-resource-type='" + resource_type + "'][data-resource-id='" + resource_id + "']");
		$(commentbox).find('.add-comment').show().find('.add-comment-input input').focus();
	});		
				
	$(".show-hidden-comments").unbind().click(function() {
		$(this).hide();
		$(this).closest('.comments-box-wrap').find('.comments:not(.blank)').show();
	});	
	
	$(".add-comment-form").unbind().submit(function() {
		
		var url = $(this).attr('action');
		var data = $(this).serialize();
		var current_form = this;  

		if (waiting_for_response == true) return false;
	  	startWaiting();
	  	
		$.post(url, data, function(response) {
			if (response['errors'] == ''){
				$(current_form).closest('.comments-box-wrap').html(response['html']);
				attachEvents();
			}
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "postAddComment"
			}, response);
			
			stopWaiting();
		}, 'json');
		
		
		return false;
	});	
	
	// delete comment and confirmation
	$(".delete-comment-btn").unbind().click(function() {

		// close other modals so they don't overlap
		$('.modal').modal('hide');

		var btn = this; 
		var comments_box = $(this).closest('.comments-box-wrap');
		
		var url = php_baseURL + '/comments/delete/comment/';
		var data = {};
		
		data['comment_id'] = $(this).attr('data-comment-id');
		data['resource_id'] = $(comments_box).find(".comments").attr('data-resource-id');
		data['resource_type'] = $(comments_box).find(".comments").attr('data-resource-type');
		
		$('#confirmation-modal').modal('show');	
		$('body').addClass('modal-open');
		$('#confirmation-modal .btn-confirm-action').unbind().click(function() {
			
			if (waiting_for_response == true) return;
			startWaiting();
			
			$.post(url, data, function(response) {
				$(comments_box).html(response['html']);
				$('#confirmation-modal').modal('hide');

				attachEvents();
				
				/**
				 * Addons can subscribe here
				 */
				$.event.trigger({
					type: "postDeleteComment"
				}, response);
				
				stopWaiting();
			}, 'json');
			
		});
		
		return;
	});	
	
	
	// delete posts and confirmation
	$(".delete-post-btn").unbind().click(function() {
		var delete_btn = this;
		var post_id = $(this).attr('data-post-id');
		
		$('#confirmation-modal').modal('show');		
		$('#confirmation-modal .btn-confirm-action').unbind().click(function() {
				$('#confirmation-modal').modal('hide');
				
				var url = php_baseURL + '/posts/delete/';
				var data = {};
				data['post_id'] = post_id;
				
				if (waiting_for_response == true) return;
			  	startWaiting();
			  	
				$.post(url, data, function(response) {
					if (response == 1){
						$(delete_btn).closest('#post-'+post_id).slideUp(function(){$(this).remove()});
					}
					
					/**
					 * Addons can subscribe here
					 */
					$.event.trigger({
						type: "postDeletePost"
					}, response);
					
					stopWaiting();
				}, 'json');
		});
		
		return;
	});	
	
	
	// delete album and confirmation
	$(".delete-album-btn").unbind().click(function() {
		var delete_btn = this;
		var album_id = $(this).attr('data-album-id');
		
		$('#confirmation-modal').modal('show');		
		$('#confirmation-modal .btn-confirm-action').unbind().click(function() {
				$('#confirmation-modal').modal('hide');
				
				var url = php_baseURL + '/profiles/deletealbum/id/';
				var data = {};				
				data['album_id'] = album_id;
				
				if (waiting_for_response == true) return;
			  	startWaiting();
			  	
				$.post(url, data, function(response) {
					if (response == 1){
						$(delete_btn).closest(".well").slideUp(function(){$(this).remove()});
					}
					
					/**
					 * Addons can subscribe here
					 */
					$.event.trigger({
						type: "postDeleteAlbum"
					}, response);
					
					stopWaiting();
					
				}, 'json');
		});
		
		return;
	});	
	
	/**
	 * share button
	 */
	$(".share-btn").unbind().click(function() {
		var share_btn = this;
		var resource_id = $(this).attr('data-resource-id');
		var resource_type = $(this).attr('data-resource-type');
		var url = php_baseURL + '/posts/share/';
		var data = {};
		
		// close other modals so they don't overlap
		$('.modal').modal('hide');
		
		$('#share-modal').modal('show');
		
		data['resource_id'] = resource_id;
		data['resource_type'] = resource_type;
		
		if (waiting_for_response == true) return;
	  	startWaiting();
	  	
		$.post(url, data, function(response) {
			$('#share-modal').html(response);

			// select
			$('#share_link').focus().select();
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "shareboxLoaded"
			}, response);
			
			stopWaiting();
		}, 'json');
		
		return false;
	});
	
	
	/**
	 * likes
	 */
	$(".toggle-like-btn").unbind().click(function() {
		
		var toggle_button = this;
		var url = php_baseURL + '/likes/togglelike/';
		var data = {};
		
		data['resource_id'] = $(this).attr('data-resource-id');
		data['resource_type'] = $(this).attr('data-resource-type');
		
		if (waiting_for_response == true) return false;
	  	startWaiting();
	  	
		$.post(url, data, function(response) {
			$(toggle_button).find('span.likes-count').html(response['count']);
			$(toggle_button).find('span.likes-text').html(response['text']);
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "postLike"
			}, response);
			
			stopWaiting();
		}, 'json');
		
		return false;
	});	
	
	$(".show-likes-btn").hover(
			function () {
				
				var likebtn = this;
				
				if(timer) {
					clearTimeout(timer);
				}
				timer = setTimeout(function() {
					
					var url = php_baseURL + '/likes/getall/';
					var data = {};
					
					data['resource_id'] = $(likebtn).attr('data-resource-id');
					data['resource_type'] = $(likebtn).attr('data-resource-type');
				
					$.post(url, data, function(response) {
						
						likers_box = '';
						
						if (response != ''){
							likers_box = '<ul>';
							$.each(response, function(i, val) {
								likers_box += '<li><a href="'+php_baseURL+'/'+val['author_name']+'">' + val['author_screen_name'] + '</a></li>';
							});
	
							likers_box += '</ul>';
						}
						
						$(likebtn).popover({
							offset: 10,
								placement : 'top',
								html: 'true',
								content : function(){
									return likers_box;
							}
						}).popover('show');
						
						/**
						 * Addons can subscribe here
						 */
						$.event.trigger({
							type: "postShowLikes"
						}, response);
						
					}, 'json');
				}, 500);
				
				
				},
			function () {
					
					var likebtn = this;
					likers_box = '';
					
					if(timer) {
						clearTimeout(timer);
					}
				}
			);	
	
	
	/**
	 * reports
	 * 
	 */
	$('.report-btn').popover({'placement':'bottom', 'html':true, 'toggle':'tooltip', 'content': function(){
		
		var report_popover = $(this);
		var url = php_baseURL + '/reports/report';
		
		var data = {};
		data['form_render'] = true;
		data['resource_id'] = $(this).attr('data-resource-id');
		data['resource_type'] = $(this).attr('data-resource-type');
		
		if (waiting_for_response == true) return;
		startWaiting();
		
		// get form content from server 
		$.post(url, data, function(response) {
			$(report_popover).siblings('.popover').find('.popover-content').html(response);
			stopWaiting();
		}, 'json');
		
		return;
	
		}}).parent().unbind().delegate('form', 'submit', function() {

		var report_box = $(this).closest('.popover');
		var report_button = $(report_box).siblings('.report-btn');
		
		var url = $(this).attr('action');
		var data = $(this).serialize();
		
		if (waiting_for_response == true) return;
	  	startWaiting();
	  	
		$.post(url, data, function(response) {
			// if no errors
			if (response == ''){
				$(report_box).fadeOut();
				$(report_button).fadeOut();
			}
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "postReport"
			}, response);
			
			stopWaiting();
		}, 'json');
		
		return false;
	    
	});
	

	/**
	 * lightbox: open
	 */
	$(".thumbnail").unbind().click(function(){

		// delete old data
		$('#lightbox-modal .modal-title').html('');
		$('#lightbox-modal .modal-body').html('');
		$('#lightbox-modal .modal-footer').html('');
		
		var resource_id = $(this).attr('data-resource-id');
		var context = $(this).attr('data-context');
		
		loadLightboxImage(resource_id, context);

	});
	// lightbox: prev/next with clicks
	$(".getimage").unbind().click(function(){

		var resource_id = $(this).attr('data-resource-id');
		var context = $(this).attr('data-context');
		
		loadLightboxImage(resource_id, context);
		
		return true;
	  	
	});
	// lightbox: prev/next with cursor keys
	$("body").unbind().keydown(function(e){

		var isOpen = $(this).hasClass('modal-open');
		if (!isOpen) return true;

		// left arrow
		if (e.keyCode == 37) { 
			var resource_id = $("#lightbox-modal .prev").attr('data-resource-id');
			var context = $("#lightbox-modal .next").attr('data-context');
		}
		
		// right arrow
		if (e.keyCode == 39) { 
			var resource_id = $("#lightbox-modal .next").attr('data-resource-id');
			var context = $("#lightbox-modal .next").attr('data-context');
		}
		
		if (resource_id > 0){
			loadLightboxImage(resource_id, context);
		}
		
		return true;
		
	});
	// lightbox: move image to album or trash
	$("#lightbox-modal .image-options-list a").unbind().click(function(e){
		
		var url = php_baseURL + '/images/moveimage';
		var album_id = $(this).attr('data-album-id');
		var album_name = $(this).html();
		var resource_id = $(this).attr('data-image-id');
		var data = {};
		
		data['resource_id'] = resource_id;
		data['album_id'] = album_id;
		
		if (waiting_for_response == true) return;
		startWaiting();
		
		$.post(url, data, function(response){
			if (album_id == 'trash') {
				$(".lightbox-full-image img").fadeTo('fast', 0.5);
			}else if (album_id == 'cover' || album_id == 'avatar') {
				// jump to edit image
				var reload_url = php_baseURL + '/images/edit';
				window.location.replace(reload_url);
				return;
			}
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "postMoveImage"
			}, response);
			
			stopWaiting();
		}, 'json');
	});
	// lightbox: rotate image
	$("#lightbox-modal .rotate-btn").unbind().click(function() {
		
		var resource_id = $(this).attr('data-resource-id');
		var url = php_baseURL + '/images/rotateimage/';
		var data = {};
		
		data['resource_id'] = resource_id;
		
		if (waiting_for_response == true) return;
	  	startWaiting();
	  	
		$.post(url, data, function(response) {
			// refresh
			if (response != false){
				$(".lightbox-full-image img").attr("src", php_postsstorageURL + response);
			}
			
			/**
			 * Addons can subscribe here
			 */
			$.event.trigger({
				type: "postRotateImage"
			}, response);
			
			stopWaiting();
		}, 'json');
		
		return false;
	});

	
	/**
	 * modal edits (posts/comments)
	 */
	$(".modal-editor").click(function() {
		
		var edit_url = $(this).attr('data-link');
		var content = $(this).closest('.media-body').find('.content:first');
		var data = {};
		var modal_content = $("#editor-modal .modal-body");
		
		// backward compatibility for <= v2.1
		if (typeof(edit_url) === 'undefined') {
			edit_url = $(this).attr('href');
		}
		
		// clear previous
		$(modal_content).html('');
		
		data['form_render'] = true;
		
		if (waiting_for_response == true) return false;
		startWaiting();
	
		$.post(edit_url, data, function(response) {
			
			$(modal_content).html(response);
			
			// take over form submit action
			$(modal_content).unbind().delegate('form', 'submit', function() {

				var url = $(this).attr('action');
				var data = $(this).serialize();
			  	
				$.post(url, data, function(response) {
					$(content).html(response);
					
					/**
					 * Addons can subscribe here
					 */
					$.event.trigger({
						type: "modalEditorEnd"
					}, response);
					
				}, 'json');
				
				$("#editor-modal").modal('hide');
				return false;
			});
			
			stopWaiting();
		}, 'json');
		
		
		$('#editor-modal').modal('show');
		
		return false;
	});
	
	/**
	 * Load more posts button
	 */
	if (enable_posts_load){
		$(".load-more-btn").click(function() {
			$(this).hide();
			loadPosts();
			return false;
		});	
	}
	
	/**
	 * Addons can subscribe here
	 */
	$.event.trigger({
		type: "postsLoaded"
	});
	
}


function loadLightboxImage(resource_id, context){
	
	var url = php_baseURL + '/posts/getlightboxdata';
	var data = {};
	
  	// add context
  	data['resource_id'] = resource_id;
  	data['context'] = context;
  	
  	if (waiting_for_response == true) return;
  	startWaiting();
  	
	$.post(url, data, function(response) {
		$('#lightbox-modal').html(response);
		attachEvents();
		
		/**
		 * Addons can subscribe here
		 */
		$.event.trigger({
			type: "lightboximageLoaded"
		}, response);
		
		stopWaiting();
	}, 'json');
}



/**
 * 
 * ON DOCUMENT LOAD
 * 
 */
$(document).ready(function(){

		attachEvents();
		
		/**
		 * prevent double submit
		 */
		$('#AddPost').submit(function(e){
			$('#AddPost input[type="submit"]').attr('disabled',true);
		});
		
		/**
		 * autoload image modal (share)
		 */
		if (php_autoLoadImage != 0){
			
			$('#lightbox-modal').modal('show');	
			loadLightboxImage(php_autoLoadImage, 'single');
		}
		
		/**
		 *  disable dragging for images
		 */
		$('img').on('dragstart', function(event) { event.preventDefault(); });
		
		
		/**
		 * prevent non alpha numeric input on some fields
		 */
		$('.alnum-only').bind('keypress', function (event) {
		    var regex = new RegExp("^[a-zA-Z0-9]+$");
		    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
		    if (event.charCode > 0 && !regex.test(key)) {
		       event.preventDefault();
		       $(this).addClass('warning-box');
		       return false;
		    }
		    $(this).removeClass('warning-box');
		});
	
		
		/**
		 * errors and validation popups
		 */
		
		// transfor all erors on load
		transformErrors(".form-group .errors");

		// remove errors on click
		$("input").click(function() {
			$(this).siblings('.errors').remove();
		});			
		
		$(".validate-ajax input").blur(function(evt) {
			
			var current_el = this;
			setTimeout(function(){
				
				// this is the element that has focus now
				var next_active = document.activeElement; 

				// prevents flashing on submit
				if(!$(next_active).is('input[type="submit"]')){
					doValidation($(current_el).closest('form'), $(current_el), false);
				}
			    });

		});

		$(".validate-ajax").submit(function(e) {
			doValidation($(this), $(this).find('input[type="submit"]'), true);
			return false;
		});
		

		/**
		 * lost password up/down
		 */			
		$(".forgot-password").click(function() {
			$(this).closest('form').slideToggle().siblings('form').slideToggle();
		});	
		
		
		/**
		 * alerts
		 */	
		$(".alert.animation-on").each(
				function(i) {
					$(this).delay(100 * i).slideDown().delay(
							300 * (i + 1) + 3000).slideUp();
				});
		$(".alert.animation-off").each(function(i) {
			$(this).show();
		});
		
		
		/**
		 * close message of the day banner
		 */
		$(".close-motd button").click(function() {
			
			var privacy_btn = this;
			var url = php_baseURL + '/notifications/clearmotd';
			
			if (waiting_for_response == true) return;
		  	startWaiting();
		  	
			$.post(url, {}, function(response) {
				
				/**
				 * Addons can subscribe here
				 */
				$.event.trigger({
					type: "closeMOTD"
				}, response);
				
				stopWaiting();
			}, 'json');
			
			return true;
		});	
		
		
		/**
		 * loading posts on scroll
		 */
		if (php_loadPostURL){
			postsScroll();
		}else{
			$(".load-more-btn").remove();
		}
		
		
		/**
		 * notifications 
		 */
		if (php_heartbeatFreq > 0){
			doHeartbeat();
			heartbeatLoop();
		}
		$(".notifications").popover({
			offset: 10,
			trigger: 'click',
			placement : 'bottom',
			html: 'true',
			content : function(){
				return notifications['html'];
		 	}
		}).mouseenter(function(e) {
			
			if(notificationtimer) {
				 $(".notifications").popover('hide');
				clearTimeout(notificationtimer);	
			}
			notificationtimer = setTimeout(function() {
				 $(".notifications").popover('show');
			}, 500);

		    $(".notifications").siblings('.popover').mouseleave(function(e) {
			    $(".notifications").popover('hide');
			});
		    
		    if (notifications['count'] > 0) {
		    	$.post(php_baseURL + '/notifications/clearnotifications/');
		    }
		    
		}).mouseleave(function(e) {
			clearTimeout(notificationtimer);
		}).click(function(e) {
			clearTimeout(notificationtimer);
		});
		

		/**
		 * remove all popovers on outside click
		 */
		$('body').click(function (e) { 
			if ($('.popover, .popover-trigger').has(e.target).length === 0 && !$('.popover, .popover-trigger').is(e.target) && $(e.target).closest('.notifications').length === 0) {
					$('.popover').remove();
				}
			});
		
		/**
		 * reports admin
		 */
		$(".admin-reported-btn").click(function() {
			
			var reported_btn = this;
			var url = php_baseURL + '/reports/updatereported/';
			var data = {};
			
			data['report_id'] = $(this).attr('data-report-id');
			data['mark_reported'] = $(this).attr('data-mark-reported');

			if (waiting_for_response == true) return;
		  	startWaiting();
		  	
			$.post(url, data, function(response) {
				$(reported_btn).closest('.single-report').slideUp();
				stopWaiting();
			}, 'json');
			
			return true;
		});

		
		/**
		 * Post new chat message
		 */
		$(".add-message-from").unbind().submit(function() {
			
			chatRefresh(true);

			return false;
		});

		
		/**
		 * Start chat loop
		 */
		if (php_controller == 'messages' && php_action == 'inbox'){
			chatScroll();
			chatLoop();
		}
		
		
		/**
		 * Delete messages
		 */
		$(".delete-message-btn").click(function() {
			
			// close other modals so they don't overlap
			$('.modal').modal('hide');

			var message_box = $(this).closest('.message-single');
			var url = php_baseURL + '/messages/remove/';
			var user = $(this).attr('data-user');
			var data = {};
			
			if (typeof(user) != 'undefined') {
				data['user'] = user;
			} else {
				data['message_id'] = $(this).attr('data-message-id');
			}
			
			$('#confirmation-modal').modal('show');	
			$('body').addClass('modal-open');
			$('#confirmation-modal .btn-confirm-action').unbind().click(function() {
				
				if (waiting_for_response == true) return;
				startWaiting();
				
				$.post(url, data, function(response) {
					
					if (response == true){
						if (typeof(user) === 'undefined') {
							$(message_box).slideUp(function(){$(this).remove()});
						} else {
							$('.message-box .message-single').remove();
						}
					}
				
					$('#confirmation-modal').modal('hide');

					attachEvents();
					
					/**
					 * Addons can subscribe here
					 */
					$.event.trigger({
						type: "postDeleteMessage"
					}, response);
					
					stopWaiting();
				}, 'json');
				
			});
			
			return;

		});	
		
		
		/**
		 * change default privacy button
		 */
		$(".change-privacy ul li a").click(function() {
			
			var privacy_btn = this;
			var url = php_baseURL + '/editprofile/defaultprivacy/';
			var data = {};
			
			data['privacy'] = $(this).attr('data-privacy');
			
			if (waiting_for_response == true) return;
		  	startWaiting();
		  	
			$.post(url, data, function(response) {
				if (response == true){
					$(privacy_btn).closest('.change-privacy').find('button span.current-privacy-level').html($(privacy_btn).html());
				}
				
				/**
				 * Addons can subscribe here
				 */
				$.event.trigger({
					type: "postChangeDefaultPrivacy"
				}, response);
				
				stopWaiting();
			}, 'json');
			
			return true;
		});	
		
		
		/**
		 * common form confirmation modal
		 */ 
		$(".form-confirmation").unbind().click(function() {
			
			// close other modals so they don't overlap
			$('.modal').modal('hide');
			
			var cform = $(this).closest('form');

			$('#confirmation-modal').modal('show');
			$('#confirmation-modal .btn-confirm-action').unbind().click(function() {
				cform.submit();
			});
			
			return false;
		});	
		
		
		/** 
		 * async callback
		 */
		if (php_hasIdentity > 0){
			$.post(php_baseURL + '/notifications/callback/');
		}
		

	
});