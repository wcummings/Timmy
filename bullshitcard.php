<?php
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

$COMMANDER_WEBHOOK = getenv("COMMANDER_WEBHOOK");

$in = file_get_contents("php://input");
$body = json_decode($in, true);
if (array_key_exists("challenge", $body)) {
    echo $body["challenge"];
}

if (array_key_exists("bot_id", $body["event"])) {
    exit(0);
}

$message = strtolower($body["event"]["text"]);

$found_bullshit_cards = array_filter($BULLSHIT_CARDS, function ($bullshit_card) use ($message) {
    return strpos(strtolower($message), $bullshit_card) !== false;
});

$bullshit_card = $found_bullshit_cards[array_rand($found_bullshit_cards)];

if (!isset($bullshit_card)) {
    exit(0);
}

$meme = memeify($bullshit_card . " is a bullshit card");
error_log($meme);
send_slack_message($COMMANDER_WEBHOOK, memeify($bullshit_card . " is a bullshit card"));

function memeify($str) {
    $result = "";
    for ($i = 0; $i < strlen($str); $i++) {
        $char = substr($str, $i, 1);
        if ($i % 2) {
            $result .= strtolower($char);
        } else {
            $result .= strtoupper($char);
        }
    }
    return $result;
}

function send_slack_message($url, $text) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["text" => $text]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $result = curl_exec($ch);
    echo $result;
}
?>
