// assets/controllers/flash_controller.js
import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    connect() {
        const flashes = JSON.parse(this.element.value);
        Object.entries(flashes).forEach(([type, messages]) => {
            messages.forEach(message => {
                this.showFlashMessage({ type, message });
            });
        });
    }

    showFlashMessage(flash) {
        Swal.fire({
            icon: flash.type,
            title: flash.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}
