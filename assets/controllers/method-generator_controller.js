import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['logs'];

    connect() {
        const form = document.getElementById('method-form');
        if (form) {
            form.addEventListener('submit', this.submit.bind(this));
        }
        const fileSelect = document.getElementById('file');
        const methodList = document.getElementById('method-list');
        const editBtn = document.getElementById('edit-method-btn');
        if (fileSelect) {
            fileSelect.addEventListener('change', () => this.updateMethodList());
            this.updateMethodList();
        }
        if (editBtn) {
            editBtn.addEventListener('click', () => this.editSelectedMethod());
        }
    }

    updateMethodList() {
        const file = document.getElementById('file').value;
        const methodList = document.getElementById('method-list');
        methodList.innerHTML = '';
        if (!file) {
            methodList.style.display = 'none';
            return;
        }
        fetch('/method-generator/methods?file=' + encodeURIComponent(file))
            .then(r => r.json())
            .then(data => {
                if (Array.isArray(data.methods) && data.methods.length > 0) {
                    data.methods.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m;
                        opt.textContent = m;
                        methodList.appendChild(opt);
                    });
                    methodList.style.display = '';
                } else {
                    methodList.style.display = 'none';
                }
            });
    }

    editSelectedMethod() {
        const methodList = document.getElementById('method-list');
        const method = methodList.value;
        if (!method) return;
        document.getElementById('method').value = method;
        this.submitEdit();
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
            add_docblock: form.add_docblock && form.add_docblock.checked ? true : false,
        };
        document.getElementById('route-link').innerHTML = '';
        // Efface les logs UNIQUEMENT ici
        const logsFrame = document.getElementById('logs-frame');
        if (logsFrame) {
            logsFrame.innerHTML = '';
        }
        // Affiche uniquement la première étape côté client pour retour immédiat
        // Correction : n'affiche rien côté client, laisse le backend gérer tous les logs
        fetch('/method-generator/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(json => {
                const jobId = json.jobId;
                this.pollTurboLogs(jobId);
            })
            .catch(e => this.appendTurboStreamLog('Erreur : ' + e));
    }

    submitEdit() {
        const form = document.getElementById('method-form');
        const data = {
            file: form.file.value,
            method: form.method.value,
            params: form.params.value,
            goal: form.goal.value,
            add_route: form.add_route && form.add_route.checked ? true : false,
            add_docblock: form.add_docblock && form.add_docblock.checked ? true : false,
            edit: true
        };
        document.getElementById('route-link').innerHTML = '';
        // Efface les logs UNIQUEMENT ici
        const logsElem = document.getElementById('logs');
        if (logsElem) {
            logsElem.textContent = '';
        }
        this.log('Modification de la méthode...');
        fetch('/method-generator/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(json => {
                const jobId = json.jobId;
                this.pollLogs(jobId);
            })
            .catch(e => this.log('Erreur : ' + e));
    }

    pollLogs(jobId) {
        const logsElem = document.getElementById('logs');
        let lastLogs = '';
        let pollCount = 0;
        const maxPolls = 150;
        const interval = setInterval(() => {
            pollCount++;
            fetch('/method-generator/log/' + jobId)
                .then(r => r.json())
                .then(data => {
                    if (data.logs !== lastLogs) {
                        if (logsElem) {
                            logsElem.textContent = data.logs;
                            logsElem.scrollTop = logsElem.scrollHeight;
                        }
                        lastLogs = data.logs;
                    }
                    // Arrêt du polling si "Fin du process." ou "SUCCESS" ou "Erreur Ollama :" apparaît
                    if (
                        (data.logs || '').includes('Fin du process.') ||
                        (data.logs || '').includes('SUCCESS') ||
                        (data.logs || '').includes('Erreur Ollama :') ||
                        pollCount >= maxPolls
                    ) {
                        clearInterval(interval);
                    }
                });
        }, 2000);
    }

    pollTurboLogs(jobId) {
        const logsFrame = document.getElementById('logs-frame');
        let lastLogs = '';
        let pollCount = 0;
        const maxPolls = 150;
        const interval = setInterval(() => {
            pollCount++;
            fetch('/method-generator/log/' + jobId)
                .then(r => r.json())
                .then(data => {
                    if (data.logs !== lastLogs) {
                        if (logsFrame) {
                            // Ajoute les nouveaux turbo-streams à la frame
                            logsFrame.insertAdjacentHTML('beforeend', data.logs.replace(lastLogs, ''));
                            logsFrame.scrollTop = logsFrame.scrollHeight;
                        }
                        lastLogs = data.logs;
                    }
                    if (
                        (data.logs || '').includes('Fin du process.') ||
                        (data.logs || '').includes('SUCCESS') ||
                        (data.logs || '').includes('Erreur Ollama :') ||
                        pollCount >= maxPolls
                    ) {
                        clearInterval(interval);
                    }
                });
        }, 2000);
    }

    appendTurboStreamLog(msg) {
        const logsFrame = document.getElementById('logs-frame');
        if (logsFrame) {
            const turboStream = `
<turbo-stream action="append" target="logs-frame">
  <template>
    <div style="white-space:pre-wrap;font-family:monospace;">${msg}</div>
  </template>
</turbo-stream>
`;
            logsFrame.insertAdjacentHTML('beforeend', turboStream);
            logsFrame.scrollTop = logsFrame.scrollHeight;
        }
    }

    showRouteLink(logs) {
        const routeRegex = /#\[Route\(['"]([^'"]+)['"]/;
        const match = logs.match(routeRegex);
        if (match && match[1]) {
            let routePath = match[1];
            const paramsField = document.getElementById('params');
            if (paramsField && paramsField.value.trim()) {
                const paramNames = paramsField.value
                    .split(',')
                    .map(s => s.trim().replace(/.*\$/, ''))
                    .filter(Boolean);
                paramNames.forEach(param => {
                    if (!routePath.includes('{' + param + '}')) {
                        if (!routePath.endsWith('/')) routePath += '/';
                        routePath += '{' + param + '}';
                    }
                });
                let url = routePath.replace(/\{([^}]+)\}/g, (m, p) => {
                    if (/id/i.test(p)) return '1';
                    if (/name|nom/i.test(p)) return 'test';
                    if (/lang/i.test(p)) return 'fr';
                    if (/nb|nombre|count/i.test(p)) return '5';
                    return 'val';
                });
                if (!url.startsWith('/')) url = '/' + url;
                const linkHtml = `<a href="${url}" target="_blank" class="btn btn-success">Tester la route générée (${url})</a>`;
                document.getElementById('route-link').innerHTML = linkHtml;
                return;
            }
            const url = routePath.startsWith('/') ? routePath : '/' + routePath;
            const linkHtml = `<a href="${url}" target="_blank" class="btn btn-success">Tester la route générée (${url})</a>`;
            document.getElementById('route-link').innerHTML = linkHtml;
        }
    }

    log(msg) {
        const logsElem = document.getElementById('logs');
        if (logsElem) {
            logsElem.textContent += (logsElem.textContent && !logsElem.textContent.endsWith('\n') ? '\n' : '') + msg + '\n';
            logsElem.scrollTop = logsElem.scrollHeight;
        }
    }
}