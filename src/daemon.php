<?php
require_once('lib/Util.php');
Util::configureErrorLogging();
require_once('lib/BotCore.php');
require_once('lib/Scoreboard.php');
require_once('lib/IdempotencyCheck.php');
require_once('lib/Config.php');
require_once('lib/OAuth.php');
require_once('lib/Scryfall.php');
require_once('lib/Slack.php');
require_once('lib/Cache.php');

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
$cache = new Cache($db);
$bot->setValue('db', $db);
$bot->setValue('oauth', $oauth);
$bot->setValue('cache', $cache);

foreach ($BULLSHIT_CARDS as $bullshit_card) {
    $bot->registerRegex('/(' . strtolower($bullshit_card) . ')/i', 'handleBullshitCard');
}

$bot->registerRegex('/\[\[.*bolas.*\]\]/i', 'showBolasCard');
$bot->registerRegex('/\[\[.*yawgmoth tree.*\]\]/i', 'showYawgmothTree');
$bot->registerCommand('/^show.*the scoreboard/i', 'showScoreboard');
$bot->registerCommand('/^show.*the score.*/i', 'showScoreboard');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*([^ ]+) won/i', 'recordGame');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*([^ ]+) was the winner/i', 'recordGame');
$bot->registerCommand('/^record a game with ([^\.,]+)[\.,][ ]*The winner was (.*)/i', 'recordGame');
$bot->registerCommand('/^roll a d(\d+)/i', 'rollDie');
$bot->registerCommand('/^roll (\d+) d(\d+)/i', 'rollDice');
$bot->registerCommand('/^memeify (.*)/i', 'memeifyMessage');
$bot->registerCommand('/^scry (.*)/i', 'scryfallSearch');
$bot->registerCommand('/.*/', 'iDontUnderstand');
$bot->registerReactionHandler('handleReactions');

function scryfallReactCacheKey($ctx, $ts) {
    return 'scryfall' . "#" . $ctx->getTeamID() . "#" . $ctx->getChannelID() . "#" . $ctx->getUserID() . "#" . $ts;
}

function scryfallSearchCacheKey($query) {
    return 'scryfallsearch' . "#" . $query . "#" . date('Y.m.d');
}

function scryfallSearch($bot, $ctx, $matches) {
    $cache = $bot->getValue('cache');
    $query = html_entity_decode($matches[1]);
    $response = $cache->cachedJson(scryfallSearchCacheKey($query), function () use ($query) {
        return Scryfall::search($query);
    });
    if ($response['total_cards'] > 0) {
        $firstCard = $response['data'][0];
        $message = $bot->reply($ctx, $firstCard['image_uris']['normal']);
        $oauth = $bot->getValue('oauth');
        $token = $oauth->getAccessToken($ctx->getTeamId());
        $ts = $message['ts'];
        $cache->setValue(scryfallReactCacheKey($ctx, $ts), json_encode(['query' => $query, 'offset' => 0]));
        Slack::addReaction($token, $ctx->getChannelId(), $ts, 'arrow_left');
        Slack::addReaction($token, $ctx->getChannelId(), $ts, 'arrow_right');
    } else {
        $bot->reply($ctx, 'No cards matching query');
    }
}

function handleReactions($bot, $ctx, $event) {
    $cache = $bot->getValue('cache');
    $oauth = $bot->getValue('oauth');
    $ts = $event['item']['ts'];
    $value = json_decode($cache->getValue(scryfallReactCacheKey($ctx, $ts)), TRUE);
    $query = $value['query'];
    $offset = $value['offset'];
    if ($query) {
        $response = $cache->cachedJson(scryfallSearchCacheKey($query), function () use ($query) {
            return Scryfall::search($query);
        });

        $c = count($response['data']);
        if ($event['reaction'] == 'arrow_left') {
            $offset = max($offset - 1, 0);
        } else if ($event['reaction'] == 'arrow_right') {
            $offset = min($offset + 1, $c - 1);
        }

        $card = $response['data'][$offset];
        $token = $oauth->getAccessToken($ctx->getTeamID());
        $text = $card['image_uris']['normal'];
        $attachments = [[
            'image_url' => $card['image_uris']['normal'],
            'thumb_url' => $card['image_uris']['small'],
            'fallback' => $text,
            'text' => $text,
            'color' => '#7CD197'
        ]];
        try {
            Slack::updateMessageAndAttachments($token, $ctx->getChannelID(), $ts, $text, $attachments);
            $cache->setValue(scryfallReactCacheKey($ctx, $ts), json_encode(['query' => $query, 'offset' => $offset]));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}

function memeifyMessage($bot, $ctx, $matches) {
    $bot->reply($ctx, Util::memeify($matches[1]));
}

function showBolasCard($bot, $ctx, $matches) {
    $card .= Util::memeify("MY POWER IS HAVING EVERY POWER") . " " . $GLOBALS['BOLAS_CARDS'][array_rand($GLOBALS['BOLAS_CARDS'])];;
    $bot->reply($ctx, $card);
}

function showYawgmothTree($bot, $ctx, $matches) {
    $bot->reply($ctx, "https://i.redd.it/ybqoaqmn8ei21.png");
}

function handleBullshitCard($bot, $ctx, $matches) {
    $bot->reply($ctx, Util::memeify($matches[0] . ' is a bullshit card'));
}

function showScoreboard($bot, $ctx, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'), $ctx->getTeamID(), $ctx->getChannelID());

    $response = '';
    $scores = $scoreboard->getScores();
    $maxScore = max(array_map(function ($score) { return $score['total_wins']; }, $scores));
    foreach ($scores as $score) {
        $winrate = $score['winrate'];
        if (is_null($winrate)) {
            $winrate = '';
        } else {
            $winrate = '(' . strval($winrate) . '%)';
        }

        $emoji = 'star';
        $isLeader = $score['total_wins'] == $maxScore;
        if ($isLeader) {
            $emoji = 'crown';
        }

        $response .= sprintf(":%s: *%s:* %d %s\n", $emoji, ucfirst($score['nickname']), $score['total_wins'], $winrate);
    }

    $bot->reply($ctx, $response);
}

function recordGame($bot, $ctx, $matches) {
    $scoreboard = new Scoreboard($bot->getValue('db'), $ctx->getTeamID(), $ctx->getChannelID());

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
