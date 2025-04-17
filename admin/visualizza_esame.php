<?php
// File: /screening/admin/visualizza_esame.php (Versione Corretta e Completa)

// Include controllo login (fondamentale!)
include_once 'auth_check.php';

// --- Definizioni Array (necessarie per la visualizzazione) ---
$domandeDN4 = [
    'dn4_1' => "Bruciante/Urgente", 'dn4_2' => "Sensazione di freddo doloroso", 'dn4_3' => "Scariche elettriche",
    'dn4_4' => "Formicolio", 'dn4_5' => "Punture di spillo", 'dn4_6' => "Intorpidimento", 'dn4_7' => "Prurito",
    'dn4_8' => "Ipoestesia al tatto", 'dn4_9' => "Ipoestesia alla puntura", 'dn4_10' => "Sfioramento della pelle"
];
$etichettePGIC = [
    7 => "Peggioramento Notevole", 6 => "Nessun Cambiamento", 5 => "Miglioramento Minimo",
    4 => "Miglioramento Lieve", 3 => "Miglioramento Moderato", 2 => "Miglioramento Buono", 1 => "Miglioramento Ottimo"
];
$colonnePGIC = [
    'pgic_fisica' => "Attività Fisica", 'pgic_sintomi' => "Sintomi",
    'pgic_emozioni' => "Emozioni", 'pgic_vita' => "Qualità Vita Globale"
];
$puntiVib = ['malleolo','alluce','5dito','1meta','5meta','meso','calcagno'];

// --- Recupero Dati da DB ---
$esame_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esame = null;
$paziente = null;
$error_message = ''; // Inizializza messaggio errore

if (!$esame_id) {
    $error_message = "ID esame mancante o non valido.";
} else {
    try {
        // Assicura connessione DB
        // (Se $db è già definita in auth_check.php o altrove, potresti rimuovere la riconnessione)
         if (!isset($db) || !($db instanceof PDO)) {
             $db = new PDO("sqlite:../pazienti.sqlite");
             $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
         }

        // Recupera dati esame
        $stmtEsame = $db->prepare("SELECT * FROM esami WHERE id = ?");
        $stmtEsame->execute([$esame_id]);
        $esame = $stmtEsame->fetch();

        if (!$esame) {
            $error_message = "Esame non trovato (ID: $esame_id).";
        } else {
            // Recupera dati paziente associato
            $stmtPaziente = $db->prepare("SELECT id, nome, cognome, data_nascita FROM pazienti WHERE id = ?");
            $stmtPaziente->execute([$esame['paziente_id']]);
            $paziente = $stmtPaziente->fetch();

            if (!$paziente){
                $error_message = "Paziente (ID: {$esame['paziente_id']}) associato all'esame non trovato.";
                $esame = null; // Invalida esame se paziente non c'è
            } else {
                // Decode JSON solo se abbiamo i dati esame e paziente
                $esame['esame_obiettivo'] = json_decode($esame['esame_obiettivo'] ?? '[]', true);
                $esame['ipercheratosi'] = json_decode($esame['ipercheratosi'] ?? '[]', true);
                $esame['sensibilita_vibratoria'] = json_decode($esame['sensibilita_vibratoria'] ?? '{}', true);
                $esame['dn4_risposte'] = json_decode($esame['dn4_risposte'] ?? '{}', true);
                $esame['pgic_risposte'] = json_decode($esame['pgic_risposte'] ?? '{}', true);
                $esame['screening_prossimo'] = json_decode($esame['screening_prossimo'] ?? '[]', true);
            }
        }
    } catch (PDOException $e) {
        $error_message = "Errore database durante il recupero dei dati.";
        error_log("Errore DB visualizza_esame (ID: $esame_id): " . $e->getMessage());
    }
}

// Funzione helper per mostrare le immagini
function displayImage($filename) {
    $basePath = '../foto/'; // Percorso relativo da /admin/ a /foto/
    $filepath = $basePath . $filename;
    $relativeUrlForBrowser = '../foto/' . $filename; // Corretto per tag <img>

    if (!empty($filename) && file_exists($filepath)) {
        // Utilizza il percorso relativo corretto nell'attributo src
        return '<img src="' . htmlspecialchars($relativeUrlForBrowser) . '" alt="Immagine Esame" style="display: block; max-width: 100%; height: auto; max-height: 200px; object-fit: contain; border: 1px solid #ccc; margin: 5px auto; border-radius: 4px;">';
    }
    // Se il file non esiste o il nome è vuoto
    return '<span style="color: #999; font-style: italic;">(Immagine non trovata o non disponibile)</span>';
}


