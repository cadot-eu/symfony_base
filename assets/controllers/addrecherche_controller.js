import { Controller } from '@hotwired/stimulus';
import flasher from '@flasher/flasher';

export default class extends Controller {
    click(event) {
        event.preventDefault();

        const rechercheInput = document.getElementById('Recherche');
        let mot = rechercheInput ? rechercheInput.value : '';
        let url;

        // Si l'élément est un lien, on utilise son href
        if (this.element.tagName === 'A' && this.element.href) {
            url = new URL(this.element.href, window.location.origin);
        } else {
            // Sinon on part de l'URL actuelle
            url = new URL(window.location.href);
        }
        //si on a pas de tri dans l'url on affiche
        if (url.searchParams.get('tri') === null) {
            flasher.flash('warning', 'Veuillez choisir un tri avant de faire une recherche');
            return;

        }

        if (this.element.dataset.reset === "true") {
            rechercheInput.value = "";
            mot = ""; // Fixed: now properly reassigns the variable
        }

        url.searchParams.set('mot', mot);
        window.location.href = url.toString();
    }
}