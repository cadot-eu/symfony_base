import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['logs'];

    connect() {
        const form = document.getElementById('method-form');
        if (form) {
            form.addEventListener('submit', this.submit.bind(this));
        }
        // Ajout : gestion du bouton Modifier et de la liste des méthodes
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
        // Optionnel : tu pourrais aussi pré-remplir params/goal si tu veux les extraire
        // Lance la génération comme pour submit, mais en mode modification
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
                                // Affichage plus lisible : retour à la ligne pour chaque ligne PHPUnit, sauf pour le poll
                                let logs = data.logs
                                    // Récupère uniquement les lignes importantes de PHPUnit
                                    .replace(/(PHPUnit [^\n]*|Testing [^\n]*|Time: [^\n]*|Memory: [^\n]*|OK \([^\n]*\)|FAILURES!|ERRORS!|WARNINGS!|Tests: [^\n]*, Assertions: [^\n]*, (Failures|Errors|Warnings): [^\n]*)/g, '\n$1\n')
                                    // Nettoie les retours à la ligne multiples
                                    .replace(/\n{3,}/g, '\n\n')
                                    // Nettoie les points du poll
                                    .replace(/\.{5,}/g, match => match.replace(/\./g, ''));
                                logsElem.textContent = logs.trim();
                                lastLogs = data.logs;
                                emptyCount = 0;
                            } else if (!data.logs) {
                                emptyCount++;
                                if (emptyCount >= 3) {
                                    clearInterval(interval);
                                    logsElem.textContent += '\n[Process terminé ou introuvable]';
                                }
                            } else {
                                // Affiche les points pour le poll uniquement si pas déjà à la ligne
                                if (!logsElem.textContent.endsWith('.')) {
                                    logsElem.textContent += '.';
                                } else {
                                    logsElem.textContent += '.';
                                }
                            }
                            // Arrêt du polling si "SUCCESS" ou "OK (" ou "Résultat PHPUnit : OK" ou "Fin du process." apparaît dans les logs
                            if (
                                (data.logs || '').includes('SUCCESS') ||
                                (data.logs || '').match(/OK\s*\(\d+\s*test/) ||
                                (data.logs || '').includes('Résultat PHPUnit : OK') ||
                                (data.logs || '').includes('Fin du process.')
                            ) {
                                clearInterval(interval);
                                this.showRouteLink(data.logs);

                                // Ajout : tente d'afficher le lien vers la route générée si présente dans le code généré
                                const routeRegex = /#\[Route\(['"]([^'"]+)['"]/;
                                const match = (data.logs || '').match(routeRegex);
                                if (match && match[1]) {
                                    const routePath = match[1];
                                    const url = routePath.startsWith('/') ? routePath : '/' + routePath;
                                    const linkHtml = `<a href="${url}" target="_blank" class="btn btn-success">Tester la route générée (${url})</a>`;
                                    document.getElementById('route-link').innerHTML = linkHtml;
                                }
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

    submitEdit() {
        const form = document.getElementById('method-form');
        const data = {
            file: form.file.value,
            method: form.method.value,
            params: form.params.value,
            goal: form.goal.value,
            add_route: form.add_route && form.add_route.checked ? true : false,
            add_docblock: form.add_docblock && form.add_docblock.checked ? true : false,
            edit: true // pour signaler que c'est une modif
        };
        document.getElementById('route-link').innerHTML = '';
        this.log('Modification de la méthode...');
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
                const maxPolls = 150;
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
                            if ((data.logs || '').includes('SUCCESS')) {
                                clearInterval(interval);
                                this.showRouteLink(data.logs);
                            }
                            if ((data.logs || '').includes('Erreur Ollama :')) {
                                clearInterval(interval);
                            }
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
        // Cherche #[Route('/xxx/{param1}/{param2}', ...)] dans les logs
        const routeRegex = /#\[Route\(['"]([^'"]+)['"]/;
        const match = logs.match(routeRegex);
        if (match && match[1]) {
            let routePath = match[1];

            // Ajout : si la méthode a des paramètres, propose un lien avec des valeurs fictives
            const paramsField = document.getElementById('params');
            if (paramsField && paramsField.value.trim()) {
                // Extrait les noms des paramètres (ex: int $id, string $name)
                const paramNames = paramsField.value
                    .split(',')
                    .map(s => s.trim().replace(/.*\$/, ''))
                    .filter(Boolean);
                paramNames.forEach(param => {
                    // Si le paramètre n'est pas déjà dans la route, on l'ajoute à la fin
                    if (!routePath.includes('{' + param + '}')) {
                        if (!routePath.endsWith('/')) routePath += '/';
                        routePath += '{' + param + '}';
                    }
                });
                // Remplace les {param} par des valeurs fictives pour le lien
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

            // Cas sans paramètre
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