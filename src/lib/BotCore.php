<?php
require_once('lib/BotContext.php');

class BotCore {

    public function __construct($oauth) {
        $this->regexRegistry = [];
        $this->botContext = [];
        $this->oauth = $oauth;
        $this->commandRegex = [];
        $this->reactionHandler = NULL; // FIXME: noop fn here
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

    public function registerReactionHandler($reactionHandler) {
        $this->reactionHandler = $reactionHandler;
    }

    public function handleMessage($payload) {
        $message = $payload['event']['text'];
        $channel_id = $payload['event']['channel'];
        $team_id = $payload['event']['team'];
        $event_type = $payload['event']['type'];
        $user_id = $payload['event']['user'];
        // Look for @mentions
        $bot_user_id = $this->oauth->getBotUserId($team_id);
        $at_mention = '<@' . $bot_user_id . '>';

        if ($event_type == "reaction_added" || $event_type == "reaction_removed") {
            $ctx = $this->reactionContext($payload);
            $fn = $this->reactionHandler;
            if ($fn != NULL) {
                $fn($this, $this->reactionContext($payload), $payload['event']);
            }
            return;
        }

        $ctx = new BotContext($channel_id, $team_id, $user_id);

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
        $token = $this->oauth->getAccessToken($ctx->getTeamId());
        if ($token != NULL) {
            return Util::sendSlackMessage($token, $ctx->getTeamId(), $ctx->getChannelId(), $message);
        }
    }

    private function reactionContext($payload) {
        $channel_id = $payload['event']['item']['channel'];
        $user_id = $payload['event']['user'];
        $team_id = $payload['team_id'];
        return new BotContext($channel_id, $team_id, $user_id);
    }

}
?>
