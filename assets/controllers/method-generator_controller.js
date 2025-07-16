import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['logs'];

    connect() {
        const form = document.getElementById('method-form');
        if (form) {
            form.addEventListener('submit', this.submit.bind(this));
        }
    }

    submit(event) {
        event.preventDefault();
        const form = event.target;
        const data = {
            file: form.file.value,
            method: form.method.value,
            params: form.params.value,
            goal: form.goal.value,
            add_route: form.add_route && form.add_route.checked ? true : false,
        };
        document.getElementById('route-link').innerHTML = ''; // Efface le lien à chaque génération
        this.log('Envoi à l\'IA...');
        fetch('/method-generator/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(json => {
                const jobId = json.jobId;
                let lastLogs = '';
                let emptyCount = 0;
                let pollCount = 0;
                const maxPolls = 150; // 150 * 2s = 5 minutes max (ajuste si besoin)
                let interval = setInterval(() => {
                    pollCount++;
                    fetch('/method-generator/log/' + jobId)
                        .then(r => r.json())
                        .then(data => {
                            const logsElem = document.getElementById('logs');
                            if (data.logs !== lastLogs) {
                                logsElem.textContent = data.logs;
                                lastLogs = data.logs;
                                emptyCount = 0;
                            } else if (!data.logs) {
                                emptyCount++;
                                if (emptyCount >= 3) {
                                    clearInterval(interval);
                                    logsElem.textContent += '\n[Process terminé ou introuvable]';
                                }
                            } else {
                                logsElem.textContent += '.';
                            }
                            // Arrêt du polling si "SUCCESS" apparaît n'importe où dans les logs
                            if ((data.logs || '').includes('SUCCESS')) {
                                clearInterval(interval);
                                this.showRouteLink(data.logs);
                            }
                            // Arrêt du polling si une erreur Ollama apparaît dans les logs
                            if ((data.logs || '').includes('Erreur Ollama :')) {
                                clearInterval(interval);
                            }
                            // Arrêt du polling à la fin de l'itération max
                            if (pollCount >= maxPolls) {
                                clearInterval(interval);
                                logsElem.textContent += '\n[Fin du polling après itérations max]';
                            }
                        });
                }, 2000);
            })
            .catch(e => this.log('Erreur : ' + e));
    }

    showRouteLink(logs) {
        // Cherche #[Route('/xxx', name: 'yyy')] dans les logs
        const routeRegex = /#\[Route\(['"]([^'"]+)['"]/;
        const match = logs.match(routeRegex);
        if (match && match[1]) {
            const routePath = match[1];
            // Si le path ne commence pas par /, ajoute-le
            const url = routePath.startsWith('/') ? routePath : '/' + routePath;
            const linkHtml = `<a href="${url}" target="_blank" class="btn btn-success">Tester la route générée (${url})</a>`;
            document.getElementById('route-link').innerHTML = linkHtml;
        }
    }

    log(msg) {
        const logs = document.getElementById('logs');
        logs.textContent += msg + '\n';
        logs.scrollTop = logs.scrollHeight;
    }
}