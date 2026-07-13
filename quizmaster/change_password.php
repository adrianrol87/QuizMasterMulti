<?php

ob_start();
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    return false;
    exit();
}
include 'library/crud.php';
$db = new Database();
$db->connect();

//to check password
if (isset($_POST['old_password']) && !isset($_POST['new_password'])) {
    $oldpassword = stripslashes($_POST['old_password']);
    $oldpassword = $db->escapeString($oldpassword);
    $auth_username = $db->escapeString($_SESSION["username"]);
    $pwordhash = md5($oldpassword);

    $sql = "SELECT * FROM authenticate WHERE `auth_pass`='$pwordhash' AND auth_username='$auth_username'";

    $db->sql($sql);
    $result = $db->getResult();
    if (!empty($result)) {
        echo "True";
    } else {
        echo "False";
    }
}

//to update password
if (isset($_POST['new_password']) && isset($_POST['old_password'])) {
    $newpassword = stripslashes($_POST['new_password']);
    $newpassword = $db->escapeString($newpassword);
    $auth_username = $db->escapeString($_SESSION["username"]);
    $pwordhash = md5($newpassword);

    $sql = "UPDATE authenticate SET auth_pass='$pwordhash' WHERE auth_username='$auth_username'";

    if ($db->sql($sql)) {
        echo "password change successfully!";
    } else {
        echo "Something wrong";
    }
}
?>
