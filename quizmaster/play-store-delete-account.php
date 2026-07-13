<?php
include('library/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");

$lang = strtolower(trim((string) ($_GET['lang'] ?? 'en')));
$typesToTry = [];
if (in_array($lang, ['es', 'en'], true)) {
    $typesToTry[] = 'delete_account_' . $lang;
}
$typesToTry[] = 'delete_account_en';
$typesToTry[] = 'delete_account_es';
$typesToTry[] = 'delete_account';

$message = '';
foreach (array_unique($typesToTry) as $type) {
    $sql = "SELECT * FROM `settings` WHERE `type` = '" . $db->escapeString($type) . "' LIMIT 1";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res) && !empty($res[0]['message'])) {
        $message = $res[0]['message'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'es' ? 'es' : 'en' ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $lang === 'es' ? 'Eliminar cuenta' : 'Delete Account' ?></title>
        <style>
            body {
                font-family: Helvetica, Arial, sans-serif;
                padding: 1em;
                max-width: 960px;
                margin: 0 auto;
                color: #1f3147;
                line-height: 1.7;
            }
        </style>
    </head>
    <body>
        <?= $message; ?>
    </body>
</html>
