--
-- Install Database
-- ================
--
-- Creates tables and foreign keys for the database.
--
-- Ryff API <http://www.github.com/rfotino/ryff-api>
-- Released under the Apache License 2.0.
--


--
-- Table Structures
-- ----------------
--

--
-- Table structure for table `apns_tokens`
--
CREATE TABLE IF NOT EXISTS `apns_tokens` (
  `apns_token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `device_token` char(64) NOT NULL,
  `device_uuid` char(36) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`apns_token_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY (`user_id`, `device_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `auth_tokens`
--
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `comments`
--
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `conversation_members`
--
CREATE TABLE IF NOT EXISTS `conversation_members` (
  `conversation_member_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_read` timestamp DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`conversation_member_id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY (`conversation_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `conversations`
--
CREATE TABLE IF NOT EXISTS `conversations` (
  `conversation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` timestamp DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `follows`
--
CREATE TABLE IF NOT EXISTS `follows` (
  `follow_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `to_id` int(10) unsigned NOT NULL,
  `from_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follow_id`),
  KEY `to_id` (`to_id`),
  KEY `from_id` (`from_id`),
  UNIQUE KEY (`to_id`, `from_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `locations`
--
CREATE TABLE IF NOT EXISTS `locations` (
  `location_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `location` point NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `messages`
--
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `sent` int(1) NOT NULL DEFAULT 0,
  `date_sent` timestamp NULL DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `notification_objects`
--
CREATE TABLE IF NOT EXISTS `notification_objects` (
  `notification_object_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned NOT NULL,
  `post_obj_id` int(10) unsigned DEFAULT NULL,
  `user_obj_id` int(10) unsigned DEFAULT NULL,
  `sent` int(1) NOT NULL DEFAULT 0,
  `date_sent` timestamp NULL DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_object_id`),
  KEY `notification_id` (`notification_id`),
  KEY `post_obj_id` (`post_obj_id`),
  KEY `user_obj_id` (`user_obj_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `notifications`
--
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `post_obj_id` int(10) unsigned DEFAULT NULL,
  `user_obj_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(32) NOT NULL,
  `read` int(1) NOT NULL DEFAULT 0,
  `date_read` timestamp NULL DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` timestamp DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `post_obj_id` (`post_obj_id`),
  KEY `user_obj_id` (`user_obj_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `post_families`
--
CREATE TABLE IF NOT EXISTS `post_families` (
  `post_family_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_family_id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`),
  UNIQUE KEY (`parent_id`, `child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `post_tags`
--
CREATE TABLE IF NOT EXISTS `post_tags` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `tag` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag_id`),
  KEY `post_id` (`post_id`),
  UNIQUE KEY (`post_id`, `tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `posts`
--
CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `duration` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `stars`
--
CREATE TABLE IF NOT EXISTS `stars` (
  `star_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`star_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY (`post_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `upvotes`
--
CREATE TABLE IF NOT EXISTS `upvotes` (
  `upvote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`upvote_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY (`post_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `user_tags`
--
CREATE TABLE IF NOT EXISTS `user_tags` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `tag` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY (`user_id`, `tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY (`username`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


--
-- Constraints
-- -----------
--

--
-- Constraints for table `apns_tokens`
--
ALTER TABLE `apns_tokens`
  ADD CONSTRAINT `apns_tokens_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_post_id_constr`
    FOREIGN KEY (`post_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `conversation_members`
--
ALTER TABLE `conversation_members`
  ADD CONSTRAINT `conversation_members_conversation_id_constr`
    FOREIGN KEY (`conversation_id`)
    REFERENCES `conversations` (`conversation_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `conversation_members_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_from_id_constr`
    FOREIGN KEY (`from_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `follows_to_id_constr`
    FOREIGN KEY (`to_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_conversation_id_constr`
    FOREIGN KEY (`conversation_id`)
    REFERENCES `conversations` (`conversation_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_objects`
--
ALTER TABLE `notification_objects`
  ADD CONSTRAINT `notification_id_constr`
    FOREIGN KEY (`notification_id`)
    REFERENCES `notifications` (`notification_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notification_objects_post_obj_id_constr`
    FOREIGN KEY (`post_obj_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notification_objects_user_obj_id_constr`
    FOREIGN KEY (`user_obj_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_post_obj_id_constr`
    FOREIGN KEY (`post_obj_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_user_obj_id_constr`
    FOREIGN KEY (`user_obj_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_families`
--
ALTER TABLE `post_families`
  ADD CONSTRAINT `post_families_parent_id_constr`
    FOREIGN KEY (`parent_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `post_families_child_id_constr`
    FOREIGN KEY (`child_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `tags_post_id_constr`
    FOREIGN KEY (`post_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stars`
--
ALTER TABLE `stars`
  ADD CONSTRAINT `stars_post_id_constr`
    FOREIGN KEY (`post_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stars_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `upvotes`
--
ALTER TABLE `upvotes`
  ADD CONSTRAINT `upvotes_post_id_constr`
    FOREIGN KEY (`post_id`)
    REFERENCES `posts` (`post_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `upvotes_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_tags`
--
ALTER TABLE `user_tags`
  ADD CONSTRAINT `tags_user_id_constr`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
