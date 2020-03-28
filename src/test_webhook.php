<?php
require_once('lib/Config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    file_put_contents(Config::LOG_DIR . '/test_webhook.log', str_replace("\n", "\\n", $data['text']) . PHP_EOL, FILE_APPEND | LOCK_EX);
} else {
    passthru('tail -'  . 1 . ' ' . Config::LOG_DIR . '/test_webhook.log');
}
?>
