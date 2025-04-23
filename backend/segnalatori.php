<?php
require_once 'db.php';

header('Content-Type: application/json');

$db = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Recupera tutti i segnalatori
    $result = $db->query('SELECT * FROM segnalatori');
    $segnalatori = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $segnalatori[] = $row;
    }
    echo json_encode($segnalatori);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aggiungi un nuovo segnalatore
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare('INSERT INTO segnalatori (nome) VALUES (:nome)');
    $stmt->bindValue(':nome', $data['nome'], SQLITE3_TEXT);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Segnalatore aggiunto con successo!']);
} else {
    // Metodo non supportato
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo non supportato']);
}
?>