<?php
require_once 'db_users.php'; // Connessione al database utenti

// Dettagli dell'utente admin
$username = "danypacilio@gmail.com";
$password = "Amicidellapesca84!";
$role = "admin";

try {
    // Connessione al database
    $db = getUsersDbConnection();

    // Controlla se l'utente esiste già
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if ($result['count'] > 0) {
        echo "L'utente admin esiste già!";
        exit;
    }

    // Inserisci il nuovo utente admin
    $stmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT); // Hash della password
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->execute();

    echo "Utente admin aggiunto con successo!";
} catch (Exception $e) {
    die("Errore durante l'aggiunta dell'utente admin: " . $e->getMessage());
}
?>