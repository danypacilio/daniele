document.addEventListener('DOMContentLoaded', function () {
    const segnalatoriList = document.getElementById('segnalatoriList');
    const segnalatoriForm = document.getElementById('segnalatoriForm');
    const nomeSegnalatore = document.getElementById('nomeSegnalatore');

    // Fetch segnalatori
    fetch('../backend/segnalatori.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(segnalatore => {
                const li = document.createElement('li');
                li.textContent = segnalatore.nome;
                segnalatoriList.appendChild(li);
            });
        });

    // Add new segnalatore
    segnalatoriForm.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('../backend/segnalatori.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: nomeSegnalatore.value })
        }).then(response => response.json())
          .then(() => location.reload());
    });
});