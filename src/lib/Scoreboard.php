<?php
require_once('lib/DbCore.php');

class Scoreboard extends DbCore {

    const INSERT_GAME_QUERY = 'INSERT INTO games (comment) VALUES (:comment)';
    const INSERT_LINE_ITEM_QUERY = 'INSERT INTO game_line_item (game_id, player_id, is_winner) VALUES (:game_id, :player_id, :is_winner)';
    const GET_ALL_PLAYERS_QUERY = 'SELECT * FROM players;';
    const REGISTER_PLAYER_QUERY = 'INSERT INTO players (nickname) VALUES (:nickname)';
    const DB_FILENAME = 'timmy.db';
    const GET_SCORES_QUERY = 'SELECT nickname, total_wins, ROUND(total_wins*1.0 / total, 2) AS winrate FROM (SELECT player_id, SUM(CASE WHEN is_winner THEN 1 ELSE 0 END) AS total_wins, COUNT(player_id) AS total FROM game_line_item GROUP BY player_id) JOIN players ON players.id = player_id ORDER BY total_wins DESC;';

    public function __construct($db) {
        parent::__construct($db);
    }

    public function getScores() {
        $result = $this->db->query(self::GET_SCORES_QUERY);
        $nicknames = $this->getPlayerIDMap();
        $scores = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $scores[] = $row;
            unset($nicknames[$row['nickname']]);
        }

        // Insert zero's for remaining players because we can't RIGHT JOIN
        foreach ($nicknames as $key => $value) {
            $scores[] = ['nickname' => $key, 'total_wins' => 0, 'win_rate' => NULL];
        }

        return $scores;
    }

    public function recordGame($playerNicknames, $winnerNickname, $comment = "") {
        $this->withTransaction(function () use ($playerNicknames, $winnerNickname, $comment) {
            $this->executeQueryWithParameters(self::INSERT_GAME_QUERY, ['comment' => $comment]);

            $winnerNickname = strtolower($winnerNickname);
            $playerNicknames = array_map('strtolower', $playerNicknames);

            $gameID = $this->db->lastInsertRowID();
            $playerIDMap = $this->getPlayerIDMap();

            if (!in_array($winnerNickname, $playerNicknames)) {
                throw new Exception('Winner does not appear in list of players');
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
                    'game_id' => $gameID,
                    'player_id' => $playerIDMap[$nickname],
                    'is_winner' => $nickname === $winnerNickname
                ]);
            }
        });
    }

    public function registerPlayer($nickname) {
        $this->executeQueryWithParameters(self::REGISTER_PLAYER_QUERY, ['nickname' => strtolower($nickname)]);
    }

    private function getPlayerIDMap() {
        $result = $this->db->query(self::GET_ALL_PLAYERS_QUERY);
        $map = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $map[$row['nickname']] = $row['id'];
        }
        return $map;
    }

}
?>
