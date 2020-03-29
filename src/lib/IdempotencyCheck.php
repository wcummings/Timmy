<?php
require_once('DbCore.php');

class IdempotencyCheck extends DbCore {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function checkEventId($eventId) {
        return $this->withTransaction(function () use ($eventId) {
            $result = $this->executeQueryWithParameters('SELECT count(*) AS c FROM webhook_idempotency WHERE event_id = :event_id', [
                'event_id' => $eventId
            ]);

            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row['c'] == 1) {
                return FALSE;
            }

            $this->executeQueryWithParameters('INSERT INTO webhook_idempotency (event_id) VALUES (:event_id)', [
                'event_id' => $eventId
            ]);
            
            return TRUE;
        });
    }

}
?>
