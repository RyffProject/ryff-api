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
define("MEDIA_EXT_PATH", "/media");

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
define("TEST_MEDIA_EXT_PATH", "/test_media");

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
define("FFMPEG_CODEC", "mp3");

/**
 * Command for getting audio information using ffprobe. Can be substituted for
 * avprobe on Ubuntu machines with avconv installed. In the future this will
 * be auto detected.
 * 
 * Sample command for avprobe:
 * avprobe -loglevel quiet -show_format -show_streams %s -of json
 */
define("AUDIO_INFO_COMMAND", 
        "ffprobe -loglevel quiet -show_format -show_streams %s -print_format json");


/**
 * Audio Quota
 * -----------
 */

/**
 * The length of time for which this quota will apply. So this value says you
 * can upload X amount of audio per AUDIO_QUOTA_TIMEFRAME seconds. Only applies
 * when TEST_MODE is false.
 */
define("AUDIO_QUOTA_TIMEFRAME", 86400);

/**
 * The seconds of audio that a user can upload per AUDIO_QUOTA_TIMEFRAME seconds.
 * Set to 0 for no quota.
 */
define("AUDIO_QUOTA_LENGTH", 7200);

/**
 * The bytes of audio that a user can upload per AUDIO_QUOTA_TIMEFRAME seconds.
 * Set to 0 for no quota.
 */
define("AUDIO_QUOTA_SIZE", 235929600);


/**
 * Registration
 * ------------
 */

/**
 * Whether registration is open or closed. If registration is closed, users will
 * be able to put their email in a table for preregisters. The script at
 * resources/send-activation-emails.php will send out activation codes for these
 * users, and user creation will require a valid activation code.
 */
define("REGISTRATION_OPEN", true);

/**
 * The "from" email address. Should be from the same domain as the original
 * server, unless you have DNS set up correctly.
 */
define("FROM_EMAIL", "Ryff Registration <register@ryff.me>");

/**
 * The subject for the preregistration received email.
 */
define("PREREGISTRATION_RECEIVED_EMAIL_SUBJECT", "Ryff Preregistration");

/**
 * This will be the email that users receive upon successfully preregistering
 * when registration is closed.
 */
define("PREREGISTRATION_RECEIVED_EMAIL_BODY", <<<'EMAIL_BODY'
<p>Thank you for letting us know you are interested by preregistering for
<a href="https://ryff.me">Ryff</a>! Your activation code will be sent to you
with instructions for creating your account when the service launches.</p>

<p>Thank you,<br />
The Ryff Team</p>
EMAIL_BODY
);

/**
 * The subject for the preregistration received email.
 */
define("PREREGISTRATION_ACTIVATION_EMAIL_SUBJECT", "Ryff Activation");

/**
 * This will be the email that preregistered users receive when activation
 * codes are sent out so that users can begin using the service. The variable
 * %ACTIVATION_CODE% will be replaced with the actual activation code.
 */
define("PREREGISTRATION_ACTIVATION_EMAIL_BODY", <<<'EMAIL_BODY'
<p>Thank you for preregistering for <a href="https://ryff.me">Ryff</a>! We are
ready for users to try out the service, and we welcome you to download the app
on the App Store. Your activation code is below, you will need it for registration.</p>

<p>Activation Code: %ACTIVATION_CODE%</p>

<p>Thank you,<br />
The Ryff Team</p>
EMAIL_BODY
);
