<?php
// _widget_recent_data.php
// Recupera gli ultimi pazienti aggiunti e gli ultimi esami effettuati.
// Incluso in index.php dopo la connessione DB (variabile $db_stats o $db).

$recentData = [
    'latest_patients' => [],
    'latest_exams' => []
];

try {
    // Assicurati che la connessione $db sia disponibile o ricreala
    if (!isset($db_stats) || !($db_stats instanceof PDO)) {
        if (!isset($db) || !($db instanceof PDO)) {
            $db_data = new PDO("sqlite:../pazienti.sqlite");
            $db_data->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db_data->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // echo "<!-- Connessione DB per dati recenti creata -->";
        } else {
             $db_data = $db; // Usa connessione da index.php
             // echo "<!-- Connessione DB per dati recenti ereditata -->";
        }
    } else {
         $db_data = $db_stats; // Usa connessione da _widget_stats.php
          // echo "<!-- Connessione DB per dati recenti ereditata da stats -->";
    }


    // Recupera ultimi 5 pazienti (ordinati per ID decrescente, che è AUTOINCREMENT)
    $stmtLatestPatients = $db_data->query("SELECT id, nome, cognome FROM pazienti ORDER BY id DESC LIMIT 5");
    $recentData['latest_patients'] = $stmtLatestPatients->fetchAll();

    // Recupera ultimi 5 esami con info paziente associato
    // Usiamo un JOIN per prendere nome/cognome paziente insieme all'esame
    $sqlLatestExams = "
        SELECT
            e.id as exam_id,
            e.data_esame,
            p.id as patient_id,
            p.nome as patient_nome,
            p.cognome as patient_cognome
        FROM esami e
        JOIN pazienti p ON e.paziente_id = p.id
        ORDER BY e.id DESC -- Ordina per ID esame (più recente)
        LIMIT 5
    ";
    $stmtLatestExams = $db_data->query($sqlLatestExams);
    $recentData['latest_exams'] = $stmtLatestExams->fetchAll();

} catch (PDOException $e) {
    error_log("Errore widget dati recenti DB: " . $e->getMessage());
    // Non mostrare errori, le liste rimarranno vuote
} catch (Exception $e) {
     error_log("Errore generale widget dati recenti: " . $e->getMessage());
}

// La variabile $recentData è ora disponibile.
?>