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
        // Gestion fichiers annexes
        // Correction : n'ajoute l'event qu'une seule fois, et vide bien extraFiles à chaque affichage
        this.extraFiles = [];
        this.extraFilesList = document.getElementById('extra-files-list');
        this.addExtraFileBtn = document.getElementById('add-extra-file-btn');
        if (this.addExtraFileBtn) {
            // Supprime tout handler précédent pour éviter le double ajout
            const newBtn = this.addExtraFileBtn.cloneNode(true);
            this.addExtraFileBtn.parentNode.replaceChild(newBtn, this.addExtraFileBtn);
            this.addExtraFileBtn = newBtn;
            this.addExtraFileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addExtraFileInput();
            });
        }
        // Vide la liste des fichiers à chaque affichage du formulaire
        if (this.extraFilesList) {
            this.extraFilesList.innerHTML = '';
            this.extraFiles = [];
        }
    }

    addExtraFileInput() {
        const idx = this.extraFiles.length;
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group mb-2';
        wrapper.style.maxWidth = '600px';
        wrapper.innerHTML = `
            <input type="file" class="form-control" data-extra-file-idx="${idx}">
            <button type="button" class="btn btn-danger" data-idx="${idx}">Supprimer</button>
        `;
        this.extraFilesList.appendChild(wrapper);
        this.extraFiles.push(null);
        const input = wrapper.querySelector('input[type="file"]');
        input.addEventListener('change', (e) => {
            this.extraFiles[idx] = e.target.files[0] || null;
        });
        const removeBtn = wrapper.querySelector('button');
        removeBtn.addEventListener('click', (event) => this.removeExtraFileInput(event));
    }

    removeExtraFileInput(event) {
        const idx = parseInt(event.target.getAttribute('data-idx'));
        this.extraFiles[idx] = null;
        event.target.closest('.input-group').remove();
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

    async submit(event) {
        event.preventDefault();
        const form = event.target;
        const data = {
            file: form.file.value,
            method: form.method.value,
            params: form.params.value,
            goal: form.goal.value,
            add_route: !!form.add_route.checked,
            add_docblock: !!form.add_docblock.checked,
        };
        document.getElementById('route-link').innerHTML = '';
        const logsFrame = document.getElementById('logs-frame');
        if (logsFrame) {
            logsFrame.innerHTML = '';
        }

        // Lecture des fichiers annexes
        const filesContent = [];
        for (let file of this.extraFiles) {
            if (file) {
                const content = await file.text();
                filesContent.push({
                    name: file.name,
                    content
                });
            }
        }
        if (filesContent.length > 0) {
            data.extra_files = filesContent;
        }

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
            add_route: !!form.add_route.checked,
            add_docblock: !!form.add_docblock.checked,
            edit: true
        };
        document.getElementById('route-link').innerHTML = '';
        const logsFrame = document.getElementById('logs-frame');
        if (logsFrame) {
            logsFrame.innerHTML = '';
        }
        this.appendTurboStreamLog('Modification de la méthode...');
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

    pollTurboLogs(jobId) {
        const logsFrame = document.getElementById('logs-frame');
        let lastLogs = '';
        let pollCount = 0;
        const maxPolls = 150;
        let stopped = false;
        const interval = setInterval(() => {
            if (stopped) return;
            pollCount++;
            fetch('/method-generator/log/' + jobId)
                .then(r => r.json())
                .then(data => {
                    if (data.logs !== lastLogs) {
                        if (logsFrame) {
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
                        stopped = true;
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