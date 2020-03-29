<?php
class BotCore {

    public function __construct($oauth) {
        $this->commandRegex = [];
        $this->botContext = [];
        $this->oauth = $oauth;
    }

    public function registerRegex($regex, $fn) {
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
        // TODO: Make a class to represent context
        $ctx = ['channel_id' => $channel_id];
        foreach ($this->commandRegex as $regexFnPair) {
            $regex = $regexFnPair[0];
            $fn = $regexFnPair[1];
            if (preg_match($regex, $message, $matches)) {
                return $fn($this, $ctx, $matches);
            }
        }
    }

    public function reply($ctx, $message) {
        $webhookURL = $oauth->getWebhookForChannel($ctx['channel_id']);
        Util::sendSlackMessage($webhookURL, $message);
    }

}
?>
