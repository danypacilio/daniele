<?php
// visualizza_esame_clean.php - Versione Finale per Copia
include 'auth_check.php';

// Array PHP (Definizioni)
$domandeDN4 = [ 'dn4_1' => "Bruciante/Urgente", 'dn4_2' => "Sensazione di freddo doloroso", 'dn4_3' => "Scariche elettriche", 'dn4_4' => "Formicolio", 'dn4_5' => "Punture di spillo", 'dn4_6' => "Intorpidimento", 'dn4_7' => "Prurito", 'dn4_8' => "Ipoestesia al tatto", 'dn4_9' => "Ipoestesia alla puntura", 'dn4_10' => "Sfioramento della pelle" ];
$etichettePGIC = [ 7 => "Peggioramento Notevole", 6 => "Nessun Cambiamento", 5 => "Miglioramento Minimo", 4 => "Miglioramento Lieve", 3 => "Miglioramento Moderato", 2 => "Miglioramento Buono", 1 => "Miglioramento Ottimo" ];
$colonnePGIC = [ 'pgic_fisica' => "Attività Fisica", 'pgic_sintomi' => "Sintomi", 'pgic_emozioni' => "Emozioni", 'pgic_vita' => "Qualità Vita Globale" ];
$puntiVib = ['malleolo','alluce','5dito','1meta','5meta','meso','calcagno'];

