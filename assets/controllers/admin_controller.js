import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

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

        let response = await fetch(`/admin/entities/${entity}`);
        let html = await response.text();
        this.contentTarget.innerHTML = html;
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
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors de la mise à jour'
            });
        }
        else {
            Swal.fire({
                position: "top-end",
                showConfirmButton: false,
                timer: 1500,
                icon: 'success',
                text: 'Mise à jour réussie'
            });
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
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors de la suppression'
            });
        }
    }


}
