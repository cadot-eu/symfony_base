import { Controller } from '@hotwired/stimulus';
import highlight from 'highlight.js';
import 'highlight.js/styles/github.css';
import BigPicture from 'bigpicture';
import initializeTooltips from '../scripts/tooltip.js';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static values = {
        url: String
    }

    connect() {
        this.abortController = null;
        this.element.addEventListener('click', () => this.rendu());
    }

    async rendu() {
        if (this.abortController) this.abortController.abort();
        this.abortController = new AbortController();

        try {
            const response = await fetch(this.urlValue, { signal: this.abortController.signal });
            const html = await response.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Highlight.js
            doc.querySelectorAll('editorjs-block-code').forEach((block) => {
                block.innerHTML = highlight.highlightAuto(block.innerText).value;
            });

            // BigPicture
            doc.querySelectorAll('.editorjs-block-attaches img').forEach((img) => {
                img.classList.add('bigpicture');
            });

            // Injecte le contenu dans un modal dynamique
            this.createModal(doc.body.innerHTML);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erreur lors du rendu :', error);
            }
        }
    }

    createModal(content) {
        const modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.tabIndex = -1;
        modalEl.innerHTML = `
            <div class="modal-dialog" data-turbo="false">
                <div class="modal-content">
                    <button type="button" class="btn-close position-absolute m-2 end-0 " style="z-index: 9999"></button>
                    <div class="modal-body">${content}</div>
                </div>
            </div>
        `;

        document.body.appendChild(modalEl);

        const modal = new Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });

        modal.show();

        // Fermeture via bouton
        modalEl.querySelector('.btn-close').addEventListener('click', () => modal.hide());

        // Fermeture = cleanup
        modalEl.addEventListener('hidden.bs.modal', () => {
            modal.dispose();
            modalEl.remove();
        });

        // BigPicture
        modalEl.querySelectorAll('.bigpicture').forEach((img) => {
            img.addEventListener('click', () => {
                BigPicture({ el: img, zoom: true });
            });
        });

        // Tooltips
        initializeTooltips();
    }
}
