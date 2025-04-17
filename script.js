// Stato globale per le immagini, la camera e la modalità
const AppState = {
  images: [],           // Array per contenere le 4 immagini base64 NUOVE scattate
  stream: null,           // Oggetto stream della webcam
  existingExamData: null, // Conterrà i dati dell'esame caricato (se in modifica)
  isEditing: false,       // Flag per indicare se siamo in modalità modifica/duplicazione
  cameraInitialized: false // Flag per evitare inizializzazioni multiple
};

// --- ELEMENTI DOM (Trovati una sola volta per efficienza) ---
let videoElement, canvasElement, previewDivElement, scattaBtnElement;
let nomeCompletoElement, dataEsameElement;
let notePodografiaElement, altrePatologieElement, osservazioniElement;
let dn4ScoreDisplay, nrsScoreDisplay, pgicScoreDisplay;
let dn4ScoreFinal, nrsScoreFinal, pgicScoreFinal;

// Funzione per trovare gli elementi DOM all'avvio
function findDOMElements() {
    videoElement = document.getElementById("videoCam");
    canvasElement = document.getElementById("canvasShot");
    previewDivElement = document.getElementById("preview");
    scattaBtnElement = document.getElementById("scattaBtn");
    nomeCompletoElement = document.getElementById("nomeCompleto");
    dataEsameElement = document.getElementById("dataEsame");
    notePodografiaElement = document.getElementById("notePodografia");
    altrePatologieElement = document.getElementById("altrePatologie");
    osservazioniElement = document.getElementById("osservazioni");
    dn4ScoreDisplay = document.getElementById('dn4ScoreDisplay');
    nrsScoreDisplay = document.getElementById('nrsScoreDisplay');
    pgicScoreDisplay = document.getElementById('pgicScoreDisplay');
    dn4ScoreFinal = document.getElementById('dn4ScoreFinal');
    nrsScoreFinal = document.getElementById('nrsScoreFinal');
    pgicScoreFinal = document.getElementById('pgicScoreFinal');

    // Verifica elementi essenziali camera
    if (!videoElement || !canvasElement || !previewDivElement || !scattaBtnElement) {
        console.error("Errore Critico: Elementi HTML essenziali per la fotocamera non trovati!");
    }
     if (!nomeCompletoElement || !dataEsameElement) {
        console.warn("Elementi nomeCompleto o dataEsame non trovati.");
    }
}

// Funzione per impostare gli event listener (calcoli, scatta bottone)
function setupEventListeners() {
    console.log("Setup event listeners...");

    // Bottone Scatta (SEMPRE agganciato)
    if (scattaBtnElement) {
        scattaBtnElement.onclick = handleScattaClick;
    } else {
         console.error("Bottone Scatta non trovato, impossibile agganciare evento.");
    }

    // Calcoli automatici
    document.querySelectorAll('#dn4 input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', calcolaDN4);
    });
    document.querySelectorAll('#nrsContainer input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', calcolaNRS);
    });
    document.querySelectorAll('.pgic-table input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', calcolaPGIC);
    });

    console.log("Event listeners configurati.");
}

// Funzione specifica per gestire il click su "Scatta"
function handleScattaClick() {
     console.log("Bottone Scatta premuto.");
     if (!AppState.cameraInitialized && !AppState.stream) {
        console.log("Camera non inizializzata, tento avvio stream...");
        startVideoStream().then(success => {
            if (success) captureAndProcessImage();
            else alert("Impossibile avviare webcam.");
        });
     } else if (AppState.stream) {
         captureAndProcessImage();
     } else {
         alert("Webcam non disponibile o bloccata.");
     }
}

// Funzione per avviare SOLO lo stream video
async function startVideoStream() {
    if (AppState.cameraInitialized) {
        console.log("Tentativo di avvio camera già inizializzata (o fallita prima).");
        return AppState.stream != null; // Ritorna true solo se lo stream è effettivamente attivo
    }

    AppState.cameraInitialized = true; // Segna come tentativo fatto

    if (!videoElement || !previewDivElement) {
        console.error("Elementi video/preview non disponibili per avviare stream.");
        return false;
    }

    try {
        console.log("Richiesta accesso webcam...");
        const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 400, height: 300 } });
        AppState.stream = stream;
        videoElement.srcObject = stream;
        await videoElement.play();
        previewDivElement.style.display = "block";
        console.log("Webcam avviata con successo.");
        return true;
    } catch (err) {
        console.error("Errore accesso webcam:", err);
        alert(`Impossibile accedere alla webcam: ${err.message}`);
        if(previewDivElement) previewDivElement.innerHTML = "<p style='color:red;'>Errore webcam</p>";
        AppState.stream = null; // Assicura che lo stream sia null in caso di errore
        return false;
    }
}

