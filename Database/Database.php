<?php
class Database {
//This class manages a single shared connection
//to the SQLite database
    protected static $dbInstance = null;

    public static function getInstance() {
        if (self::$dbInstance === null) {
            $dbPath = 'Database/petwatch.db';
            self::$dbInstance = new PDO('sqlite:' . $dbPath);
            self::$dbInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            self::$dbInstance->exec("PRAGMA foreign_keys = ON;");
        }
        return self::$dbInstance;
    }
}
?>
