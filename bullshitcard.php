<?php
require('lib/Util.php');
require('lib/BotCore.php');

$BULLSHIT_CARDS = [
    "azcanta",
    "land tax",
    "approach of the second sun",
    "hatred",
    "yuriko",
    "homeward path",
    "grand abolisher",
    "protean hulk"
];

$COMMANDER_WEBHOOK = getenv('COMMANDER_WEBHOOK');

$body = Util::handleWebhookChallenge();

function handleBullshitCard($bot, $matches) {
    // $bot->respond(Util::memeify($matches[0] . 'is a bullshit card'));
    Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], Util::memeify($matches[0] . ' is a bullshit card'));
}

$bot = new BotCore();
foreach ($BULLSHIT_CARDS as $bullshit_card) {
    $bot->registerRegex('/(' . strtolower($bullshit_card) . ')/i', 'handleBullshitCard');
}

$bot->handleMessage($body['event']['text']);
?>
