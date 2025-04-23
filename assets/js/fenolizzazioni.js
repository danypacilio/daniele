document.addEventListener('DOMContentLoaded', function () {
    const fenolizzazioniForm = document.getElementById('fenolizzazioniForm');
    const fenolizzazioniTable = document.getElementById('fenolizzazioniTable');
    const resetFormButton = document.getElementById('resetForm');

    // Fetch and populate fenolizzazioni
    function loadFenolizzazioni() {
        fetch('../backend/fenolizzazioni.php')
            .then(response => response.json())
            .then(data => {
                fenolizzazioniTable.innerHTML = '';
                data.forEach(fenolizzazione => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${fenolizzazione.nome}</td>
                        <td>${fenolizzazione.cognome}</td>
                        <td>${fenolizzazione.data}</td>
                        <td>${fenolizzazione.tempo}</td>
                        <td>${fenolizzazione.tampone}</td>
                        <td>${fenolizzazione.recidiva || 'N/A'}</td>
                        <td>${fenolizzazione.segnalatore}</td>
                        <td>
                            <button onclick="editFenolizzazione(${fenolizzazione.id})"><i class="fas fa-edit"></i> Modifica</button>
                            <button onclick="deleteFenolizzazione(${fenolizzazione.id})"><i class="fas fa-trash"></i> Elimina</button>
                        </td>
                    `;
                    fenolizzazioniTable.appendChild(row);
                });
            });
    }

    // Add or Update fenolizzazione
    fenolizzazioniForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('id').value;
        const formData = {
            id: id ? parseInt(id, 10) : null,
            nome: document.getElementById('nome').value,
            cognome: document.getElementById('cognome').value,
            data: document.getElementById('data').value,
            tempo: parseInt(document.getElementById('tempo').value, 10),
            tampone: document.getElementById('tampone').value,
            recidiva: document.getElementById('recidiva').value,
            segnalatore_id: parseInt(document.getElementById('segnalatore').value, 10)
        };

        const method = id ? 'PUT' : 'POST';

        fetch('../backend/fenolizzazioni.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        }).then(() => {
            fenolizzazioniForm.reset();
            document.getElementById('id').value = '';
            loadFenolizzazioni();
        });
    });

    // Edit fenolizzazione
    window.editFenolizzazione = function (id) {
        fetch('../backend/fenolizzazioni.php')
            .then(response => response.json())
            .then(data => {
                const fenolizzazione = data.find(item => item.id === id);
                document.getElementById('id').value = fenolizzazione.id;
                document.getElementById('nome').value = fenolizzazione.nome;
                document.getElementById('cognome').value = fenolizzazione.cognome;
                document.getElementById('data').value = fenolizzazione.data;
                document.getElementById('tempo').value = fenolizzazione.tempo;
                document.getElementById('tampone').value = fenolizzazione.tampone;
                document.getElementById('recidiva').value = fenolizzazione.recidiva || '';
                loadSegnalatori(() => {
                    document.getElementById('segnalatore').value = fenolizzazione.segnalatore_id;
                });
            });
    };

    // Delete fenolizzazione
    window.deleteFenolizzazione = function (id) {
        if (confirm('Sei sicuro di voler eliminare questa fenolizzazione?')) {
            fetch('../backend/fenolizzazioni.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            }).then(() => loadFenolizzazioni());
        }
    };

    // Load segnalatori
    function loadSegnalatori(callback) {
        fetch('../backend/segnalatori.php')
            .then(response => response.json())
            .then(data => {
                const segnalatoreSelect = document.getElementById('segnalatore');
                segnalatoreSelect.innerHTML = '<option value="" disabled selected>Seleziona un segnalatore</option>';
                data.forEach(segnalatore => {
                    const option = document.createElement('option');
                    option.value = segnalatore.id;
                    option.textContent = segnalatore.nome;
                    segnalatoreSelect.appendChild(option);
                });
                if (callback) callback();
            });
    }

    // Reset form
    resetFormButton.addEventListener('click', function () {
        fenolizzazioniForm.reset();
        document.getElementById('id').value = '';
    });

    // Initialize
    loadSegnalatori();
    loadFenolizzazioni();
});