<?php
// Imposta il percorso del database degli utenti
$dbPath = __DIR__ . '/users.sqlite';

// Controlla se il file del database esiste già
if (file_exists($dbPath)) {
    echo "Il database utenti esiste già. Nessuna azione necessaria.";
    exit;
}

// Crea una nuova connessione al database SQLite
try {
    $db = new SQLite3($dbPath);
    echo "Database utenti creato con successo.<br>";
} catch (Exception $e) {
    die("Errore nella creazione del database utenti: " . $e->getMessage());
}

// Script SQL per creare la tabella degli utenti
$sql = "
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL, -- 'admin' o 'user'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";

// Esegui lo script SQL
try {
    $db->exec($sql);
    echo "Tabella utenti creata con successo.";
} catch (Exception $e) {
    die("Errore nella creazione della tabella utenti: " . $e->getMessage());
}
?>