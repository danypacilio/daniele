document.addEventListener('DOMContentLoaded', function () {
    // Fetch statistics
    fetch('../backend/statistiche.php')
        .then(response => response.json())
        .then(data => {
            // Populate general statistics
            document.getElementById('totaleInterventi').textContent = data.totaleInterventi;
            document.getElementById('totaleRecidive').textContent = data.totaleRecidive;
            document.getElementById('percentualeRecidive').textContent = data.percentualeRecidive + '%';
            document.getElementById('interventiSenzaRecidive').textContent = data.interventiSenzaRecidive;

            // Populate recidive per tampone
            const recidivePerTampone = document.getElementById('recidivePerTampone');
            data.recidivePerTampone.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `${item.tampone}: ${item.recidive}`;
                recidivePerTampone.appendChild(li);
            });

            // Populate interventi per segnalatore
            const interventiPerSegnalatore = document.getElementById('interventiPerSegnalatore');
            data.interventiPerSegnalatore.forEach(item => {
                const li = document.createElement('li');
                li.textContent = `${item.segnalatore}: ${item.interventi} (${item.percentuale}%)`;
                interventiPerSegnalatore.appendChild(li);
            });
        });
});