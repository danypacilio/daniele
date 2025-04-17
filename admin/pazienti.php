<?php
$page_title = 'Elenco Pazienti';
include '_header_admin.php'; // Include header comune

// --- Configurazione Paginazione ---
$results_per_page = 15;

// --- Gestione Ricerca ---
$search_term = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING));
$search_sql_where = ''; // Clausola WHERE completa
$search_params = [];    // Parametri per la query
if (!empty($search_term)) {
    $search_sql_where = "WHERE (lower(nome) LIKE lower(:search) OR lower(cognome) LIKE lower(:search) OR lower(nome || ' ' || cognome) LIKE lower(:search) OR lower(cognome || ' ' || nome) LIKE lower(:search))";
    $search_params[':search'] = '%' . $search_term . '%';
}

// --- Logica Paginazione ---
$total_results = 0; $total_pages = 0; $pazienti = []; $current_page = 1;
try {
     if (!isset($db) || !($db instanceof PDO)) { $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); }
    $count_sql = "SELECT COUNT(id) as total FROM pazienti " . $search_sql_where;
    $stmtCount = $db->prepare($count_sql); $stmtCount->execute($search_params); $total_results = $stmtCount->fetchColumn();
    if ($total_results > 0) {
        $total_pages = ceil($total_results / $results_per_page);
        $current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        if ($current_page > $total_pages) { $current_page = $total_pages; } if ($current_page < 1) { $current_page = 1; }
        $offset = ($current_page - 1) * $results_per_page;
        $data_sql = "SELECT id, nome, cognome, data_nascita FROM pazienti " . $search_sql_where . " ORDER BY cognome, nome LIMIT :limit OFFSET :offset";
        $stmtData = $db->prepare($data_sql);
        foreach ($search_params as $key => $value) { $stmtData->bindValue($key, $value, PDO::PARAM_STR); }
        $stmtData->bindValue(':limit', $results_per_page, PDO::PARAM_INT); $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtData->execute(); $pazienti = $stmtData->fetchAll();
    }
} catch (PDOException $e) { $error_message_db = "Errore database."; error_log("Errore pazienti.php: ".$e->getMessage()); }
?>

<!-- Stili aggiuntivi specifici per questa pagina -->
<style>
    .search-form-container {
        padding: 15px 20px;
        background-color: var(--medium-gray);
        border-radius: var(--border-radius);
        margin-bottom: 30px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 15px;
    }
    .search-form-container input[type="text"] {
        padding: 9px 12px;
        border: 1px solid #bbb;
        border-radius: var(--border-radius);
        font-size: 0.95em;
        flex-grow: 1; /* Occupa spazio disponibile */
        min-width: 200px;
    }
    .search-form-container button {
        padding: 9px 18px;
        font-size: 0.95em;
        white-space: nowrap;
    }
    .search-form-container a {
        font-size: 0.9em;
        color: var(--text-medium);
        margin-left: 10px; /* Spazio per link "Mostra tutti" */
    }
</style>

<div class="admin-container">
    <div class="page-title">
        <h1><i class="bi bi-people-fill" style="margin-right: 10px;"></i> Gestione Pazienti</h1>
        <div class="page-controls">
            <a href="nuovo_paziente.php" class="button button-success"><i class="bi bi-person-plus-fill"></i> Aggiungi Paziente</a>
        </div>
    </div>

     <!-- Form di Ricerca con stile migliorato -->
     <form action="pazienti.php" method="GET" class="search-form-container">
        <input type="text" name="search" placeholder="Cerca per Nome o Cognome..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" class="button button-primary"><i class="bi bi-search"></i> Cerca</button>
        <?php if($search_term): ?>
            <a href="pazienti.php">Mostra Tutti</a>
        <?php endif; ?>
     </form>

    <!-- Messaggi Successo/Errore -->
    <?php if (isset($_GET['success'])): ?> <p class="success-message"> <?php if($_GET['success']=='add') echo 'Paziente aggiunto con successo.'; elseif($_GET['success']=='edit') echo 'Paziente modificato con successo.'; elseif($_GET['success']=='delete') echo 'Paziente eliminato con successo.'; ?> </p> <?php endif; ?>
    <?php if (isset($_GET['error'])): ?> <p class="error"> Si è verificato un errore. <?php /* Aggiungere dettagli errore se necessario */ ?> </p> <?php endif; ?>
    <?php if (isset($error_message_db)): ?> <p class="error">Errore nel caricamento dei dati dal database.</p> <?php endif; ?>


    <?php if (empty($pazienti) && !$error_message_db): ?>
        <p class="no-data">Nessun paziente <?php echo $search_term ? 'trovato per i criteri di ricerca.' : 'registrato.'; ?></p>
    <?php elseif (!empty($pazienti)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>Data Nascita</th>
                        <th style="text-align: right; padding-right: 15px;">Azioni</th> <!-- Padding a destra per azioni -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pazienti as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['id']); ?></td>
                            <td><?php echo htmlspecialchars($p['cognome']); ?></td>
                            <td><?php echo htmlspecialchars($p['nome']); ?></td>
                            <td><?php echo $p['data_nascita'] ? htmlspecialchars(date('d/m/Y', strtotime($p['data_nascita']))) : 'N/D'; ?></td>
                            <td class="actions"> <!-- Assicura allineamento a destra ereditato o specifico -->
                                <!-- Bottoni con classi corrette e icone -->
                                <a href="esami.php?pid=<?php echo $p['id']; ?>" class="button button-info view-exam" title="Visualizza Esami"><i class="bi bi-journal-text"></i> Esami</a>
                                <a href="modifica_paziente.php?id=<?php echo $p['id']; ?>" class="button edit-exam" style="background-color:#fd7e14;" title="Modifica Paziente"><i class="bi bi-pencil-square"></i> Modifica</a>
                                <!-- Bottone Elimina con classe corretta e onclick -->
 <a href="../php/elimina_paziente.php?id=<?php echo $p['id']; ?>"
    class="button delete-patient"
    title="Elimina Paziente"
    style="background-color: #dc3545 !important;" /* FORZA COLORE ROSSO */
    onclick="return confirm('Sei SICURO di voler eliminare questo paziente?');">
    🗑️ Elimina
 </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione -->
         <?php if ($total_pages > 1): ?>
             <div class="pagination"> <?php if ($current_page > 1): ?> <a href="?page=<?php echo $current_page - 1; echo $search_term ? '&search='.urlencode($search_term) : ''; ?>">&laquo; Prec</a> <?php else: ?> <span class="disabled">&laquo; Prec</span> <?php endif; ?> <?php $range = 2; for ($i = 1; $i <= $total_pages; $i++): if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)): if ($i == $current_page): ?> <span class="current-page"><?php echo $i; ?></span> <?php else: ?> <a href="?page=<?php echo $i; echo $search_term ? '&search='.urlencode($search_term) : ''; ?>"><?php echo $i; ?></a> <?php endif; elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1): ?> <span class="disabled">...</span> <?php endif; endfor; ?> <?php if ($current_page < $total_pages): ?> <a href="?page=<?php echo $current_page + 1; echo $search_term ? '&search='.urlencode($search_term) : ''; ?>">Succ &raquo;</a> <?php else: ?> <span class="disabled">Succ &raquo;</span> <?php endif; ?> </div>
             <p class="pagination-info">Pagina <?php echo $current_page; ?> di <?php echo $total_pages; ?> (Totale: <?php echo $total_results; ?> pazienti<?php echo $search_term ? ' corrispondenti' : ''; ?>)</p>
         <?php endif; ?>

    <?php endif; ?>

</div> <!-- Fine admin-container -->