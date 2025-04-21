import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';


let datePicker = false;
export default class extends Controller {
    static values = {
        url: String,
        entity: String,
        regex: String,
        regexMessage: String,
    };



    connect() {
        if (this.element.tagName == 'SELECT') {
            this.element.addEventListener("change", this.sendUpdate.bind(this));
        }
        else if (this.element.querySelector('input')) { //pour les datepicker
            this.element.querySelector('input').addEventListener("input", this.sendUpdate.bind(this));
            datePicker = true;

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
        let valeur = this.element.innerText;
        if (this.element.tagName == 'SELECT') {
            valeur = this.element.options[this.element.selectedIndex].getAttribute('name');
        }
        else if (datePicker) {
            if (this.element.querySelector('input').value) {
                valeur = valeur = this.element.querySelector('input').value;

            }
        }
        else {
            if (this.regexValue) {
                let regex = new RegExp(this.regexValue);
                if (!regex.test(valeur)) {
                    flasher.error(this.regexMessageValue);
                    return;
                }
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
            this.element.innerText = this.valueValue;
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
