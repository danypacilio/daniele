<?php
require_once 'db.php';

header('Content-Type: application/json');

$db = getDbConnection();

// Calculate statistics
$totaleInterventi = $db->querySingle('SELECT COUNT(*) FROM fenolizzazioni');
$totaleRecidive = $db->querySingle('SELECT COUNT(*) FROM fenolizzazioni WHERE recidiva IS NOT NULL AND recidiva != ""');
$percentualeRecidive = $totaleInterventi > 0 ? ($totaleRecidive / $totaleInterventi) * 100 : 0;
$interventiSenzaRecidive = $totaleInterventi - $totaleRecidive;

// Recidive per tipo di tampone
$recidivePerTampone = [];
$result = $db->query('SELECT tampone, COUNT(*) as recidive FROM fenolizzazioni WHERE recidiva IS NOT NULL AND recidiva != "" GROUP BY tampone');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recidivePerTampone[] = $row;
}

// Interventi per segnalatore
$interventiPerSegnalatore = [];
$result = $db->query('SELECT s.nome as segnalatore, COUNT(f.id) as interventi, 
                             (COUNT(f.id) * 100.0 / (SELECT COUNT(*) FROM fenolizzazioni)) as percentuale
                      FROM fenolizzazioni f
                      INNER JOIN segnalatori s ON f.segnalatore_id = s.id
                      GROUP BY s.id');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $interventiPerSegnalatore[] = $row;
}

// Return JSON
echo json_encode([
    'totaleInterventi' => $totaleInterventi,
    'totaleRecidive' => $totaleRecidive,
    'percentualeRecidive' => round($percentualeRecidive, 2),
    'interventiSenzaRecidive' => $interventiSenzaRecidive,
    'recidivePerTampone' => $recidivePerTampone,
    'interventiPerSegnalatore' => $interventiPerSegnalatore
]);
?>