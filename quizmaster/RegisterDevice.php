<?php

include('library/crud.php');
$db = new Database();
$db->connect();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $sql = "SELECT id FROM tbl_devices WHERE token ='" . $token . "'";
    $db->sql($sql);
    $res = $db->getResult();

    if (empty($res)) {
        $sql = "INSERT INTO tbl_devices (token) VALUES ('" . $token . "')";
        $db->sql($sql);
        $response['error'] = false;
        $response['message'] = 'Device registered successfully';
        echo json_encode($response);
        return false;
    } else {
        $response['error'] = true;
        $response['message'] = 'Device is already registered';
        echo json_encode($response);
        return false;
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Invalid Request...';
}
?>