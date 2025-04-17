<?php
$page_title = 'Modifica Paziente'; // Titolo default, aggiornato dopo caricamento
include '_header_admin.php'; // Include header e auth check

$paziente_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$paziente = null;
$error_message_load = '';

// Verifica ID valido e carica dati paziente
if (!$paziente_id) {
     $error_message_load = "ID paziente non valido o mancante.";
} else {
    try {
        if (!isset($db) || !($db instanceof PDO)) { $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); }
        $stmt = $db->prepare("SELECT id, nome, cognome, data_nascita FROM pazienti WHERE id = ?"); $stmt->execute([$paziente_id]); $paziente = $stmt->fetch();
        if (!$paziente) { $error_message_load = "Paziente non trovato."; }
        else { $page_title = 'Modifica: ' . htmlspecialchars($paziente['cognome'] . ' ' . $paziente['nome']); }
    } catch (PDOException $e) { $error_message_load = "Errore database."; error_log("Errore modifica_paziente GET DB: ".$e->getMessage()); }
}
// La logica POST per salvare le modifiche è gestita da /php/modifica_paziente.php
?>

<div class="admin-container">
     <div class="page-title">
        <h1><i class="bi bi-pencil-square" style="margin-right: 10px;"></i> <?php echo $page_title; // Titolo dinamico ?></h1>
         <div class="page-controls">
             <a href="pazienti.php" class="button button-secondary">← Annulla e Torna all'Elenco</a>
         </div>
    </div>

    <?php if ($error_message_load): // Errore durante il caricamento iniziale ?>
        <p class="error"><?php echo htmlspecialchars($error_message_load); ?></p>
    <?php elseif ($paziente): // Se il paziente è stato caricato, mostra il form ?>
        <?php if (isset($_GET['error'])): // Mostra errori relativi a un TENTATIVO di salvataggio fallito ?>
            <p class="error">
                 <?php
                    // Potresti rendere i messaggi più specifici se php/modifica_paziente.php li passa
                    if ($_GET['error'] == 'missing_fields') echo 'Errore: Nome e cognome sono obbligatori.';
                    elseif ($_GET['error'] == 'db') echo 'Errore del database durante il salvataggio.';
                    else echo 'Si è verificato un errore durante il salvataggio.';
                ?>
            </p>
        <?php endif; ?>

        <form action="../php/modifica_paziente.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $paziente['id']; ?>">

            <div class="form-group">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($paziente['nome']); ?>" required>
            </div>

            <div class="form-group">
                <label for="cognome">Cognome *</label>
                <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($paziente['cognome']); ?>" required>
            </div>

            <div class="form-group">
                <label for="data_nascita">Data di Nascita</label>
                <input type="date" id="data_nascita" name="data_nascita" value="<?php echo htmlspecialchars($paziente['data_nascita']); // Il tipo date gestisce il formato YYYY-MM-DD ?>">
                 <small>Lasciare vuoto se non disponibile.</small>
            </div>

            <div class="form-actions">
                <!-- Usa le classi CSS V10 -->
                <button type="submit" class="button button-primary"><i class="bi bi-check-lg"></i> Salva Modifiche</button>
                <a href="pazienti.php" class="button-cancel">Annulla</a> <!-- Link stile cancelletto -->
            </div>
        </form>
    <?php else: // Caso $paziente è null senza $error_message_load ?>
         <p class="error">Impossibile caricare i dati del paziente da modificare.</p>
    <?php endif; ?>
</div>
