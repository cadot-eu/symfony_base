import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    click(event) {
        event.preventDefault();
        console.log(this.element);

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

        console.log(this.element.dataset);

        if (this.element.dataset.reset === "true") {
            rechercheInput.value = "";
            mot = ""; // Fixed: now properly reassigns the variable
        }

        url.searchParams.set('mot', mot);
        window.location.href = url.toString();
    }
}