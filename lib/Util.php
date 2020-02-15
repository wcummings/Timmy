<?php
class Util {
    
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

    function sendSlackMessage($webhookURL, $text) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhookURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["text" => $text]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        echo $result;
    }

    function handleWebhookChallenge() {
        $in = file_get_contents("php://input");
        $body = json_decode($in, true);
        if (array_key_exists("challenge", $body)) {
            echo $body["challenge"];
        }

        if (array_key_exists("bot_id", $body["event"])) {
            exit(0);
        }

        return $body;
    }

}
?>
