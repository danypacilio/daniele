<?php
// _header_admin.php - Header comune per l'area admin V3
// Include il controllo di autenticazione all'inizio
// Questo script imposta anche le variabili di sessione come $_SESSION['user_id'] e $_SESSION['username']
include_once 'auth_check.php';

// Recupera username dalla sessione per visualizzarlo (usa variabili da auth_check)
$loggedInUsernameForHeader = $loggedInUsername ?? 'Utente'; // Usa variabile da auth_check se disponibile
$loggedInUserIdForHeader = $loggedInUserId ?? null;       // Usa variabile da auth_check se disponibile
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Il titolo viene impostato dalla pagina specifica che include questo header -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Screening'; ?></title>
    <!-- CSS Principale Admin -->
    <link rel="stylesheet" href="style.css">
    <!-- Inclusione Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Stili aggiuntivi per logo e icone nav -->
    <style>
        .admin-header .header-logo img {
            max-height: 60px; /* Altezza logo */
            width: auto;
            vertical-align: middle;
            margin-right: 15px;
        }
        /* Opzionale: Nascondi titolo testuale se c'è logo */
         .admin-header .header-logo + h1 {
            /* display: none; */
         }
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; /* Permetti wrap su schermi piccoli */
         }
         .header-left-side { /* Contenitore logo e titolo */
             display: flex;
             align-items: center;
             margin-right: 20px; /* Spazio tra logo/titolo e nav */
             margin-bottom: 5px; /* Spazio sotto su mobile se va a capo */
         }
         .admin-header nav {
             /* margin-left: auto; // Non più necessario con justify-content */
             display: flex;
             align-items: center;
             flex-wrap: wrap; /* Permetti wrap link nav */
             gap: 10px 15px; /* Spazio verticale e orizzontale tra i link */
         }
         .admin-header nav a { /* Stile base link nav */
            color: var(--header-link, #adb5bd); /* Usa variabile CSS o default */
            text-decoration: none;
            padding: 5px 0;
            transition: color 0.2s ease;
            font-size: 0.95em;
            white-space: nowrap;
            display: inline-flex; /* Per allineare icona e testo */
            align-items: center;
         }
         .admin-header nav a i { /* Stile icone nella nav */
             margin-right: 6px;
             font-size: 1.1em;
             vertical-align: middle;
             position: relative;
             top: -1px;
         }
         .admin-header nav a:hover {
            color: var(--header-link-hover, #ffffff);
         }
         .admin-header nav span.welcome-user { /* Stile messaggio benvenuto */
             margin-right: 20px;
             color: var(--header-link, #adb5bd);
             font-size: 0.9em;
             white-space: nowrap;
             order: -1; /* Mette il benvenuto prima su mobile quando va a capo */
             @media (min-width: 992px) { order: 0; } /* Ordine normale su desktop */
         }
    </style>
</head>
<body>
    <header class="admin-header">
        <!-- Contenitore Sinistra: Logo (+ Titolo opzionale) -->
        <div class="header-left-side">
            <a href="index.php" class="header-logo" title="Vai alla Dashboard">
                <!-- Assicurati che il percorso sia corretto! Da /admin/ a /screening/logo.png -->
                <img src="../logo2.png" alt="Logo Screening Podologico">
            </a>
            <!-- Rimosso titolo H1 testuale, lasciato solo logo cliccabile -->
            <!-- <h1>Screening Podologico</h1> -->
        </div>

        <!-- Navigazione Principale -->
        <nav>
            <?php if (isset($loggedInUserIdForHeader)): // Mostra navigazione solo se utente è loggato ?>
                <span class="welcome-user">Benvenuto, Dr <?php echo htmlspecialchars($loggedInUsernameForHeader); ?>!</span>
                <a href="index.php" title="Dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="pazienti.php" title="Gestisci Pazienti"><i class="bi bi-people-fill"></i> Pazienti</a>
                <a href="cerca_esami_data.php" title="Cerca Esami per Data"><i class="bi bi-calendar-week"></i> Diabesys</a>
				<a href="report_esami.php" title="Report esami"><i class="bi bi-calendar-week"></i> Report</a>
                <a href="backup.php" title="Backup Database"><i class="bi bi-database-down"></i> Backup</a>
                <a href="cambia_credenziali.php" title="Modifica le tue credenziali"><i class="bi bi-key-fill"></i> Credenziali</a>
                <a href="logout.php" title="Esci"><i class="bi bi-box-arrow-right"></i> Logout</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="admin-main-content">
        <!-- Il contenuto specifico della pagina inizia dopo questo tag -->