// Funzione per catturare l'immagine dal video e processarla
function captureAndProcessImage() {
     if (!AppState.stream || !canvasElement || !videoElement || !previewDivElement) {
         alert("Impossibile scattare: webcam non pronta o elementi mancanti.");
         console.error("captureAndProcessImage chiamato ma stream/elementi non pronti.");
         return;
     }
    console.log("Cattura immagine...");
    const ctx = canvasElement.getContext("2d");
    ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
    console.log("Immagine disegnata su canvas.");

    // Ferma lo stream e nascondi preview
    AppState.stream.getTracks().forEach(track => track.stop());
    AppState.stream = null;
    videoElement.srcObject = null;
    previewDivElement.style.display = "none";

    // Genera rotazioni
    const baseImage = canvasElement.toDataURL("image/jpeg", 0.85);
    const angles = [0, 90, 180, 270];
    Promise.all(angles.map(angle => rotateImage(baseImage, angle)))
        .then(rotatedImages => {
            console.log("Immagini ruotate generate.");
            AppState.images = rotatedImages; // Salva NUOVE immagini
            rotatedImages.forEach((src, index) => {
                const imgElement = document.getElementById(`img${index + 1}`);
                if (imgElement) imgElement.src = src;
            });
        })
        .catch(err => {
            console.error("Errore rotazione immagini:", err);
            alert("Errore durante la rotazione delle immagini.");
        });
}


// Funzione per recuperare e precompilare i dati di un esame esistente
async function loadExistingExam(examId) {
    console.log(`!!! CHIAMATA loadExistingExam con ID: ${examId}`); // Log per conferma
    if (!examId) {
         console.error("loadExistingExam chiamato senza un ID valido.");
         return; // Sicurezza aggiuntiva
    }
    AppState.isEditing = true;

    try {
        const response = await fetch(`php/get_dati_esame.php?id=${examId}`);
        if (!response.ok) throw new Error(`Errore HTTP ${response.status}`);
        const examData = await response.json();
        if (examData.error) throw new Error(examData.error);

        AppState.existingExamData = examData;
        console.log("Dati esame caricati:", examData);

        // --- Precompila Form ---
        const params = new URLSearchParams(location.search);
        const isDuplicated = params.get("duplicated") === "1";
         if (dataEsameElement) {
             // Se duplicato, usa data odierna, altrimenti usa data salvata
             dataEsameElement.value = isDuplicated
                ? new Date().toISOString().split("T")[0]
                : (examData.data_esame || new Date().toISOString().split("T")[0]);
         }

        // Immagini
        const fotoBasePath = 'foto/';
        let hasImages = false;
        for (let i = 1; i <= 4; i++) {
            const imgElement = document.getElementById(`img${i}`);
            const fotoKey = `foto${i}`;
            if (examData[fotoKey] && imgElement) {
                 // Nota: il percorso qui è relativo a esame.html, non a script.js
                 imgElement.src = fotoBasePath + examData[fotoKey];
                 hasImages = true;
            } else if(imgElement) {
                 imgElement.src = '';
                 imgElement.alt = `Foto ${i} non disponibile`;
            }
        }
        if (hasImages && previewDivElement) {
            previewDivElement.style.display = "none";
             AppState.cameraInitialized = true; // Non serve ri-avviare la camera
             console.log("Esame esistente con immagini, webcam nascosta.");
        } else {
            // Non ci sono immagini SALVATE per questo esame, avvia la camera
             console.log("Esame esistente SENZA immagini, avvio webcam...");
             await startVideoStream();
        }

        // Campi Testo
        if(notePodografiaElement) notePodografiaElement.value = examData.note_podografia || '';
        if(altrePatologieElement) altrePatologieElement.value = examData.altre_patologie || '';
        if(osservazioniElement) osservazioniElement.value = examData.osservazioni || '';

        // Checkbox
        populateCheckboxes('obiettivo', examData.esame_obiettivo || []);
        populateCheckboxes('ipercheratosi', examData.ipercheratosi || []);
        populateCheckboxes('screening', examData.screening_prossimo || []);

        // Tabella Vibrazioni
        const vibInputs = document.querySelectorAll('.vibratoria-table input[type="number"]');
        vibInputs.forEach(input => {
            input.value = (examData.sensibilita_vibratoria && examData.sensibilita_vibratoria[input.name]) ? examData.sensibilita_vibratoria[input.name] : '';
        });

        // Radio Buttons
        populateRadiosFromObject('dn4', examData.dn4_risposte || {});
        populateRadiosSimple('nrs', examData.nrs_dolore);
        populateRadiosFromObject('pgic', examData.pgic_risposte || {});

        // Ricalcola/Visualizza Punteggi DOPO aver popolato i radio/check
        calcolaDN4();
        calcolaNRS();
        calcolaPGIC();

        console.log("Form precompilato con dati esistenti.");

    } catch (error) {
        console.error("Errore caricamento dati esame esistente:", error);
        // Mostra l'errore specifico restituito da get_dati_esame.php se possibile
        alert(`Impossibile caricare i dati dell'esame: ${error.message}`);
    }
}

