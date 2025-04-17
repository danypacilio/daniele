<?php
// Script per eliminare un paziente e tutti i suoi esami e file immagine associati.
include '../admin/auth_check.php'; // Assicura che l'utente sia loggato

$paziente_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$paziente_id) {
    // ID non valido o mancante, torna alla lista con errore
    header('Location: ../admin/pazienti.php?error=delete_invalid_id');
    exit;
}

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Inizia una transazione per assicurare che tutto venga eseguito o nulla
    $db->beginTransaction();

    // 1. Trova tutti gli esami del paziente per recuperare i nomi dei file immagine
    $stmtEsami = $db->prepare("SELECT id, foto1, foto2, foto3, foto4 FROM esami WHERE paziente_id = ?");
    $stmtEsami->execute([$paziente_id]);
    $esamiDaEliminare = $stmtEsami->fetchAll(PDO::FETCH_ASSOC);

    // 2. Elimina i file immagine associati dal filesystem
    foreach ($esamiDaEliminare as $esame) {
        foreach (['foto1', 'foto2', 'foto3', 'foto4'] as $fotoKey) {
            $filename = $esame[$fotoKey];
            if (!empty($filename)) {
                $filepath = __DIR__ . '/../foto/' . $filename;
                if (file_exists($filepath)) {
                    unlink($filepath); // Elimina il file fisico
                }
            }
        }
    }

    // 3. Elimina tutti gli esami del paziente dal database
    // (Potrebbe essere già fatto da ON DELETE CASCADE se impostato correttamente)
    // Lo facciamo esplicitamente per sicurezza.
    $stmtDelEsami = $db->prepare("DELETE FROM esami WHERE paziente_id = ?");
    $stmtDelEsami->execute([$paziente_id]);

    // 4. Elimina il paziente dal database
    $stmtDelPaziente = $db->prepare("DELETE FROM pazienti WHERE id = ?");
    $stmtDelPaziente->execute([$paziente_id]);

    // Se tutto è andato a buon fine, conferma la transazione
    $db->commit();

    // Reindirizza alla lista pazienti con messaggio di successo
    header('Location: ../admin/pazienti.php?success=delete');
    exit;

} catch (PDOException $e) {
    // Se c'è un errore, annulla la transazione
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Errore eliminazione paziente DB (ID: $paziente_id): " . $e->getMessage());
    // Reindirizza con errore generico
    header('Location: ../admin/pazienti.php?error=delete_db');
    exit;
} catch (Exception $e) {
     // Altri errori (es. unlink)
     if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Errore generale eliminazione paziente (ID: $paziente_id): " . $e->getMessage());
    header('Location: ../admin/pazienti.php?error=delete_general');
    exit;
}
?>