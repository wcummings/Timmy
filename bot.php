<?php
require_once('lib/Util.php');
require_once('lib/BotCore.php');
require_once('lib/Scoreboard.php');
require_once('lib/IdempotencyCheck.php');
require_once('lib/Config.php');

$COMMANDER_WEBHOOK = Config::webhookURL();

if (Config::TEST_MODE) {
    $COMMANDER_WEBHOOK = 'http://localhost/' . Config::URL_PREFIX . 'test_webhook.php';
}

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

$db = new SQLite3('timmy.db');
$bot = new BotCore();
$bot->setValue('db', $db);

foreach ($BULLSHIT_CARDS as $bullshit_card) {
    $bot->registerRegex('/(' . strtolower($bullshit_card) . ')/i', 'handleBullshitCard');
}

$bot->registerRegex('/^Timmy show.*the scoreboard/i', 'showScoreboard');
$bot->registerRegex('/^Timmy record a game with ([^\.,]+)[\.,][ ]*([^ ]+) won/i', 'recordGame');
$bot->registerRegex('/^Timmy record a game with ([^\.,]+)[\.,][ ]*([^ ]+) was the winner/i', 'recordGame');
$bot->registerRegex('/^Timmy record a game with ([^\.,]+)[\.,][ ]*The winner was (.*)/i', 'recordGame');
$bot->registerRegex('/^Timmy/i', 'iDontUnderstand');

function handleBullshitCard($bot, $matches) {
    // $bot->respond(Util::memeify($matches[0] . 'is a bullshit card'));
    Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], Util::memeify($matches[0] . ' is a bullshit card'));
}

function showScoreboard($bot, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'));

    $response = '';
    foreach ($scoreboard->getScores() as $score) {
        $response .= sprintf(":star: *%s:* %d\n", ucfirst($score['nickname']), $score['total_wins']);
    }

    Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], $response);
}

function recordGame($bot, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'));

    $playerNicknamesString = strtolower($matches[1]);
    $playerNicknames = array_filter(preg_split('/([ ,]+|and)/i', $playerNicknamesString), function ($s) { return $s != ''; });
    $winnerNickname = trim(strtolower($matches[2]));

    try {
        $scoreboard->recordGame($playerNicknames, $winnerNickname);
        Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], 'Your wish is my command');
    } catch (Exception $e) {
        Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], $e->getMessage());
    }

}

function iDontUnderstand($bot, $matches) {
    Util::sendSlackMessage($GLOBALS['COMMANDER_WEBHOOK'], 'I don\'t understand');
}

$checker = new IdempotencyCheck($db);
if (!$checker->checkEventId($body['event_id'])) {
    if (!Config::TEST_MODE) {
        return;
    }
}

Util::processQueueMessages(function ($json) use ($bot) {
    $body = json_decode($json);
    $bot->handleMessage($body['event']['text']);
});
?>
