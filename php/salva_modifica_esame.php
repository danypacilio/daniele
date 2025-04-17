<?php
// Script per SALVARE un nuovo esame o MODIFICARE uno esistente - CON DEBUG IMMAGINI

// --- DEBUGGING ATTIVO ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // Non mostrare errori a schermo per sicurezza
ini_set('log_errors', 1); // Abilita log degli errori
ini_set('error_log', __DIR__ . '/../php_error.log'); // Salva errori in un file nella root /screening/
// -------------------------

header('Content-Type: application/json');
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// --- Validazione Dati Input ---
if (!$data || !isset($data['paziente_id']) || !filter_var($data['paziente_id'], FILTER_VALIDATE_INT)) {
    error_log("Salvataggio fallito: ID paziente mancante o non valido. Dati ricevuti: " . $input);
    echo json_encode(['success' => false, 'error' => 'ID paziente mancante o non valido.']);
    exit;
}
if (!isset($data['data_esame']) || !strtotime($data['data_esame'])) {
     error_log("Salvataggio fallito: Data esame mancante o non valida per paziente ID " . $data['paziente_id']);
    echo json_encode(['success' => false, 'error' => 'Data esame mancante o non valida.']);
    exit;
}

$paziente_id = intval($data['paziente_id']);
$esame_id = isset($data['esame_id']) ? filter_var($data['esame_id'], FILTER_VALIDATE_INT) : null;
$data_esame_db = date('Y-m-d', strtotime($data['data_esame']));
$is_update = ($esame_id !== null);

