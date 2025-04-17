<?php
// Script per duplicare un esame esistente e reindirizzare alla modifica del nuovo esame.
include '../admin/auth_check.php'; // Assicura che l'utente sia loggato

$esame_id_originale = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$paziente_info = null; // Per nome e cognome nel redirect

if (!$esame_id_originale) {
    header('Location: ../admin/index.php?error=duplicate_exam_invalid_id');
    exit;
}

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. Recupera i dati dell'esame originale
    $stmtOrig = $db->prepare("SELECT * FROM esami WHERE id = ?");
    $stmtOrig->execute([$esame_id_originale]);
    $esame_originale = $stmtOrig->fetch();

    if (!$esame_originale) {
        header('Location: ../admin/pazienti.php?error=exam_to_duplicate_not_found');
        exit;
    }

    $paziente_id = $esame_originale['paziente_id'];

    // Recupera nome e cognome del paziente per l'URL di redirect
    $stmtPaziente = $db->prepare("SELECT nome, cognome FROM pazienti WHERE id = ?");
    $stmtPaziente->execute([$paziente_id]);
    $paziente_info = $stmtPaziente->fetch();
    if (!$paziente_info) {
         // Se il paziente non esiste più, c'è un problema di integrità, torna indietro
         header('Location: ../admin/esami.php?pid=' . $paziente_id . '&error=patient_not_found_for_exam');
         exit;
    }


    // 2. Prepara i dati per il nuovo esame
    $nuovi_dati = $esame_originale;
    unset($nuovi_dati['id']);
    $nuovi_dati['data_esame'] = date('Y-m-d'); // Data di oggi

    // 3. Prepara la query SQL per l'inserimento
    $colonne = implode(', ', array_keys($nuovi_dati));
    $placeholders = ':' . implode(', :', array_keys($nuovi_dati));
    $sql = "INSERT INTO esami ($colonne) VALUES ($placeholders)";
    $stmtInsert = $db->prepare($sql);

    // 4. Esegui l'inserimento
    $stmtInsert->execute($nuovi_dati);

    // 5. Recupera l'ID del NUOVO esame appena inserito
    $nuovo_esame_id = $db->lastInsertId();

    // 6. Reindirizza alla pagina esame.html per la MODIFICA del nuovo esame
    $redirect_url = sprintf(
        "../esame.html?id=%d&paziente_id=%d&nome=%s&cognome=%s&duplicated=1", // Aggiunto paziente_id e flag duplicated
        $nuovo_esame_id,
        $paziente_id,
        urlencode($paziente_info['nome']),
        urlencode($paziente_info['cognome'])
    );
    header('Location: ' . $redirect_url);
    exit;

} catch (PDOException $e) {
    error_log("Errore duplicazione esame DB (Orig ID: $esame_id_originale): " . $e->getMessage());
    $redirect_url = isset($paziente_id) ? '../admin/esami.php?pid=' . $paziente_id . '&error=duplicate_exam_db' : '../admin/pazienti.php?error=duplicate_exam_db';
    header('Location: ' . $redirect_url);
    exit;
} catch (Exception $e) {
    error_log("Errore generale duplicazione esame (Orig ID: $esame_id_originale): " . $e->getMessage());
     $redirect_url = isset($paziente_id) ? '../admin/esami.php?pid=' . $paziente_id . '&error=duplicate_exam_general' : '../admin/pazienti.php?error=duplicate_exam_general';
    header('Location: ' . $redirect_url);
    exit;
}
?>