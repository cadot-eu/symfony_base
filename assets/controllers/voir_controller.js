// assets/controllers/image-lightbox_controller.js
import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';
export default class extends Controller {
    connect() {
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new Popover(popoverTriggerEl);
        });

        // Wrap images with lightbox links
        document.querySelectorAll('img').forEach(img => {
            // Create an <a> link around each image
            const link = document.createElement('a');
            link.href = img.src; // Link to the image
            link.classList.add('glightbox'); // Class for GLightbox
            link.setAttribute('data-gallery', 'gallery'); // Group images

            // Place the image in the link
            img.parentNode.insertBefore(link, img);
            link.appendChild(img);
        });

        // Initialize GLightbox
        const lightbox = GLightbox({
            selector: '.glightbox',
            zoomable: true, // Enable zoom with mouse wheel
            touchNavigation: true, // Handle touch zoom
            width: '100%',
            loop: true // Loop navigation
        });

        //on ajoute un eveènement qui fait que lorsque que l'on survole un .buttondiv on met en surbrillance la div
        document.querySelectorAll('.buttonp, .buttondiv').forEach(async button => {
            button.addEventListener('mouseover', () => {
                const div = button.parentNode;
                div.classList.add('hoverdiv');
            });
            button.addEventListener('mouseout', () => {
                const div = button.parentNode;
                div.classList.remove('hoverdiv');
            });
            //si on clique dessus
            button.addEventListener('click', async (event) => {
                const id = document.querySelector('#article').getAttribute('attr-id');
                try {
                    let selection = button.parentNode.innerHTML;
                    //on supprime tous les boutons button avec les classes .buttonp et .buttondiv
                    const buttons = document.querySelectorAll('.buttonp, .buttondiv');
                    buttons.forEach(button => {
                        selection = selection.replace(button.outerHTML, '');
                    });
                    //on remplace le bouton par un bouton de type button
                    //si on a CTRL d'appuyé en même temps que le click

                    const response = await fetch(`/FluxPub/${id}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/vnd.turbo-stream.html',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ selection: selection })
                    });
                    const html = await response.text();
                    Turbo.renderStreamMessage(html);
                } catch (error) {
                    console.error('Erreur:', error);
                }
            });
        });
    }
}