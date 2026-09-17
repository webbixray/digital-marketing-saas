/**
 * Tests for DMSaaS Component classes
 * Tests the component system from components.js
 */
import { describe, it, expect, beforeEach, afterEach } from 'vitest'

// Component classes extracted for testing
class Component {
    constructor(element) {
        this.element = element;
        this.init();
    }

    init() {}

    on(event, selector, callback) {
        this.element.addEventListener(event, (e) => {
            if (e.target.matches(selector)) {
                callback(e);
            }
        });
    }

    find(selector) {
        return this.element.querySelector(selector);
    }

    findAll(selector) {
        return this.element.querySelectorAll(selector);
    }
}

class Modal extends Component {
    init() {
        this.modal = this.find('[data-modal]');
        this.closeBtn = this.find('[data-modal-close]');
        this.overlay = this.find('[data-modal-overlay]');

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }
    }

    open() {
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
    }

    close() {
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
    }
}

class Dropdown extends Component {
    init() {
        this.button = this.find('[data-dropdown-button]');
        this.menu = this.find('[data-dropdown-menu]');

        if (this.button) {
            this.button.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });
        }

        document.addEventListener('click', () => {
            if (this.menu) this.menu.classList.add('hidden');
        });
    }

    toggle() {
        this.menu.classList.toggle('hidden');
    }
}

class Tabs extends Component {
    init() {
        this.tabButtons = this.findAll('[data-tab-button]');
        this.tabContents = this.findAll('[data-tab-content]');

        this.tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tabButton;
                this.switchTab(target);
            });
        });
    }

    switchTab(target) {
        this.tabButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tabButton === target);
        });
        this.tabContents.forEach(content => {
            content.classList.toggle('hidden', content.dataset.tabContent !== target);
        });
    }
}

class Accordion extends Component {
    init() {
        this.items = this.findAll('[data-accordion-item]');

        this.items.forEach(item => {
            const header = item.querySelector('[data-accordion-header]');
            const content = item.querySelector('[data-accordion-content]');

            if (header && content) {
                header.addEventListener('click', () => {
                    const isOpen = content.classList.contains('hidden');
                    this.items.forEach(i => {
                        i.querySelector('[data-accordion-content]').classList.add('hidden');
                    });
                    if (isOpen) {
                        content.classList.remove('hidden');
                    }
                });
            }
        });
    }
}

class ToggleSwitch extends Component {
    init() {
        this.toggle = this.find('[data-toggle]');
        if (this.toggle) {
            this.toggle.addEventListener('change', () => {
                const target = this.toggle.dataset.toggle;
                const targetEl = document.querySelector(`[data-toggle-target="${target}"]`);
                if (targetEl) {
                    targetEl.classList.toggle('hidden', !this.toggle.checked);
                }
            });
        }
    }
}

class SearchSelect extends Component {
    init() {
        this.input = this.find('[data-search-select]');
        this.options = this.find('[data-search-options]');
        this.hiddenInput = this.find('input[type="hidden"]');

        if (this.input) {
            this.input.addEventListener('input', () => this.filter());
        }
    }

    filter() {
        const query = this.input.value.toLowerCase();
        const items = this.options.querySelectorAll('[data-search-item]');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? '' : 'none';
        });
    }
}

