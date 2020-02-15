<?php

class Scoreboard {

    private const INSERT_GAME_QUERY = 'INSERT INTO games (comment) VALUES (:comment)';
    private const INSERT_LINE_ITEM_QUERY = 'INSERT INTO game_line_item (game_id, player_id, is_winner) VALUES (:game_id, :player_id, :is_winner)';

    function __construct() {
        $this->db = new SQLite3('scoreboard.db');
    }

    function recordGame($playerNicknames, $winnerNickname, $comment = "") {
        $this->db->exec('BEGIN');
        // TODO: wrap remaining method in try/catch and rollback on error

        $this->executeQueryWithParameters(self::INSERT_GAME_QUERY, ['comment' => $comment]);

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

        $this->db->exec('COMMIT');
    }

    private function executeQueryWithParameters($query, $params) {
        $statement = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindParam(":" . $key, $value);
        }
        return $statement->execute();
    }
    
    private function getPlayerIDMap() {
        $result = $this->db->query('SELECT * FROM players');
        $map = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $map[$row['nickname']] = $row['id'];
        }
        return $map;
    }

}

?>
