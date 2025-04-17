<?php
include 'auth_check.php'; // Controllo login

// --- Configurazione Percorsi ---
// Percorsi relativi alla posizione di questo script (che è in /admin/)
$dbFile = __DIR__ . '/../pazienti.sqlite';        // File database nella root /screening/
$fotoDir = __DIR__ . '/../foto';                  // Cartella foto nella root /screening/
$zipFilenameBase = 'backup_screening_completo_'; // Prefisso per il file zip
$tempZipDir = __DIR__ . '/../'; // Dove creare temporaneamente lo zip (nella root /screening/) - Assicurati sia scrivibile!

// --- Verifica Preliminare ---
if (!extension_loaded('zip')) {
    die("Errore: L'estensione PHP 'zip' non è abilitata sul server. Impossibile creare l'archivio.");
}
if (!file_exists($dbFile)) {
    die("Errore: File database non trovato: " . htmlspecialchars($dbFile));
}
if (!is_dir($fotoDir)) {
    // Crea la cartella foto se non esiste (utile se non ci sono ancora state immagini)
    if (!mkdir($fotoDir, 0775, true)) {
         die("Errore: La cartella delle foto non esiste e non è stato possibile crearla: " . htmlspecialchars($fotoDir));
    }
     error_log("Nota backup: La cartella foto non esisteva ed è stata creata.");
}
if (!is_readable($fotoDir)) {
     die("Errore: La cartella delle foto non è leggibile: " . htmlspecialchars($fotoDir));
}
if (!is_writable($tempZipDir)) {
     die("Errore: La cartella temporanea per lo zip non è scrivibile: " . htmlspecialchars($tempZipDir) . ". Controlla i permessi.");
}


// --- Creazione Archivio ZIP ---
$zipFilename = $zipFilenameBase . date('Y-m-d_H-i-s') . '.zip';
$zipFilePath = rtrim($tempZipDir, '/') . '/' . $zipFilename; // Percorso completo del file zip temporaneo

$zip = new ZipArchive();

// Apri il file zip per la creazione (sovrascrive se esiste già un file temporaneo con lo stesso nome)
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Errore: Impossibile creare l'archivio ZIP in: " . htmlspecialchars($zipFilePath));
}

// 1. Aggiungi il file database
// Il secondo parametro è il nome/percorso che avrà il file *all'interno* dello zip
if (!$zip->addFile($dbFile, 'pazienti.sqlite')) {
     $zip->close(); // Chiudi l'archivio anche in caso di errore parziale
     unlink($zipFilePath); // Elimina zip parziale
    die("Errore: Impossibile aggiungere il file database all'archivio.");
}

// 2. Aggiungi la cartella delle foto e il suo contenuto
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($fotoDir, RecursiveDirectoryIterator::SKIP_DOTS), // Salta . e ..
    RecursiveIteratorIterator::LEAVES_ONLY
);

$addedImage = false;
foreach ($files as $name => $file) {
    // Salta le directory (anche se LEAVES_ONLY dovrebbe già farlo)
    if (!$file->isDir()) {
        // Percorso file reale sul server
        $filePath = $file->getRealPath();
        // Percorso relativo all'interno dello zip (mantenendo la cartella 'foto')
        $relativePath = 'foto/' . substr($filePath, strlen($fotoDir) + 1); // Es: foto/nomefile.jpg

        if (!$zip->addFile($filePath, $relativePath)) {
             error_log("Backup Warning: Impossibile aggiungere il file immagine '$relativePath' all'archivio.");
             // Non blocchiamo l'intero backup per un file, ma logghiamo l'errore
        } else {
            $addedImage = true;
        }
    }
}
// Aggiungi una directory vuota 'foto' se non c'erano immagini, per mantenere la struttura
if (!$addedImage && is_dir($fotoDir)) {
     $zip->addEmptyDir('foto');
}


// 3. Finalizza l'archivio ZIP
if (!$zip->close()) {
     // Prova a eliminare il file zip se la chiusura fallisce
     if (file_exists($zipFilePath)) { unlink($zipFilePath); }
    die("Errore: Impossibile finalizzare l'archivio ZIP.");
}

// --- Invio del File ZIP per il Download ---

// Verifica che il file ZIP esista prima di inviare gli header
if (!file_exists($zipFilePath)) {
    die("Errore Critico: Il file ZIP non è stato trovato dopo la creazione: " . htmlspecialchars($zipFilePath));
}

// Pulisci l'output buffer se c'è stato qualche output accidentale prima degli header
if (ob_get_level()) {
    ob_end_clean();
}

// Imposta gli header per forzare il download
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"'); // Usa il nome generato
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($zipFilePath));
// header('Content-Transfer-Encoding: binary'); // Opzionale

// Leggi il file e invialo all'output
flush(); // Assicura che gli header vengano inviati prima del file
readfile($zipFilePath);

// --- Pulizia ---
// Elimina il file ZIP temporaneo dal server DOPO averlo inviato
unlink($zipFilePath);

exit; // Termina lo script
?>