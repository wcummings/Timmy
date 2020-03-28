<?php
require_once('lib/Util.php');
Util::configureErrorLogging();
require_once('lib/Config.php');

$body = Util::handleWebhookChallenge();
file_put_contents(Config::LOG_DIR . '/event_hooks.log', json_encode($body) . PHP_EOL, FILE_APPEND | LOCK_EX);
Util::publishQueueMessage(json_encode($body));
?>
