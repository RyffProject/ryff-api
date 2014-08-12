<?php

/**
 * Get Tags Suggested
 * ==================
 * 
 * Authentication required.
 * Returns an array of no more than 10 tags to suggest the user search for.
 * 
 * Return on success:
 * "success" The success message.
 * "tags" An array of the suggested tags.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

//Selects popular tags belonging to users the current user follows and their posts
$query = "SELECT t.`tag`, COUNT(t.`tag`) AS `score`
          FROM (
            -- Tags on posts the user has upvoted
            SELECT pt.`tag` AS `tag`
            FROM `post_tags` AS pt
            JOIN `posts` AS p
            ON p.`post_id` = pt.`post_id`
            JOIN `upvotes` AS up
            ON up.`post_id` = p.`post_id`
            WHERE up.`user_id` = ".$db->real_escape_string($CURRENT_USER->id)."
            
            UNION ALL
            
            -- Tags on posts by people the user follows
            SELECT pt.`tag` AS `tag`
            FROM `post_tags` AS pt
            JOIN `posts` AS p
            ON p.`post_id` = pt.`post_id`
            JOIN `users` AS u
            ON u.`user_id` = p.`user_id`
            JOIN `follows` AS f
            ON f.`from_id` = p.`user_id`
            WHERE f.`from_id` = ".$db->real_escape_string($CURRENT_USER->id)."
          
            UNION ALL

            -- Tags on people the user follows
            SELECT ut.`tag` AS `tag`
            FROM `user_tags` AS ut
            JOIN `users` AS u
            ON u.`user_id` = ut.`user_id`
            JOIN `follows` AS f
            ON f.`from_id` = u.`user_id`
            WHERE f.`from_id` = ".$db->real_escape_string($CURRENT_USER->id)."
          ) AS t
          GROUP BY t.`tag`
          ORDER BY `score` DESC
          LIMIT 10";
$results = $db->query($query);
if ($results) {
    $tags = array();
    while ($row = $results->fetch_assoc()) {
        $tags[] = $row['tag'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved suggested tags.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Error retrieving suggested tags."));
}
