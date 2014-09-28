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
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

set_time_limit(0);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../resources"
)));

require_once("global.php");

$start_time = time();
$total_time = 50;

while (time() - $start_time < $total_time) {
    //$dbh->beginTransaction();
    $post_query = "
        SELECT `post_id`, `user_id`, `active`, `converted`, `hq_converted`
        FROM `posts`
        WHERE `converted` = 0
        ORDER BY `date_created` ASC
        LIMIT 1
        FOR UPDATE";
    $post_sth = $dbh->prepare($post_query);
    if (!$post_sth->execute() || !$post_sth->rowCount()) {
        $dbh->rollBack();
        sleep(5);
        continue;
    }
    
    $post_results = $post_sth->fetch(PDO::FETCH_ASSOC);
    $post_id = (int)$post_results['post_id'];
    $user_id = (int)$post_results['user_id'];
    $active = (bool)$post_results['active'];
    $converted = (bool)$post_results['converted'];
    $converted_hq = (bool)$post_results['hq_converted'];
    
    $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
    $raw_path = "$media_dir/riffs/raw/$post_id.m4a";
    $hq_path = "$media_dir/riffs/hq/$post_id.m4a";
    
    //Save high quality
    if (!$active || !$converted_hq) {
        $converted_hq_result = MediaFiles::save_audio($raw_path, $post_id, true);
        if (!$active) {
            if ($converted_hq_result) {
                Notification::add($user_id, "post-converted", $post_id, null, null, null);
                Post::set_active($post_id, true);
            } else {
                Notification::add($user_id, "post-failed", $post_id, null, null, null);
                Post::delete($post_id);
                $dbh->commit();
                continue;
            }
        }
        Post::set_converted($post_id, true, true);
    }
    
    //Save low quality
    $source_path = file_exists($raw_path) ? $raw_path : $hq_path;
    MediaFiles::save_audio($source_path, $post_id, false);
    Post::set_converted($post_id, false, true);
    
    $dbh->commit();
}
