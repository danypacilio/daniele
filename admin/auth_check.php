<?php
// Includere questo file all'inizio di ogni pagina admin protetta.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Se non c'è sessione utente, reindirizza al login
    header('Location: login.php');
    exit;
}

// Opzionale: potresti voler aggiungere un controllo sul ruolo o permessi qui in futuro
$loggedInUserId = $_SESSION['user_id'];
$loggedInUsername = $_SESSION['username'];
?>