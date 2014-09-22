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
 * The outward facing url of the site. ex) http://localhost/ryff
 */
define("SITE_ROOT", "http://localhost/ryff");

/**
 * The absolute path to the media folder. ex) /var/www/html/media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("MEDIA_ABSOLUTE_PATH", __DIR__."/../public_html/media");

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
define("DB_NAME", "ryff");
define("DB_USER", "ryff");
define("DB_HOST", "localhost");
define("DB_PASS", "");


/**
 * Testing constants
 * -----------------
 */

/**
 * Use the test database and media.
 */
if (!defined("TEST_MODE")) {
    define("TEST_MODE", true);
}

/**
 * The absolute path to the test media folder. ex) /var/www/html/test_media
 * The media folder contains 'avatars', 'posts', and 'riffs' subfolders.
 */
define("TEST_MEDIA_ABSOLUTE_PATH", __DIR__."/../public_html/test_media");

/**
 * The test database credentials. Used for running the test suite or for
 * populating a database with test data. The database must be created manually
 * before use, but tables will be installed at runtime.
 */
define("TEST_DB_NAME", "ryfftest");
define("TEST_DB_USER", "ryff");
define("TEST_DB_HOST", "localhost");
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


/**
 * Audio Conversion
 * ----------------
 */

/**
 * Name of ffmpeg command line utility. Will need to be changed to "avconv"
 * for newer versions of Ubuntu.
 */
define("FFMPEG_COMMAND", "ffmpeg");

/**
 * Audio codec passed to ffmpeg when converting audio. You may have codecs
 * available on your system besides the default.
 */
define("FFMPEG_CODEC", "aac -strict -2");
