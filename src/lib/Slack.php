<?php
require_once('lib/Util.php');

class Slack {
    
    const ADD_REACTION_URL = "https://slack.com/api/reactions.add?token=%s&channel=%s&name=%s&timestamp=%s";
    const CHAT_UPDATE_URL = "https://slack.com/api/chat.update?token=%s&channel=%s&ts=%s&text=%s&attachments=%s";

    public static function addReaction($accessToken, $channelId, $ts, $reaction) {
        $response = json_decode(Util::httpPost(sprintf(self::ADD_REACTION_URL, $accessToken, $channelId, $reaction, $ts), NULL), TRUE);
        if (!$response['ok']) {
            throw new Exception($response['error']);
        }

        return $response;
    }

    public static function updateMessageAndAttachments($accessToken, $channelId, $ts, $text, $attachments) {
        $response = json_decode(Util::httpPost(sprintf(self::CHAT_UPDATE_URL, $accessToken, $channelId, $ts, $text, urlencode(json_encode($attachments))), NULL), TRUE);

        if (!$response['ok']) {
            throw new Exception($response['error']);
        }

        return $response;
    }

}

?>
