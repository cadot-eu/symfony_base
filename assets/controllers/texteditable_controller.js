import { Controller } from '@hotwired/stimulus';
import EditorJS from "https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.26.5/+esm"
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import Paragraph from '@editorjs/paragraph';
import Quote from '@editorjs/quote';
import Warning from '@editorjs/warning';
import Image from '@editorjs/image';
import Code from '@editorjs/code';
import LinkTool from '@editorjs/link';
import Delimiter from '@editorjs/delimiter';
import Table from '@editorjs/table';
import AttachesTool from '@editorjs/attaches';
import Alert from 'editorjs-alert';
import Marker from '@editorjs/marker';
import Strikethrough from '@sotaproject/strikethrough';
import Tooltip from '../scripts/editorjs/tooltip.js';
import initializeTooltips from '../scripts/tippy.js';
import getEnvEditorjs from '../scripts/getEnvEditorjs.js';

const interditPlugins = await getEnvEditorjs.fetchEnv();

import { Modal } from 'bootstrap';
import flasher from '@flasher/flasher';

export default class extends Controller {
    static values = {
        content: { type: String, default: '' },
        directory: { type: String, default: 'default' },
        url: String
    }

    connect() {
        this.element.addEventListener('click', () => this.openEditor());
    }

    openEditor() {
        // Créer le modal dynamiquement
        const modalElement = document.createElement('div');
        modalElement.classList.add('modal', 'fade');
        modalElement.innerHTML = `
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Éditeur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editor-container"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" id="save-content" class="btn btn-primary">Enregistrer</button>
                    </div>
                </div>
            </div>
        `;

        // Ajouter le modal au body
        document.body.appendChild(modalElement);

        // Initialiser le modal Bootstrap
        const modal = new Modal(modalElement);

        // Afficher le modal
        modal.show();

        // Initialiser l'éditeur une fois le modal ouvert
        try {
            this.initializeEditor();


        } catch (error) {
            flasher.error('Erreur lors de l\'initialisation de l\'éditeur:', error);
        }

        // Gérer la suppression du modal après fermeture
        modalElement.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modalElement);
        });
        // save
        document.getElementById('save-content').addEventListener('click', () => {
            this.saveContent();
        });
    }

    initializeEditor() {
        try {
            // Parse le contenu initial s'il existe
            const initialData = this.contentValue ? JSON.parse(this.contentValue) : {};

            // Initialiser EditorJS
            this.editor = new EditorJS({
                onReady: () => {
                    //on liste les class ce-block et on active un drg and drop
                    document.querySelectorAll('.ce-block').forEach((block) => {
                        //block.insertAdjacentHTML('afterbegin', '<i class="bi bi-arrows-move drag-handle"></i>');
                    });
                    //on met les tooltips
                    initializeTooltips();

                },
                holder: 'editor-container',
                autofocus: true,
                inlineToolbar: true,
                data: initialData,
                tools: this.getEditorTools()
            });

        } catch (error) {
            console.error('Erreur lors de l\'initialisation de l\'éditeur:', error);
        }

    }

    getEditorTools() {
        let listTools = {
            header: {
                class: Header,
                config: {
                    placeholder: "Entrez un en-tête",
                    levels: [2, 3, 4],
                    defaultLevel: 3
                }
            },
            paragraph: {
                class: Paragraph,
            },
            tooltip: {
                class: Tooltip,
                config: {
                    tooltipPlaceholder: "Entrez le texte du tooltip"
                }
            },
            quote: {
                class: Quote,
                config: {
                    quotePlaceholder: "Entrez une citation",
                    captionPlaceholder: "Auteur de la citation"
                }
            },
            image: {
                class: Image,
                config: {
                    endpoints: {
                        byFile: '/editorjs/upload/articles',
                    }
                }
            },
            code: {
                class: Code,
                config: {
                    placeholder: "Entrez votre code"
                }
            },
            warning: {
                class: Warning,
                config: {
                    titlePlaceholder: "Avertissement",
                    messagePlaceholder: "Message d'avertissement"
                }
            },
            attaches: {
                class: AttachesTool,
                config: {
                    endpoint: '/editorjs/upload/file/${directory}'
                }
            },
            Marker: {
                class: Marker,
                shortcut: 'CTRL+SHIFT+M',
            },
            strikethrough: Strikethrough,
            linkTool: {
                class: LinkTool,
            },
            delimiter: Delimiter,
            table: Table,
            alert: {
                class: Alert,
                shortcut: 'CMD+SHIFT+A',
                config: {
                    alertTypes: ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'light', 'dark'],
                    defaultType: 'primary',
                },
            },
            list: {
                class: EditorjsList,
                config: {
                    defaultStyle: 'unordered'
                },
            },


        };
        //on retire les plugins interdit
        listTools = Object.keys(listTools).reduce((acc, key) => {
            if (!interditPlugins.includes(key)) {
                acc[key] = listTools[key];
            }
            return acc;
        }, {});

        console.log(listTools);
        return listTools;
    }

    async saveContent() {
        try {
            // Sauvegarder le contenu de l'éditeur
            const outputData = await this.editor.save();

            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ value: JSON.stringify(outputData) })
            });

            const data = await response.json();

            if (data.success) {
                // Notification de succès
                if (typeof flasher !== 'undefined') {
                    flasher.success('Contenu mis à jour avec succès');
                    // Onm et le contenu à jour
                    this.contentValue = JSON.stringify(outputData);


                }
                // Fermer le modal
                const modalElement = document.querySelector('.modal');
                if (modalElement) {
                    const modal = Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
            } else {
                throw new Error('Erreur lors de la mise à jour');
            }
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            if (typeof flasher !== 'undefined') {
                flasher.error('Impossible de mettre à jour le contenu');
            }
        }
    }

    disconnect() {
        // Nettoyer l'éditeur si nécessaire
        if (this.editor) {
            this.editor.destroy();
        }
    }
}