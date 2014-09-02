<?php

/**
 * @class UnitTestEnvironment
 * ==========================
 * 
 * Implements unit tests for basic actions that models can take. Will test
 * user creation, following, post creation, upvoting, tagging, messaging, etc.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("test-environment.class.php");

class UnitTestEnvironment extends TestEnvironment {
    /**
     * Adds and returns a random new user, or null on failure.
     * 
     * @return User|null
     */
    private function get_test_user() {
        return User::add(
            $this->get_words(2),
            preg_replace('/[^a-zA-Z0-9]/', '', $this->get_word()),
            $this->get_word()."@example.com",
            $this->get_words(10),
            $this->get_word(),
            ""
        );
    }
    
    /**
     * Adds and returns a random new post for the given users, with the
     * given parent_ids optionally as parent posts. Optionally with custom content.
     * 
     * @param int $user_id
     * @param array $parent_ids [optional]
     * @param string $content [optional]
     * @return Post|null
     */
    private function get_test_post($user_id, $parent_ids = array(), $content = null) {
        return Post::add(
            $content !== null ? $content : $this->get_words(10),
            $parent_ids,
            "",
            "",
            0,
            "",
            $user_id
        );
    }
    
    /**
     * Adds and deletes a test user.
     * 
     * @return boolean
     */
    protected function create_user_test() {
        $user = $this->get_test_user();
        if (!$user) {
            echo "Failed to create user.\n";
            return false;
        }
        if (!User::delete($user->id)) {
            echo "Failed to delete user.\n";
            return false;
        }
        return true;
    }
    
    /**
     * Creates a new user, then tries to get it by id, username, and email.
     * 
     * @return boolean
     */
    protected function get_user_test() {
        $user = $this->get_test_user();
        if (User::get_by_id($user->id) != $user) {
            echo "Failed to get user by id.\n";
            return false;
        }
        if (User::get_by_username($user->username) != $user) {
            echo "Failed to get user by username.\n";
            return false;
        }
        if (User::get_by_email($user->email) != $user) {
            echo "Failed to get user by email.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates a new user and tries to update their name, username, email, bio,
     * and password.
     * 
     * @return boolean
     */
    protected function update_user_test() {
        $user = $this->get_test_user();
        
        $new_name = $this->get_words(2);
        $new_username = $this->get_word();
        $new_email = $this->get_word()."@example.com";
        $new_bio = $this->get_words(10);
        $new_password = $this->get_word();
        
        $user->set_name($new_name);
        $user->set_username($new_username);
        $user->set_email($new_email);
        $user->set_bio($new_bio);
        $user->set_password($new_password);
        
        $user = User::get_by_id($user->id);
        if ($user->name !== $new_name) {
            echo "Failed to update name.\n";
            return false;
        }
        if ($user->username !== $new_username) {
            echo "Failed to update username.\n";
            return false;
        }
        if ($user->email !== $new_email) {
            echo "Failed to update email.\n";
            return false;
        }
        if ($user->bio !== $new_bio) {
            echo "Failed to update bio.\n";
            return false;
        }
        if (!Auth::is_login_valid($new_username, $new_password)) {
            echo "Failed to update password.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates two users, has one follow another, then deletes the follow.
     * 
     * @return boolean
     */
    protected function follow_test() {
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        if (!Follow::add($user1->id, $user2->id)) {
            echo "Failed to add follow.\n";
            return false;
        }
        if (Follow::get_followers(1, 15, $user1->id) != array(User::get_by_id($user2->id))) {
            echo "Get followers failed.\n";
            return false;
        }
        if (Follow::get_following(1, 15, $user2->id) != array(User::get_by_id($user1->id))) {
            echo "Get following failed.\n";
            return false;
        }
        if (!Follow::delete($user1->id, $user2->id)) {
            echo "Failed to delete follow.";
            return false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return true;
    }
    
    /**
     * Creates a user and a post, tries to get the post, tries to create a second
     * post with the first as a parent, then deletes the posts.
     * 
     * @return boolean
     */
    protected function post_test() {
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        if (!$post) {
            echo "Failed to add post.\n";
            return false;
        }
        if (Post::get_by_id($post->id) != $post) {
            echo "Failed to get post.\n";
            return false;
        }
        $post2 = $this->get_test_post($user->id, array($post->id));
        if (!$post2 || $post2->get_parents() != array($post)) {
            echo "Failed to add post with parents.\n";
            return false;
        }
        if (!Post::delete($post->id) || !Post::delete($post2->id)) {
            echo "Failed to delete post.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates two users and a post by the first of them. Then has the second
     * upvote the post and then remove the upvote.
     * 
     * @return boolean
     */
    protected function upvote_test() {
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $post = $this->get_test_post($user1->id);
        if (!Upvote::add($post->id, $user2->id)) {
            echo "Failed to add upvote.\n";
            return false;
        }
        if (!Upvote::delete($post->id, $user2->id)) {
            echo "Failed to delete upvote.\n";
            return false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return true;
    }
    
    /**
     * Creates a user and a post, then stars the post, gets the user's starred
     * posts, and deletes the star from the post.
     * 
     * @return boolean
     */
    protected function star_test() {
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        if (!Star::add($post->id, $user->id)) {
            echo "Failed to add star.\n";
            return false;
        }
        if (Star::get_starred_posts($user->id) != array($post)) {
            echo "Failed to get starred posts.\n";
            return false;
        }
        if (!Star::delete($post->id, $user->id)) {
            echo "Failed to delete star.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates a post with two tags, then checks that getting those tags shows
     * they are each on one post.
     * 
     * @return boolean
     */
    protected function post_tags_test() {
        $user = $this->get_test_user();
        $tag1 = "tag1";
        $tag2 = "tag2";
        $content = "#$tag1 #$tag2 ".$this->get_words(6);
        $post = $this->get_test_post($user->id, array(), $content);
        $tag_obj1 = Tag::get_by_tag($tag1);
        $tag_obj2 = Tag::get_by_tag($tag2);
        if ($tag_obj1->num_posts !== 1 || $tag_obj1->tag !== $tag1 ||
                $tag_obj2->num_posts !== 1 || $tag_obj2->tag !== $tag2) {
            echo "Failed to add tags.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates a user and adds a tag to them, then removes it.
     * 
     * @return boolean
     */
    protected function user_tags_test() {
        $user = $this->get_test_user();
        $tag = $this->get_word();
        Tag::add_for_user($tag, $user->id);
        $user = User::get_by_id($user->id);
        if ($user->tags != array(Tag::get_by_tag($tag))) {
            echo "Failed to add tag for user.\n";
            return false;
        }
        Tag::delete_from_user($tag, $user->id);
        $user = User::get_by_id($user->id);
        if (!empty($user->tags)) {
            echo "Failed to remove tag for user.\n";
            return false;
        }
        User::delete($user->id);
        return true;
    }
    
    /**
     * Creates a conversation between three users, then makes one leave, then
     * deletes the conversation.
     * 
     * @return boolean
     */
    protected function conversation_test() {
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $user3 = $this->get_test_user();
        $conversation = Conversation::add(array($user1->id, $user2->id, $user3->id));
        if (!$conversation) {
            echo "Failed to add conversation.\n";
            return false;
        }
        if (!in_array($user1, $conversation->users) ||
                !in_array($user2, $conversation->users) ||
                !in_array($user3, $conversation->users)) {
            echo "Failed to add conversation members.\n";
            return false;
        }
        Conversation::delete_member($conversation->id, $user1->id);
        $conversation = Conversation::get_by_id($conversation->id, $user3->id);
        if (in_array($user1, $conversation->users) ||
                !in_array($user2, $conversation->users) ||
                !in_array($user3, $conversation->users)) {
            echo "Failed to remove conversation member.\n";
            return false;
        }
        Conversation::delete($conversation->id);
        if (Conversation::get_by_id($conversation->id, $user3->id)) {
            echo "Failed to delete conversation.\n";
            return false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        User::delete($user3->id);
        return true;
    }
    
    /**
     * Creates two users and a conversation between them. Sends a message from
     * one user, then gets unread messages from the conversation to see that it
     * is there, then sets the conversation as read and checks that unread
     * messages are empty.
     * 
     * @return boolean
     */
    protected function message_test() {
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $conversation = Conversation::add(array($user1->id, $user2->id));
        $content = $this->get_words(10);
        $message = Message::send($content, $conversation->id, $user1->id);
        $messages = Message::get_for_conversation($conversation->id, 1, 15, false, $user2->id);
        if (empty($messages) || $messages[0] != $message) {
            echo "Failed to send message.\n";
            return false;
        }
        $messages_unread = Message::get_for_conversation($conversation->id, 1, 15, true, $user2->id);
        if (!empty($messages_unread)) {
            echo "Failed to set messages as read.\n";
            return false;
        }
        Conversation::delete($conversation->id);
        User::delete($user1->id);
        User::delete($user2->id);
        return true;
    }
    
    /**
     * Creates two users and gives one user all four types of notification,
     * then makes sure the user who did the following is attached to the follow
     * notification, then checks that setting a notification as read works.
     * 
     * @global User $CURRENT_USER
     * @return boolean
     */
    protected function notification_test() {
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $post1 = $this->get_test_post($user1->id);
        $this->get_test_post($user2->id, array(), "@".$user1->username);
        $this->get_test_post($user2->id, array($post1->id));
        Upvote::add($post1->id, $user2->id);
        Follow::add($user1->id, $user2->id);
        $notifications = Notification::get_latest(1, 15, $user1->id);
        if (count($notifications) !== 4) {
            echo "Failed to add notifications.\n";
            return false;
        }
        foreach ($notifications as $n) {
            if ($n->type === 'follow') {
                $notification = $n;
                break;
            }
        }
        if (!isset($notification)) {
            echo "Failed to find follow notification.\n";
            return false;
        }
        if (empty($notification->users) ||
                $notification->users[0] != User::get_by_id($user2->id)) {
            echo "Failed to add user to follow notification.\n";
            return false;
        }
        Notification::set_read($notification->id);
        $read_notification = Notification::get_by_id($notification->id, $user1->id);
        if (!$read_notification->is_read) {
            echo "Failed to set notification as read.\n";
            return false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return true;
    }
    
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        $tests = array(
            "create_user_test" => "Create user test",
            "get_user_test" => "Get user test",
            "update_user_test" => "Update user test",
            "follow_test" => "Follow test",
            "post_test" => "Post test",
            "upvote_test" => "Upvote test",
            "star_test" => "Star test",
            "post_tags_test" => "Post tags test",
            "user_tags_test" => "User tags test",
            "conversation_test" => "Conversation test",
            "message_test" => "Message test",
            "notification_test" => "Notification test"
        );
        foreach ($tests as $test => $message) {
            if (!$this->do_test($test, $message)) {
                return false;
            }
        }
        return true;
    }
}
