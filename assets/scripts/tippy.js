import tippy from 'tippy.js';
import 'tippy.js/themes/light-border.css';
export default function initializeTooltips(elements = document.querySelectorAll('[data-bs-toggle="tooltip"]')) {
    elements.forEach(el => {
        tippy(el, {
            content: el.getAttribute('data-bs-title'),
            allowHTML: true,
            interactive: true,
            default: true,
            theme: 'light-border'
        });
    });
}

window.addEventListener('load', () => {
    initializeTooltips();
});
