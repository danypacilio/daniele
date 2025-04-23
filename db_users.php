<?php
function getUsersDbConnection() {
    $db = new SQLite3(__DIR__ . '/users.sqlite');
    return $db;
}
?>