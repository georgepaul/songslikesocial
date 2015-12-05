<?php
/**
 * Power lobby add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

if (! defined('APPLICATION_PATH')) die('not allowed');

$controller = $request->getControllerName();
$action = $request->getActionName();

// show only on home page
if ($controller !== 'index' || $action != 'index') return;

$this->attach('hook_view_sidebar', 20, function($view) { 

	require_once 'lobby_class.php';
	require_once 'lobby_model.php';
	
	$Lobby = new LobbyClass();

	$Lobby->getFriendSuggestions();
	$Lobby->getOnlineUsers();
	$Lobby->getPopularUsers();
	$Lobby->getPopularGroups();
	$Lobby->getPopularPages();

});

