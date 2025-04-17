<?php
// _widget_stats.php
// Recupera le statistiche di base dal database.
// Questo file viene incluso in index.php, quindi auth_check.php è già stato eseguito.
// La variabile $db (connessione PDO) dovrebbe essere già disponibile se la includi dopo la connessione.
// Per sicurezza, riconnettiamoci qui.

$stats = [
    'total_patients' => 0,
    'total_exams' => 0,
];

try {
    // Assicurati che la connessione $db sia disponibile o ricreala
     if (!isset($db) || !($db instanceof PDO)) {
         // Se $db non è definita o non è un oggetto PDO, prova a connetterti
         $db_stats = new PDO("sqlite:../pazienti.sqlite");
         $db_stats->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $db_stats->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
         // echo "<!-- Connessione DB per stats creata -->"; // Debug
     } else {
         // Se $db è già definita dall'include precedente (es. da index.php)
         $db_stats = $db;
          // echo "<!-- Connessione DB per stats ereditata -->"; // Debug
     }


    // Conta i pazienti
    $stmtPatients = $db_stats->query("SELECT COUNT(id) as total FROM pazienti");
    $resultPatients = $stmtPatients->fetch();
    if ($resultPatients) {
        $stats['total_patients'] = $resultPatients['total'];
    }

    // Conta gli esami
    $stmtExams = $db_stats->query("SELECT COUNT(id) as total FROM esami");
    $resultExams = $stmtExams->fetch();
     if ($resultExams) {
        $stats['total_exams'] = $resultExams['total'];
    }

} catch (PDOException $e) {
    error_log("Errore widget statistiche DB: " . $e->getMessage());
    // Non mostrare errori all'utente qui, semplicemente i contatori rimarranno a 0
} catch (Exception $e) {
     error_log("Errore generale widget statistiche: " . $e->getMessage());
}

// La variabile $stats è ora disponibile per essere usata nel file che include questo.
?>