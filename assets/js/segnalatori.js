document.addEventListener('DOMContentLoaded', function () {
    const segnalatoreForm = document.getElementById('segnalatoreForm');
    const segnalatoreTable = document.getElementById('segnalatoreTable');

    // Funzione per caricare l'elenco dei segnalatori
    function loadSegnalatori() {
        fetch('../backend/segnalatori.php')
            .then(response => response.json())
            .then(data => {
                segnalatoreTable.innerHTML = ''; // Svuota la tabella
                data.forEach(segnalatore => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${segnalatore.id}</td>
                        <td>${segnalatore.nome}</td>
                    `;
                    segnalatoreTable.appendChild(row);
                });
            })
            .catch(error => console.error('Errore nel caricamento dei segnalatori:', error));
    }

    // Funzione per aggiungere un nuovo segnalatore
    segnalatoreForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const nome = document.getElementById('nomeSegnalatore').value;
        if (!nome) {
            alert('Il nome del segnalatore è obbligatorio.');
            return;
        }

        fetch('../backend/segnalatori.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    segnalatoreForm.reset();
                    loadSegnalatori();
                } else {
                    alert('Errore: ' + data.message);
                }
            })
            .catch(error => console.error('Errore nell\'aggiunta del segnalatore:', error));
    });

    // Carica i segnalatori all'avvio della pagina
    loadSegnalatori();
});