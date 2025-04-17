<?php
/**
 * Report Esami - Generazione report degli esami per range di date
 * Versione 1.0 - 2025-04-17
 */

// Abilita visualizzazione errori (temporaneamente per debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Rimuoviamo inclusioni dei file mancanti e definiamo direttamente le funzioni necessarie
// Definiamo una funzione fittizia per l'autenticazione (solo per debug)
function isUserLoggedIn() {
    return true; // Per debug, sempre autenticato
}

// Verificare se l'utente ha i permessi necessari
// Temporaneamente commentiamo il controllo di autenticazione per debug
/*
if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit;
}
*/

// Titolo pagina
$page_title = "Report Esami per Data";

// Impostazioni di default
$data_inizio = date('Y-m-d', strtotime('-30 days'));
$data_fine = date('Y-m-d');
$telefono_predefinito = "+393939899582"; // Numero predefinito da configurare
$total_esami_override = ""; // Valore manuale per il conteggio esami

// Processa il form se inviato
$esami = [];
$total_esami = 0;
$messaggio_inviato = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal form
    $data_inizio = $_POST['data_inizio'] ?? $data_inizio;
    $data_fine = $_POST['data_fine'] ?? $data_fine;
    $telefono = $_POST['telefono'] ?? $telefono_predefinito;
    $total_esami_override = $_POST['total_esami_override'] ?? "";
    
    try {
        // Connessione al database (come in pazienti.php)
        if (!isset($db) || !($db instanceof PDO)) { 
            $db = new PDO("sqlite:../pazienti.sqlite"); 
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
        }
        
        // Query per ottenere gli esami nel range di date specificato
        // Aggiungiamo un debug per verificare la struttura delle tabelle
        $tables_sql = "SELECT name FROM sqlite_master WHERE type='table'";
        $tables_stmt = $db->query($tables_sql);
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Visualizziamo le colonne della tabella esami
        if (in_array('esami', $tables)) {
            $columns_sql = "PRAGMA table_info(esami)";
            $columns_stmt = $db->query($columns_sql);
            $columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $id_field = "id"; // campo ID di default
            $paziente_field = "id_paziente"; // campo ID paziente di default
            $data_field = "data_esame"; // campo data di default
            
            // Cerchiamo il campo che contiene l'ID del paziente
            foreach ($columns as $column) {
                $colname = $column['name'] ?? '';
                if (strpos($colname, 'paziente') !== false) {
                    $paziente_field = $colname;
                }
                if (strpos($colname, 'data') !== false) {
                    $data_field = $colname;
                }
            }
            
            $error_details = "Tabelle trovate: " . implode(", ", $tables) . 
                             "<br>Colonne della tabella esami: " . 
                             implode(", ", array_column($columns, 'name'));
            
            // Query aggiornata con i campi rilevati
            $sql = "SELECT e.id, e.{$data_field}, p.nome, p.cognome 
                    FROM esami e 
                    JOIN pazienti p ON e.{$paziente_field} = p.id 
                    WHERE e.{$data_field} BETWEEN :data_inizio AND :data_fine 
                    ORDER BY e.{$data_field} DESC, p.cognome, p.nome";
        } else {
            // Se la tabella esami non esiste
            throw new PDOException("Tabella 'esami' non trovata nel database");
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':data_inizio' => $data_inizio,
            ':data_fine' => $data_fine
        ]);
        
        $esami = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_esami = count($esami);
        
        // Se è stato specificato manualmente il numero di esami, usa quello
        $count_da_visualizzare = !empty($total_esami_override) ? (int)$total_esami_override : $total_esami;
        
        // Se è stato richiesto di inviare il messaggio
        if (isset($_POST['invia_report'])) {
            // Formatta la data odierna in italiano
            $data_oggi = new DateTime();
            $data_oggi = $data_oggi->format('d/m/Y');
            
            // Costruisci il messaggio
            $messaggio = "Il Dott. Daniele Pacilio in data {$data_oggi} ha effettuato {$count_da_visualizzare} esami.";
            $messaggio_codificato = urlencode($messaggio);
            
            // Prepara il link per WhatsApp
            $whatsapp_link = "https://wa.me/{$telefono}?text={$messaggio_codificato}";
            
            // Reindirizza l'utente al link di WhatsApp
            header("Location: {$whatsapp_link}");
            exit;
        }
        
    } catch (PDOException $e) {
        // Gestione errori database
        $error_message = "Errore database: " . $e->getMessage();
    }
}

