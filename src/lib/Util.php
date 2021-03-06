<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Util {
   
    const SEND_MESSAGE_URL = "https://slack.com/api/chat.postMessage?token=%s&channel=%s&text=%s&unfurl_media=true&unfurl_links=true";

    public static function sendSlackMessage($accessToken, $teamId, $channelId, $text) {
        $response = json_decode(self::httpPost(sprintf(self::SEND_MESSAGE_URL, $accessToken, $channelId, urlencode($text)), NULL), TRUE);
        return $response;
    }
 
    public static function memeify($str) {
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

    public static function httpPost($url, $payload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload != NULL) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        return $result;
    }

    public static function httpGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_Setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        return $result;
    }
    
    public static function handleWebhookChallenge() {
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

    public static function publishQueueMessage($payload) {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('task_queue', false, true, false, false);

        $msg = new AMQPMessage(
            $payload,
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $channel->basic_publish($msg, '', 'task_queue');

        $channel->close();
        $connection->close();
    }

    public static function processQueueMessages($callback)  {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('task_queue', false, true, false, false);
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('task_queue', '', false, false, false, false, function($msg) use ($callback) {
            $callback($msg);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        });
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    public static function configureErrorLogging() {
        ini_set('log_errors', TRUE);
        error_reporting(E_ALL);
    }

    public function randomFloat($min = 0, $max = 1) {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

}
?>
