/**
 * Tooltip Tool pour EditorJS
 */
export default class Tooltip {
    /**
     * Constructeur de l'outil
     *
     * @param {object} options - Les options fournies lors de la construction
     * @param {string} options.tooltipPlaceholder - Placeholder pour le texte du tooltip
     */
    constructor({ data, config, api }) {
        this.data = data || {};
        this.api = api;
        this.config = config || {};
        this.tooltipPlaceholder = config.tooltipPlaceholder || 'Entrez le texte du tooltip';

        // CSS pour l'élément sélectionné avec tooltip
        this.CSS = {
            tooltip: 'tooltip-toggle'
        };
    }

    /**
     * Surcharge de render() pour indiquer que c'est un outil inline
     */
    static get isInline() {
        return true;
    }

    /**
     * Renvoie true pour indiquer que l'outil est actif sur le nœud courant
     *
     * @param {HTMLElement} node - Le nœud avec lequel vérifier l'activation
     * @return {boolean}
     */
    checkState(selection) {
        const tooltip = this.getTooltipFromSelection(selection);
        return !!tooltip; // Retourne true si un tooltip a été trouvé
    }

    /**
     * Trouve l'élément tooltip dans la sélection actuelle
     * 
     * @param {Selection} selection - L'objet selection du DOM
     * @returns {HTMLElement|null} - L'élément tooltip ou null si aucun n'est trouvé
     */
    getTooltipFromSelection(selection) {
        if (!selection || !selection.anchorNode) {
            return null;
        }

        // si nextSibling on le renvoie
        if (this.isTooltip(selection.anchorNode.nextSibling)) {
            return selection.anchorNode.nextSibling;
        }


        return null;
    }

    /**
     * Vérifie si un nœud est un élément tooltip
     * 
     * @param {Node} node - Le nœud à vérifier
     * @returns {boolean} - True si c'est un tooltip
     */
    isTooltip(node) {
        return (
            node &&
            node.nodeType === Node.ELEMENT_NODE &&
            node.classList &&
            node.classList.contains(this.CSS.tooltip) &&
            node.hasAttribute('data-bs-toggle') &&
            node.hasAttribute('data-bs-title')
        );
    }

    /**
     * Balises autorisées pour contenir cet outil
     */
    static get sanitize() {
        return {
            span: {
                class: true,
                'data-bs-toggle': true,
                'data-bs-title': true
            }
        };
    }

    /**
     * Renvoie l'icône et le titre de l'outil
     */
    static get title() {
        return 'Tooltip';
    }

    /**
     * Renvoie l'icône SVG pour l'outil
     */
    static get toolbox() {
        return {
            icon: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 1C7.79086 1 6 2.79086 6 5C6 7.20914 7.79086 9 10 9C12.2091 9 14 7.20914 14 5C14 2.79086 12.2091 1 10 1ZM5 5C5 2.23858 7.23858 0 10 0C12.7614 0 15 2.23858 15 5C15 7.76142 12.7614 10 10 10C7.23858 10 5 7.76142 5 5ZM9.5 12H10.5V19H9.5V12Z"/></svg>',
            title: 'Tooltip'
        };
    }

