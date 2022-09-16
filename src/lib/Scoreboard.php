<?php
require_once('lib/DbCore.php');

class Scoreboard extends DbCore {

    const INSERT_GAME_QUERY = 'INSERT INTO games (team_id, comment) VALUES (:team_id, :comment)';
    const INSERT_LINE_ITEM_QUERY = 'INSERT INTO game_line_item (team_id, game_id, player_id, is_winner, channel_id) VALUES (:team_id, :game_id, :player_id, :is_winner, :channel_id)';
    const GET_ALL_PLAYERS_QUERY = 'SELECT * FROM players WHERE team_id=:team_id;';
    const REGISTER_PLAYER_QUERY = 'INSERT INTO players (team_id, nickname) VALUES (:team_id, :nickname)';
    const DB_FILENAME = 'timmy.db';
    const GET_SCORES_QUERY = 'SELECT nickname, total_wins, (CASE WHEN total >= 7 THEN ROUND((total_wins*1.0 / total) * 100, 2) ELSE NULL END) AS winrate FROM (SELECT player_id, SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) AS total_wins, COUNT(player_id) AS total FROM game_line_item WHERE team_id=:team_id AND channel_id=:channel_id GROUP BY player_id) JOIN players ON players.id = player_id ORDER BY total_wins DESC;';

    public function __construct($db, $teamID, $channelID) {
        $this->teamID = $teamID;
        $this->channelID = $channelID;
        parent::__construct($db);
    }

    public function getScores() {
        $result = $this->executeQueryWithParameters(
            self::GET_SCORES_QUERY,
            ['team_id' => $this->teamID, 'channel_id' => $this->channelID]);

        $nicknames = $this->getPlayerIDMap();
        $scores = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $scores[] = $row;
            unset($nicknames[$row['nickname']]);
        }

        // Insert zero's for remaining players because we can't RIGHT JOIN
        foreach ($nicknames as $key => $value) {
            $scores[] = ['nickname' => $key, 'total_wins' => 0, 'winrate' => NULL];
        }

        return $scores;
    }

    public function recordGame($playerNicknames, $winnerNicknames, $comment = "") {
        $this->withTransaction(function () use ($playerNicknames, $winnerNicknames, $comment) {
            $this->executeQueryWithParameters(self::INSERT_GAME_QUERY, [
                'team_id' => $this->teamID,
                'comment' => $comment
            ]);

            $winnerNicknames = array_map('strtolower', $winnerNickname);
            $playerNicknames = array_map('strtolower', $playerNicknames);

            $gameID = $this->db->lastInsertRowID();
            $playerIDMap = $this->getPlayerIDMap();

            foreach ($winnerNicknames as $winnerNickname) {
                if (!in_array($winnerNickname, $playerNicknames)) {
                    throw new Exception('Winner does not appear in list of players');
                }
            }

            $invalidNicknames = array_filter($playerNicknames, function ($nickname) use($playerIDMap) {
                return !array_key_exists($nickname, $playerIDMap);
            });

            if (count($invalidNicknames) > 0) {
                throw new Exception("Could not record result, the following players are not registered: "
                                    . implode($invalidNicknames, ", "));
            }

            foreach ($playerNicknames as $nickname) {
                $this->executeQueryWithParameters(self::INSERT_LINE_ITEM_QUERY, [
                    'team_id' => $this->teamID,
                    'channel_id' => $this->channelID,
                    'game_id' => $gameID,
                    'player_id' => $playerIDMap[$nickname],
                    'is_winner' => in_array($nickname, $winnerNickname)
                ]);
            }
        });
    }

    public function registerPlayer($nickname) {
        $this->executeQueryWithParameters(self::REGISTER_PLAYER_QUERY, [
            'team_id' => $this->teamID,
            'nickname' => strtolower($nickname)
        ]);
    }

    private function getPlayerIDMap() {
        $result = $this->executeQueryWithParameters(self::GET_ALL_PLAYERS_QUERY, [
            'team_id' => $this->teamID
        ]);
        $map = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $map[$row['nickname']] = $row['id'];
        }
        return $map;
    }

}
?>
