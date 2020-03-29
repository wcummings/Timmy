<?php
require_once('lib/DbCore.php');
require_once('lib/Config.php');
require_once('lib/Util.php');

class OAuth extends DbCore {

    const SCOPES = ['incoming-webhook'];
    const AUTHORIZE_TPL = 'https://slack.com/oauth/v2/authorize?scope=%s&client_id=%s';
    const ACCESS_TPL = 'https://slack.com/api/oauth.v2.access?client_id=%s&client_secret=%s&code=%s';
    const INSERT_WEBHOOK_QUERY = 'INSERT OR REPLACE INTO webhooks (channel_id, team_id, channel, url) VALUES (:channel_id, :team_id, :channel, :url);';
    const INSERT_TOKEN_QUERY = 'INSERT OR REPLACE INTO access_tokens (team_id, access_token, bot_user_id) VALUES (:team_id, :access_token, :bot_user_id)';
    const GET_WEBHOOK_QUERY = 'SELECT url FROM webhooks WHERE channel_id = :channel_id';

    public function __construct($db) {
        parent::__construct($db);
    }

    public static function authorizeURL() {
        return sprintf(self::AUTHORIZE_TPL, implode(',', $SCOPES), Config::CLIENT_ID);
    }

    public static function accessURL($code) {
        return sprintf(self::ACCESS_TPL, Config::CLIENT_ID, Config::CLIENT_SECRET, $code);
    }

    public function storeAccessToken($code) {
        $data = json_decode(Util::httpGet(self::accessURL($code)), true);
        
        if (!isset($data['ok'])) {
            error_log('Couldnt get access token: ' . print_r($data, true));
            return;
        }
        
        $this->withTransaction(function () use ($data) {
            $this->executeQueryWithParameters(self::INSERT_TOKEN_QUERY, [
                'team_id' => $data['team']['id'],
                'access_token' => $data['access_token'],
                'bot_user_id' => $data['bot_user_id']
            ]);
            $this->executeQueryWithParameters(self::INSERT_WEBHOOK_QUERY, [
                'channel_id' => $data['incoming_webhook']['channel_id'],
                'team_id' => $data['team']['id'],
                'channel' => $data['incoming_webhook']['channel'],
                'url' => $data['incoming_webhook']['url']
            ]);
        });

    }

    public function getWebhookForChannel($channel_id) {
        if (Config::TEST_MODE) {
            return 'http://localhost/' . Config::URL_PREFIX . 'test_webhook.php';
        }

        $result = $this->executeQueryWithParameters(self::GET_WEBHOOK_QUERY, ['channel_id' => $channel_id]);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['url'];
    }

    // TODO: method to retrieve access token

}
?>
