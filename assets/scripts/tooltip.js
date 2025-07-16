import { computePosition, offset, flip, shift, autoUpdate } from '@floating-ui/dom';

export default function initializeTooltips(elements) {
    // Correction: s'assurer qu'elements est toujours un NodeList/Array
    if (!elements) {
        elements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    }

    // Convertir en Array si c'est un NodeList pour éviter les erreurs
    const elementsArray = Array.from(elements);

    if (elementsArray.length === 0) return;

    elementsArray.forEach(el => {
        // Éviter d'initialiser plusieurs fois le même élément
        if (el.dataset.tooltipInitialized === 'true') return;
        el.dataset.tooltipInitialized = 'true';

        let title = el.getAttribute('data-bs-title');
        if (title == '') return;

        try {
            title = JSON.parse(title);
            if (typeof title === 'object') {
                const entries = Object.entries(title);
                if (entries.length === 1 && typeof entries[0][1] === 'object') {
                    title = Object.entries(entries[0][1]).map(([key, value]) => `${key}: ${value}`).join('<br>');
                } else {
                    title = entries.map(([key, value]) => `${key}: ${value}`).join('<br>');
                }
            }
        } catch (e) {
            // nothing to do
        }

        el.setAttribute('data-bs-title', title);

        const tooltip = document.createElement('div');
        tooltip.className = el.dataset.bsCustomClass || '';
        tooltip.innerHTML = el.getAttribute('data-bs-title') || '';

        Object.assign(tooltip.style, {
            position: 'absolute',
            zIndex: 9999,
            backgroundColor: '#fff',
            border: '1px solid #ccc',
            padding: '8px',
            borderRadius: '4px',
            boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
            display: 'none',
            maxHeight: 'calc(100vh - 100px)',
            overflowY: 'auto',
        });

        document.body.appendChild(tooltip);

        let cleanup;
        let hideTimeout;

        function showTooltip() {
            tooltip.style.display = 'block';
            cleanup = autoUpdate(el, tooltip, () => {
                computePosition(el, tooltip, {
                    placement: 'top',
                    middleware: [offset(8), flip(), shift()],
                }).then(({ x, y }) => {
                    Object.assign(tooltip.style, {
                        left: `${x}px`,
                        top: `${y}px`,
                    });
                });
            });
        }

        function hideTooltip() {
            tooltip.style.display = 'none';
            cleanup?.();
        }

        function scheduleHide() {
            hideTimeout = setTimeout(() => {
                if (!el.matches(':hover') && !tooltip.matches(':hover')) {
                    hideTooltip();
                }
            }, 300);
        }

        el.addEventListener('mouseover', () => {
            clearTimeout(hideTimeout);
            showTooltip();
        });

        el.addEventListener('mouseout', scheduleHide);
        tooltip.addEventListener('mouseover', () => clearTimeout(hideTimeout));
        tooltip.addEventListener('mouseout', scheduleHide);

        // Nettoyer le tooltip quand l'élément est supprimé du DOM
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.removedNodes.forEach((node) => {
                    if (node === el || (node.nodeType === 1 && node.contains(el))) {
                        tooltip.remove();
                        cleanup?.();
                        observer.disconnect();
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
}

// Fonction wrapper pour les événements
function handleTooltipInitialization() {
    initializeTooltips();
}

window.addEventListener('load', handleTooltipInitialization);
document.addEventListener('turbo:load', handleTooltipInitialization);
document.addEventListener('turbo:after-stream-render', handleTooltipInitialization);
document.addEventListener('turbo:frame-load', handleTooltipInitialization);