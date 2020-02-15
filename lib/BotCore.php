<?php
class BotCore {

    function __construct() {
        $this->commandRegex = [];
    }

    function registerCommand($regex, $fn) {
        $this->commandRegex[] = [$regex, $fn];
    }

}
?>
