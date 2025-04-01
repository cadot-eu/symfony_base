export default class Tooltip {
    /**
     * Allow to use Tooltip as Inline Tool
     * @returns {boolean}
     */
    static get isInline() {
        return true;
    }

    /**
     * Sanitize config for Tooltip Tool
     * @returns {object}
     */
    static get sanitize() {
        return {
            span: {
                class: true,
                'data-bs-toggle': true,
                'data-bs-title': true,
                'data-bs-html': true
            },
            html: {
                b: true,
                i: true,
                u: true,
                em: true,
                strong: true,
                a: {
                    href: true,
                    target: true
                },
                ul: true,
                ol: true,
                li: true,
                p: true,
                br: true,
                h1: true,
                h2: true,
                h3: true,
                h4: true,
                h5: true,
                h6: true,
                img: {
                    src: true,
                    alt: true,
                    title: true
                },
                table: true,
                tr: true,
                td: true,
                th: true,
                thead: true,
                tbody: true,
                tfoot: true,
                caption: true,
                col: true,
                colgroup: true
            }
        };
    }

    /**
     * Get current state
     * @returns {boolean}
     */
    get state() {
        return this._state;
    }

    /**
     * Set current state and update button UI
     * @param {boolean} state - Current state
     */
    set state(state) {
        this._state = state;
        this.button.classList.toggle(this.api.styles.inlineToolButtonActive, state);
    }

    /**
     * Constructor
     * @param {object} param0 - Constructor config
     */
    constructor({ api }) {
        this.api = api;
        this.button = null;
        this._state = false;
        this.tag = 'SPAN';
        this.class = 'cdx-tooltip';

    }

    /**
     * Create button element for Inline Toolbar
     * @returns {HTMLElement}
     */
    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="bi bi-chat-bubble" viewBox="0 0 24 24"><path d="M2 2h20v20l-4-4H2V2z"/></svg>';
        this.button.classList.add(this.api.styles.inlineToolButton);
        return this.button;
    }

    /**
     * Handle click on the Inline Tool button
     * @param {Range} range - Current selection range
     */
    surround(range) {
        if (this.state) {
            this.unwrap(range);
            return;
        }
        this.wrap(range);
    }

    /**
     * Wrap selected text in a marker
     * @param {Range} range - Current selection range
     */
    wrap(range) {
        const selectedText = range.extractContents();
        const mark = document.createElement(this.tag);

        // Add class
        mark.classList.add(this.class);
        mark.setAttribute('data-bs-toggle', 'tooltip');
        mark.setAttribute('data-bs-html', 'true');
        mark.setAttribute('data-bs-title', '');
        // Insert the selected text
        mark.appendChild(selectedText);

        // Insert the mark at the selection point
        range.insertNode(mark);

        // Expand selection to the entire mark element
        this.api.selection.expandToTag(mark);
    }

    /**
     * Unwrap text from marker element
     * @param {Range} range - Current selection range
     */
    unwrap(range) {
        const mark = this.api.selection.findParentTag(this.tag, this.class);

        if (mark) {
            // Extract the contents from the range
            const text = range.extractContents();

            // Remove the marker
            mark.remove();

            // Insert the contents back
            range.insertNode(text);
        }
    }

    /**
     * Check if marker is selected and show color picker accordingly
     */
    checkState() {
        const mark = this.api.selection.findParentTag(this.tag, this.class);

        // Update button state
        this.state = !!mark;

        if (this.state) {
            this.showActions(mark);
        } else {
            this.hideActions();
        }
    }

    /**
     * Create color picker UI for the Inline Toolbar
     * @returns {HTMLElement} - Color picker element
     */
    renderActions() {
        //on créé un input pour demander le texte du tooltip
        this.tooltipInputDiv = document.createElement('div');
        this.tooltipInput = document.createElement('textarea');
        this.tooltipInput.setAttribute('rows', '5');
        this.tooltipInput.setAttribute('cols', '50');
        this.tooltipInput.placeholder = 'Texte du tooltip';
        this.tooltipInput.classList.add('form-control');
        this.tooltipInput.setAttribute('data-bs-title', 'tooltip');
        this.tooltipInput.setAttribute('data-bs-html', 'true');
        //on ajoute un petit texte pour expliquer l'utilisation de l'input
        const balises = this.constructor.sanitize.html;
        this.tooltipInputHelper = document.createElement('small');
        this.tooltipInputHelper.classList.add('form-text');
        this.tooltipInputHelper.textContent = 'Balises autorisées:';
        const balisesList = Object.keys(balises);
        for (let i = 0; i < balisesList.length; i += 5) {
            const balisesChunk = balisesList.slice(i, i + 5).join(', ');
            this.tooltipInputHelper.insertAdjacentHTML('beforeend', `<br>${balisesChunk}`);
        }
        this.tooltipInputDiv.appendChild(this.tooltipInput);
        this.tooltipInputDiv.appendChild(this.tooltipInputHelper);
        return this.tooltipInputDiv;
    }
    showActions(mark) {
        const storedTitle = mark.getAttribute('data-bs-title');
        this.tooltipInput.value = storedTitle;

        this.tooltipInput.oninput = () => {
            mark.setAttribute('data-bs-title', this.api.sanitizer.clean(this.tooltipInput.value, this.constructor.sanitize.html));
        };

        this.tooltipInput.hidden = false;
    }

    hideActions() {
        if (this.tooltipInput) {
            // Remove event handler
            this.tooltipInput.onchange = null;

            // Hide the tooltip input
            this.tooltipInput.hidden = true;
        }
    }


}