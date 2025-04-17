<?php
// Script per salvare i dati dell'esame inviati da script.js

// Non serve controllo login qui perché l'accesso a esame.html
// dovrebbe già avvenire da un link generato dall'area admin protetta.
// Se vuoi maggiore sicurezza, puoi aggiungere un token o usare la sessione.

header('Content-Type: application/json'); // Risponde sempre in JSON

// Legge il corpo della richiesta JSON inviato da fetch()
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validazione dati essenziali
if (!$data || !isset($data['paziente_id']) || !filter_var($data['paziente_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'error' => 'ID paziente mancante o non valido.']);
    exit;
}
if (!isset($data['data_esame']) || !strtotime($data['data_esame'])) {
    echo json_encode(['success' => false, 'error' => 'Data esame mancante o non valida.']);
    exit;
}

$paziente_id = intval($data['paziente_id']);
$data_esame_db = date('Y-m-d', strtotime($data['data_esame'])); // Formato YYYY-MM-DD

// Funzione helper per salvare le immagini base64 come file JPG nella cartella /foto/
function salvaImmagineBase64($base64String, $prefix) {
    if (empty($base64String) || strpos($base64String, 'data:image/jpeg;base64,') !== 0) {
        return null; // Stringa vuota o non valida
    }

    // Rimuovi l'intestazione 'data:image/jpeg;base64,'
    $imageData = base64_decode(str_replace('data:image/jpeg;base64,', '', $base64String));
    if ($imageData === false) {
        return null; // Errore decodifica
    }

    $filename = $prefix . '_' . uniqid() . '.jpg';
    $filepath = __DIR__ . '/../foto/' . $filename; // Percorso relativo dalla cartella /php/

    // Assicurati che la cartella /foto/ esista e sia scrivibile
    $fotoDir = dirname($filepath);
    if (!is_dir($fotoDir)) {
        if (!mkdir($fotoDir, 0775, true)) {
             error_log("Errore: impossibile creare la cartella foto: " . $fotoDir);
             return null; // Non posso creare la cartella
        }
    }
     if (!is_writable($fotoDir)) {
        error_log("Errore: la cartella foto non è scrivibile: " . $fotoDir);
        return null; // Cartella non scrivibile
    }


    if (file_put_contents($filepath, $imageData)) {
        return $filename; // Restituisce solo il nome del file
    } else {
        error_log("Errore: impossibile scrivere il file immagine: " . $filepath);
        return null; // Errore scrittura file
    }
}

// Salva le 4 immagini
$foto1_filename = salvaImmagineBase64($data['foto1'] ?? null, 'f1_' . $paziente_id);
$foto2_filename = salvaImmagineBase64($data['foto2'] ?? null, 'f2_' . $paziente_id);
$foto3_filename = salvaImmagineBase64($data['foto3'] ?? null, 'f3_' . $paziente_id);
$foto4_filename = salvaImmagineBase64($data['foto4'] ?? null, 'f4_' . $paziente_id);

// Prepara i dati per il database
$params = [
    ':pid' => $paziente_id,
    ':data' => $data_esame_db,
    // Immagini (nomi file o NULL)
    ':f1' => $foto1_filename,
    ':f2' => $foto2_filename,
    ':f3' => $foto3_filename,
    ':f4' => $foto4_filename,
    // Testi
    ':note' => $data['notePodografia'] ?? null,
    ':altre' => $data['altrePatologie'] ?? null,
    ':osservazioni' => $data['osservazioni'] ?? null,
    // JSON
    ':obiettivo' => json_encode($data['obiettivo'] ?? []),
    ':iper' => json_encode($data['ipercheratosi'] ?? []),
    ':vib' => json_encode($data['sensibilita_vibratoria'] ?? new stdClass()), // Usa oggetto vuoto se non presente
    ':dn4' => json_encode($data['dn4_risposte'] ?? new stdClass()),
    ':pgic' => json_encode($data['pgic_risposte'] ?? new stdClass()),
    ':screening' => json_encode($data['screening'] ?? []),
    // Punteggi
    ':dn4score' => filter_var($data['dn4_punteggio'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]),
    ':nrs' => filter_var($data['nrs_dolore'] ?? null, FILTER_VALIDATE_INT), // Può essere null
    ':pgicscore' => filter_var($data['pgic_totale'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]),
];

// Controlla se nrs è null e imposta il parametro correttamente per PDO
if ($params[':nrs'] === false || $params[':nrs'] === null) { // filter_var può restituire false
    $params[':nrs'] = null;
}


try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Controlla se il paziente esiste (opzionale ma buona pratica)
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pazienti WHERE id = ?");
    $stmtCheck->execute([$paziente_id]);
    if ($stmtCheck->fetchColumn() == 0) {
         echo json_encode(['success' => false, 'error' => 'ID paziente non trovato nel database.']);
         exit;
    }


    // Query SQL per inserire l'esame
    $sql = "INSERT INTO esami (
        paziente_id, data_esame,
        foto1, foto2, foto3, foto4,
        note_podografia, esame_obiettivo, ipercheratosi,
        altre_patologie, sensibilita_vibratoria,
        osservazioni, dn4_risposte, dn4_punteggio,
        nrs_dolore, pgic_risposte, pgic_totale,
        screening_prossimo
      ) VALUES (
        :pid, :data,
        :f1, :f2, :f3, :f4,
        :note, :obiettivo, :iper,
        :altre, :vib,
        :osservazioni, :dn4, :dn4score,
        :nrs, :pgic, :pgicscore,
        :screening
      )";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Risposta di successo
    echo json_encode(['success' => true, 'message' => 'Esame salvato con successo.']);

} catch (PDOException $e) {
    error_log("Errore salvataggio esame DB: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Errore interno del server durante il salvataggio.']);
} catch (Exception $e) {
     error_log("Errore generale salvataggio esame: " . $e->getMessage());
     echo json_encode(['success' => false, 'error' => 'Errore imprevisto durante il salvataggio.']);
}
?>