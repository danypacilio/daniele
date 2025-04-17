<?php
$page_title = 'Aggiungi Nuovo Paziente';
include '_header_admin.php';

$error_message = '';
$warning_message = '';
$existing_patient_id = null;

// Recupera eventuali dati reinviati dopo errore
$submitted_nome = filter_input(INPUT_GET, 'nome', FILTER_SANITIZE_STRING);
$submitted_cognome = filter_input(INPUT_GET, 'cognome', FILTER_SANITIZE_STRING);
$submitted_data_nascita = filter_input(INPUT_GET, 'data_nascita', FILTER_SANITIZE_STRING);

// Gestisci messaggi di errore/warning dal redirect
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_fields': $error_message = 'Errore: Nome e cognome sono obbligatori.'; break;
        case 'invalid_date': $error_message = 'Errore: Formato data di nascita non valido.'; break;
        case 'duplicate':
            $warning_message = '<strong>ATTENZIONE:</strong> Esiste già un paziente con questi dati.';
            $existing_patient_id = filter_input(INPUT_GET, 'existing_id', FILTER_VALIDATE_INT);
            if ($existing_patient_id) {
                 // Usiamo le classi button corrette per i link
                 $warning_message .= ' <a href="modifica_paziente.php?id='.$existing_patient_id.'" class="button button-warning" style="font-size:0.85em; padding: 4px 8px; vertical-align: middle;">Modifica Esistente?</a>';
                 $warning_message .= ' <a href="esami.php?pid='.$existing_patient_id.'" class="button button-info" style="font-size:0.85em; padding: 4px 8px; vertical-align: middle;">Vedi Esami?</a>';
            }
            $warning_message .= '<br>Verifica i dati o annulla.';
            break;
        case 'db': $error_message = 'Errore del database durante il salvataggio.'; break;
        default: $error_message = 'Errore sconosciuto.'; break;
    }
}
?>

<div class="admin-container">
    <div class="page-title">
        <h1><i class="bi bi-person-plus-fill" style="margin-right: 10px;"></i> Nuovo Paziente</h1>
        <div class="page-controls">
             <a href="pazienti.php" class="button button-secondary">← Annulla e Torna all'Elenco</a>
        </div>
    </div>

    <?php if ($error_message): ?><p class="error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>
    <?php if ($warning_message): ?><p class="warning-message"><?php echo $warning_message; // HTML è già controllato sopra ?></p><?php endif; ?>

    <form action="../php/aggiungi_paziente.php" method="POST">
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($submitted_nome ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="cognome">Cognome *</label>
            <input type="text" id="cognome" name="cognome" required value="<?php echo htmlspecialchars($submitted_cognome ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="data_nascita">Data di Nascita</label>
            <input type="date" id="data_nascita" name="data_nascita" value="<?php echo htmlspecialchars($submitted_data_nascita ?? ''); ?>">
            <small>Lasciare vuoto se non disponibile.</small> <!-- Modificata nota -->
        </div>
        <div class="form-actions">
            <button type="submit" class="button button-success"><i class="bi bi-check-lg"></i> Salva Paziente</button>
        </div>
    </form>
</div>
