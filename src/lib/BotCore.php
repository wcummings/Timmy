<?php
require_once('lib/BotContext.php');

class BotCore {

    public function __construct($oauth) {
        $this->regexRegistry = [];
        $this->botContext = [];
        $this->oauth = $oauth;
        $this->commandRegex = [];
    }

    public function registerRegex($regex, $fn) {
        $this->regexRegistry[] = [$regex, $fn];
    }

    public function registerCommand($regex, $fn) {
        $this->commandRegex[] = [$regex, $fn];
    }

    public function setValue($key, $value) {
        $this->botContext[$key] = $value;
    }
    
    public function getValue($key) {
        return $this->botContext[$key];
    }

    public function handleMessage($payload) {
        $message = $payload['event']['text'];
        $channel_id = $payload['event']['channel'];
        $team_id = $payload['event']['team'];
        // Look for @mentions
        $bot_user_id = $this->oauth->getBotUserId($team_id);
        $at_mention = '<@' . $bot_user_id . '>';

        $ctx = new BotContext($channel_id, $team_id);
        if (strstr($message, $at_mention)) {
            $rest = trim(str_replace($at_mention, '', $message));
            foreach ($this->commandRegex as $regexFnPair) {
                $regex = $regexFnPair[0];
                $fn = $regexFnPair[1];
                if (preg_match($regex, $rest, $matches)) {
                    return $fn($this, $ctx, $matches);
                }
            }            
        }
        
        foreach ($this->regexRegistry as $regexFnPair) {
            $regex = $regexFnPair[0];
            $fn = $regexFnPair[1];
            if (preg_match($regex, $message, $matches)) {
                return $fn($this, $ctx, $matches);
            }
        }
    }

    public function reply($ctx, $message) {
        $webhookURL = $this->oauth->getWebhookForChannel($ctx->getChannelID());
        if ($webhookURL != NULL) {
            Util::sendSlackMessage($webhookURL, $message);
        }
    }

}
?>
