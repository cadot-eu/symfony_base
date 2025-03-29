import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';


export default class extends Controller {
    static values = {
        url: String,
        entity: String
    };



    connect() {
        this.element.addEventListener("blur", this.sendUpdate.bind(this));

    }

    disconnect() {
        this.element.removeEventListener("blur", this.sendUpdate.bind(this));
    }


    async sendUpdate() {
        let response = await fetch(this.urlValue, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entity: this.entityValue, field: this.fieldValue, value: this.element.innerText, id: this.idValue })
        });

        let result = await response.json();
        if (!result.success) {
            this.element.innerText = this.valueValue;
            flasher.error('Erreur lors de la mise à jour du champ');
        }
        else {
            flasher.success('Champ mis à jour');
        }
    }

}