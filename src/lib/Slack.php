<?php
require_once('lib/Util.php');

class Slack {
    
    const ADD_REACTION_URL = "https://slack.com/api/reactions.add?token=%s&channel=%s&name=%s&timestamp=%s";

    public static function addReaction($accessToken, $channelId, $ts, $reaction) {
        $response = json_decode(Util::httpPost(sprintf(self::ADD_REACTION_URL, $accessToken, $channelId, $reaction, $ts), NULL), TRUE);
        if (!$response['ok']) {
            throw new Exception($response['error']);
        }
        return $response;
    }

}

?>
