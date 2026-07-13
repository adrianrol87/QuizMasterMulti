<?php

$accepted_origins = array("http://" . $_SERVER['HTTP_HOST']);

// Images upload path

if (!is_dir('images/instruction')) {
    mkdir('images/instruction', 0777, true);
}
$imageFolder = "images/instruction/";

reset($_FILES);
$temp = current($_FILES);
if (is_uploaded_file($temp['tmp_name'])) {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Same-origin requests won't set an origin. If the origin is set, it must be valid.
        if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        } else {
            header("HTTP/1.1 403 Origin Denied");
            return;
        }
    }

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    // Accept upload if there was no origin, or if it is an accepted origin
    $filename = $temp['name'];

// file type
    $filetype = $_POST['filetype'];

// Valid extension
    if ($filetype == 'image') {
        $valid_ext = array('png', 'jpeg', 'jpg');
    } else if ($filetype == 'media') {
        $valid_ext = array('mp4', 'mp3');
    }

// Location
    $location = $imageFolder . $temp['name'];

// file extension
    $file_extension = pathinfo($location, PATHINFO_EXTENSION);
    $file_extension = strtolower($file_extension);

    $return_filename = "";

// Check extension
    if (in_array($file_extension, $valid_ext)) {

        // Upload file
        if (move_uploaded_file($temp['tmp_name'], $location)) {

            $return_filename = $filename;
        }
    }

    echo $return_filename;
} else {
    // Notify editor that the upload failed
    header("HTTP/1.1 500 Server Error");
}
?>