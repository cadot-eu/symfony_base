//exemple d'utilisation
//<button data-confirmation-formaction-value="{{ path('supprimerFlux', {'flux': flux.id}) }}" data-controller="confirmation" />/data-action="confirmation#confirm" class="nobutton text-danger">
//x
//</button>

import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    static values = {
        formaction: String
    }
    confirm(event) {
        event.preventDefault();

        if (this.element.dataset.confirmed === "true") {
            this.submitRequest();
            return;
        }

        Swal.fire({
            title: 'Êtes-vous certain?',
            text: "Cette action est irréversible",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                Turbo.visit(this.formactionValue, {
                    method: 'DELETE'
                });
            }
        });
    }


}