describe('Component System', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    describe('Modal', () => {
        it('opens modal', () => {
            document.body.innerHTML = `
                <div data-component="modal">
                    <div data-modal class="hidden">Modal Content</div>
                    <button data-modal-close>Close</button>
                </div>
            `;
            const el = document.querySelector('[data-component="modal"]');
            const modal = new Modal(el);

            modal.open();
            expect(modal.modal.classList.contains('hidden')).toBe(false);
            expect(modal.modal.classList.contains('flex')).toBe(true);
        });

        it('closes modal via close button', () => {
            document.body.innerHTML = `
                <div data-component="modal">
                    <div data-modal class="hidden">Modal Content</div>
                    <button data-modal-close>Close</button>
                </div>
            `;
            const el = document.querySelector('[data-component="modal"]');
            const modal = new Modal(el);

            modal.open();
            modal.closeBtn.click();
            expect(modal.modal.classList.contains('hidden')).toBe(true);
        });

        it('closes modal via overlay click', () => {
            document.body.innerHTML = `
                <div data-component="modal">
                    <div data-modal class="hidden">Modal Content</div>
                    <div data-modal-overlay></div>
                </div>
            `;
            const el = document.querySelector('[data-component="modal"]');
            const modal = new Modal(el);

            modal.open();
            modal.overlay.click();
            expect(modal.modal.classList.contains('hidden')).toBe(true);
        });
    });

    describe('Dropdown', () => {
        it('toggles dropdown menu', () => {
            document.body.innerHTML = `
                <div data-component="dropdown">
                    <button data-dropdown-button>Toggle</button>
                    <div data-dropdown-menu class="hidden">Menu</div>
                </div>
            `;
            const el = document.querySelector('[data-component="dropdown"]');
            const dropdown = new Dropdown(el);

            dropdown.button.click();
            expect(dropdown.menu.classList.contains('hidden')).toBe(false);

            dropdown.button.click();
            expect(dropdown.menu.classList.contains('hidden')).toBe(true);
        });
    });

    describe('Tabs', () => {
        it('switches tabs', () => {
            document.body.innerHTML = `
                <div data-component="tabs">
                    <button data-tab-button="tab1" class="active">Tab 1</button>
                    <button data-tab-button="tab2">Tab 2</button>
                    <div data-tab-content="tab1">Content 1</div>
                    <div data-tab-content="tab2" class="hidden">Content 2</div>
                </div>
            `;
            const el = document.querySelector('[data-component="tabs"]');
            const tabs = new Tabs(el);

            tabs.switchTab('tab2');
            expect(tabs.tabButtons[1].classList.contains('active')).toBe(true);
            expect(tabs.tabContents[1].classList.contains('hidden')).toBe(false);
            expect(tabs.tabContents[0].classList.contains('hidden')).toBe(true);
        });
    });

    describe('Accordion', () => {
        it('opens accordion item', () => {
            document.body.innerHTML = `
                <div data-component="accordion">
                    <div data-accordion-item>
                        <button data-accordion-header>Header 1</button>
                        <div data-accordion-content class="hidden">Content 1</div>
                    </div>
                    <div data-accordion-item>
                        <button data-accordion-header>Header 2</button>
                        <div data-accordion-content class="hidden">Content 2</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="accordion"]');
            const accordion = new Accordion(el);

            accordion.items[0].querySelector('[data-accordion-header]').click();
            expect(accordion.items[0].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(false);
        });

        it('closes other items when opening one', () => {
            document.body.innerHTML = `
                <div data-component="accordion">
                    <div data-accordion-item>
                        <button data-accordion-header>Header 1</button>
                        <div data-accordion-content class="hidden">Content 1</div>
                    </div>
                    <div data-accordion-item>
                        <button data-accordion-header>Header 2</button>
                        <div data-accordion-content class="hidden">Content 2</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="accordion"]');
            const accordion = new Accordion(el);

            // Open first item
            accordion.items[0].querySelector('[data-accordion-header]').click();
            // Open second item
            accordion.items[1].querySelector('[data-accordion-header]').click();

            expect(accordion.items[0].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(true);
            expect(accordion.items[1].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(false);
        });
    });

    describe('ToggleSwitch', () => {
        it('toggles target element', () => {
            document.body.innerHTML = `
                <div data-component="toggle-switch">
                    <input type="checkbox" data-toggle="target1">
                </div>
                <div data-toggle-target="target1" class="hidden">Target</div>
            `;
            const el = document.querySelector('[data-component="toggle-switch"]');
            const toggle = new ToggleSwitch(el);

            toggle.toggle.checked = true;
            toggle.toggle.dispatchEvent(new Event('change'));

            const target = document.querySelector('[data-toggle-target="target1"]');
            expect(target.classList.contains('hidden')).toBe(false);
        });
    });

    describe('SearchSelect', () => {
        it('filters options by search query', () => {
            document.body.innerHTML = `
                <div data-component="search-select">
                    <input data-search-select type="text">
                    <div data-search-options>
                        <div data-search-item>Apple</div>
                        <div data-search-item>Banana</div>
                        <div data-search-item>Cherry</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="search-select"]');
            const searchSelect = new SearchSelect(el);

            searchSelect.input.value = 'app';
            searchSelect.input.dispatchEvent(new Event('input'));

            const items = searchSelect.options.querySelectorAll('[data-search-item]');
            expect(items[0].style.display).toBe('');
            expect(items[1].style.display).toBe('none');
            expect(items[2].style.display).toBe('none');
        });
    });
});
