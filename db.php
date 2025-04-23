<?php
// Database connection
function getDbConnection() {
    $db = new SQLite3(__DIR__ . '/../db/database.sqlite');
    return $db;
}
?>