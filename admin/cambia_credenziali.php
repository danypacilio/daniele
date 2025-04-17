<?php
$page_title = 'Cambia Credenziali';
include '_header_admin.php'; // Include header e auth check

$error_message = '';
$success_message = '';

// Logica PHP POST per cambiare le PROPRIE credenziali (come prima)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['password_attuale'] ?? ''; $new_username = trim($_POST['nuovo_username'] ?? ''); $new_password = $_POST['new_password'] ?? ''; $confirm_password = $_POST['confirm_password'] ?? '';
    if(empty($current_password)){$error_message='Password attuale obbligatoria.';} elseif(!empty($new_password)&&$new_password!==$confirm_password){$error_message='Le nuove password non coincidono.';} elseif(!empty($new_password)&&strlen($new_password)<6){$error_message='Password min. 6 caratteri.';} elseif(empty($new_username)&&empty($new_password)){$error_message='Fornire nuovo username o nuova password.';} else {
        try {
            if (!isset($db) || !($db instanceof PDO)) { $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); }
            $userId = $_SESSION['user_id']; $stmt = $db->prepare("SELECT username, password FROM utenti WHERE id = ?"); $stmt->execute([$userId]); $currentUser = $stmt->fetch();
            if (!$currentUser || !password_verify($current_password, $currentUser['password'])) { $error_message = 'Password attuale errata.'; }
            else {
                $updateFields = []; $params = []; $username_changed = false;
                if (!empty($new_username) && $new_username !== $currentUser['username']) {
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM utenti WHERE lower(username)=lower(?) AND id!=?"); $stmtCheck->execute([$new_username, $userId]);
                    if($stmtCheck->fetchColumn()>0){ $error_message="Username '".htmlspecialchars($new_username)."' già in uso."; } else { $updateFields[]="username = :username"; $params[':username']=$new_username; $username_changed=true; }
                }
                if (!empty($new_password) && empty($error_message)) {
                    $newHashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    if ($newHashedPassword===false) { $error_message = "Errore tecnico password."; } else { $updateFields[]="password = :password"; $params[':password']=$newHashedPassword; }
                }
                if (!empty($updateFields) && empty($error_message)) {
                    $params[':user_id'] = $userId; $sql = "UPDATE utenti SET ".implode(', ', $updateFields)." WHERE id = :user_id"; $stmtUpdate = $db->prepare($sql);
                    if ($stmtUpdate->execute($params)) { $success_message = "Credenziali aggiornate!"; if ($username_changed) { $_SESSION['username'] = $new_username; $success_message .= " Username aggiornato."; } $_POST = ['nuovo_username'=>$new_username];} else { $error_message = "Errore aggiornamento DB."; }
                } elseif (empty($error_message)) { $error_message = "Nessuna modifica valida."; }
            }
        } catch (PDOException $e) { $error_message = "Errore database."; error_log("Errore cambio credenziali DB: ".$e->getMessage()); }
         catch (Exception $e) { $error_message = "Errore imprevisto."; error_log("Errore generico cambio credenziali: ".$e->getMessage()); }
    }
}
?>

<!-- Stili specifici per questa pagina (visualizza password) -->
<style>
    .password-wrapper { position: relative; width: 100%; }
    .password-wrapper input[type="password"],
    .password-wrapper input[type="text"] { padding-right: 45px !important; } /* Spazio per icona */
    .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; font-size: 1.3em; color: #aaa; line-height: 1; }
    .toggle-password:hover { color: #555; }
</style>

<div class="admin-container">
    <div class="page-title">
        <h1><i class="bi bi-key-fill" style="margin-right: 10px;"></i> Modifica le Tue Credenziali</h1>
         <div class="page-controls">
             <a href="index.php" class="button button-secondary">← Torna alla Dashboard</a>
         </div>
    </div>
    <p style="margin-bottom: 25px; color: var(--text-medium);">Usa questo modulo per cambiare il tuo username o la tua password di accesso. Devi inserire la password attuale per confermare le modifiche.</p>

    <?php if (!empty($success_message)): ?><p class="success-message"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>
    <?php if (!empty($error_message)): ?><p class="error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>

    <form action="cambia_credenziali.php" method="POST">
        <!-- Campo nascosto per distinguere l'azione se in futuro aggiungerai altri form -->
        <!-- <input type="hidden" name="action" value="change_own_credentials"> -->

        <div class="form-group">
            <label for="password_attuale">Password Attuale *</label>
            <div class="password-wrapper">
                <input type="password" id="password_attuale" name="password_attuale" required>
                <span class="toggle-password" onclick="togglePasswordVisibility('password_attuale', this)" title="Mostra/Nascondi Password">👁️</span>
            </div>
            <small>Necessaria per salvare qualsiasi modifica.</small>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

        <div class="form-group">
            <label for="nuovo_username">Nuovo Username (Opzionale)</label>
            <input type="text" id="nuovo_username" name="nuovo_username" value="<?php echo htmlspecialchars($_POST['nuovo_username'] ?? ($_SESSION['username'] ?? '')); ?>">
            <small>Lascia invariato se vuoi cambiare solo la password.</small>
        </div>

         <div class="form-group">
            <label for="new_password">Nuova Password (Opzionale)</label>
             <div class="password-wrapper">
                <input type="password" id="new_password" name="new_password">
                 <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)" title="Mostra/Nascondi Password">👁️</span>
             </div>
             <small>Lascia vuoto se non vuoi cambiare la password (min. 6 caratteri).</small>
        </div>

         <div class="form-group">
            <label for="confirm_password">Conferma Nuova Password</label>
             <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password">
                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" title="Mostra/Nascondi Password">👁️</span>
            </div>
             <small>Obbligatorio solo se stai inserendo una nuova password.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary"><i class="bi bi-check-lg"></i> Aggiorna Credenziali</button>
            <a href="index.php" class="button-cancel">Annulla</a> <!-- Usa stile cancelletto -->
        </div>
    </form>

     <!-- Sezione gestione altri utenti rimossa -->

</div>

<!-- Script JS per toggle password -->
<script>
 function togglePasswordVisibility(inputId, toggleElement) { const passwordInput = document.getElementById(inputId); if (!passwordInput || !toggleElement) return; const currentType = passwordInput.getAttribute('type'); if (currentType === 'password') { passwordInput.setAttribute('type', 'text'); toggleElement.textContent = '🙈'; toggleElement.setAttribute('title', 'Nascondi'); } else { passwordInput.setAttribute('type', 'password'); toggleElement.textContent = '👁️'; toggleElement.setAttribute('title', 'Mostra'); } }
</script>
