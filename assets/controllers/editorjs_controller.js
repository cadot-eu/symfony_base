import { Controller } from '@hotwired/stimulus';
import EditorJS from "https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.26.5/+esm"
import Header from '@editorjs/header';
import List from '@editorjs/nested-list';
import Paragraph from '@editorjs/paragraph';
import Quote from '@editorjs/quote';
import Warning from '@editorjs/warning';
import Image from '@editorjs/image';
import Code from '@editorjs/code';
import LinkTool from '@editorjs/link';
import Delimiter from '@editorjs/delimiter';
import Table from '@editorjs/table';
import AttachesTool from '@editorjs/attaches';
import flasher from '@flasher/flasher';

export default class extends Controller {
    connect() {
        const editor = new EditorJS({
            "autofocus": true,
            "holder": this.element.id,
            "inlineToolbar": true,
            "tools": {
                "header": {
                    "inlineToolbar": true,
                    "class": Header,
                    "shortcut": "CMD+H",
                    "config": {
                        "placeholder": "Enter a header",
                        "levels": [2, 3, 4],
                        "defaultLevel": 3
                    }
                },
                "paragraph": {
                    "inlineToolbar": true,
                    "class": Paragraph,
                    "shortcut": "CMD+P",

                },
                "list": {
                    "inlineToolbar": true,
                    "class": List,
                    "shortcut": "CMD+L",
                    "config": {
                        "defaultStyle": "unordered"
                    }
                },
                "quote": {
                    "class": Quote,
                    "inlineToolbar": true,
                    "shortcut": "CMD+SHIFT+O",
                    "config": {
                        "quotePlaceholder": "Enter a quote",
                        "captionPlaceholder": "Quote's author"
                    }
                },
                "image": {
                    "inlineToolbar": true,
                    "class": Image,
                    "shortcut": "CMD+SHIFT+I",
                    "config": {
                        "endpoints": {
                            "byFile": '/editorjs/upload/articles',
                        }
                    }
                },

                "code": {
                    "inlineToolbar": true,
                    "class": Code,
                    "shortcut": "CMD+/",
                    "inlineToolbar": true,
                    "config": {
                        "placeholder": "Enter code"
                    }
                },
                "warning": {
                    "inlineToolbar": true,
                    "class": Warning,
                    "shortcut": "CMD+SHIFT+X",
                    "config": {
                        "titlePlaceholder": "Warning",
                        "messagePlaceholder": "Warning message"
                    }
                },
                "attaches": {
                    "inlineToolbar": true,
                    "class": AttachesTool,
                    "config": {
                        "endpoint": '/editorjs/upload/file/articles'
                    }
                },
                "linkTool": {
                    "inlineToolbar": true,
                    "class": LinkTool,
                    "config": {
                        "endpoint": "/api/links"
                    }
                },
                "delimiter": {
                    "inlineToolbar": true,
                    "class": Delimiter
                },
                "table": {
                    "inlineToolbar": true,
                    "class": Table
                },


            },
            data: this.element.innerText,
            onReady: () => {
                //this.element.innerText = 'test';
            },
            onChange: () => {
                editor.save().then((outputData) => {
                    fetch('/admin/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ entity: this.entityValue, field: this.fieldValue, value: this.element.innerText, id: this.idValue })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                flasher.success('Contenu mis à jour');
                                Turbolinks.reload();
                            }
                            else {
                                flasher.error('Erreur lors de la mise à jour du contenu');
                            }
                        });
                });
            }
        });
    }
}
