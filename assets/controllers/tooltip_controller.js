import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static values = {
        html: String
    }

    connect() {
        // Créer l'élément modal dynamiquement
        this.modalElement = document.createElement('div');
        this.modalElement.classList.add('modal', 'fade');
        this.modalElement.innerHTML = `
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            ${this.htmlValue || ''}
          </div>
        </div>
      </div>
    `;
        document.body.appendChild(this.modalElement);

        // Initialiser le modal Bootstrap
        this.bootstrapModal = new Modal(this.modalElement);

        // Ajouter l'événement de survol
        this.element.addEventListener('mouseover', () => {
            this.bootstrapModal.show();
        });

    }

    // Nettoyer lors de la déconnexion du contrôleur
    disconnect() {
        if (this.modalElement) {
            this.modalElement.remove();
        }
    }
}