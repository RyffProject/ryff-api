<?php
/**
 * Configuration
 * =============
 * 
 * Contains the configuration settings for this instance of the application.
 * The individual settings are marked as required or optional with a brief
 * description. Some have reasonable default values.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */


/**
 * General
 * -------
 */

/**
 * The outward facing url of the site. ex) http://localhost
 */
define("SITE_ROOT", "");

/**
 * The outward facing url of the media folder of the site. ex) http://localhost/media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("MEDIA_URL", "");

/**
 * The absolute path to the media folder. ex) /var/www/html/media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("MEDIA_ABSOLUTE_PATH", "");

/**
 * The amount of time it takes for auth tokens to expire, in seconds.
 */
define("COOKIE_LIFESPAN", 604800);

/**
 * The amount of time it takes before notifications will not stack, in seconds.
 */
define("NOTIFICATION_TIMEOUT", 7200);

/**
 * The timezone to use.
 */
date_default_timezone_set("America/Los_Angeles");


/**
 * Database Credentials
 * --------------------
 */

/**
 * The main database credentials. The database must be created manually and
 * then tables must be installed with the provided SQL script before use.
 */
define("DB_NAME", "");
define("DB_USER", "");
define("DB_HOST", "");
define("DB_PASS", "");


/**
 * Testing constants
 * -----------------
 */

/**
 * Use the test database and media.
 */
if (!defined("TEST_MODE")) {
    define("TEST_MODE", false);
}

/**
 * The outward facing url of the test media folder of the site.
 * ex) http://localhost/test_media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("TEST_MEDIA_URL", "");

/**
 * The absolute path to the test media folder. ex) /var/www/html/test_media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("TEST_MEDIA_ABSOLUTE_PATH", "");

/**
 * The test database credentials. Used for running the test suite or for
 * populating a database with test data. The database must be created manually
 * before use, but tables will be installed at runtime.
 */
define("TEST_DB_NAME", "");
define("TEST_DB_USER", "");
define("TEST_DB_HOST", "");
define("TEST_DB_PASS", "");


/**
 * Apple Push Notification service
 * -------------------------------
 */

/**
 * Path to the APNs SSL certificate.
 */
define("APNS_CERTIFICATE", "");

/**
 * Passphrase for the APNs private key.
 */
define("APNS_PASSPHRASE", "");

/**
 * The APNs gateway to connect to.
 */
define("APNS_GATEWAY", "");

/**
 * How long the script will wait to connect to the gateway.
 */
define("APNS_CONNECT_TIMEOUT", 60);

/**
 * How many notifications the cron script will push per run. This is not the
 * number of notifications actually sent to APNs, because each notification
 * could be sent to multiple devices for the user.
 */
define("PUSH_NOTIFICATION_LIMIT", 1000);

/**
 * How many messages the cron script will push per run. This is not the number
 * of notifications actually sent to APNs, because each message could be sent
 * to multiple users, and those users could each have multiple devices.
 * Alternatively, if everyone has already read the message, it will be sent
 * to no one but will still count toward the limit.
 */
define("PUSH_MESSAGE_LIMIT", 1000); //How many messages the cron script will push per run


/**
 * Logs
 * ----
 */

/**
 * Error log.
 */
ini_set("log_errors", 1);
ini_set("error_log", __DIR__."/../logs/error.log");
