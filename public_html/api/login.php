<?php



set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if ($ERRORS) {
    echo json_encode(array("error" => "Unable to connect to database"));
    exit;
}

$username = isset($_POST['auth_username']) ? trim($_POST['auth_username']) : "";
$password = isset($_POST['auth_password']) ? $_POST['auth_password'] : "";

if (!$username) {
    $ERRORS++;
}
if (!$password) {
    $ERRORS++;
}

if ($ERRORS) {
    echo json_encode(array("error" => "Missing username or password"));
    exit;
}

if (User::is_login_valid($username, $password)) {
    echo json_encode(array(
        "success" => "You have logged in successfully.",
        "user" => User::get_by_username($username)
        ));
    exit;
}

echo json_encode(array("error" => "Invalid username or password"));
exit;
