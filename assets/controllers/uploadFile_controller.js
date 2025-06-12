import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';

export default class extends Controller {
    static values = {
        entity: String,
        field: String,
        id: Number
    }

    chooseFile() {
        const chooser = document.createElement('input');
        chooser.type = 'file';
        chooser.addEventListener('change', (event) => {
            const file = event.target.files[0];
            this.uploadFileWithFile(file);
        });
        chooser.click();
    }

    async uploadFileWithFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('data', JSON.stringify({
            entity: this.entityValue,
            field: this.fieldValue,
            id: this.idValue.toString()
        }));

        const response = await fetch('/dashboard/uploadFile', {
            method: "POST",
            body: formData
        });

        if (response.ok) {
            Turbo.visit(window.location.href, { action: 'replace' });


        } else {
            flasher.error('Une erreur s\'est produite lors de l\'upload du fichier.');
        }
    }
}

