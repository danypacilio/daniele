<?php
// backup_automatico.php
// Script da eseguire via CRON per creare un backup ZIP settimanale.

// --- SICUREZZA BASE ---
// Impedisce l'accesso diretto via browser (opzionale ma consigliato)
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_secret'])) { // Puoi aggiungere un secret se vuoi triggerarlo via URL
     // die('Accesso non consentito.');
     // Per ora lo lasciamo eseguibile per test, ma in produzione sarebbe meglio bloccarlo.
}

// --- Configurazione Percorsi ---
// Percorsi ASSOLUTI sono più affidabili per CRON
$baseDir = dirname(__DIR__); // Directory /screening/
$dbFile = $baseDir . '/pazienti.sqlite';
$fotoDir = $baseDir . '/foto';
$backupDir = $baseDir . '/backup_settimanali'; // Nuova cartella per i backup
$zipFilenameBase = 'backup_screening_auto_';

// --- Log File (Utile per CRON) ---
$logFile = $baseDir . '/backup_cron.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

logMessage("--- Avvio Backup Automatico ---");

// --- Verifica Preliminare ---
if (!extension_loaded('zip')) {
    logMessage("ERRORE: Estensione PHP 'zip' non caricata.");
    exit;
}
if (!file_exists($dbFile)) {
    logMessage("ERRORE: File database non trovato: $dbFile");
    exit;
}
if (!is_dir($fotoDir)) {
    logMessage("WARNING: Cartella foto non trovata: $fotoDir. Creo backup solo del DB.");
    // Non usciamo, creiamo backup solo DB
} elseif (!is_readable($fotoDir)) {
     logMessage("ERRORE: Cartella foto non leggibile: $fotoDir");
     exit;
}

// --- Crea la Cartella Backup se non Esiste ---
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0775, true)) { // Permessi 775 per sicurezza
        logMessage("ERRORE: Impossibile creare la cartella backup: $backupDir");
        exit;
    }
    logMessage("Cartella backup creata: $backupDir");
     // Aggiungi un file .htaccess per protezione (opzionale ma consigliato)
     file_put_contents($backupDir . '/.htaccess', "Options -Indexes\nDeny from all\n");
     logMessage("File .htaccess di protezione aggiunto alla cartella backup.");

}
if (!is_writable($backupDir)) {
     logMessage("ERRORE: La cartella backup non è scrivibile: $backupDir");
     exit;
}

// --- Creazione Archivio ZIP ---
$zipFilename = $zipFilenameBase . date('Y-m-d') . '.zip'; // Nome file con solo data
$zipFilePath = $backupDir . '/' . $zipFilename; // Salva direttamente nella cartella backup

logMessage("Creazione archivio ZIP: $zipFilePath");
$zip = new ZipArchive();

if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    logMessage("ERRORE: Impossibile creare l'archivio ZIP: $zipFilePath");
    exit;
}

// Aggiungi database
if ($zip->addFile($dbFile, 'pazienti.sqlite')) {
    logMessage("File database aggiunto allo ZIP.");
} else {
     logMessage("ERRORE: Impossibile aggiungere il database allo ZIP.");
     // Continua comunque per provare a salvare le foto
}

// Aggiungi foto (se la cartella esiste)
if (is_dir($fotoDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fotoDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $fotoAggiunte = 0;
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = 'foto/' . substr($filePath, strlen($fotoDir) + 1);
            if ($zip->addFile($filePath, $relativePath)) {
                $fotoAggiunte++;
            } else {
                 logMessage("WARNING: Impossibile aggiungere file foto: $relativePath");
            }
        }
    }
     // Aggiungi directory vuota se non c'erano foto
     if ($fotoAggiunte === 0) {
         $zip->addEmptyDir('foto');
     }
    logMessage("Aggiunte $fotoAggiunte immagini allo ZIP.");
} else {
     logMessage("Cartella foto non presente, backup solo del database.");
      $zip->addEmptyDir('foto'); // Aggiunge la cartella vuota per coerenza
}

// Finalizza lo ZIP
if ($zip->close()) {
    logMessage("Archivio ZIP creato e finalizzato con successo.");
    // Opzionale: Eliminare backup vecchi (es. più vecchi di 4 settimane)
    // ... logica per trovare e cancellare file .zip vecchi in $backupDir ...
} else {
    logMessage("ERRORE: Impossibile finalizzare l'archivio ZIP.");
    if (file_exists($zipFilePath)) { unlink($zipFilePath); } // Tenta di pulire
}

logMessage("--- Fine Backup Automatico ---");
exit; // Termina lo script
?>