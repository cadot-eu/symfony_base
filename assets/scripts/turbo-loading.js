// assets/js/turbo-loading.js
document.addEventListener('turbo:before-fetch-request', function () {
    document.body.classList.add('turbo-loading');
});

document.addEventListener('turbo:before-fetch-response', function () {
    document.body.classList.remove('turbo-loading');
});

// En cas d'erreur, assurez-vous que l'indicateur de chargement disparaît aussi
document.addEventListener('turbo:fetch-request-error', function () {
    document.body.classList.remove('turbo-loading');
});