<?php

/**
 * @class Util
 * ===========
 * 
 * Provides static utility functions used by more than one class.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Util {
    /**
     * Helper function that gets a timestamp that represents one day ago, or
     * one week ago, etc.
     * 
     * @param string $time_str One of "day", "week", "month", or "all".
     * @return string The timestamp.
     */
    public static function get_from_date($time_str) {
        switch ($time_str) {
            case "day":
                $from_time = time() - (60 * 60 * 24);
                break;
            case "week":
                $from_time = time() - (60 * 60 * 24 * 7);
                break;
            case "month":
                $from_time = time() - (60 * 60 * 24 * 30);
                break;
            case "all":
            default:
                $from_time = 0;
                break;
        }
        return date("Y-m-d H:i:s", $from_time);
    }
    
    /**
     * Logs the time, script, user, and message in the log file specified in
     * config.php.
     * 
     * @global User $CURRENT_USER
     * @param string $message
     */
    public static function error_log($message) {
        global $CURRENT_USER;
        
        $info = array(
            "Script: {$_SERVER["PHP_SELF"]}",
            "User: ".($CURRENT_USER ? $CURRENT_USER->id : "-"),
            "Error: $message"
        );
        error_log(implode("   ", $info));
    }
}
