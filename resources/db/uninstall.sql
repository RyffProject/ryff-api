--
-- Uninstall Database
-- ==================
--
-- Drops all tables. Order matters, because we can't delete
-- a table that other tables rely on via foreign key.
--
-- Ryff API <http://www.github.com/rfotino/ryff-api>
-- Released under the Apache License 2.0.
--

--
-- `conversation_members` and `messages` rely on `conversations`
--
DROP TABLE IF EXISTS `conversation_members`;
DROP TABLE IF EXISTS `messages`;

--
-- Now we can drop `conversations` because there are no more
-- foreign key constraints.
--
DROP TABLE IF EXISTS `conversations`;

--
-- `notification_objects` relies on `notifications`
--
DROP TABLE IF EXISTS `notification_objects`;

--
-- Now we can drop `notifications` because there are no more
-- foreign key constraints.
--
DROP TABLE IF EXISTS `notifications`;

--
-- `post_families`, `post_tags`, `stars`, `upvotes`,
-- and `riffs` rely on `posts`
--
DROP TABLE IF EXISTS `post_families`;
DROP TABLE IF EXISTS `post_tags`;
DROP TABLE IF EXISTS `riffs`;
DROP TABLE IF EXISTS `stars`;
DROP TABLE IF EXISTS `upvotes`;

--
-- Now we can drop `posts` because there are no more foreign
-- key constraints
--
DROP TABLE IF EXISTS `posts`;

--
-- `follows`, `locations`, `apns_tokens`, `auth_tokens`,
-- `user_tags`, `messages`, `conversation_members`,
-- `notification_objects`, `notifications`, `stars`,
-- `upvotes`, and `posts` rely on `users`
--
DROP TABLE IF EXISTS `follows`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `apns_tokens`;
DROP TABLE IF EXISTS `auth_tokens`;
DROP TABLE IF EXISTS `user_tags`;

--
-- Now we can drop `users` because there are no more foreign
-- key constraints.
--
DROP TABLE IF EXISTS `users`;
