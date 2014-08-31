<?php
/**
 * Send Push Notifications
 * =======================
 * 
 * This script will send unsent notifications to devices via APNs.
 * 
 * This script is meant to be run as often as possible through a scheduling
 * service like cron. It will create a lock file, send as many notifications as
 * specified in config.php, then remove the lock file.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../resources"
)));

require_once("global.php");

$lock_file = __DIR__."/notifications.lock";
if (file_exists($lock_file)) {
    echo "Found existing notification lock file. Exiting now.\n";
    exit;
}

file_put_contents($lock_file, "Locked at ".date('Y-m-d H:i:s')."\n");

$num_sent = PushNotification::send_all(PUSH_NOTIFICATION_LIMIT);
echo "Sent $num_sent notifications.\n";

if (file_exists($lock_file)) {
    unlink($lock_file);
}
