<?php
require_once('lib/OAuth.php');

if (isset($_GET['code'])) {
    $db = new SQLite3('timmy.db');
    $oauth = new OAuth($db);
    $oauth->storeAccessToken($_GET['code']);
    echo "Authorized";
} else {
    header('Location: ' . OAuth::authorizeURL());
}
?>
