// assets/controllers/image-lightbox_controller.js
import { Controller } from '@hotwired/stimulus';
import 'glightbox/dist/css/glightbox.min.css';
import Swal from 'sweetalert2';
import flasher from '@flasher/flasher';

flasher.renderOptions({
    'theme.flasher': {
        position: 'bottom-right',
        timeout: 3000,
        direction: 'bottom'
    }
});

export default class extends Controller {
    connect() {
        document.addEventListener('mouseup', () => {
            reperes();
        });

    };



}

async function addPubAFter(html) {
    try {
        const idArticle = document.querySelector('#idArticle').value;
        const response = await fetch(`/FluxBas/${idArticle}`, {
            method: 'POST',
            headers: {
                'Accept': 'text/vnd.turbo-stream.html',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ selection: html })
        });
        const htmlreponse = await response.text();
        Turbo.renderStreamMessage(htmlreponse);
        flasher.success('Reperes ajoutés');
    } catch (error) {
        console.error('Erreur:', error);
        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
    }
}

function reperes() {
    let selectedText = null;
    let selectedHtml = null;
    let savedRange = null;

    const selection = window.getSelection();
    if (!selection.rangeCount) return;
    selectedText = selection.toString().trim();
    // Save selection

    if (window.getSelection) {
        const sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
            savedRange = sel.getRangeAt(0);
        }
    }

    // Get HTML content of selection
    const container = document.createElement('div');
    const clonedRange = savedRange.cloneContents();
    container.appendChild(clonedRange);
    selectedHtml = container.innerHTML;
    if (selectedText) {
        // Get article ID
        const id = document.querySelector('#article').getAttribute('attr-id');

        // Show action dialog
        Swal.fire({
            title: `Action pour : "${selectedText.substring(0, 30)}${selectedText.length > 30 ? '...' : ''}"`,
            icon: 'question',
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            showCloseButton: true,
            html: ['important', 'vocabulaire', 'question'].map((text) => {
                return `<button id="${text}" class="autre-btn text-black swal2-confirm swal2-styled bg-${text}" style="display: inline-block; margin-left: 10px;">${text}</button>`;
            }).join(' ')
        });

        // Function to restore selection
        const restoreSelection = () => {
            if (savedRange) {
                if (window.getSelection) {
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedRange);
                }
            }
        };

        // Add event listener for Important button
        document.querySelectorAll('.autre-btn').forEach(button => {
            const idbutton = button.getAttribute('id');
            button.addEventListener('click', async () => {
                if (idbutton === 'question') {
                    // Afficher le champ de question
                    const questionResult = await Swal.fire({
                        title: 'Poser une question',
                        input: 'text',
                        inputPlaceholder: 'Votre question',
                        showCancelButton: true,
                        confirmButtonText: 'Confirmer',
                        cancelButtonText: 'Annuler',
                        focusConfirm: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                    if (questionResult.isConfirmed && questionResult.value) {
                        try {
                            const response = await fetch(`/superadmin/Articlequestion/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'text/vnd.turbo-stream.html',
                                },
                                body: JSON.stringify({
                                    question: questionResult.value,
                                    selection: selectedHtml
                                })
                            });
                            const html = await response.text();
                            Turbo.renderStreamMessage(html);
                            flasher.success('Question ajoutée');

                        } catch (error) {
                            console.error('Erreur:', error);
                            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                        }
                    } else {
                        restoreSelection();
                    }
                }
                if (idbutton === 'vocabulaire') {
                    Swal.close();
                    try {
                        const response = await fetch(`/superadmin/ArticleAddVocabulaire/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/vnd.turbo-stream.html',
                            },
                            body: JSON.stringify({ selection: selectedHtml })
                        });
                        const html = await response.text();
                        Turbo.renderStreamMessage(html);
                        flasher.success('Vocabulaire ajouté');
                    } catch (error) {
                        console.error('Erreur:', error);
                        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                    }
                }

                if (idbutton === 'important') {
                    Swal.close();
                    try {
                        const response = await fetch(`/superadmin/ArticleImportant/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/vnd.turbo-stream.html',
                            },
                            body: JSON.stringify({ selection: selectedHtml })
                        });
                        const html = await response.text();
                        Turbo.renderStreamMessage(html);
                        flasher.success('Important ajouté');
                    } catch (error) {
                        console.error('Erreur:', error);
                        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                    }
                }
            });
        });


    }

}