<?php
// Script per eliminare un singolo esame e i suoi file immagine associati.
include '../admin/auth_check.php'; // Assicura che l'utente sia loggato

$esame_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$paziente_id_redirect = null; // Per sapere a quale pagina esami tornare

if (!$esame_id) {
    // ID esame non valido o mancante. Dove reindirizzo? Prova alla dashboard.
    header('Location: ../admin/index.php?error=delete_exam_invalid_id');
    exit;
}

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Inizia transazione
    $db->beginTransaction();

    // 1. Trova l'esame per ottenere i nomi dei file e l'ID del paziente per il redirect
    $stmtEsame = $db->prepare("SELECT paziente_id, foto1, foto2, foto3, foto4 FROM esami WHERE id = ?");
    $stmtEsame->execute([$esame_id]);
    $esame = $stmtEsame->fetch(PDO::FETCH_ASSOC);

    if (!$esame) {
        // Esame non trovato, annulla e reindirizza
        $db->rollBack();
        header('Location: ../admin/pazienti.php?error=exam_not_found'); // Torna alla lista pazienti generica
        exit;
    }

    $paziente_id_redirect = $esame['paziente_id']; // Salva l'ID per il redirect finale

    // 2. Elimina i file immagine associati
    foreach (['foto1', 'foto2', 'foto3', 'foto4'] as $fotoKey) {
        $filename = $esame[$fotoKey];
        if (!empty($filename)) {
            $filepath = __DIR__ . '/../foto/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    // 3. Elimina l'esame dal database
    $stmtDelEsame = $db->prepare("DELETE FROM esami WHERE id = ?");
    $stmtDelEsame->execute([$esame_id]);

    // Conferma transazione
    $db->commit();

    // Reindirizza alla pagina degli esami del paziente specifico
    header('Location: ../admin/esami.php?pid=' . $paziente_id_redirect . '&success=delete_exam');
    exit;

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Errore eliminazione esame DB (ID: $esame_id): " . $e->getMessage());
    // Reindirizza alla pagina esami del paziente se possibile, altrimenti alla lista pazienti
    $redirect_url = $paziente_id_redirect ? '../admin/esami.php?pid=' . $paziente_id_redirect . '&error=delete_exam_db' : '../admin/pazienti.php?error=delete_exam_db';
    header('Location: ' . $redirect_url);
    exit;
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Errore generale eliminazione esame (ID: $esame_id): " . $e->getMessage());
    $redirect_url = $paziente_id_redirect ? '../admin/esami.php?pid=' . $paziente_id_redirect . '&error=delete_exam_general' : '../admin/pazienti.php?error=delete_exam_general';
    header('Location: ' . $redirect_url);
    exit;
}
?>