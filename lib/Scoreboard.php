<?php
class Scoreboard {

    const INSERT_GAME_QUERY = 'INSERT INTO games (comment) VALUES (:comment)';
    const INSERT_LINE_ITEM_QUERY = 'INSERT INTO game_line_item (game_id, player_id, is_winner) VALUES (:game_id, :player_id, :is_winner)';
    const GET_SCORES_QUERY = 'SELECT nickname, SUM(game_line_item.is_winner) AS total_wins FROM players LEFT JOIN game_line_item ON id = game_line_item.player_id GROUP BY player_id ORDER BY total_wins DESC;';
    const GET_ALL_PLAYERS_QUERY = 'SELECT * FROM players;';
    const REGISTER_PLAYER_QUERY = 'INSERT INTO players (nickname) VALUES (:nickname)';
    const DB_FILENAME = 'timmy.db';

    function __construct() {
        $this->db = new SQLite3(self::DB_FILENAME);
        $this->db->enableExceptions(true);
    }

    function getScores() {
        $result = $this->db->query(self::GET_SCORES_QUERY);
        $scores = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $scores[] = $row;
        }
        return $scores;
    }

    function recordGame($playerNicknames, $winnerNickname, $comment = "") {
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

    function registerPlayer($nickname) {
        $this->executeQueryWithParameters(self::REGISTER_PLAYER_QUERY, ['nickname' => strtolower($nickname)]);
    }

    private function executeQueryWithParameters($query, $params = []) {
        $statement = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(":" . $key, $value);
        }

        return $statement->execute();
    }
    
    private function withTransaction($fn) {
        $this->db->exec('BEGIN');
        try {
            $fn();
            $this->db->exec('COMMIT');
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        }
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
