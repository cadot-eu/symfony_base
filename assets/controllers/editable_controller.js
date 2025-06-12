import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';


export default class extends Controller {
    static values = {
        url: String,
        entity: String,
        regex: String,
        associationid: { type: String, default: '' },
        regexValue: String,
        regexMessage: String,
    };
    connect() {
        //pour les checkboxs
        if (this.element.classList.contains('enumselect') || this.element.classList.contains('onecheckselect')) {
            this.element.querySelectorAll('.form-check-input').forEach(el => {
                el.addEventListener("change", this.sendUpdate.bind(this));
            });
        }
        if (this.element.tagName == 'INPUT' && this.element.type == 'checkbox') {
            this.element.addEventListener("change", this.sendUpdate.bind(this));
        }
        else if (this.element.tagName == 'SELECT') {
            this.element.addEventListener("change", this.sendUpdate.bind(this));
        }
        else if (this.element.querySelector('input') && (this.element.querySelector('input').type == 'date' || this.element.querySelector('input').type == 'datetime-local')) { //pour les datepicker
            this.element.querySelector('input').addEventListener("input", this.sendUpdate.bind(this));
            this.datePicker = true;
        }
        else {
            //is on a un regex on l'ajoute avec son message
            this.element.addEventListener("blur", this.sendUpdate.bind(this));
        }
    }

    disconnect() {
        if (this.element.tagName === 'SELECT') {
            this.element.removeEventListener("change", this.sendUpdate);
        }
        else if (this.element.querySelector('input')) {
            this.element.querySelector('input').removeEventListener("input", this.sendUpdate);
            this.element.querySelector('input').removeEventListener("change", this.sendUpdate);
        }
        else {
            this.element.removeEventListener("blur", this.sendUpdate);
        }
    }


    async sendUpdate() {
        let valeur = this.element.textContent.trim();
        if (this.regexValue) {
            let regex = new RegExp(this.regexValue);
            if (!regex.test(valeur)) {
                flasher.error(this.regexMessageValue + ' : ' + valeur);
                return;
            }
        }
        if (this.element.tagName == 'INPUT' && this.element.type == 'checkbox') {
            if (this.associationidValue == '')
                valeur = this.element.checked;
            else
                valeur = { 'associationid': this.associationidValue, 'value': this.element.checked };
        }
        else if (this.element.tagName == 'SELECT') {
            if (this.element.getAttribute('multiple') == 'true') {
                let values = [];
                for (let i = 0; i < this.element.options.length; i++) {
                    if (this.element.options[i].selected) {
                        values.push(this.element.options[i].getAttribute('name'));
                    }
                }
                valeur = values;
            }
            else {
                valeur = this.element.options[this.element.selectedIndex].getAttribute('name');

            }
        }
        else if (this.element.classList.contains('onecheckselect')) {
            valeur = this.element.querySelector('input').checked;
        }
        else if (this.element.classList.contains('enumselect')) {
            let values = [];
            this.element.querySelectorAll('input.form-check-input:checked').forEach(input => {
                values.push(input.getAttribute('name'));
            });
            valeur = values;
        }
        else if (this.datePicker) {
            if (this.element.querySelector('input').value) {
                valeur = valeur = this.element.querySelector('input').value;
            }
        }

        let response = await fetch(this.urlValue, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entity: this.entityValue, field: this.fieldValue, value: valeur, id: this.idValue })
        });

        let result = null;
        try {
            result = await response.json();
        } catch (e) {
            if (e instanceof SyntaxError) {
                flasher.error('Erreur lors de la mise à jour du champ');
            }
            else {
                throw e;
            }
        }
        if (!result.success) {
            this.element.textContent = this.valueValue;
            flasher.error('Erreur lors de la mise à jour du champ');
        }
        else {
            //on fait clignoter le champ
            this.element.classList.add('flash');
            setTimeout(() => {
                this.element.classList.remove('flash');
            }, 1000);
        }
    }

}
