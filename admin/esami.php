<?php
$page_title = 'Elenco Esami'; // Titolo default, aggiornato dopo caricamento paziente
include '_header_admin.php'; // Include header e auth check

$paziente_id = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
$paziente = null;
$esami = [];
$error_message = '';
$success_message = '';

// Messaggi da redirect
if(isset($_GET['success'])) { if($_GET['success'] === 'duplicate_exam') $success_message = 'Esame duplicato. Puoi modificarlo ora.'; if($_GET['success'] === 'delete_exam') $success_message = 'Esame eliminato.'; }
if(isset($_GET['error'])) { if($_GET['error'] === 'duplicate_exam_db') $error_message = 'Errore DB duplicazione.'; if($_GET['error'] === 'delete_exam_db') $error_message = 'Errore DB eliminazione.'; if($_GET['error'] === 'exam_to_duplicate_not_found') $error_message = 'Esame originale non trovato.'; if($_GET['error'] === 'patient_not_found_for_exam') $error_message = 'Paziente associato non trovato.'; if($_GET['error'] === 'missing_pid') $error_message = 'ID paziente mancante.'; if($_GET['error'] === 'patient_not_found') $error_message = 'Paziente non trovato.'; }

if (!$paziente_id && empty($error_message)) { $error_message = "ID paziente mancante o non valido."; }
elseif($paziente_id) {
    try {
        if (!isset($db) || !($db instanceof PDO)) { $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); }
        $stmtPaziente = $db->prepare("SELECT id, nome, cognome FROM pazienti WHERE id = ?"); $stmtPaziente->execute([$paziente_id]); $paziente = $stmtPaziente->fetch();
        if (!$paziente) { $error_message = "Paziente non trovato (ID: $paziente_id)."; }
        else {
            $page_title = 'Esami di ' . htmlspecialchars($paziente['cognome'] . ' ' . $paziente['nome']); // Aggiorna titolo
            $stmtEsami = $db->prepare("SELECT id, data_esame, dn4_punteggio, nrs_dolore, pgic_totale FROM esami WHERE paziente_id = ? ORDER BY data_esame DESC");
            $stmtEsami->execute([$paziente_id]); $esami = $stmtEsami->fetchAll();
        }
    } catch (PDOException $e) { $error_message = "Errore database."; error_log("Errore esami.php: ".$e->getMessage()); }
}
?>

<div class="admin-container">
    <?php if (!empty($error_message)): ?>
         <div class="page-title"><h1>Errore</h1></div> <!-- Titolo per pagina errore -->
        <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <a href="pazienti.php" class="button button-secondary">← Torna ai Pazienti</a>
    <?php elseif ($paziente): ?>
        <div class="page-title">
            <h1><i class="bi bi-journal-text" style="margin-right: 10px;"></i> Esami di: <?php echo htmlspecialchars($paziente['cognome'] . ' ' . $paziente['nome']); ?></h1>
            <div class="page-controls">
                <a href="pazienti.php" class="button button-secondary">← Torna ai Pazienti</a>
                <a href="../esame.html?paziente_id=<?php echo $paziente['id']; ?>&nome=<?php echo urlencode($paziente['nome']); ?>&cognome=<?php echo urlencode($paziente['cognome']); ?>" class="button button-success"><i class="bi bi-file-earmark-plus-fill"></i> Nuovo Esame</a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?> <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p> <?php endif; ?>

        <?php if (empty($esami)): ?>
            <p class="no-data">Nessun esame registrato per questo paziente.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Data Esame</th>
                            <th>DN4</th>
                            <th>NRS</th>
                            <th>PGIC</th>
                            <th style="text-align: right; padding-right: 15px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($esami as $e): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($e['data_esame']))); // Mostra anche ora ?></td>
                                <td><?php echo $e['dn4_punteggio'] ?? 'N/D'; ?>/10</td>
                                <td><?php echo isset($e['nrs_dolore']) && $e['nrs_dolore'] !== null ? $e['nrs_dolore'] : 'N/D'; ?></td>
                                <td><?php echo $e['pgic_totale'] ?? 'N/D'; ?>/28</td>
                                <td class="actions">
                                    <!-- Bottoni con classi e icone V10 -->
                                    <a href="visualizza_esame.php?id=<?php echo $e['id']; ?>" class="button button-info view-exam" title="Visualizza Dettagli"><i class="bi bi-eye-fill"></i> Visualizza</a>
                                    <a href="../php/duplica_esame.php?id=<?php echo $e['id']; ?>" class="button button-warning duplicate-exam" title="Duplica Esame" onclick="return confirm('Duplicare questo esame? Potrai modificarlo.');"><i class="bi bi-files"></i> Duplica</a>
                                    <a href="../esame.html?id=<?php echo $e['id']; ?>&paziente_id=<?php echo $paziente['id']; ?>&nome=<?php echo urlencode($paziente['nome']); ?>&cognome=<?php echo urlencode($paziente['cognome']); ?>" class="button edit-exam" style="background-color: #fd7e14;" title="Modifica Esame"><i class="bi bi-pencil-square"></i> Modifica</a>
                                    <a href="../php/elimina_esame.php?id=<?php echo $e['id']; ?>" class="button button-danger delete-exam" title="Elimina Esame" onclick="return confirm('Eliminare questo esame? Azione irreversibile.');"><i class="bi bi-trash3-fill"></i> Elimina</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
         <!-- Fallback se $paziente è null ma non c'era errore -->
         <p class="error">Impossibile caricare i dati.</p>
         <a href="pazienti.php" class="button button-secondary">Torna ai Pazienti</a>
    <?php endif; ?>
</div> <!-- Fine admin-container -->
