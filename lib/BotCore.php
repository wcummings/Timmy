<?php
class BotCore {

    function __construct() {
        $this->commandRegex = [];
        $this->botContext = [];
    }

    function registerRegex($regex, $fn) {
        $this->commandRegex[] = [$regex, $fn];
    }

    function setValue($key, $value) {
        $this->botContext[$key] = $value;
    }
    
    function getValue($key) {
        return $this->botContext[$key];
    }

    function handleMessage($message) {
        foreach ($this->commandRegex as $regexFnPair) {
            $regex = $regexFnPair[0];
            $fn = $regexFnPair[1];
            if (preg_match($regex, $message, $matches)) {
                return $fn($this, $matches);
            }
        }
    }

    function respond($message) {
        echo json_encode(['text' => $message]);
    }

}
?>
