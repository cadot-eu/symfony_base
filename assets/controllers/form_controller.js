import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';
import swal from 'sweetalert2';

// example:
// <button type="button" data-controller="form" data-form-url-value="/admin/update" data-form-value="Bonjour" data-form-method-value="PUT">Cliquez ici</button>
export default class extends Controller {
    static values = {
        url: String,
        confirmation: { type: Boolean, default: false },
        value: { type: String, default: '' },
        method: { type: String, default: 'POST' },
        parent: { type: String, default: '' }
    };

    connect() {
        this.element.addEventListener('click', this.sendForm.bind(this));
    }

    disconnect() {
        this.element.removeEventListener('click', this.sendForm.bind(this));
    }

    async sendForm() {
        if (this.confirmationValue) {
            const result = await swal.fire({
                title: 'Confirmation',
                text: 'Etes-vous sur de vouloir envoyer ce formulaire ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui',
                cancelButtonText: 'Non'
            });
            if (!result.isConfirmed) {
                return;
            }
        }
        let response = await fetch(this.urlValue, {
            method: this.methodValue,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ value: this.element.innerText })
        });

        let result = await response.json();
        if (!result.success) {
            flasher.error('Erreur lors de l\'envoi du formulaire');
        } else {
            flasher.success('Formulaire envoyé');
            if (this.parentValue && this.methodValue === 'DELETE' && document.querySelector(this.parentValue)) {
                document.querySelector(this.parentValue).remove();
            }
        }
    }
}
