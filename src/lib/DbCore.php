<?php
class DbCore {

    function __construct($db) {
        $this->db = $db;
        $this->db->enableExceptions(true);
    }

    protected function executeQueryWithParameters($query, $params = []) {
        $statement = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(":" . $key, $value);
        }

        return $statement->execute();
    }
    
    protected function withTransaction($fn) {
        $this->db->exec('BEGIN');
        try {
            $result = $fn();
            $this->db->exec('COMMIT');
            return $result;
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }
}
?>
