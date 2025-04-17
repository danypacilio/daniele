<?php
$page_title = 'Ricerca Esami per Data';
include '_header_admin.php'; // Include header comune e auth_check

$data_selezionata = null;
$esami_trovati = [];
$error_message = '';

// Controlla se è stata inviata una data tramite GET
if (isset($_GET['data_ricerca']) && !empty($_GET['data_ricerca'])) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ricerca'])) {
        $data_selezionata = $_GET['data_ricerca'];
        try {
             if (!isset($db) || !($db instanceof PDO)) { $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); }
             $sql = "SELECT e.id as esame_id, e.data_esame, p.id as paziente_id, p.nome as paziente_nome, p.cognome as paziente_cognome FROM esami e JOIN pazienti p ON e.paziente_id = p.id WHERE date(e.data_esame) = date(:data_sel) ORDER BY time(e.data_esame) ASC, p.cognome, p.nome"; // Ordina prima per ora
             $stmt = $db->prepare($sql); $stmt->execute([':data_sel' => $data_selezionata]); $esami_trovati = $stmt->fetchAll();
        } catch (PDOException $e) { $error_message = "Errore database."; error_log("Ricerca data errore: " . $e->getMessage()); }
    } else { $error_message = "Formato data non valido."; }
}
?>

<!-- Stile specifico per il form di ricerca data -->
<style>
    .search-date-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 15px; /* Spazio tra elementi */
        padding: 20px;
        background-color: var(--medium-gray);
        border-radius: var(--border-radius);
        margin-bottom: 30px;
    }
    .search-date-form label {
        margin: 0; /* Rimuovi margini label */
        font-weight: 600;
        color: var(--text-medium);
    }
    .search-date-form input[type="date"] {
        padding: 9px 12px;
        border: 1px solid #ccc;
        border-radius: var(--border-radius);
        font-size: 1em;
        /* Non impostare width 100% qui */
        min-width: 160px; /* Larghezza minima per data */
    }
    .search-date-form button {
         padding: 9px 18px;
         font-size: 1em;
    }
    .search-date-form .new-search-link {
        font-size: 0.9em;
        color: var(--text-medium);
        margin-left: auto; /* Spinge a destra */
    }
     .search-date-form .new-search-link:hover {
         color: var(--primary-color);
     }
     .results-title { /* Stile per H3 risultati */
        font-size: 1.5em;
        color: var(--text-dark);
        text-align: center;
        margin-bottom: 25px;
     }

</style>

<div class="admin-container">
    <div class="page-title">
        <h1><i class="bi bi-calendar-week" style="margin-right: 10px;"></i> Ricerca Esami per Data</h1>
        <div class="page-controls">
             <a href="index.php" class="button button-secondary">← Torna alla Dashboard</a>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <!-- Form di Ricerca Data con Stile Aggiornato -->
    <form action="cerca_esami_data.php" method="GET" class="search-date-form">
        <label for="data_ricerca">Seleziona la data:</label>
        <input type="date" id="data_ricerca" name="data_ricerca" value="<?php echo htmlspecialchars($data_selezionata ?? date('Y-m-d')); ?>" required>
        <button type="submit" class="button button-info"><i class="bi bi-search"></i> Cerca Esami</button>
        <?php if ($data_selezionata): ?>
             <a href="cerca_esami_data.php" class="new-search-link">Nuova Ricerca</a>
        <?php endif; ?>
    </form>

    <!-- Risultati della Ricerca -->
    <?php if ($data_selezionata): ?>
        <h3 class="results-title">Esami del <?php echo htmlspecialchars(date('d/m/Y', strtotime($data_selezionata))); ?></h3>

        <?php if (empty($esami_trovati)): ?>
            <p class="no-data">Nessun esame trovato per questa data.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ora</th>
                            <th>Paziente</th>
                            <th style="text-align: right; padding-right: 15px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($esami_trovati as $esame): ?>
                            <tr>
                                <td> <?php echo htmlspecialchars(date('H:i', strtotime($esame['data_esame']))); ?> </td>
                                <td>
                                    <a href="visualizza_esame_clean.php?id=<?php echo $esame['esame_id']; ?>" target="_blank" title="Apri dettagli esame in nuova scheda">
                                        <?php echo htmlspecialchars($esame['paziente_cognome'] . ' ' . $esame['paziente_nome']); ?>
                                    </a>
                                    <small style="display: block; color: var(--text-light);">(ID Paziente: <?php echo $esame['paziente_id']; ?>)</small> <!-- Mostra ID Paziente qui per riferimento -->
                                </td>
                                <td class="actions">
                                     <a href="visualizza_esame_clean.php?id=<?php echo $esame['esame_id']; ?>" target="_blank" class="button button-info view-exam" title="Apri in Nuova Scheda"><i class="bi bi-box-arrow-up-right"></i> Apri</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align:center; font-size:0.9em; color: var(--text-light);">Trovati <?php echo count($esami_trovati); ?> esami.</p>
        <?php endif; ?>
    <?php endif; ?>

</div>