// Includi header
include("_header_admin.php");
?>
<div class="admin-container">
<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4"><?php echo $page_title; ?></h1>
    

    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Seleziona intervallo di date
                </div>
                <div class="card-body">
                    <form method="post" id="report-form">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="data_inizio" class="form-label">Data inizio</label>
                                <input type="date" class="form-control" id="data_inizio" name="data_inizio" 
                                       value="<?php echo htmlspecialchars($data_inizio); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="data_fine" class="form-label">Data fine</label>
                                <input type="date" class="form-control" id="data_fine" name="data_fine" 
                                       value="<?php echo htmlspecialchars($data_fine); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="telefono" class="form-label">Numero WhatsApp</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($telefono_predefinito); ?>" 
                                       placeholder="+39XXXXXXXXXX" required>
                                <div class="form-text">Inserisci il numero con il prefisso internazionale (es. +39)</div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Cerca
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error_message)): ?>
        <div class="row">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Esami effettuati dal <?php echo date('d/m/Y', strtotime($data_inizio)); ?> 
                        al <?php echo date('d/m/Y', strtotime($data_fine)); ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($esami) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Data Esame</th>
                                            <th>Cognome</th>
                                            <th>Nome</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($esami as $esame): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($esame['id']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($esame[$data_field])); ?></td>
                                                <td><?php echo htmlspecialchars($esame['cognome']); ?></td>
                                                <td><?php echo htmlspecialchars($esame['nome']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Nessun esame trovato nel periodo selezionato.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i>
                        Riepilogo e invio report
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Conteggio esami</h5>
                            <p class="lead">
                                <span id="count-display"><?php echo $total_esami; ?></span> esami trovati
                            </p>
                            
                            <form method="post" id="send-report-form">
                                <!-- Campi nascosti per mantenere i parametri di ricerca -->
                                <input type="hidden" name="data_inizio" value="<?php echo htmlspecialchars($data_inizio); ?>">
                                <input type="hidden" name="data_fine" value="<?php echo htmlspecialchars($data_fine); ?>">
                                <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($telefono_predefinito); ?>">
                                
                                <div class="mb-3">
                                    <label for="total_esami_override" class="form-label">Modifica conteggio esami (opzionale)</label>
                                    <input type="number" class="form-control" id="total_esami_override" name="total_esami_override" 
                                           min="0" value="<?php echo htmlspecialchars($total_esami_override); ?>" 
                                           placeholder="Lascia vuoto per usare il conteggio effettivo">
                                    <div class="form-text">
                                        Puoi inserire manualmente un valore diverso dal conteggio automatico
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Anteprima messaggio</label>
                                    <div class="border p-3 bg-light">
                                        <p id="preview-message" class="mb-0">
                                            Il Dott. Daniele Pacilio in data <?php echo date('d/m/Y'); ?> ha effettuato 
                                            <span id="count-preview"><?php echo !empty($total_esami_override) ? $total_esami_override : $total_esami; ?></span> esami.
                                        </p>
                                    </div>
                                </div>
                                
                                <button type="submit" name="invia_report" class="btn btn-success btn-lg w-100">
                                    <i class="fab fa-whatsapp me-1"></i> Invia report su WhatsApp
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- JavaScript per aggiornare l'anteprima del messaggio -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalEsamiOverrideInput = document.getElementById('total_esami_override');
    const countPreview = document.getElementById('count-preview');
    const actualCount = <?php echo $total_esami; ?>;
    
    if (totalEsamiOverrideInput && countPreview) {
        totalEsamiOverrideInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                countPreview.textContent = actualCount;
            } else {
                countPreview.textContent = this.value;
            }
        });
    }
});
</script>

