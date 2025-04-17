<?php
// Script per gestire l'aggiunta di un nuovo paziente con controllo duplicati.
include '../admin/auth_check.php'; // Assicura che l'utente sia loggato

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/nuovo_paziente.php'); exit;
}

// Validazione input base
$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$data_nascita = trim($_POST['data_nascita'] ?? ''); // Formato YYYY-MM-DD

if (empty($nome) || empty($cognome)) {
    // Nome e cognome obbligatori
    header('Location: ../admin/nuovo_paziente.php?error=missing_fields&nome='.urlencode($nome).'&cognome='.urlencode($cognome).'&data_nascita='.urlencode($data_nascita));
    exit;
}

// Formatta la data per il DB (NULL se vuota o non valida)
$data_nascita_db = null;
if (!empty($data_nascita) && ($timestamp = strtotime($data_nascita)) !== false) {
     $data_nascita_db = date('Y-m-d', $timestamp);
} elseif (!empty($data_nascita)) {
    // Data inserita ma non valida
     header('Location: ../admin/nuovo_paziente.php?error=invalid_date&nome='.urlencode($nome).'&cognome='.urlencode($cognome).'&data_nascita='.urlencode($data_nascita));
    exit;
}


try {
    $db = new PDO("sqlite:../pazienti.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


    // --- CONTROLLO DUPLICATI ---
    // Cerca un paziente con stesso nome, cognome (case-insensitive) E stessa data di nascita
    // Gestisce correttamente anche il caso in cui la data di nascita sia NULL
    $sqlCheck = "SELECT id FROM pazienti
                 WHERE lower(nome) = lower(:nome)
                   AND lower(cognome) = lower(:cognome)
                   AND " . ($data_nascita_db === null ? "data_nascita IS NULL" : "data_nascita = :data_nascita");

    $stmtCheck = $db->prepare($sqlCheck);
    $paramsCheck = [':nome' => $nome, ':cognome' => $cognome];
    if ($data_nascita_db !== null) {
        $paramsCheck[':data_nascita'] = $data_nascita_db;
    }

    $stmtCheck->execute($paramsCheck);
    $existingPatient = $stmtCheck->fetch();

    if ($existingPatient) {
        // *** PAZIENTE DUPLICATO TROVATO ***
        $existing_id = $existingPatient['id'];
        // Reindirizza indietro al form con messaggio di errore e ID esistente
        header('Location: ../admin/nuovo_paziente.php?error=duplicate&existing_id='.$existing_id.'&nome='.urlencode($nome).'&cognome='.urlencode($cognome).'&data_nascita='.urlencode($data_nascita));
        exit;
    } else {
        // --- NESSUN DUPLICATO TROVATO: INSERISCI ---
        $stmtInsert = $db->prepare("INSERT INTO pazienti (nome, cognome, data_nascita) VALUES (:nome, :cognome, :data_nascita)");
        $stmtInsert->execute([
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':data_nascita' => $data_nascita_db // Usa la data formattata o NULL
        ]);

        // Reindirizza alla lista pazienti dopo il successo
        header('Location: ../admin/pazienti.php?success=add');
        exit;
    }

} catch (PDOException $e) {
    error_log("Errore aggiunta paziente DB: " . $e->getMessage());
    // Reindirizza con errore generico e mantiene i dati inseriti
     header('Location: ../admin/nuovo_paziente.php?error=db&nome='.urlencode($nome).'&cognome='.urlencode($cognome).'&data_nascita='.urlencode($data_nascita));
    exit;
}
?>