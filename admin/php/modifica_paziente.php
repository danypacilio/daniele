<?php
// Script per gestire la modifica di un paziente esistente dal form admin.
include '../admin/auth_check.php'; // Assicura che l'utente sia loggato

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/pazienti.php'); // Se non è POST, torna alla lista
    exit;
}

// Recupera e valida l'ID del paziente
$paziente_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$paziente_id) {
    header('Location: ../admin/pazienti.php?error=invalid_id');
    exit;
}

// Validazione altri input
$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$data_nascita = trim($_POST['data_nascita'] ?? '');

if (empty($nome) || empty($cognome)) {
    // Nome e cognome sono obbligatori
    header('Location: ../admin/modifica_paziente.php?id=' . $paziente_id . '&error=1'); // Torna al form con errore
    exit;
}

$data_nascita_db = !empty($data_nascita) ? date('Y-m-d', strtotime($data_nascita)) : null;

try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Aggiorna i dati del paziente nel database
    $stmt = $db->prepare("UPDATE pazienti SET nome = ?, cognome = ?, data_nascita = ? WHERE id = ?");
    $stmt->execute([$nome, $cognome, $data_nascita_db, $paziente_id]);

    // Reindirizza alla lista pazienti dopo successo
    header('Location: ../admin/pazienti.php?success=edit');
    exit;

} catch (PDOException $e) {
    error_log("Errore modifica paziente DB: " . $e->getMessage());
    header('Location: ../admin/modifica_paziente.php?id=' . $paziente_id . '&error=db'); // Torna al form con errore
    exit;
}
?>