// --- Funzione Salvataggio Immagini con Logging Dettagliato ---
function salvaImmagineBase64($base64String, $prefix) {
    $logPrefix = "[salvaImmagineBase64 - " . $prefix . "] ";

    if (empty($base64String)) {
        error_log($logPrefix . "Stringa base64 vuota, nessuna immagine da salvare.");
        return ['success' => true, 'filename' => null]; // Nessuna nuova immagine
    }
     if (strpos($base64String, 'data:image/jpeg;base64,') !== 0) {
        error_log($logPrefix . "Formato base64 non valido (manca header JPEG). Stringa ricevuta (inizio): " . substr($base64String, 0, 50));
        // Potrebbe essere un URL di un'immagine esistente se in modalità modifica? Per ora lo trattiamo come errore.
         // In futuro potresti voler gestire il caso in cui NON è base64 ma un URL esistente.
        // return ['success' => true, 'filename' => null]; // Consideralo come "nessuna nuova immagine"
         return ['success' => false, 'error' => 'Formato immagine non valido (atteso JPEG base64)']; // Segnala errore
    }

    // Rimuovi l'intestazione
    $imageData = base64_decode(str_replace('data:image/jpeg;base64,', '', $base64String));
    if ($imageData === false) {
         error_log($logPrefix . "Errore durante base64_decode.");
        return ['success' => false, 'error' => 'Errore decodifica base64'];
    }

    $filename = $prefix . '_' . uniqid() . '.jpg';
    $filepath = __DIR__ . '/../foto/' . $filename;
    $fotoDir = dirname($filepath);
    error_log($logPrefix . "Tentativo salvataggio in: " . $filepath);

    // Controllo esistenza e permessi cartella FOTO
    if (!is_dir($fotoDir)) {
        error_log($logPrefix . "La cartella foto NON ESISTE: " . $fotoDir . ". Tento di crearla.");
        if (!mkdir($fotoDir, 0775, true)) { // Crea ricorsivamente con permessi 775
             $errorMsg = "Impossibile creare la cartella foto: " . $fotoDir;
             error_log($logPrefix . $errorMsg);
             return ['success' => false, 'error' => $errorMsg];
        }
         error_log($logPrefix . "Cartella foto creata con successo.");
    }

     if (!is_writable($fotoDir)) {
         $errorMsg = "La cartella foto NON è SCRIVIBILE: " . $fotoDir . ". Controlla i permessi del server!";
         error_log($logPrefix . $errorMsg);
        return ['success' => false, 'error' => $errorMsg];
    } else {
         error_log($logPrefix . "Cartella foto (" . $fotoDir . ") risulta scrivibile.");
    }


    // Tenta di scrivere il file
    if (file_put_contents($filepath, $imageData)) {
         error_log($logPrefix . "File immagine salvato con successo: " . $filename);
        return ['success' => true, 'filename' => $filename];
    } else {
         $errorMsg = "Impossibile scrivere il file immagine: " . $filepath . ". Errore file_put_contents.";
         error_log($logPrefix . $errorMsg);
        return ['success' => false, 'error' => $errorMsg];
    }
}

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Controlla esistenza paziente
    $stmtCheckP = $db->prepare("SELECT COUNT(*) FROM pazienti WHERE id = ?");
    $stmtCheckP->execute([$paziente_id]);
    if ($stmtCheckP->fetchColumn() == 0) { throw new Exception('Paziente (ID: '.$paziente_id.') non trovato nel database.'); }
    error_log("Paziente ID " . $paziente_id . " trovato.");

    // --- Gestione Immagini ---
    error_log("Inizio gestione immagini per paziente ID " . $paziente_id . ($is_update ? " (UPDATE Esame ID: ".$esame_id.")" : " (INSERT Nuovo Esame)"));
    $foto_filenames = [];
    $existing_fotos = [];

    if ($is_update) {
        $stmtFotos = $db->prepare("SELECT foto1, foto2, foto3, foto4 FROM esami WHERE id = ?");
        $stmtFotos->execute([$esame_id]);
        $existing_fotos = $stmtFotos->fetch() ?: [];
        error_log("Foto esistenti per esame ID " . $esame_id . ": " . json_encode($existing_fotos));
    }

    $allImagesOk = true;
    $imageErrors = [];
    for ($i = 1; $i <= 4; $i++) {
        $key = 'foto' . $i;
        // Prova a salvare l'immagine solo se $data[$key] contiene dati base64 NUOVI
        $result = salvaImmagineBase64($data[$key] ?? null, "f{$i}_" . $paziente_id);

        if (!$result['success']) {
            $allImagesOk = false;
            $imageErrors[] = "Foto {$i}: " . $result['error'];
            $foto_filenames[$key] = $existing_fotos[$key] ?? null; // Mantieni vecchia foto se salvataggio fallisce
             error_log("FALLITO salvataggio Immagine {$i}. Errore: " . $result['error']);
        } else {
            // Se salvataggio riuscito O se non c'era nuova immagine (filename=null)
            $foto_filenames[$key] = $result['filename'] ?? ($existing_fotos[$key] ?? null);

             // Elimina la vecchia foto SOLO se ne è stata caricata una nuova con successo
             if ($result['filename'] && isset($existing_fotos[$key]) && $existing_fotos[$key] !== $result['filename']) {
                 $oldFilePath = __DIR__ . '/../foto/' . $existing_fotos[$key];
                 if (file_exists($oldFilePath)) {
                     if(unlink($oldFilePath)){
                         error_log("Vecchia immagine eliminata: " . $existing_fotos[$key]);
                     } else {
                          error_log("ATTENZIONE: Impossibile eliminare vecchia immagine: " . $existing_fotos[$key]);
                     }
                 }
             }
        }
    }

     // Se c'è stato un errore grave nel salvataggio di *qualsiasi* nuova immagine, blocca l'operazione
     if (!$allImagesOk) {
         throw new Exception("Errore durante il salvataggio di una o più immagini:\n- " . implode("\n- ", $imageErrors));
     }
     error_log("Gestione immagini completata. Nomi file finali: " . json_encode($foto_filenames));


    // --- Preparazione Parametri Database ---
    $params = [ /* ... come prima ... */ ];
     // (Assicurati che questa parte sia identica a quella che ti ho fornito prima)
     $params = [
        ':pid' => $paziente_id,
        ':data' => $data_esame_db,
        ':f1' => $foto_filenames['foto1'],
        ':f2' => $foto_filenames['foto2'],
        ':f3' => $foto_filenames['foto3'],
        ':f4' => $foto_filenames['foto4'],
        ':note' => $data['notePodografia'] ?? null,
        ':altre' => $data['altrePatologie'] ?? null,
        ':osservazioni' => $data['osservazioni'] ?? null,
        ':obiettivo' => json_encode($data['obiettivo'] ?? []),
        ':iper' => json_encode($data['ipercheratosi'] ?? []),
        ':vib' => json_encode($data['sensibilita_vibratoria'] ?? new stdClass()),
        ':dn4' => json_encode($data['dn4_risposte'] ?? new stdClass()),
        ':pgic' => json_encode($data['pgic_risposte'] ?? new stdClass()),
        ':screening' => json_encode($data['screening'] ?? []),
        ':dn4score' => filter_var($data['dn4_punteggio'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]),
        ':nrs' => filter_var($data['nrs_dolore'] ?? null, FILTER_VALIDATE_INT),
        ':pgicscore' => filter_var($data['pgic_totale'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]),
    ];
     if ($params[':nrs'] === false || $params[':nrs'] === null) { $params[':nrs'] = null; }

     error_log("Parametri pronti per DB: " . json_encode(array_keys($params))); // Log solo le chiavi per brevità


    // --- Esecuzione Query (INSERT o UPDATE) ---
    if ($is_update) {
        $params[':esame_id'] = $esame_id;
        $sql = "UPDATE esami SET
            paziente_id = :pid, data_esame = :data, foto1 = :f1, foto2 = :f2, foto3 = :f3, foto4 = :f4,
            note_podografia = :note, esame_obiettivo = :obiettivo, ipercheratosi = :iper,
            altre_patologie = :altre, sensibilita_vibratoria = :vib, osservazioni = :osservazioni,
            dn4_risposte = :dn4, dn4_punteggio = :dn4score, nrs_dolore = :nrs,
            pgic_risposte = :pgic, pgic_totale = :pgicscore, screening_prossimo = :screening
            WHERE id = :esame_id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $message = 'Esame modificato con successo.';
         error_log("UPDATE Esame ID " . $esame_id . " eseguito.");
    } else {
        $sql = "INSERT INTO esami ( /* ... colonne ... */ ) VALUES ( /* ... placeholders ... */ )";
         // (Assicurati che la query INSERT sia corretta come nel file precedente)
         $sql = "INSERT INTO esami (
            paziente_id, data_esame, foto1, foto2, foto3, foto4,
            note_podografia, esame_obiettivo, ipercheratosi, altre_patologie,
            sensibilita_vibratoria, osservazioni, dn4_risposte, dn4_punteggio,
            nrs_dolore, pgic_risposte, pgic_totale, screening_prossimo
          ) VALUES (
            :pid, :data, :f1, :f2, :f3, :f4, :note, :obiettivo, :iper, :altre,
            :vib, :osservazioni, :dn4, :dn4score, :nrs, :pgic, :pgicscore, :screening
          )";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $message = 'Esame salvato con successo.';
        error_log("INSERT Nuovo Esame per Paziente ID " . $paziente_id . " eseguito.");
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    $errorMsg = 'Errore database: ' . $e->getMessage();
    error_log("ERRORE PDO salvataggio/modifica esame: " . $errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]); // Mostra errore DB per debug
} catch (Exception $e) {
     $errorMsg = 'Errore generico: ' . $e->getMessage();
     error_log("ERRORE Generale salvataggio/modifica esame: " . $errorMsg);
     echo json_encode(['success' => false, 'error' => $errorMsg]);
}
?>