// Imposta Titolo Pagina HTML
$page_html_title = 'Dettaglio Esame';
if ($paziente) {
    $page_html_title = 'Esame ' . htmlspecialchars($paziente['cognome'] . ' ' . $paziente['nome']) . ' del ' . ($esame ? date('d/m/Y', strtotime($esame['data_esame'])) : 'N/D');
} elseif ($error_message) {
    $page_html_title = 'Errore Visualizzazione';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_html_title; ?></title>
    <link rel="stylesheet" href="style.css"> <!-- CSS Admin Globale -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> <!-- Bootstrap Icons -->
    <style>
        /* Stili ESSENZIALI per il contenuto + Stampa + Header semplice */
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; line-height: 1.6; }
        .page-header-simple { background-color: var(--header-bg, #343a40); padding: 15px 30px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .page-header-simple h1 { margin: 0; font-size: 1.5em; font-weight: 500; display: flex; align-items: center;}
        .page-header-simple h1 i { margin-right: 10px; color: var(--info-color, #17a2b8); } /* Icona titolo blu chiaro */
        .page-header-simple nav { display: flex; align-items: center; gap: 20px; } /* Aumentato gap */
        .page-header-simple nav a { color: var(--header-link, #adb5bd); text-decoration: none; font-size: 0.95em; display: inline-flex; align-items: center; gap: 6px; }
        .page-header-simple nav a:hover { color: var(--header-link-hover, #ffffff); }
        .page-header-simple nav a i { font-size: 1.1em; position: relative; top: 1px;}

        .exam-container { padding: 30px; max-width: 800px; margin: 20px auto; background-color: #fff; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h3 { font-size: 1.3em; border-bottom: 1px solid #eee; padding-bottom: 5px; text-align: center; margin-top: 25px; margin-bottom: 15px; color: #0056b3; font-weight: 600;}
        .subtitle { text-align: center; font-style: italic; color: #555; margin-top: -10px; margin-bottom: 15px; font-size: 0.9em; }
        p, li { line-height: 1.5; font-size: 0.95em; margin-bottom: 0.5em;} ul, ol { padding-left: 25px; margin-top: 0.5em; margin-bottom: 1em;} ol li { margin-bottom: 5px; }
        .info-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #ccc; }
        .info-section p { margin: 5px 0; font-size: 1em; } .info-section strong { display: inline-block; min-width: 110px; font-weight: bold;}
        .foto-grid-container { margin-bottom: 25px; } .foto-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 5px; justify-items: center; }
        .notes-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 10px 15px; border-radius: 4px; margin-top: 5px; min-height: 40px; white-space: pre-wrap; font-size: 0.95em;}
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.9em; } th, td { padding: 6px; text-align: center; border: 1px solid #dee2e6; } th { background-color: #e9ecef; font-weight: bold; }
        td.pgic-label { text-align: left; padding-left: 8px;} td .selected-mark { font-weight: bold; color: #28a745; }
        .italic-ref { font-style: italic; color: #6c757d; text-align: center; margin-top: 5px; font-size: 0.85em; }
        .error { color: #dc3545; border: 1px solid #f5c6cb; background-color: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px; text-align: center; }
        .content-print-header-img { display: none; }
        .print-button-container { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; }
        .signature-container { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; width: 100%; overflow: hidden; }
        .signature-text { float: right; text-align: right; font-size: 0.9em; line-height: 1.4; color: var(--text-medium); }
        .signature-text p { margin: 0 0 3px 0; } .signature-text p:first-child { font-weight: bold; color: var(--text-dark); }
        /* Bottoni (assicurati che siano definiti in style.css o qui) */
        .button { display: inline-block; padding: 9px 18px; font-size: 0.95em; font-weight: 500; text-decoration: none; border-radius: 6px; color: white !important; text-align: center; cursor: pointer; border: none; transition: background-color 0.2s ease; margin-right: 8px; margin-bottom: 8px; vertical-align: middle; }
        .button-secondary { background-color: #6c757d; } .button-secondary:hover { background-color: #5a6268; }

        @media print {
             body { margin: 1.5cm; margin-top: 0.5cm; padding: 0; font-size: 10pt; background-color: #fff; }
            .content-print-header-img { display: block !important; width: 100%; height: 8cm; object-fit: contain; margin-bottom: 0cm; page-break-after: avoid; }
            .page-header-simple, .print-button-container { display: none !important; }
            .exam-container { border: none; box-shadow: none; padding: 0; margin: 0; max-width: 100%; width: 100%; }
            .info-section { margin-top: 0; padding-top: 0; page-break-after: avoid;}
            .foto-grid-container { page-break-inside: avoid !important; } .foto-grid { page-break-inside: avoid; } .foto-grid img { max-width: 85% !important; height: auto !important; }
            table, .notes-box, ul, ol { page-break-inside: avoid; }
            h3 { page-break-after: avoid; font-size: 1.1em; margin-top: 15px;}
            .signature-container { margin-top: 1cm; padding-top: 1cm; border-top: none; page-break-before: auto; page-break-inside: avoid; width: 100%; position: relative; clear: both;}
            .signature-text { float: right; text-align: right; font-size: 0.85em; line-height: 1.3; color: #333;}
            .signature-text p { margin: 0 0 2px 0; } .signature-text p:first-child { font-weight: bold; }
            a { text-decoration: none; color: #000; }
            th, td { padding: 4px; font-size: 9pt;} td.pgic-label { padding-left: 4px;}
        }
    </style>
</head>
<body>
    <!-- Header Semplice Specifico -->
    <header class="page-header-simple">
         <h1> <i class="bi bi-file-earmark-medical-fill"></i> <?php echo $page_html_title; ?> </h1>
         <nav>
            <?php if ($paziente): ?>
                 <a href="esami.php?pid=<?php echo $esame['paziente_id']; ?>" class="back-button-page" title="Torna elenco esami">
                     <i class="bi bi-arrow-left-circle-fill"></i> Torna agli Esami
                 </a>
                 <a href="pazienti.php" title="Elenco Pazienti">
                     <i class="bi bi-people-fill"></i> Pazienti
                 </a>
            <?php else: ?>
                 <a href="pazienti.php" title="Elenco Pazienti"> <i class="bi bi-people-fill"></i> Pazienti </a>
            <?php endif; ?>
            <a href="logout.php" title="Esci"> <i class="bi bi-box-arrow-right"></i> Logout </a>
         </nav>
    </header>

    <!-- Contenitore Esame -->
    <div class="exam-container">
        <img src="../header.jpg" alt="Intestazione Studio" class="content-print-header-img"> <!-- Solo per Stampa -->

        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
             <p style="text-align: center;"><a href="pazienti.php" class="button button-secondary">Torna ai Pazienti</a></p>
        <?php elseif ($esame && $paziente): ?>
            <!-- Dati Paziente e Data -->
            <div class="info-section">
                <p><strong>Paziente:</strong> <?php echo htmlspecialchars($paziente['cognome'] . ' ' . $paziente['nome']); ?></p>
                <p><strong>Data Nascita:</strong> <?php echo !empty($paziente['data_nascita']) ? htmlspecialchars(date('d/m/Y', strtotime($paziente['data_nascita']))) : 'N/D'; ?></p>
                <p><strong>Data Esame:</strong> <?php echo htmlspecialchars(date('d/m/Y ', strtotime($esame['data_esame']))); ?></p>
            </div>

            <!-- Immagini -->
            <div class="foto-grid-container">
                 
                 <div class="foto-grid">
                     <?php echo displayImage($esame['foto1']); ?>
                     <?php echo displayImage($esame['foto2']); ?>
                     <?php echo displayImage($esame['foto3']); ?>
                     <?php echo displayImage($esame['foto4']); ?>
                 </div>
            </div>

             <!-- Note Podografia, ecc... -->
             <h3>Note Podografia</h3> <div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['note_podografia']?:'(N/D)')); ?></div>
             <?php if(!empty($esame['esame_obiettivo'])): ?><h3>Esame Obiettivo Podologico</h3><ul><?php foreach($esame['esame_obiettivo'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif; ?>
             <?php if(!empty($esame['ipercheratosi'])): ?><h3>Ipercheratosi</h3><ul><?php foreach($esame['ipercheratosi'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif; ?>
             <h3>Localizzazione Ulcere</h3> <div style="text-align:center;"><img src="../centro.jpg" alt="Schema" style="max-width:500px; width:100%; border:1px solid #eee;"></div>
             <h3>Altre Patologie Podologiche</h3><div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['altre_patologie']?:'(N/D)')); ?></div>
             <h3> Prove di Sensibilità Vibratoria</h3> <p class="subtitle">( Esame eseguito mediante Biotesiometer - Rif: < 25 )</p> <?php if(!empty($esame['sensibilita_vibratoria'])):?><table><thead><tr><th>Punto</th><th>DX</th><th>SX</th></tr></thead><tbody><?php foreach($puntiVib as $p):?><tr><td><?php echo ucfirst($p);?></td><td><?php echo htmlspecialchars($esame['sensibilita_vibratoria']['vib_'.$p.'_dx']??'-');?></td><td><?php echo htmlspecialchars($esame['sensibilita_vibratoria']['vib_'.$p.'_sx']??'-');?></td></tr><?php endforeach;?></tbody></table><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <h3>Osservazioni</h3><div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['osservazioni']?:'(N/D)')); ?></div>
             <h3>Questionario DN4</h3> <?php if(isset($esame['dn4_risposte'])&&is_array($esame['dn4_risposte'])&&count($esame['dn4_risposte'])>0):?><ol><?php foreach($domandeDN4 as $k=>$d): $r=$esame['dn4_risposte'][$k]??'N/D';?><li><?php echo htmlspecialchars($d);?>: <strong><?php echo htmlspecialchars($r);?></strong></li><?php endforeach;?></ol><p><strong>Punteggio:</strong> <?php echo $esame['dn4_punteggio']??'N/D';?>/10</p><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <h3>Scala NRS del Dolore (Scala Unidimensionale)</h3><p><strong>Valore:</strong> <?php echo isset($esame['nrs_dolore'])&&$esame['nrs_dolore']!==null?$esame['nrs_dolore']:'N/D';?></p>
             <h3>Valutazione PGIC</h3> <p class="subtitle">Da quando ha iniziato il trattamento, può descrivere il cambiamento per quanto riguarda:</p> <?php if(isset($esame['pgic_risposte'])&&is_array($esame['pgic_risposte'])&&count($esame['pgic_risposte'])>0):?><table><thead><tr><th>Valutazione</th><?php foreach($colonnePGIC as $l):?><th><?php echo htmlspecialchars($l);?></th><?php endforeach;?></tr></thead><tbody><?php foreach($etichettePGIC as $v=>$et):?><tr><td class="pgic-label"><?php echo $v." - ".htmlspecialchars($et);?></td><?php foreach($colonnePGIC as $k=>$l): $rS=$esame['pgic_risposte'][$k]??null; $isS=($rS!==null&&$rS==$v);?><td><?php echo $isS?'<span class="selected-mark">✔️</span>':'-';?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table><p><strong>Totale:</strong> <?php echo $esame['pgic_totale']??'N/D';?>/28</p><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <?php if(!empty($esame['screening_prossimo'])):?><h3>Screening tra:</h3><ul><?php foreach($esame['screening_prossimo'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif;?>

            <!-- Firma Testuale -->
            <div class="signature-container">
                <div class="signature-text">
                    <p>Dott. Daniele Pacilio</p> <p>Podologo</p>
                    <p>Ordine TSRM-PSTRP Na Av Bn Co</p> <p>Iscr. Albo Podologi n° 122</p>
                </div>
            </div>

            <!-- Pulsante Stampa -->
            <div class="print-button-container">
                 <button onclick="window.print()" class="button button-secondary print-button"><i class="bi bi-printer-fill"></i> Stampa Esame</button>
            </div>
        <?php endif; ?>
    </div> <!-- Fine exam-container -->

    <!-- NON includere _footer_admin.php qui -->
</body>
</html>