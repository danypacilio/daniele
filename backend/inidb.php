<?php
// Imposta il percorso del database SQLite
$dbPath = __DIR__ . '/../db/database.sqlite';

// Controlla se il file del database esiste già
if (file_exists($dbPath)) {
    echo "Il database esiste già. Nessuna azione necessaria.";
    exit;
}

// Crea una nuova connessione al database SQLite
try {
    $db = new SQLite3($dbPath);
    echo "Database creato con successo.<br>";
} catch (Exception $e) {
    die("Errore nella creazione del database: " . $e->getMessage());
}

// Script SQL per creare le tabelle
$sql = "
CREATE TABLE segnalatori (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL
);

CREATE TABLE fenolizzazioni (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    cognome TEXT NOT NULL,
    data TEXT NOT NULL,
    tempo INTEGER NOT NULL,
    tampone TEXT NOT NULL,
    recidiva TEXT,
    segnalatore_id INTEGER,
    FOREIGN KEY (segnalatore_id) REFERENCES segnalatori(id)
);
";

// Esegui lo script SQL
try {
    $db->exec($sql);
    echo "Tabelle create con successo.";
} catch (Exception $e) {
    die("Errore nella creazione delle tabelle: " . $e->getMessage());
}
?>