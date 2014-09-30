<?php
/**
 * Add Preregister
 * ===============
 * 
 * Adds an email to the preregister table with an activation code to be sent
 * out later so the user can register. Sends a confirmation email to the user
 * on success.
 * 
 * POST variables:
 * "email" (required) The email address to preregister.
 * 
 * Return on success:
 * "success" The success message.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$email = isset($_POST['email']) ? $_POST['email'] : "";
if (!Preregister::is_email_valid($email)) {
    echo json_encode(array(
        "error" => "You must provide a valid email address."
    ));
} else if (Preregister::exists($email)) {
    echo json_encode(array(
        "error" => "This email address has already been preregistered."
    ));
} else if (!Preregister::add($email)) {
    echo json_encode(array(
        "error" => "There was an error with your preregistration. Please try again."
    ));
} else {
    echo json_encode(array(
        "success" => "You have successfully preregistered. You should receive a confirmation email shortly."
    ));
}
