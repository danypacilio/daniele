<?php
session_start();

// Distruggi tutte le variabili di sessione.
$_SESSION = array();

// Se si desidera distruggere completamente la sessione, cancellare anche il cookie di sessione.
// Nota: Questo distruggerà la sessione e non solo i dati della sessione!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Infine , distruggere la sessione.
session_destroy();

// Reindirizza alla pagina di login con un messaggio opzionale
header('Location: login.php?logout=1');
exit;
?>