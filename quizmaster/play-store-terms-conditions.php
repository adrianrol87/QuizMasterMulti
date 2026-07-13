<?php
include('library/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");
$sql = "SELECT * FROM `settings` where `type` = 'update_terms'";
$db->sql($sql);
$res = $db->getResult();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width'>
        <title>Terms & Conditions</title>
        <style> body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding:1em; } </style>
    </head>
    <body>
        <?php echo $res[0]['message']; ?>
    </body>
</html>