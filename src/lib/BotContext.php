<?php
class BotContext {

    public function __construct($channelID, $teamID, $userID) {
        $this->channelID = $channelID;
        $this->teamID = $teamID;
        $this->userID = $userID;
    }

    public function getTeamID() {
        return $this->teamID;
    }

    public function getChannelID() {
        return $this->channelID;
    }

    public function getUserID() {
        return $this->userID;
    }

}
?>
