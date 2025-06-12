// assets/controllers/table_reorder_controller.js
import { Controller } from "@hotwired/stimulus";
import Sortable from "sortablejs";

export default class extends Controller {
    static values = {
        entity: String,
        orderField: { type: String, default: 'ordre' }
    };

    connect() {
        this.sortable = Sortable.create(this.element, {
            handle: 'tr', // ou précise un handle spécifique si tu veux
            animation: 150,
            onEnd: this.onEnd.bind(this)
        });
    }

    async onEnd(event) {
        const row = event.item;
        const entityId = row.dataset.id;
        const newOrder = event.newIndex + 1;

        try {
            const response = await fetch('/dashboard/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    entity: this.entityValue,
                    field: this.orderFieldValue,
                    id: entityId,
                    newOrder: newOrder
                })
            });

            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            this.showSuccess('Ordre mis à jour');
        } catch (error) {
            console.error(error);
            this.showError("Erreur lors de la mise à jour");
        }
    }

    showSuccess(message) {
        if (window.showNotification) {
            window.showNotification(message, 'success');
        } else {
            console.log('✅', message);
        }
    }

    showError(message) {
        if (window.showNotification) {
            window.showNotification(message, 'error');
        } else {
            console.error('❌', message);
        }
    }
}
