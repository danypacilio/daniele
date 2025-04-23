document.addEventListener('DOMContentLoaded', function () {
    const headerElement = document.getElementById('header');
    if (headerElement) {
        fetch('header.html')
            .then(response => response.text())
            .then(html => {
                headerElement.innerHTML = html;
            })
            .catch(error => console.error('Errore nel caricamento dell\'header:', error));
    }
});