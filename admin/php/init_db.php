<?php
// Script per creare/ricreare il database e le tabelle necessarie.
// Eseguire UNA SOLA VOLTA accedendo via browser.

// --- CONFIGURAZIONE ---
// Percorso relativo al file del database dalla posizione di questo script
$dbPath = __DIR__ . '/../pazienti.sqlite';
$dsn = "sqlite:" . $dbPath;
$defaultAdminUser = 'admin';
$defaultAdminPass = 'admin'; // !! CAMBIARE QUESTA PASSWORD IN PRODUZIONE !!

// --- INIZIO SCRIPT ---
echo "<!DOCTYPE html><html lang='it'><head><meta charset='UTF-8'><title>Init DB</title>";
echo "<style>body { font-family: sans-serif; padding: 20px; line-height: 1.6; } .ok { color: green; } .error { color: red; font-weight: bold; } pre { background: #eee; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; word-wrap: break-word; } </style>";
echo "</head><body>";
echo "<h1>Inizializzazione Database Screening</h1>";
echo "<p>Database Target: <code>" . htmlspecialchars($dbPath) . "</code></p>";

try {
    // Assicurati che la directory esista
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        if (!mkdir($dbDir, 0775, true)) {
            throw new Exception("Impossibile creare la directory: " . $dbDir);
        }
        echo "<p class='ok'>Directory creata: " . htmlspecialchars($dbDir) . "</p>";
    }

    // Connessione/Creazione Database
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p class='ok'>Connessione al database riuscita.</p>";

    echo "<h2>Creazione Tabelle</h2>";

    // --- Tabella Pazienti ---
    echo "<p>Tentativo creazione tabella 'pazienti'...";
    $db->exec("DROP TABLE IF EXISTS pazienti;"); // Rimuove la tabella se esiste già
    $db->exec("CREATE TABLE pazienti (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL CHECK(length(nome) > 0),
        cognome TEXT NOT NULL CHECK(length(cognome) > 0),
        data_nascita TEXT -- Formato YYYY-MM-DD o vuoto
    );");
    echo " <span class='ok'>OK.</span></p>";

    // --- Tabella Esami ---
    echo "<p>Tentativo creazione tabella 'esami'...";
    $db->exec("DROP TABLE IF EXISTS esami;");
    // La definizione della tabella esami è qui, SENZA commenti interni problematici
    $db->exec("CREATE TABLE esami (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        paziente_id INTEGER NOT NULL,
        data_esame TEXT NOT NULL,
        foto1 TEXT,
        foto2 TEXT,
        foto3 TEXT,
        foto4 TEXT,
        note_podografia TEXT,
        esame_obiettivo TEXT,         -- Contiene JSON Array
        ipercheratosi TEXT,           -- Contiene JSON Array
        altre_patologie TEXT,
        sensibilita_vibratoria TEXT,  -- Contiene JSON Object
        osservazioni TEXT,
        dn4_risposte TEXT,            -- Contiene JSON Object
        dn4_punteggio INTEGER,
        nrs_dolore INTEGER,           -- Può essere NULL se non selezionato
        pgic_risposte TEXT,           -- Contiene JSON Object
        pgic_totale INTEGER,
        screening_prossimo TEXT,      -- Contiene JSON Array
        FOREIGN KEY (paziente_id) REFERENCES pazienti(id) ON DELETE CASCADE
    );");
    echo " <span class='ok'>OK.</span></p>";

    // --- Tabella Utenti Admin ---
    echo "<p>Tentativo creazione tabella 'utenti'...";
    $db->exec("DROP TABLE IF EXISTS utenti;");
    $db->exec("CREATE TABLE utenti (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL CHECK(length(username) > 0),
        password TEXT NOT NULL CHECK(length(password) > 0) -- Hash
    );");
    echo " <span class='ok'>OK.</span></p>";

    // --- Inserimento Utente Admin di Default ---
    echo "<p>Controllo/Inserimento utente admin di default ('" . htmlspecialchars($defaultAdminUser) . "')...";
    $hashedPass = password_hash($defaultAdminPass, PASSWORD_DEFAULT);

    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM utenti WHERE username = ?");
    $stmtCheck->execute([$defaultAdminUser]);
    $userExists = $stmtCheck->fetchColumn();

    if ($userExists == 0) {
        $stmtInsert = $db->prepare("INSERT INTO utenti (username, password) VALUES (?, ?)");
        $stmtInsert->execute([$defaultAdminUser, $hashedPass]);
        echo " <span class='ok'>Utente '" . htmlspecialchars($defaultAdminUser) . "' inserito con password '" . htmlspecialchars($defaultAdminPass) . "'. <strong>CAMBIA LA PASSWORD!</strong></span></p>";
    } else {
        echo " <span class='ok'>Utente '" . htmlspecialchars($defaultAdminUser) . "' già presente.</span></p>";
    }

    // Imposta permessi sul file database (se possibile)
    if (chmod($dbPath, 0664)) {
         echo "<p class='ok'>Permessi del file database impostati a 0664.</p>";
    } else {
         echo "<p class='error'>Attenzione: Impossibile impostare i permessi del file database. Potrebbero esserci problemi di scrittura.</p>";
    }


    echo "<hr><h2 class='ok'>✅ Inizializzazione completata con successo!</h2>";
    echo "<p style='color:red; font-weight:bold;'>RICORDA DI CANCELLARE QUESTO FILE ('init_db.php') DAL SERVER ORA!</p>";
    echo "<p><a href='../admin/login.php'>Vai alla pagina di Login</a></p>";

} catch (PDOException $e) {
    echo "<hr><h2 class='error'>❌ ERRORE DATABASE DURANTE L'INIZIALIZZAZIONE:</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p class='error'><strong>Verifica che la cartella '/screening/' esista e sia scrivibile dal server web (PHP).</strong></p>";
} catch (Exception $e) {
     echo "<hr><h2 class='error'>❌ ERRORE GENERICO DURANTE L'INIZIALIZZAZIONE:</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "</body></html>";
?>