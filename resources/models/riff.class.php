<?php

/**
 * @class Riff
 * ===========
 * 
 * Provides a class for Riff objects and static functions related to riffs.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Riff {
    /**
     * The riff_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The title of the riff.
     * 
     * @var string
     */
    public $title;
    
    /**
     * The duration of the riff in seconds.
     * 
     * @var int
     */
    public $duration;
    
    /**
     * The URL to the riff's audio.
     * 
     * @var string 
     */
    public $link;
    
    /**
     * Constructs a new Riff instance with the given member variable values.
     * 
     * @param int $id
     * @param string $title
     * @param int $duration
     * @param string $link
     */
    protected function __construct($id, $title, $duration, $link) {
        $this->id = (int)$id;
        $this->title = $title;
        $this->duration = $duration;
        $this->link = $link;
    }
    
    /**
     * Adds a new Riff.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @param string $title
     * @param int $duration
     * @param string $riff_tmp_path
     * @return Riff|null The new Riff object, or null on failure.
     */
    public static function add($post_id, $title, $duration, $riff_tmp_path) {
        global $dbh;
        
        $query = "
            INSERT INTO `riffs` (`post_id`, `title`, `duration`)
            VALUES (:post_id, :title, :duration)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('title', $title);
        $sth->bindValue('duration', $duration);
        if ($sth->execute()) {
            $riff_id = $dbh->lastInsertId();
            
            $riff_new_path = MEDIA_ABSOLUTE_PATH."/riffs/$riff_id.m4a";
            if (is_uploaded_file($riff_tmp_path)) {
                $saved_riff = move_uploaded_file($riff_tmp_path, $riff_new_path);
            } else {
                $saved_riff = copy($riff_tmp_path, $riff_new_path);
            }
            
            if ($saved_riff) {
                return Riff::get_by_post_id($post_id);
            } else {
                Riff::delete($riff_id);
            }
        }
        return null;
    }
    
    /**
     * Deletes a Riff object with the given riff_id.
     * 
     * @global PDO $dbh
     * @param int $riff_id
     * @return boolean
     */
    public static function delete($riff_id) {
        global $dbh;
        
        MediaFiles::delete_riff_audio((int)$riff_id);
        
        $query = "
            DELETE FROM `riffs`
            WHERE `riff_id` = :riff_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('riff_id', $riff_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns the Riff object attached to the given post_id, if there is one.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @return Riff|null The Riff object, or null if it doesn't exist.
     */
    public static function get_by_post_id($post_id) {
        global $dbh;
        
        $query = "SELECT * FROM `riffs` WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $riff_id = $row['riff_id'];
            $path = MEDIA_ABSOLUTE_PATH."/riffs/$riff_id.m4a";
            if (file_exists($path)) {
                return new Riff($row['riff_id'], $row['title'], 
                                $row['duration'], MEDIA_URL."/riffs/$riff_id.m4a");
            }
        }
        
        return null;
    }
}
