<?php
class BotContext {

    public function __construct($channelID, $teamID) {
        $this->channelID = $channelID;
        $this->teamID = $teamID;
    }

    public function getTeamID() {
        return $this->teamID;
    }

    public function getChannelID() {
        return $this->channelID;
    }

}
?>
