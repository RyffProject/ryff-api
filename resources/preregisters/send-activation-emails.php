<?php
/**
 * Convert Audio
 * =============
 * 
 * This script will attempt to convert audio in posts from media/riffs/raw to
 * media/riffs/hq and media/riffs. It should be scheduled by cron to run once
 * per minute. Users will be notified on success or failure if their original
 * uploads were in a format that needed conversion.
 * 
 * Command-line arguments:
 * "--no-setup" Doesn't install or uninstall the database.
 * "--no-teardown" Doesn't uninstall the database.
 * "--type=TYPE" The type of tests to run, one of api, unit, or populate.
 * "--cycles=CYCLES" The number of populate cycles to run.
 * "--help" Brings up the help text.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

if (in_array('--help', $argv)) {
    echo "--info: Get info on the number of preregistrations, emails sent, etc.\n";
    echo "--num=NUM: The number of activation emails to send.\n";
    echo "--email=EMAIL: The type of test. One of api, unit, or populate.\n";
    echo "--help: Brings up this text.\n";
    exit;
}

set_time_limit(0);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (in_array('--info', $argv)) {
    $query = "
        SELECT
            (SELECT COUNT(*) FROM `preregisters`) AS `total`,
            (SELECT COUNT(*) FROM `preregisters` WHERE `sent` = 1) AS `sent`,
            (SELECT COUNT(*) FROM `preregisters` WHERE `sent` = 0) AS `unsent`,
            (SELECT COUNT(*) FROM `preregisters` WHERE `used` = 1) AS `used`,
            (SELECT COUNT(*) FROM `preregisters` WHERE `used` = 0 AND `sent` = 1) AS `unused`";
    $sth = $dbh->prepare($query);
    if (!$sth->execute() || !$sth->rowCount() || !($info = $sth->fetch(PDO::FETCH_ASSOC))) {
        echo "There was an error getting preregistration information.\n";
        exit;
    }
    echo "Preregistration Information\n";
    echo "===========================\n";
    echo "Total preregisters: {$info['total']}\n";
    echo "Total sent:         {$info['sent']}\n";
    echo "Total unsent:       {$info['unsent']}\n";
    echo "Total used:         {$info['used']}\n";
    echo "Total unused:       {$info['unused']}\n";
    exit;
}

foreach ($argv as $arg) {
    if (strpos($arg, '--email=') === 0) {
        $email = substr($arg, 8);
        break;
    }
}
if (isset($email)) {
    if (!Preregister::exists($email)) {
        if (!Preregister::is_email_valid($email)) {
            echo "Couldn't add invalid email address $email.\n";
            exit;
        } else if (!Preregister::add($email)) {
            echo "There was an error preregistering email address $email.\n";
            exit;
        }
    }
    if (!Preregister::send_activation($email)) {
        echo "There was an error sending the activation email to $email.\n";
        exit;
    }
    echo "An activation code has been sent to email address $email.\n";
    exit;
}

foreach ($argv as $arg) {
    if (strpos($arg, '--num=') === 0) {
        $num_to_send = (int)substr($arg, 6);
        $num_sent = 0;
        break;
    }
}
if (isset($num_to_send)) {
    echo "Sending activation codes to $num_to_send email addresses\n";
    echo "============================".str_repeat("=", strlen($num_to_send))."================\n";
    for ($i = 0; $i < $num_to_send; $i++) {
        $query = "
            SELECT `email` FROM `preregisters` WHERE `sent` = 0 AND `used` = 0
            ORDER BY `date_created`, `preregister_id` ASC
            LIMIT 1";
        $sth = $dbh->prepare($query);
        if (!$sth->execute() || !$sth->rowCount() || !($row = $sth->fetch(PDO::FETCH_ASSOC))) {
            break;
        } else if (!Preregister::send_activation($row['email'])) {
            echo "Error sending activation email to email address {$row['email']}.\n";
            continue;
        }
        echo "Sent activation email to email address {$row['email']}.\n";
        $num_sent++;
    }
    echo "Sent $num_sent emails.\n";
    exit;
}

echo "Missing argument.\n";
echo "Use --help for more information.\n";
