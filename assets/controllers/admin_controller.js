import { Controller } from '@hotwired/stimulus';
import { Notyf } from 'notyf';
const notyf = new Notyf(
    {
        duration: 2000,
        position: {
            x: 'right',
            y: 'top',
        }
    }
);

export default class extends Controller {
    static targets = ["content"];

    connect() {
        document.querySelectorAll(".nav-link").forEach(link => {
            link.addEventListener("click", this.loadEntity.bind(this));
        });
    }

    async loadEntity(event) {
        event.preventDefault();
        let entity = event.currentTarget.dataset.entity;
        Turbo.visit(`/admin/entities/${entity}`);
    }

    async updateField(event) {
        let field = event.currentTarget.dataset.field;
        let entity = event.currentTarget.dataset.entity;
        let id = event.currentTarget.dataset.id;
        let value = event.currentTarget.innerText;

        let response = await fetch('/admin/update', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entity, field, value, id })
        });

        let result = await response.json();
        if (!result.success) {
            notyf.error('Erreur lors de la mise à jour du champ');

        }
        else {
            notyf.success('Champ mis à jour');

        }

    }
    async deleteEntity(event) {
        let confirmDelete = await Swal.fire({
            title: 'Suppression',
            text: 'Voulez-vous vraiment supprimer cet élément ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer'
        });

        if (!confirmDelete.isConfirmed) {
            return;
        }

        let entity = event.currentTarget.dataset.entity;
        let id = event.currentTarget.dataset.id;

        let response = await fetch('/admin/delete', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entity, id })
        });

        let result = await response.json();
        if (result.success) {
            event.currentTarget.closest("tr").remove();
        } else {
            notyf.error('Erreur lors de la suppression de l\'élément');
        }
    }


}
