// assets/controllers/copy_to_clipboard_controller.js
import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    static values = {
        text: String
    }

    copy() {
        navigator.clipboard.writeText(this.textValue).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'URL copiée dans le presse-papier',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        });
    }
}
