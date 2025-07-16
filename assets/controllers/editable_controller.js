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
        //pour les checkboxs les select multiple ...
        if (this.element.querySelectorAll('.form-check-input').length > 0) {
            this.element.querySelectorAll('.form-check-input').forEach(el => {
                el.addEventListener("change", this.sendUpdate.bind(this));
            });
        }
        //pour un checkbox simple
        if (this.element.tagName == 'INPUT' && this.element.type == 'checkbox') {
            this.element.addEventListener("change", this.sendUpdate.bind(this));
        }
        //pour un select
        else if (this.element.tagName == 'SELECT') {
            this.element.addEventListener("change", this.sendUpdate.bind(this));
        }
        //pour un input date
        else if (this.element.querySelector('input') && (this.element.querySelector('input').type == 'date' || this.element.querySelector('input').type == 'datetime-local')) { //pour les datepicker
            this.element.querySelector('input').addEventListener("input", this.sendUpdate.bind(this));
            this.datePicker = true;
        }
        // pour les autres
        else {
            //is on a un regex on l'ajoute avec son message
            this.element.addEventListener("blur", this.sendUpdate.bind(this));
            this.element.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = (event.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text);
            });
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
        //checkbox simple
        if (this.element.tagName == 'INPUT' && this.element.type == 'checkbox') {
            if (this.associationidValue == '')
                valeur = this.element.checked;
            else
                valeur = { 'associationid': this.associationidValue, 'value': this.element.checked };
        }
        //checkbox multiple
        else if (this.element.querySelectorAll('.form-check-input').length > 0) {
            let values = [];
            for (let i = 0; i < this.element.querySelectorAll('.form-check-input').length; i++) {
                if (this.element.querySelectorAll('.form-check-input')[i].checked) {
                    values.push(this.element.querySelectorAll('.form-check-input')[i].getAttribute('data-name'));
                }
            }
            valeur = values;
        }
        //select multiple
        else if (this.element.options && this.element.options.length > 0) {
            let values = [];
            for (let i = 0; i < this.element.options.length; i++) {
                if (this.element.options[i].selected) {
                    values.push(this.element.options[i].getAttribute('data-name'));
                }
            }
            valeur = values;
        }
        //datepicker
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
