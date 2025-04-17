<?php
// Script per recuperare i dati di un singolo esame per la modifica/visualizzazione

header('Content-Type: application/json');
// Non serve auth_check qui di solito, l'accesso a esame.html è già controllato
// Se vuoi più sicurezza, aggiungilo: include '../admin/auth_check.php';

$esame_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$esame_id) {
    echo json_encode(['error' => 'ID esame mancante o non valido.']);
    exit;
}

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT * FROM esami WHERE id = ?");
    $stmt->execute([$esame_id]);
    $esame = $stmt->fetch();

    if (!$esame) {
        echo json_encode(['error' => 'Esame non trovato.']);
        exit;
    }

    // Decodifica i campi JSON prima di inviarli
    $esame['esame_obiettivo'] = json_decode($esame['esame_obiettivo'] ?? '[]', true);
    $esame['ipercheratosi'] = json_decode($esame['ipercheratosi'] ?? '[]', true);
    $esame['sensibilita_vibratoria'] = json_decode($esame['sensibilita_vibratoria'] ?? '{}', true);
    $esame['dn4_risposte'] = json_decode($esame['dn4_risposte'] ?? '{}', true);
    $esame['pgic_risposte'] = json_decode($esame['pgic_risposte'] ?? '{}', true);
    $esame['screening_prossimo'] = json_decode($esame['screening_prossimo'] ?? '[]', true);

    // Rimuovi dati che non servono al frontend o che potrebbero essere sensibili
    // unset($esame['paziente_id']); // Forse utile tenerlo per debug?

    echo json_encode($esame); // Invia i dati JSON

} catch (PDOException $e) {
    error_log("Errore recupero dati esame DB (ID: $esame_id): " . $e->getMessage());
    echo json_encode(['error' => 'Errore database durante il recupero dei dati.']);
}
?>