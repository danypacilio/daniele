<?php
$page_title = 'Dashboard Admin';
include '_header_admin.php'; // Include header comune e auth_check

// Include il file per le statistiche
// Assicurati che questo file esista e funzioni correttamente
// e che definisca la variabile $stats (un array con 'total_patients' e 'total_exams')
@include_once '_widget_stats.php'; // Usiamo @ per sopprimere errori se il file manca temporaneamente
if (!isset($stats)) { // Fallback se l'include fallisce o non definisce $stats
    $stats = ['total_patients' => 'N/D', 'total_exams' => 'N/D'];
    error_log("Attenzione: _widget_stats.php non trovato o non ha definito \$stats in index.php");
}
?>

<!-- Stili Specifici per Layout V10 - Corretto -->
<style>
    /* Stili Generali (dovrebbero essere in style.css principale, ma inclusi qui per coerenza) */
    :root { --primary-color: #4A90E2; --primary-hover: #357ABD; --secondary-color: #6c757d; --secondary-hover: #5a6268; --success-color: #5cb85c; --success-hover: #4cae4c; --warning-color: #f0ad4e; --warning-text: #333; --warning-hover: #ec971f; --danger-color: #d9534f; --danger-hover: #c9302c; --info-color: #5bc0de; --info-hover: #46b8da; --light-gray: #f9f9f9; --medium-gray: #eef1f4; --dark-gray: #d1d9e1; --text-dark: #343a40; --text-medium: #5a6268; --text-light: #88929b; --header-bg: #2c3e50; --header-link: #bdc3c7; --header-link-hover: #ffffff; --white: #ffffff; --border-radius: 8px; --box-shadow: 0 4px 12px rgba(0,0,0,0.07); --box-shadow-hover: 0 8px 20px rgba(0,0,0,0.12); }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 0; background-color: var(--light-gray); color: var(--text-dark); line-height: 1.6; font-size: 15px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    /* Importa Google Font (opzionale, mettilo nel <head> di _header_admin.php se preferisci) */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    .admin-main-content { padding: 25px 20px; } @media (min-width: 768px) { .admin-main-content { padding: 40px; } }
    .admin-container { max-width: 1200px; margin: 0 auto; background-color: transparent; padding: 0; border-radius: 0; box-shadow: none; }
    .page-title { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 35px; padding-bottom: 15px; border-bottom: 1px solid var(--dark-gray); }
    .page-title h1 { font-size: 2.1em; color: var(--text-dark); margin: 0; font-weight: 600; display: flex; align-items: center; gap: 10px;} .page-title h1 i { color: var(--primary-color); }
    .page-controls { margin-left: auto; }

    /* Layout a Colonne */
    .dashboard-layout { display: flex; flex-wrap: wrap; gap: 30px; margin-top: 0; }
    .dashboard-sidebar-stats { flex: 1; min-width: 270px; order: 1; }
    .dashboard-main-searches { flex: 2.2; min-width: 300px; order: 2; display: flex; flex-direction: column; gap: 30px; }

    /* Stile Box Comune */
    .dash-box, .sidebar-box, .stat-card { background-color: var(--white); padding: 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); border: 1px solid transparent; transition: transform 0.25s ease, box-shadow 0.25s ease; display: flex; flex-direction: column; height: 100%; box-sizing: border-box; overflow: hidden; position: relative;}
    .dash-box:hover, .sidebar-box:hover, .stat-card:hover { transform: translateY(-5px); box-shadow: var(--box-shadow-hover); }
    .dash-box-header { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid var(--medium-gray); }
    .dash-box-header .icon { font-size: 1.6em; color: var(--primary-color); line-height: 1; transition: transform 0.3s ease; }
    .dash-box:hover .dash-box-header .icon, .sidebar-box:hover .dash-box-header .icon, .stat-card:hover .dash-box-header .icon { transform: scale(1.1); }
    .dash-box-header h3, .sidebar-box h4 { margin: 0; font-size: 1.15em; font-weight: 600; color: var(--text-dark); }
    .sidebar-box h4 .icon { font-size: 1.2em; color: var(--secondary-color); }
    .dash-box-content { flex-grow: 1; }

    /* Card Statistiche */
    .stat-card { border-left: 5px solid var(--primary-color); }
    .stat-card .dash-box-header .icon { color: var(--primary-color); }
    .stat-card-content { display: flex; justify-content: space-around; align-items: center; padding: 15px 0; text-align: center; flex-grow: 1; gap: 20px;}
    .stat-item .icon { font-size: 2em; margin-bottom: 10px; display: block; color: var(--text-light); }
    .stat-item .count { font-size: 3em; font-weight: 700; color: var(--text-dark); display: block; line-height: 1; margin-bottom: 5px; }
    .stat-card:hover .stat-item .count { color: var(--primary-color); }
    .stat-item .count-label { font-size: 1em; color: var(--text-light); margin-top: 0; display: block; font-weight: 500; }
    .stat-card .button { margin-top: auto; padding-top: 15px; }
    .stat-card .button.button-secondary { width: auto; font-size: 0.85em; padding: 6px 12px; margin-top: 8px;} /* Bottoni piccoli sotto stats */

    /* Box Ricerca Sidebar */
    .sidebar-box { border-left: 4px solid var(--secondary-color); }
    .sidebar-box form label { font-weight: 500; font-size: 0.9em; margin-bottom: 5px; display: block; color: var(--text-medium);}
    .sidebar-box form input[type="text"], .sidebar-box form input[type="date"] { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 12px; font-size: 1em; box-sizing: border-box; }
    .sidebar-box form button { width: 100%; padding: 10px 15px; font-size: 1em; }
    .sidebar-box.search-patient-box { border-left-color: #6f42c1; } .sidebar-box.search-patient-box .icon { color: #6f42c1; }
    .sidebar-box.search-date-box { border-left-color: var(--info-color); } .sidebar-box.search-date-box .icon { color: var(--info-color); }

    /* Bottoni (assicurati che le classi base siano in style.css principale) */
    .button, button { /* ... */ } .button:hover, button:hover { /* ... */ } .button-primary { /* ... */ } .button-secondary { /* ... */ } .button-success { /* ... */ } .button-info { /* ... */ } .button-danger { /* ... */ } .button-warning { /* ... */ } a.button-cancel { /* ... */ }
    /* Messaggi (assicurati che siano in style.css) */
    .error, .error-message { /* ... */ } .success-message { /* ... */ } .logout-success { /* ... */ }
    /* Footer (assicurati stile in style.css) */
    .admin-footer { /* ... */ }

    /* Responsive */
    @media (max-width: 900px) { .dashboard-layout { flex-direction: column; } .dashboard-sidebar-stats { order: -1; /* Statistiche sopra su mobile */ } }
    @media (max-width: 600px) { .stats-card-content { flex-direction: column; gap: 25px; padding: 20px 0;} .stat-item { margin-bottom: 10px; } }

</style>

<!-- Contenuto Principale della Pagina -->
<div class="admin-container">
    <div class="page-title">
         <h1><i class="bi bi-speedometer2" style="margin-right: 10px; color: var(--primary-color);"></i> Dashboard</h1>
         <div class="page-controls">
             <a href="nuovo_paziente.php" class="button button-success" style="font-size: 1em; padding: 10px 20px;">
                <i class="bi bi-person-plus-fill"></i> Nuovo Paziente
             </a>
         </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'login'): ?>
        <p class="success-message">Accesso effettuato!</p>
    <?php endif; ?>

    <!-- Layout a Colonne -->
    <div class="dashboard-layout">

        <!-- Colonna Sinistra (Statistiche) -->
        <aside class="dashboard-sidebar-stats">
            <!-- Box Statistiche Unite -->
            <div class="dash-box stat-card"> <!-- Usiamo classe .stat-card -->
                <div class="dash-box-header">
                    <span class="icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                    <h3>Statistiche Veloci</h3>
                </div>
                <div class="dash-box-content stat-card-content">
                     <div class="stat-item">
                         <span class="icon"><i class="bi bi-people-fill"></i></span>
                         <span class="count"><?php echo $stats['total_patients'] ?? '0'; ?></span>
                         <span class="count-label">Pazienti</span>
                         <a href="pazienti.php" class="button button-secondary">Vedi Elenco</a>
                     </div>
                     <div class="stat-item">
                          <span class="icon"><i class="bi bi-journal-check"></i></span>
                         <span class="count"><?php echo $stats['total_exams'] ?? '0'; ?></span>
                         <span class="count-label">Esami</span>
                          <a href="pazienti.php" class="button button-secondary">Gestisci</a>
                     </div>
                </div>
            </div>
            <!-- Altri widget per questa colonna se necessario -->
        </aside>

        <!-- Colonna Destra (Ricerche) -->
        <section class="dashboard-main-searches">
             <!-- Box Ricerca Paziente -->
            <div class="dash-box sidebar-box search-patient-box">
                 <div class="dash-box-header">
                     <span class="icon"><i class="bi bi-search"></i></span>
                     <h4>Ricerca Paziente</h4>
                 </div>
                 <div class="dash-box-content">
                     <form action="pazienti.php" method="GET">
                         <label for="search_paz_main">Nome o Cognome:</label>
                         <input type="text" id="search_paz_main" name="search" placeholder="Cerca..." required>
                         <button type="submit" class="button button-primary"><i class="bi bi-binoculars-fill"></i> Trova Paziente</button>
                     </form>
                 </div>
            </div>

             <!-- Box Ricerca Esami per Data -->
            <div class="dash-box sidebar-box search-date-box">
                <div class="dash-box-header">
                     <span class="icon"><i class="bi bi-calendar-event"></i></span>
                     <h4>Ricerca Esami per Data</h4>
                 </div>
                  <div class="dash-box-content">
                     <form action="cerca_esami_data.php" method="GET">
                        <label for="search_data_main">Seleziona data:</label>
                        <input type="date" id="search_data_main" name="data_ricerca" value="<?php echo date('Y-m-d'); ?>" required>
                        <button type="submit" class="button button-info"><i class="bi bi-search"></i> Cerca Data</button>
                     </form>
                 </div>
            </div>
        </section>

    </div> <!-- Fine dashboard-layout -->

</div> <!-- Fine admin-container -->