// Recupero dati DB
$esame_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esame_data_for_js = null; $paziente_data_for_js = null; $error_message = '';
if (!$esame_id) { $error_message = "ID esame mancante."; }
else {
    try {
        $db = new PDO("sqlite:../pazienti.sqlite"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $stmtEsame = $db->prepare("SELECT * FROM esami WHERE id = ?"); $stmtEsame->execute([$esame_id]); $esame = $stmtEsame->fetch();
        if (!$esame) { $error_message = "Esame non trovato."; }
        else {
            $stmtPaziente = $db->prepare("SELECT id, nome, cognome, data_nascita FROM pazienti WHERE id = ?"); $stmtPaziente->execute([$esame['paziente_id']]); $paziente = $stmtPaziente->fetch();
            if (!$paziente){ $error_message = "Paziente non trovato."; $esame = null; }
            else { if($esame) { /* Decode JSON */ $esame['esame_obiettivo']=json_decode($esame['esame_obiettivo']??'[]',true); $esame['ipercheratosi']=json_decode($esame['ipercheratosi']??'[]',true); $esame['sensibilita_vibratoria']=json_decode($esame['sensibilita_vibratoria']??'{}',true); $esame['dn4_risposte']=json_decode($esame['dn4_risposte']??'{}',true); $esame['pgic_risposte']=json_decode($esame['pgic_risposte']??'{}',true); $esame['screening_prossimo']=json_decode($esame['screening_prossimo']??'[]',true); $esame_data_for_js=$esame; $paziente_data_for_js=$paziente; } }
        }
    } catch (PDOException $e) { $error_message = "Errore database."; error_log("Errore DB vis_clean (ID: $esame_id): ".$e->getMessage()); }
}

// Funzione helper immagini
function displayImageClean($filename) { $basePath='../foto/';$filepath=$basePath.$filename;$relativeUrl=$basePath.$filename;if(!empty($filename)&&file_exists($filepath)){return '<img src="'.htmlspecialchars($relativeUrl).'" alt="Img Esame" data-original-src="'.htmlspecialchars($relativeUrl).'" style="display: block; max-width: 100%; height: auto; max-height: 200px; object-fit: contain; border: 1px solid #ccc; margin: 5px auto; border-radius: 4px;">';} return '<span style="color:#999; font-style:italic;">(N/D)</span>';}
// Titolo Pagina
$page_html_title = ''; if ($paziente) { $page_html_title .= htmlspecialchars($paziente['cognome'].' '.$paziente['nome']) . ' - Esame'; } elseif ($error_message) { $page_html_title = 'Errore'; } else { $page_html_title = 'Visualizzazione Esame';}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_html_title; ?></title>
    <style>
        /* Stili ESSENZIALI per il contenuto e la stampa */
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; line-height: 1.6; }
        .exam-content-wrapper { padding: 30px; max-width: 800px; margin: 20px auto; background-color: #fff; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
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
        .error-message-inline { color: #dc3545; border: 1px solid #f5c6cb; background-color: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px; text-align: center; }
        .content-print-header-img { display: none; }
        .action-buttons-container-top { text-align: right; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #ccc; }
        .action-button { padding: 8px 18px; font-size: 0.9em; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; vertical-align: middle;}
        .copy-button { background-color: #17a2b8; } .copy-button:hover { background-color: #117a8b; }
        .copy-feedback { display: inline-block; margin-left: 10px; font-weight: bold; font-size: 0.9em; opacity: 0; transition: opacity 0.5s ease-out; vertical-align: middle;}
        .copy-feedback.visible { opacity: 1; }

        @media print {
             body { margin: 1.5cm; margin-top: 0.5cm; padding: 0; font-size: 10pt; background-color: #fff; }
            .content-print-header-img { display: block !important; width: 100%; height: 8cm; object-fit: contain; margin-bottom: 1cm; page-break-after: avoid; }
            .action-buttons-container-top { display: none !important; } /* Nascondi bottone copia in stampa */
            .exam-content-wrapper { border: none; box-shadow: none; padding: 0; margin: 0; max-width: 100%; width: 100%; user-select: auto; }
            .info-section { margin-top: 0; padding-top: 0; page-break-after: avoid;} .foto-grid-container { page-break-inside: avoid !important; } .foto-grid { page-break-inside: avoid; } .foto-grid img { max-width: 85% !important; height: auto !important; } table, .notes-box, ul, ol { page-break-inside: avoid; } h3 { page-break-after: avoid; font-size: 1.1em; margin-top: 15px;} a { text-decoration: none; color: #000; } th, td { padding: 4px; font-size: 9pt;} td.pgic-label { padding-left: 4px;}
            .signature-container { display: none !important;} /* Nascondi firma in stampa */
        }
    </style>
</head>
<body>
    <!-- Wrapper -->
    <div class="exam-content-wrapper" id="exam-content-to-copy">
         <img src="../header.jpg" alt="Intestazione Studio" class="content-print-header-img"> <!-- Solo per Stampa -->
        <?php if (!empty($error_message)): ?>
            <p class="error-message-inline"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($esame && $paziente): ?>
             <!-- BOTTONE COPIA IN ALTO -->
             <div class="action-buttons-container-top" id="action-buttons">
                 <span id="copyFeedback" class="copy-feedback"></span>
                 <button onclick="copyCleanHtmlWithImages()" class="action-button copy-button">📋 Copia Esame (con Immagini)</button>
             </div>
             <!-- Dati Paziente e Data -->
             <div class="info-section"> <p><strong>Paziente:</strong> <?php echo htmlspecialchars($paziente['cognome'].' '.$paziente['nome']); ?></p> <p><strong>Data Nascita:</strong> <?php echo !empty($paziente['data_nascita'])?htmlspecialchars(date('d/m/Y',strtotime($paziente['data_nascita']))):'N/D'; ?></p> <p><strong>Data Esame:</strong> <?php echo htmlspecialchars(date('d/m/Y ',strtotime($esame['data_esame']))); ?></p> </div>
             <!-- Immagini -->
             <div class="foto-grid-container"><div class="foto-grid"> <?php echo displayImageClean($esame['foto1']); ?> <?php echo displayImageClean($esame['foto2']); ?> <?php echo displayImageClean($esame['foto3']); ?> <?php echo displayImageClean($esame['foto4']); ?> </div> </div>
             <!-- Note Podografia, etc... -->
             <h3>Note Podografia</h3> <div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['note_podografia']?:'(N/D)')); ?></div>
             <?php if(!empty($esame['esame_obiettivo'])): ?><h3>Esame Obiettivo Podologico</h3><ul><?php foreach($esame['esame_obiettivo'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif; ?>
             <?php if(!empty($esame['ipercheratosi'])): ?><h3>Ipercheratosi</h3><ul><?php foreach($esame['ipercheratosi'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif; ?>
             <h3>Localizzazione Ulcere</h3> <div style="text-align:center;"><img src="../centro.jpg" alt="Schema" style="max-width:500px; width:100%; border:1px solid #eee;"></div>
             <h3>Altre Patologie Podlogiche</h3><div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['altre_patologie']?:'(N/D)')); ?></div>
             <h3>Prove di Sensibilità Vibratoria</h3> <p class="subtitle">( Esame eseguito mediante Biotesiometer - Rif: < 25 )</p> <?php if(!empty($esame['sensibilita_vibratoria'])):?><table><thead><tr><th>Punto</th><th>DX</th><th>SX</th></tr></thead><tbody><?php foreach($puntiVib as $p):?><tr><td><?php echo ucfirst($p);?></td><td><?php echo htmlspecialchars($esame['sensibilita_vibratoria']['vib_'.$p.'_dx']??'-');?></td><td><?php echo htmlspecialchars($esame['sensibilita_vibratoria']['vib_'.$p.'_sx']??'-');?></td></tr><?php endforeach;?></tbody></table><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <h3>Osservazioni</h3><div class="notes-box"><?php echo nl2br(htmlspecialchars($esame['osservazioni']?:'(N/D)')); ?></div>
             <h3>Questionario DN4</h3> <?php if(isset($esame['dn4_risposte'])&&is_array($esame['dn4_risposte'])&&count($esame['dn4_risposte'])>0):?><ol><?php foreach($domandeDN4 as $k=>$d): $r=$esame['dn4_risposte'][$k]??'N/D';?><li><?php echo htmlspecialchars($d);?>: <strong><?php echo htmlspecialchars($r);?></strong></li><?php endforeach;?></ol><p><strong>Punteggio:</strong> <?php echo $esame['dn4_punteggio']??'N/D';?>/10</p><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <h3>Scala NRS del Dolore (Scala Unidimensionale)</h3><p><strong>Valore:</strong> <?php echo isset($esame['nrs_dolore'])&&$esame['nrs_dolore']!==null?$esame['nrs_dolore']:'N/D';?></p>
             <h3>Patients’ Global Impression of Change (PGIC)</h3> <p class="subtitle">Da quando ha iniziato il trattamento, può descrivere il cambiamento, se c’è stato, per quanto riguarda::</p> <?php if(isset($esame['pgic_risposte'])&&is_array($esame['pgic_risposte'])&&count($esame['pgic_risposte'])>0):?><table><thead><tr><th>Valutazione</th><?php foreach($colonnePGIC as $l):?><th><?php echo htmlspecialchars($l);?></th><?php endforeach;?></tr></thead><tbody><?php foreach($etichettePGIC as $v=>$et):?><tr><td class="pgic-label"><?php echo $v." - ".htmlspecialchars($et);?></td><?php foreach($colonnePGIC as $k=>$l): $rS=$esame['pgic_risposte'][$k]??null; $isS=($rS!==null&&$rS==$v);?><td><?php echo $isS?'<span class="selected-mark">✔️</span>':'-';?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table><p><strong>Totale:</strong> <?php echo $esame['pgic_totale']??'N/D';?>/28</p><?php else:?><p class="italic-ref">(N/D)</p><?php endif;?>
             <?php if(!empty($esame['screening_prossimo'])):?><h3>Screening tra:</h3><ul><?php foreach($esame['screening_prossimo'] as $i):?><li><?php echo htmlspecialchars($i);?></li><?php endforeach;?></ul><?php endif;?>
             <!-- Firma e Bottone Stampa Rimossi dal contenuto visibile -->
        <?php endif; ?>
    </div> <!-- Fine exam-content-wrapper -->

    <!-- Passaggio dati PHP a JS -->
    <script> const phpData = { esame: <?php echo json_encode($esame_data_for_js); ?>, paziente: <?php echo json_encode($paziente_data_for_js); ?>, domandeDN4: <?php echo json_encode($domandeDN4); ?>, etichettePGIC: <?php echo json_encode($etichettePGIC); ?>, colonnePGIC: <?php echo json_encode($colonnePGIC); ?>, puntiVib: <?php echo json_encode($puntiVib); ?> }; const phpDataLoaded = <?php echo ($esame && $paziente) ? 'true' : 'false'; ?>; </script>

    <!-- SCRIPT PER LA COPIA CON IMMAGINI EMBEDDED e HTML PULITO (Include tabelle corrette) -->
    <script>
        // Funzione helper imageUrlToBase64 (uguale a prima)
        async function imageUrlToBase64(url, index) { const logPrefix = `[Img ${index + 1}]:`; try { console.log(`${logPrefix} Fetch ${url}...`); const r = await fetch(url+'?'+Date.now(), {cache:'no-store'}); if (!r.ok) throw new Error(`HTTP ${r.status}`); const b = await r.blob(); console.log(`${logPrefix} Blob: ${b.type}`); return new Promise((res, rej)=>{ const reader = new FileReader(); reader.onloadend=()=>res(reader.result); reader.onerror=(e)=>rej(`FR error`); reader.readAsDataURL(b); }); } catch (e) { console.error(`${logPrefix} Error:`, e); return null; } }

        // Funzione principale per COSTRUIRE e COPIARE HTML PULITO
        async function copyCleanHtmlWithImages() {
            const feedbackSpan = document.getElementById('copyFeedback'); if (!feedbackSpan) return;
            if (!phpDataLoaded || !phpData.esame || !phpData.paziente) { alert("Errore: Dati non caricati."); return; }
            if (!navigator.clipboard || !navigator.clipboard.write) { alert('Errore: API Appunti non supportata.'); return; }
            console.log("--- Avvio Copia HTML Pulito ---"); feedbackSpan.textContent = " Elaboro..."; feedbackSpan.style.color = 'orange'; feedbackSpan.classList.add('visible');
            const imagePromises = []; const imageBase64Map = {}; const imageKeys = ['foto1','foto2','foto3','foto4']; let imageIndex = 0;
            imageKeys.forEach(key => { const filename = phpData.esame[key]; if (filename) { const imageUrl = `../foto/${filename}`; imagePromises.push(imageUrlToBase64(imageUrl, imageIndex).then(base64 => { if (base64) imageBase64Map[filename] = base64; })); imageIndex++; } });
            try {
                await Promise.all(imagePromises); console.log("Conversione immagini OK."); feedbackSpan.textContent = " Costruisco HTML...";

                // *** COSTRUZIONE HTML PULITO ***
                let cleanHtml = `<div style="font-family: Arial, sans-serif; line-height: 1.5; font-size: 11pt; max-width: 700px; margin: auto;">`;
                // cleanHtml += `<h2 style="text-align:center; border-bottom:1px solid #ccc; padding-bottom:5px; margin-bottom:15px;">Dettaglio Esame</h2>`; // Titolo Rimosso
                cleanHtml += `<p><strong>Paziente:</strong> ${phpData.paziente.cognome} ${phpData.paziente.nome}</p>`;
                cleanHtml += `<p><strong>Data Nascita:</strong> ${phpData.paziente.data_nascita ? new Date(phpData.paziente.data_nascita).toLocaleDateString('it-IT') : 'N/D'}</p>`;
                cleanHtml += `<p><strong>Data Esame:</strong> ${phpData.esame.data_esame ? new Date(phpData.esame.data_esame).toLocaleDateString('it-IT') : 'N/D'}</p><hr style="border:none; border-top:1px solid #eee; margin:15px 0;">`;
                cleanHtml += `<h3 style="margin-top:15px; font-size:1.1em;">Immagini</h3><div style="text-align:center; margin-bottom:15px;">`; imageKeys.forEach(key => { const filename = phpData.esame[key]; if (filename && imageBase64Map[filename]) { cleanHtml += `<img src="${imageBase64Map[filename]}" alt="${key}" style="max-width: 45%; height: auto; margin: 5px; border: 1px solid #eee;"><br style="display: block; content: ''; margin-bottom: 5px;">`; } else if (filename) { cleanHtml += `<p style="font-style:italic; font-size:0.9em;">[Img ${key} non caricata]</p>`; } }); cleanHtml += `</div>`;
                const addSectionClean = (t, c) => (c && c.trim() !== '' && c.trim() !== '<p>(N/D)</p>' && c.trim() !== '<p style="font-style:italic;">(N/D)</p>' && c.trim() !== '<ul></ul>' && c.trim() !== '<ol></ol>') ? `<h3 style="margin-top:20px; padding-bottom:3px; border-bottom:1px solid #eee; font-size:1.1em;">${t}</h3>${c}` : '';
                const formatListClean = (i) => i && i.length > 0 ? `<ul style="padding-left:20px; margin-top:5px;">${i.map(it => `<li style="margin-bottom:3px;">${it}</li>`).join('')}</ul>` : '<p style="font-style:italic; font-size:0.9em;">(N/D)</p>';
                const formatNotesClean = (n) => n ? `<p style="margin-top:5px; white-space:pre-wrap;">${n.replace(/\n/g, '<br>')}</p>` : '<p style="font-style:italic; font-size:0.9em;">(N/D)</p>';
                cleanHtml += addSectionClean('Note Podografia', formatNotesClean(phpData.esame.note_podografia));
                cleanHtml += addSectionClean('Esame Obiettivo', formatListClean(phpData.esame.esame_obiettivo));
                cleanHtml += addSectionClean('Ipercheratosi', formatListClean(phpData.esame.ipercheratosi));
                cleanHtml += addSectionClean('Altre Patologie', formatNotesClean(phpData.esame.altre_patologie));
                // Tabella Sensibilità CORRETTA
                let vibTableClean = ''; if (phpData.esame.sensibilita_vibratoria && Object.keys(phpData.esame.sensibilita_vibratoria).length > 0) { vibTableClean=`<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:9pt;"><thead><tr><th style="background:#eee;">Punto</th><th style="background:#eee;">DX</th><th style="background:#eee;">SX</th></tr></thead><tbody>`; phpData.puntiVib.forEach(p=>{vibTableClean+=`<tr><td>${p.charAt(0).toUpperCase()+p.slice(1)}</td><td>${phpData.esame.sensibilita_vibratoria['vib_'+p+'_dx']??'-'}</td><td>${phpData.esame.sensibilita_vibratoria['vib_'+p+'_sx']??'-'}</td></tr>`;}); vibTableClean+=`</tbody></table><p style="font-size:9pt;font-style:italic; text-align:center;">Rif:<25</p>`;} else {vibTableClean='<p style="font-style:italic; font-size:0.9em;">(N/D)</p>';} cleanHtml += addSectionClean('Sensibilità Vibratoria', '<p style="font-size:9pt;font-style:italic; text-align:center;">(Biotesiometer)</p>' + vibTableClean);
                cleanHtml += addSectionClean('Osservazioni', formatNotesClean(phpData.esame.osservazioni));
                // DN4 CORRETTO
                let dn4HtmlClean=''; if(phpData.esame.dn4_risposte&&Object.keys(phpData.esame.dn4_risposte).length>0){dn4HtmlClean='<ol style="padding-left:20px; margin-top:5px;">';Object.keys(phpData.domandeDN4).forEach(k=>{dn4HtmlClean+=`<li style="margin-bottom:3px;">${phpData.domandeDN4[k]}: <strong>${phpData.esame.dn4_risposte[k]??'N/D'}</strong></li>`;}); dn4HtmlClean+=`</ol><p><strong>Punteggio: ${phpData.esame.dn4_punteggio??'N/D'}/10</strong></p>`;} else {dn4HtmlClean='<p style="font-style:italic; font-size:0.9em;">(N/D)</p>';} cleanHtml+=addSectionClean('Questionario DN4', dn4HtmlClean);
                cleanHtml+=addSectionClean('Scala NRS', `<p><strong>Valore: ${phpData.esame.nrs_dolore ?? 'N/D'}</strong></p>`);
                // PGIC CORRETTO
                let pgicHtmlClean=''; if(phpData.esame.pgic_risposte&&Object.keys(phpData.esame.pgic_risposte).length>0){pgicHtmlClean='<p style="font-size:9pt;font-style:italic; text-align:center;">Cambiamento:</p>'; pgicHtmlClean+=`<table border="1" cellpadding="4" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:9pt;"><thead><tr><th style="background:#eee;">Valutazione</th>`; Object.values(phpData.colonnePGIC).forEach(l=>{pgicHtmlClean+=`<th style="background:#eee;">${l}</th>`;}); pgicHtmlClean+=`</tr></thead><tbody>`; Object.keys(phpData.etichettePGIC).sort((a,b)=>b-a).forEach(v=>{ pgicHtmlClean+=`<tr><td style="text-align:left;">${v} - ${phpData.etichettePGIC[v]}</td>`; Object.keys(phpData.colonnePGIC).forEach(k=>{const r=phpData.esame.pgic_risposte[k]??null;pgicHtmlClean+=`<td>${(r!==null&&r==v)?'✔️':'-'}</td>`;}); pgicHtmlClean+=`</tr>`; }); pgicHtmlClean+=`</tbody></table><p><strong>Totale: ${phpData.esame.pgic_totale??'N/D'}/28</strong></p>`;} else {pgicHtmlClean='<p style="font-style:italic; font-size:0.9em;">(N/D)</p>';} cleanHtml+=addSectionClean('PGIC', pgicHtmlClean);
                cleanHtml+=addSectionClean('Screening tra:', formatListClean(phpData.esame.screening_prossimo));
                cleanHtml+=`</div>`; // Chiudi wrapper

                // Copia negli appunti
                const htmlBlob=new Blob([cleanHtml],{type:'text/html'}); const tempDiv=document.createElement('div'); tempDiv.innerHTML=cleanHtml; const plainText=tempDiv.innerText||tempDiv.textContent||''; const textBlob=new Blob([plainText],{type:'text/plain'}); const clipboardItem=new ClipboardItem({'text/html':htmlBlob,'text/plain':textBlob}); await navigator.clipboard.write([clipboardItem]);
                console.log("Contenuto pulito copiato."); feedbackSpan.textContent="Copiato!"; feedbackSpan.style.color='green'; setTimeout(()=>{feedbackSpan.classList.remove('visible'); feedbackSpan.textContent='';},2500);
            } catch (error) { console.error("Errore copia:",error); alert(`Errore copia: ${error.message||error}. Prova manuale.`); feedbackSpan.textContent="Errore!"; feedbackSpan.style.color='red'; setTimeout(()=>{feedbackSpan.classList.remove('visible'); feedbackSpan.textContent=''; feedbackSpan.style.color='green';},3500); }
        }
    </script>

</body>
</html>