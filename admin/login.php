<?php
session_start();
$error_message = '';

// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Gestione invio form di login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Username e password sono obbligatori.';
    } else {
        try {
            $db = new PDO("sqlite:../pazienti.sqlite");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("SELECT id, username, password FROM utenti WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                session_regenerate_id(true);
                header('Location: index.php?success=login');
                exit;
            } else {
                $error_message = 'Credenziali non valide.';
            }
        } catch (PDOException $e) {
            $error_message = "Errore del database. Riprova più tardi.";
            error_log("Errore login DB: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Screening</title>
    <style>
        /* Stili Login + Visualizza Password */
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4; margin: 0; }
        .login-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 90%; max-width: 380px; text-align: center; }
        .login-logo img { max-width: 80%; height: auto; margin-bottom: 25px; }
        h2 { margin-bottom: 25px; color: #333; font-size: 1.5em; font-weight: 500;}
        .form-group { margin-bottom: 20px; position: relative; /* Serve per posizionare icona password */ }
        .form-group input[type="text"],
        .form-group input[type="password"] {
             width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em;
        }
        /* Stile specifico per input password quando c'è l'icona */
        .password-wrapper input { padding-right: 45px !important; }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%; /* Allinea al centro dell'input */
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            font-size: 1.3em;
            color: #aaa;
            line-height: 1;
        }
         .toggle-password:hover { color: #555; }

        button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.2s; }
        button:hover { background-color: #0056b3; }
        .error { color: #dc3545; margin-top: 15px; font-size: 0.9em; }
        .logout-success { color: #28a745; margin-top: 15px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="../logo.png" alt="Logo Studio Podologico">
        </div>
        <h2>Accesso Area Riservata</h2>

        <?php if (isset($_GET['logout'])): ?> <p class="logout-success">Logout effettuato con successo.</p> <?php endif; ?>
        <?php if (!empty($error_message)): ?> <p class="error"><?php echo htmlspecialchars($error_message); ?></p> <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                 <input type="text" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
           <div class="form-group password-wrapper"> <!-- Wrapper aggiunto -->
                <input type="password" id="login_password" name="password" placeholder="Password" required> <!-- Aggiunto ID -->
                <span class="toggle-password" onclick="togglePasswordVisibility('login_password', this)" title="Mostra/Nascondi Password">👁️</span> <!-- Icona aggiunta -->
           </div>
            <button type="submit">Accedi</button>
        </form>
    </div>

    <!-- Script JavaScript (può stare qui o in un file .js separato) -->
    <script>
        function togglePasswordVisibility(inputId, toggleElement) {
            const passwordInput = document.getElementById(inputId);
            if (!passwordInput || !toggleElement) return;
            const currentType = passwordInput.getAttribute('type');
            if (currentType === 'password') {
                passwordInput.setAttribute('type', 'text');
                toggleElement.textContent = '🙈';
                toggleElement.setAttribute('title', 'Nascondi Password');
            } else {
                passwordInput.setAttribute('type', 'password');
                toggleElement.textContent = '👁️';
                toggleElement.setAttribute('title', 'Mostra Password');
            }
        }
    </script>

</body>
</html>