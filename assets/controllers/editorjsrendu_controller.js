import { Controller } from '@hotwired/stimulus';
import highlight from 'highlight.js';
import 'highlight.js/styles/github.css';
import BigPicture from 'bigpicture';


export default class extends Controller {
    static values = {
        url: String,
        destination: { type: String, default: '#preview' }
    }

    connect() {
        this.element.addEventListener('click', () => this.rendu());
    }

    async rendu() {
        // On demande au controller les données par get
        const response = await fetch(this.urlValue);
        const data = await response.text();

        // On parse le HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');

        // On applique highlight.js aux blocs de code
        const codeBlocks = doc.querySelectorAll('editorjs-block-code');
        codeBlocks.forEach((block) => {
            block.innerHTML = highlight.highlightAuto(block.innerText).value;
        });
        // pour les blocs attaches on ajoute une big picture sur le lien
        const attaches = doc.querySelectorAll('.editorjs-block-attaches');
        attaches.forEach((attach) => {
            //on prend l'img et on y met une big picture
            const img = attach.querySelector('img');
            if (!img) return;
            //on ajoute une class bigpicture
            img.classList.add('bigpicture');

        });

        // On injecte le HTML MODIFIÉ (et non l'original)
        document.querySelector(this.destinationValue).innerHTML = doc.documentElement.innerHTML;
        // On ajoute le bigpicture sur un click
        document.querySelectorAll('.bigpicture').forEach((img) => {
            img.addEventListener('click', () => {
                BigPicture({
                    el: img,
                    zoom: true
                });
            })
        })
    }
}