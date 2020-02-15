<?php
class BotCore {

    function __construct() {
        $this->commandRegex = [];
    }

    function registerRegex($regex, $fn) {
        $this->commandRegex[] = [$regex, $fn];
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
