<?php
require_once('lib/Util.php');
Util::configureErrorLogging();
require_once('lib/BotCore.php');
require_once('lib/Scoreboard.php');
require_once('lib/IdempotencyCheck.php');
require_once('lib/Config.php');
require_once('lib/OAuth.php');

$BULLSHIT_CARDS = [
    "azcanta",
    "land tax",
    "approach of the second sun",
    "hatred",
    "yuriko",
    "homeward path",
    "grand abolisher",
    "protean hulk",
    "teferi's protection",
    "teferis protection",
    "expropriate"
];

$BOLAS_CARDS = [
    "https://i.redd.it/f9w6hz7ycin21.jpg"
];

$db = new SQLite3('timmy.db');
$oauth = new OAuth($db);
$bot = new BotCore($oauth);
$bot->setValue('db', $db);

foreach ($BULLSHIT_CARDS as $bullshit_card) {
    $bot->registerRegex('/(' . strtolower($bullshit_card) . ')/i', 'handleBullshitCard');
}

$bot->registerRegex('/\[\[.*bolas.*\]\]/i', 'showBolasCard');
$bot->registerCommand('/^show.*the scoreboard/i', 'showScoreboard');
$bot->registerCommand('/^show.*the score.*/i', 'showScoreboard');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*([^ ]+) won/i', 'recordGame');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*([^ ]+) was the winner/i', 'recordGame');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*The winner was (.*)/i', 'recordGame');
$bot->registerCommand('/^roll a d(\d+)/i', 'rollDie');
$bot->registerCommand('/^roll (\d+) d(\d+)/i', 'rollDice');
$bot->registerCommand('/^memeify (.*)/i', 'memeifyMessage');
$bot->registerCommand('/.*/', 'iDontUnderstand');

function memeifyMessage($bot, $ctx, $matches) {
    $bot->reply($ctx, Util::memeify($matches[1]));
}

function showBolasCard($bot, $ctx, $matches) {
    $card .= Util::memeify("MY POWER IS HAVING EVERY POWER") . " " . $GLOBALS['BOLAS_CARDS'][array_rand($GLOBALS['BOLAS_CARDS'])];;
    $bot->reply($ctx, $card);
}

function handleBullshitCard($bot, $ctx, $matches) {
    $bot->reply($ctx, Util::memeify($matches[0] . ' is a bullshit card'));
}

function showScoreboard($bot, $ctx, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'), $ctx->getTeamID());

    $response = '';
    $isFirst = TRUE;
    foreach ($scoreboard->getScores() as $score) {
        $winrate = $score['winrate'];
        if (is_null($winrate)) {
            $winrate = '';
        } else {
            $winrate = '(' . strval($winrate) . '%)';
        }

        $emoji = 'star';
        if ($isFirst) {
            $emoji = 'crown';
        }

        $response .= sprintf(":%s: *%s:* %d %s\n", $emoji, ucfirst($score['nickname']), $score['total_wins'], $winrate);
        $isFirst = FALSE;
    }

    $bot->reply($ctx, $response);
}

function recordGame($bot, $ctx, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'), $ctx->getTeamID());

    $playerNicknamesString = strtolower($matches[1]);
    $playerNicknames = array_filter(preg_split('/([ ,]+|and)/i', $playerNicknamesString), function ($s) { return $s != ''; });
    $winnerNickname = trim(strtolower($matches[2]));

    try {
        $scoreboard->recordGame($playerNicknames, $winnerNickname);
        $bot->reply($ctx, 'Your wish is my command');
    } catch (Exception $e) {
        $bot->reply($ctx, $e->getMessage());
    }
}

function rollDie($bot, $ctx, $matches) {
    $sides = $matches[1];
    $bot->reply($ctx, 'BIG MONAYYYY.....');
    sleep(2);
    $bot->reply($ctx, sprintf(':game_die: *(%d)* :game_die:', rand(1, $sides)));
}

function rollDice($bot, $ctx, $matches) {
    $no_rolls = $matches[1];
    $sides = $matches[2];
    $rolls = [];
    
    if ($no_rolls > 10) {
        $bot->reply($ctx, Util::memeify('This isnt Warhammer bro'));
        return;
    }

    for ($i = 0; $i < $no_rolls; $i++) {
        $rolls[] = rand(1, $sides);
    }

    $bot->reply($ctx, 'NOOOO WHAMMMMIEEESS.....');
    sleep(2);
    $bot->reply($ctx, ':game_die: ' . implode(' ', array_map(function ($roll) { return " *(".$roll.")* "; }, $rolls)) . ' :game_die:');
}

function iDontUnderstand($bot, $ctx, $matches) {
    $bot->reply($ctx, 'I don\'t understand');
}

Util::processQueueMessages(function ($msg) use ($bot, $db) {
    $body = json_decode($msg->body, true);

    echo "* Received message ", $msg->body, "\n";

    $checker = new IdempotencyCheck($db);
    if (!$checker->checkEventId($body['event_id'])) {
        if (!Config::TEST_MODE) {
            return;
        }
    }

    $bot->handleMessage($body);
});
?>
