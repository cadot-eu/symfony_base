import { Controller } from '@hotwired/stimulus';

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
            alert("Erreur lors de la mise à jour");
        }
    }
    async deleteEntity(event) {
        if (!confirm("Voulez-vous vraiment supprimer cet élément ?")) {
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
            alert("Erreur lors de la suppression");
        }
    }
    async CreateEntity(event) {
        let entity = event.currentTarget.dataset.entity;
        let response = await fetch('/admin/creer', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entity })
        });

        let result = await response.json();
        if (!result.success) {
            alert("Erreur lors de la création");
        }

    }

}
