SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


DROP TABLE IF EXISTS `addons`;
CREATE TABLE IF NOT EXISTS `addons` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `addon_name` varchar(50) NOT NULL,
  `meta_key` varchar(50) NOT NULL,
  `meta_value` text NOT NULL,
  `resource_type` varchar(10) DEFAULT NULL,
  `resource_id` int(10) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_type` (`resource_type`),
  KEY `resource_id` (`resource_id`),
  KEY `plugin` (`addon_name`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `albums`;
CREATE TABLE IF NOT EXISTS `albums` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `user_id` int(10) NOT NULL,
  `cover_image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `author_id` int(10) NOT NULL,
  `resource_type` varchar(10) NOT NULL,
  `resource_id` int(10) NOT NULL,
  `created_on` datetime NOT NULL,
  `content` text NOT NULL,
  `is_hidden` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_type` (`resource_type`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`author_id`),
  KEY `is_spam` (`is_hidden`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `connections`;
CREATE TABLE IF NOT EXISTS `connections` (
  `user_id` int(10) NOT NULL,
  `follow_id` int(10) NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`follow_id`),
  UNIQUE KEY `user_id_follow_id` (`user_id`,`follow_id`),
  KEY `user_id` (`user_id`),
  KEY `follow_user_id` (`follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `file_name` varchar(50) NOT NULL,
  `original` varchar(50) NOT NULL,
  `uploaded_by` int(10) NOT NULL,
  `owner_id` int(10) NOT NULL,
  `post_id` int(10) NOT NULL,
  `album_id` int(10) NOT NULL,
  `size` int(10) NOT NULL,
  `created_on` datetime NOT NULL,
  `is_hidden` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `album_id` (`album_id`),
  KEY `user_id` (`owner_id`),
  KEY `post_id` (`post_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `uid` (`uid`),
  KEY `is_hidden` (`is_hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `resource_type` varchar(10) NOT NULL,
  `resource_id` int(10) NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_resource_type_resource_id` (`user_id`,`resource_type`,`resource_id`),
  KEY `user_id` (`user_id`),
  KEY `resource_type` (`resource_type`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL,
  `from_user_id` int(10) NOT NULL,
  `to_user_id` int(10) NOT NULL,
  `content` text NOT NULL,
  `is_new` int(1) NOT NULL DEFAULT '0',
  `sent_on` datetime NOT NULL,
  `is_hidden` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `from` (`from_user_id`),
  KEY `to` (`to_user_id`),
  KEY `status` (`is_new`),
  KEY `is_hidden` (`is_hidden`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` int(10) NOT NULL,
  `to_user` int(10) NOT NULL,
  `resource_type` varchar(10) NOT NULL,
  `resource_id` int(10) NOT NULL,
  `is_new` int(1) NOT NULL,
  `email_sent` int(1) NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`to_user`),
  KEY `is_read` (`is_new`),
  KEY `is_sent` (`email_sent`),
  KEY `resource_id` (`resource_id`),
  KEY `type` (`type`),
  KEY `resource_type` (`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `options`;
CREATE TABLE IF NOT EXISTS `options` (
  `meta_key` varchar(50) NOT NULL,
  `meta_value` text NOT NULL,
  PRIMARY KEY (`meta_key`),
  KEY `key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `options` (`meta_key`, `meta_value`) VALUES
('allow_addons', '1'),
('allow_guests', '1'),
('auto_follow_users', ''),
('background', 'background.jpg'),
('background_color', 'FFFFFF'),
('background_noimage', '1'),
('background_repeat', '0'),
('background_scroll', '0'),
('background_stretch', '0'),
('common_head', ''),
('cover_ysize', '220'),
('css_custom', ''),
('css_theme', '/bootstrap/css/bootstrap.min.css'),
('default_language', 'en'),
('error_log_file', 'logs/error.log'),
('facebook_appid', ''),
('facebook_secret', ''),
('global_head', '<title>Social Network</title>\r\n'),
('heartbeatfreq', '15'),
('keep_original', '1'),
('license_code', ''),
('limit_comments', '3'),
('limit_posts', '8'),
('mail_adapter', 'mail'),
('mail_from', 'info@localhost'),
('mail_from_name', 'Social Network'),
('mail_host', ''),
('mail_login', ''),
('mail_password', ''),
('mail_port', ''),
('mail_security', ''),
('mail_username', ''),
('max_file_upload_size', '1048576'),
('max_images_per_post', '6'),
('middle_banner', ''),
('motd', ''),
('network_name', 'SocialStrap'),
('newuser_notify_email', ''),
('pagination_limit', '10'),
('profiles_head', '<title>Social Network | PROFILE_SCREEN_NAME</title>'),
('recaptcha_active', '0'),
('recaptcha_privatekey', ''),
('recaptcha_publickey', ''),
('report_notify_email', ''),
('resample_images', '1'),
('resample_maxheight', '740'),
('resample_maxwidth', '740'),
('schema_version', '1'),
('session_lifetime', '7889238'),
('sidebar_banner', ''),
('sidebar_max_users', '9'),
('top_banner', ''),
('username_maxchars', '20'),
('username_minchars', '4'),
('user_manage_groups', '1'),
('user_manage_pages', '1'),
('disable_groups_pages', '0'),
('max_post_length', '2000'),
('wide_layout', '0');

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `author_id` int(10) NOT NULL,
  `wall_id` int(10) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `privacy` varchar(50) NOT NULL,
  `created_on` datetime NOT NULL,
  `is_hidden` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`author_id`),
  KEY `is_spam` (`is_hidden`),
  KEY `wall_id` (`wall_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `post_meta`;
CREATE TABLE IF NOT EXISTS `post_meta` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `post_id` int(10) NOT NULL,
  `meta_key` varchar(50) NOT NULL,
  `meta_value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`post_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE IF NOT EXISTS `profiles` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `screen_name` varchar(50) NOT NULL,
  `owner` int(10) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `activationkey` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) NOT NULL,
  `cover` varchar(255) NOT NULL,
  `relogin_request` int(1) NOT NULL DEFAULT '0',
  `default_privacy` varchar(50) DEFAULT NULL,
  `profile_privacy` varchar(50) NOT NULL,
  `is_hidden` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`name`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `activationkey` (`activationkey`),
  KEY `type` (`type`),
  KEY `owner` (`owner`),
  KEY `is_hidden` (`is_hidden`),
  KEY `screen_name` (`screen_name`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `profiles` (`id`, `type`, `name`, `screen_name`, `owner`, `password`, `language`, `role`, `email`, `activationkey`, `avatar`, `cover`, `relogin_request`, `default_privacy`, `profile_privacy`, `is_hidden`) VALUES
(1, 'user', 'admin', 'admin', 0, '$2a$08$HNDylOI4WEBcumPnk2KxBOJFOh8LtMSeJFfejomJPH86ibNkJ3bWC', 'en', 'admin', 'admin@localhost', 'activated', 'default/generic.jpg', 'default/1.jpg', 0, 'public', 'public', 0);

DROP TABLE IF EXISTS `profile_meta`;
CREATE TABLE IF NOT EXISTS `profile_meta` (
  `profile_id` int(10) NOT NULL,
  `meta_key` varchar(50) NOT NULL,
  `meta_value` text NOT NULL,
  UNIQUE KEY `profile_id_meta_key` (`profile_id`,`meta_key`),
  KEY `profile_id` (`profile_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `profile_meta` (`profile_id`, `meta_key`, `meta_value`) VALUES
(1, 'badges', ''),
(1, 'bulk_notifications', '{"notification_email_1":"0","notification_email_2":"0","notification_email_3":"0","notification_email_4":"0","notification_email_6":"0","notification_email_7":"0","notification_email_8":"0"}'),
(1, 'description', ''),
(1, 'gender', 'void'),
(1, 'last_heartbeat', '0'),
(1, 'last_login', '2013-01-01 00:00:00'),
(1, 'location', ''),
(1, 'show_online_status', 's'),
(1, 'website', '');

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `resource_type` varchar(50) NOT NULL,
  `resource_id` int(10) NOT NULL,
  `reason` text NOT NULL,
  `created_on` datetime NOT NULL,
  `reviewed_by` int(10) NOT NULL,
  `is_accepted` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_type` (`resource_type`),
  KEY `resource_id` (`resource_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `is_accepted` (`is_accepted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(50) NOT NULL,
  `modified` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `visits`;
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resource_type` varchar(10) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `address` varchar(50) NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `resource_type` (`resource_type`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