// Funzioni Helper per Popolare Form (uguali a prima)
function populateCheckboxes(groupName, values) {
    document.querySelectorAll(`input[type="checkbox"][name="${groupName}"]`).forEach(cb => {
        cb.checked = values.includes(cb.value);
    });
}
function populateRadiosFromObject(prefix, dataObject) {
     document.querySelectorAll(`input[type="radio"][name^="${prefix}_"]`).forEach(radio => radio.checked = false);
    for (const radioName in dataObject) {
        const radio = document.querySelector(`input[type="radio"][name="${radioName}"][value="${dataObject[radioName]}"]`);
        if (radio) radio.checked = true;
    }
}
function populateRadiosSimple(radioGroupName, value) {
     document.querySelectorAll(`input[type="radio"][name="${radioGroupName}"]`).forEach(r => r.checked = false);
     if(value !== null && value !== undefined) {
         const radio = document.querySelector(`input[type="radio"][name="${radioGroupName}"][value="${value}"]`);
         if (radio) radio.checked = true;
     }
}


// Funzioni Calcolo Punteggi (uguali a prima)
function calcolaDN4() {
  const radios = document.querySelectorAll('#dn4 input[type="radio"][value="SI"]:checked');
  const punteggio = radios.length;
  if (dn4ScoreDisplay) dn4ScoreDisplay.textContent = punteggio;
  if (dn4ScoreFinal) dn4ScoreFinal.textContent = punteggio;
}
function calcolaNRS() {
  const radioChecked = document.querySelector('#nrsContainer input[type="radio"][name="nrs"]:checked');
  const punteggio = radioChecked ? radioChecked.value : "N/D";
  if (nrsScoreDisplay) nrsScoreDisplay.textContent = punteggio;
  if (nrsScoreFinal) nrsScoreFinal.textContent = punteggio;
}
function calcolaPGIC() {
  const sezioni = ["fisica", "sintomi", "emozioni", "vita"];
  let somma = 0;
  sezioni.forEach(sezione => {
    const radioChecked = document.querySelector(`input[type="radio"][name="pgic_${sezione}"]:checked`);
    if (radioChecked) { somma += parseInt(radioChecked.value, 10); }
  });
  if (pgicScoreDisplay) pgicScoreDisplay.textContent = somma;
  if (pgicScoreFinal) pgicScoreFinal.textContent = somma;
}

// Funzione rotateImage (uguale a prima)
function rotateImage(base64, angle) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      const sourceWidth = img.width; const sourceHeight = img.height;
      const canvas = document.createElement("canvas"); const ctx = canvas.getContext("2d");
      if (angle === 90 || angle === 270) { canvas.width = sourceHeight; canvas.height = sourceWidth; }
      else { canvas.width = sourceWidth; canvas.height = sourceHeight; }
      ctx.translate(canvas.width / 2, canvas.height / 2);
      ctx.rotate(angle * Math.PI / 180);
      ctx.drawImage(img, -sourceWidth / 2, -sourceHeight / 2);
      resolve(canvas.toDataURL("image/jpeg", 0.8));
    };
    img.onerror = (err) => { reject("Errore caricamento img per rotazione"); }
    img.src = base64;
  });
}