    /**
     * Surcharge de render() pour définir le comportement du plugin quand il est activé
     */
    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.textContent = 'Tooltip';
        this.button.classList.add('ce-inline-tool');
        return this.button;
    }

    /**
     * Demande le texte à mettre dans le tooltip
     *
     * @param {String} oldText - Le texte actuel du tooltip
     * @return {String} Le nouveau texte du tooltip
     */
    async askNewText(oldText) {
        const modal = new Modal(document.getElementById('modalTooltip'), { backdrop: 'static' });
        document.getElementById('modalTooltipInput').value = oldText;
        document.getElementById('modalTooltipSave').addEventListener('click', (e) => {
            e.preventDefault();
            modal.hide();
        });
        modal.show();
        const promise = new Promise((resolve) => {
            document.getElementById('modalTooltipSave').addEventListener('click', () => {
                const text = document.getElementById('modalTooltipInput').value;
                resolve(text);
            });
        });
        return promise;
    }

    /**
     * Substitue le texte sélectionné avec un élément de tooltip ou modifie un tooltip existant
     *
     * @param {Range} range - L'objet range pour le texte sélectionné
     */
    surround(range) {
        // Récupérer l'objet Selection actuel
        const selection = window.getSelection();

        // Chercher si un tooltip existe déjà dans la sélection
        const existingTooltip = this.getTooltipFromSelection(selection);

        // Variable pour stocker le texte du tooltip existant
        let existingTooltipText = '';

        // Si un tooltip existe déjà
        if (existingTooltip) {
            // Récupérer le texte actuel du tooltip
            existingTooltipText = existingTooltip.getAttribute('data-bs-title') || '';

            // Demander le nouveau texte, en pré-remplissant avec le texte existant
            const newTooltipText = prompt(this.tooltipPlaceholder, existingTooltipText);

            // Si l'utilisateur annule, on ne fait rien
            if (newTooltipText === null) {
                return;
            }

            // Si l'utilisateur a vidé le texte, on supprime le tooltip
            if (newTooltipText === '') {
                // Désactiver le tooltip Bootstrap si nécessaire
                if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
                    try {
                        const tooltipInstance = bootstrap.Tooltip.getInstance(existingTooltip);
                        if (tooltipInstance) {
                            tooltipInstance.dispose();
                        }
                    } catch (e) {
                        console.warn('Erreur lors de la suppression du tooltip Bootstrap:', e);
                    }
                }

                // Enlever les attributs tooltip
                existingTooltip.classList.remove(this.CSS.tooltip);
                existingTooltip.removeAttribute('data-bs-toggle');
                existingTooltip.removeAttribute('data-bs-title');
            } else {
                // Sinon, mettre à jour le texte du tooltip
                existingTooltip.setAttribute('data-bs-title', newTooltipText);

                // Réinitialiser le tooltip Bootstrap si nécessaire
                if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
                    try {
                        const tooltipInstance = bootstrap.Tooltip.getInstance(existingTooltip);
                        if (tooltipInstance) {
                            tooltipInstance.dispose();
                        }
                        new bootstrap.Tooltip(existingTooltip);
                    } catch (e) {
                        console.warn('Erreur lors de la réinitialisation du tooltip Bootstrap:', e);
                    }
                }
            }

            return;
        }

        // Si aucun tooltip n'existe, on en crée un nouveau

        // Demander le texte du tooltip
        const tooltipText = prompt(this.tooltipPlaceholder, '');

        // Si l'utilisateur annule, on ne fait rien
        if (tooltipText === null) {
            return;
        }

        // Si l'utilisateur n'a pas entré de texte, on ne fait rien
        if (tooltipText === '') {
            return;
        }

        // Créer un conteneur pour le tooltip
        const newTooltipEl = document.createElement('span');
        newTooltipEl.classList.add(this.CSS.tooltip);
        newTooltipEl.setAttribute('data-bs-toggle', 'tooltip');
        newTooltipEl.setAttribute('data-bs-title', tooltipText);

        // Extraire le contenu de la sélection et l'insérer dans notre élément
        const content = range.extractContents();
        newTooltipEl.appendChild(content);

        // Insérer notre élément tooltip dans le document
        range.insertNode(newTooltipEl);

        // Mettre à jour la position du curseur après l'élément
        this.api.selection.expandToTag(newTooltipEl);

        // Initialiser le tooltip Bootstrap si disponible
        if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
            try {
                new bootstrap.Tooltip(newTooltipEl);
            } catch (e) {
                console.warn('Erreur lors de l\'initialisation du tooltip Bootstrap:', e);
            }
        }
    }

    /**
     * Méthode appelée lorsque l'éditeur est sauvegardé
     *
     * @returns {object} - Objet avec le contenu du tooltip
     */
    save() {
        // Cette méthode n'est pas nécessaire pour un inline tool
        return {};
    }
}