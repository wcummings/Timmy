<?php
require_once('DbCore.php');

class Cache extends DbCore {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function setValue($key, $value) {
        $this->executeQueryWithParameters('INSERT OR REPLACE INTO cache (key, value) VALUES (:key, :value)', [
            'key' => $key,
            'value' => $value
        ]);
    }

    public function getValue($key) {
        $result = $this->executeQueryWithParameters('SELECT value FROM cache WHERE key = :key', ['key' => $key]);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if (!$row) {
            return NULL;
        }
        return $row['value'];
    }

    public function cachedJson($key, $fn) {
        $value = $this->getValue($key);
        if ($value) {
            return json_decode($value, TRUE);
        }

        $value = $fn();

        $this->setValue($key, json_encode($value));
        return $value;
    }

}