// Funzione salvaEsame (uguale a prima, chiama salva_modifica_esame.php)
function salvaEsame() {
  console.log("Salvataggio esame avviato...");
  const params = new URLSearchParams(location.search);
  // Prendi ID paziente da 'paziente_id' o fallback su 'id' se il link è vecchio
  const pazienteId = params.get("paziente_id") || params.get("id");
  const esameId = AppState.existingExamData?.id; // ID Esame solo se stiamo modificando

  if (!pazienteId || !/^[1-9]\d*$/.test(pazienteId)) { // Controllo ID paziente valido
    alert("❌ ID paziente mancante o non valido nell'URL! Impossibile salvare.");
    return;
  }

   // Se NON stiamo modificando E non ci sono immagini NUOVE
  if (!AppState.isEditing && AppState.images.length < 4) {
     if (!confirm("⚠️ Non sono state scattate le foto. Salvare comunque l'esame?")) return;
     while (AppState.images.length < 4) { AppState.images.push(""); }
  }

  const payload = {
    paziente_id: pazienteId,
    esame_id: esameId, // Sarà null per nuovi esami
    nome: params.get("nome") || "",
    cognome: params.get("cognome") || "",
    data_esame: document.getElementById("dataEsame")?.value || new Date().toISOString().split("T")[0],
    // Foto: Invia base64 SOLO se ci sono immagini *nuove* in AppState.images
    foto1: AppState.images[0] || "",
    foto2: AppState.images[1] || "",
    foto3: AppState.images[2] || "",
    foto4: AppState.images[3] || "",
    // Altri campi...
    notePodografia: document.getElementById("notePodografia")?.value || "",
    altrePatologie: document.getElementById("altrePatologie")?.value || "",
    osservazioni: document.getElementById("osservazioni")?.value || "",
    obiettivo: Array.from(document.querySelectorAll('input[name="obiettivo"]:checked')).map(el => el.value),
    ipercheratosi: Array.from(document.querySelectorAll('input[name="ipercheratosi"]:checked')).map(el => el.value),
    screening: Array.from(document.querySelectorAll('input[name="screening"]:checked')).map(el => el.value),
    sensibilita_vibratoria: Object.fromEntries(Array.from(document.querySelectorAll('.vibratoria-table input[type="number"]')).map(el => [el.name, el.value || null])),
    dn4_risposte: Object.fromEntries(Array.from(document.querySelectorAll('#dn4 input[type="radio"]:checked')).map(el => [el.name, el.value])),
    pgic_risposte: Object.fromEntries(Array.from(document.querySelectorAll('.pgic-table input[type="radio"]:checked')).map(el => [el.name, el.value])),
    dn4_punteggio: parseInt(document.getElementById('dn4ScoreDisplay')?.textContent || '0', 10),
    nrs_dolore: document.querySelector('#nrsContainer input[name="nrs"]:checked')?.value || null,
    pgic_totale: parseInt(document.getElementById('pgicScoreDisplay')?.textContent || '0', 10),
  };
   if (payload.nrs_dolore !== null) { payload.nrs_dolore = parseInt(payload.nrs_dolore, 10); }

  console.log("Invio payload:", payload);

  fetch("php/salva_modifica_esame.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(response => response.json())
  .then(data => {
    console.log("Risposta server:", data);
    if (data.success) {
      alert(`✅ Esame ${esameId ? 'modificato' : 'salvato'}!`);
      // Pulisci AppState images dopo salvataggio riuscito
      AppState.images = [];
      window.location.href = `admin/esami.php?pid=${pazienteId}`;
    } else {
      alert(`❌ Errore salvataggio: ${data.error || 'Errore sconosciuto.'}`);
    }
  })
  .catch(error => {
    alert("❌ Errore di rete o server.");
    console.error('Errore fetch:', error);
  });
}

// --- Inizializzazione all'avvio della pagina ---
window.addEventListener("DOMContentLoaded", () => {
  console.log("--- INIZIO DOMContentLoaded ---");
  const params = new URLSearchParams(location.search);
  const examIdFromUrl = params.get("id");
  const pazienteIdFromUrl = params.get("paziente_id");
  const nome = params.get("nome") || "";
  const cognome = params.get("cognome") || "";

  console.log("Parametri URL rilevati:");
  console.log("  ID Esame (id):", examIdFromUrl);
  console.log("  ID Paziente (paziente_id):", pazienteIdFromUrl);
  console.log("  Nome:", nome);
  console.log("  Cognome:", cognome);

  findDOMElements(); // Trova elementi

  const isValidExamId = examIdFromUrl && /^[1-9]\d*$/.test(examIdFromUrl);
  console.log("ID Esame è valido per modifica/duplicazione?", isValidExamId);

  if (nomeCompletoElement) {
    nomeCompletoElement.textContent = `${nome} ${cognome}`.trim() || "Paziente non specificato";
  }
  if (dataEsameElement) dataEsameElement.valueAsDate = new Date(); // Default oggi

  setupEventListeners(); // Aggancia eventi

  // Logica Nuovo Esame vs Modifica/Duplica
  if (isValidExamId) {
      console.log(`--> ESEGUO loadExistingExam(${examIdFromUrl})`);
      // Non chiamare startVideoStream qui, lo farà loadExistingExam se necessario
      loadExistingExam(examIdFromUrl); // Gestisce tutto, inclusa camera se mancano foto
  } else {
      console.log("--> Modalità Nuovo Esame: ESEGUO startVideoStream() e calcoli default");
      AppState.isEditing = false;
      startVideoStream(); // Avvia camera per nuovo esame
      // Calcola punteggi iniziali basati sui default HTML
      calcolaDN4();
      calcolaNRS();
      calcolaPGIC();
  }
  console.log("--- FINE DOMContentLoaded ---");
});