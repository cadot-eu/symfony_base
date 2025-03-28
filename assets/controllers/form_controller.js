import { Controller } from '@hotwired/stimulus';
import { Notyf } from 'notyf';
const notyf = new Notyf({ duration: 2000, position: { x: 'right', y: 'top' } });

// example:
// <button type="button" data-controller="form" data-form-url-value="/admin/update" data-form-value="Bonjour" data-form-method-value="PUT">Cliquez ici</button>
export default class extends Controller {
    static values = {
        url: String,
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
        let response = await fetch(this.urlValue, {
            method: this.methodValue,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ value: this.element.innerText })
        });

        let result = await response.json();
        if (!result.success) {
            notyf.error('Erreur lors de l\'envoi du formulaire');
        } else {
            notyf.success('Formulaire envoyé');
            if (this.parentValue && this.methodValue === 'DELETE' && document.querySelector(this.parentValue)) {
                document.querySelector(this.parentValue).remove();
            }
        }
    }
}
