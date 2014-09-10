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
}
