<?php
require_once 'db.php';

header('Content-Type: application/json');

$db = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all fenolizzazioni
    $result = $db->query('SELECT f.*, s.nome as segnalatore FROM fenolizzazioni f LEFT JOIN segnalatori s ON f.segnalatore_id = s.id');
    $fenolizzazioni = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $fenolizzazioni[] = $row;
    }
    echo json_encode($fenolizzazioni);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new fenolizzazione
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare('INSERT INTO fenolizzazioni (nome, cognome, data, tempo, tampone, recidiva, segnalatore_id) 
                          VALUES (:nome, :cognome, :data, :tempo, :tampone, :recidiva, :segnalatore_id)');
    $stmt->bindValue(':nome', $data['nome'], SQLITE3_TEXT);
    $stmt->bindValue(':cognome', $data['cognome'], SQLITE3_TEXT);
    $stmt->bindValue(':data', $data['data'], SQLITE3_TEXT);
    $stmt->bindValue(':tempo', $data['tempo'], SQLITE3_INTEGER);
    $stmt->bindValue(':tampone', $data['tampone'], SQLITE3_TEXT);
    $stmt->bindValue(':recidiva', $data['recidiva'], SQLITE3_TEXT);
    $stmt->bindValue(':segnalatore_id', $data['segnalatore_id'], SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Fenolizzazione aggiunta con successo!']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update an existing fenolizzazione
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare('UPDATE fenolizzazioni SET nome = :nome, cognome = :cognome, data = :data, tempo = :tempo, tampone = :tampone, recidiva = :recidiva, segnalatore_id = :segnalatore_id WHERE id = :id');
    $stmt->bindValue(':id', $data['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':nome', $data['nome'], SQLITE3_TEXT);
    $stmt->bindValue(':cognome', $data['cognome'], SQLITE3_TEXT);
    $stmt->bindValue(':data', $data['data'], SQLITE3_TEXT);
    $stmt->bindValue(':tempo', $data['tempo'], SQLITE3_INTEGER);
    $stmt->bindValue(':tampone', $data['tampone'], SQLITE3_TEXT);
    $stmt->bindValue(':recidiva', $data['recidiva'], SQLITE3_TEXT);
    $stmt->bindValue(':segnalatore_id', $data['segnalatore_id'], SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Fenolizzazione aggiornata con successo!']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete a fenolizzazione
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare('DELETE FROM fenolizzazioni WHERE id = :id');
    $stmt->bindValue(':id', $data['id'], SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Fenolizzazione eliminata con successo!']);
}
?>