/**
 * Tests for DMSaaS Component classes
 * Tests the component system from resources/js/components.js
 *
 * Covers: Modal, Dropdown, Tabs, Accordion, ToggleSwitch, SearchSelect
 * with emphasis on open/close, toggle, and event handling patterns.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// ---------------------------------------------------------------------------
// Component classes extracted verbatim from resources/js/components.js
// (kept in sync manually — any change there must be mirrored here)
// ---------------------------------------------------------------------------

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

// ===========================================================================
// TEST SUITE
// ===========================================================================

describe('Component System', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    // =======================================================================
    // MODAL
    // =======================================================================

    describe('Modal', () => {

        describe('open()', () => {
            it('removes hidden class and adds flex class', () => {
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

            it('is idempotent — calling open() twice keeps modal visible', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                modal.open();
                modal.open();

                expect(modal.modal.classList.contains('hidden')).toBe(false);
                expect(modal.modal.classList.contains('flex')).toBe(true);
            });

            it('works when modal starts without hidden class (already visible)', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal>Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                modal.open();

                expect(modal.modal.classList.contains('hidden')).toBe(false);
                expect(modal.modal.classList.contains('flex')).toBe(true);
            });
        });

        describe('close()', () => {
            it('adds hidden class and removes flex class', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="flex">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                modal.close();

                expect(modal.modal.classList.contains('hidden')).toBe(true);
                expect(modal.modal.classList.contains('flex')).toBe(false);
            });

            it('is idempotent — calling close() twice keeps modal hidden', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="flex">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                modal.close();
                modal.close();

                expect(modal.modal.classList.contains('hidden')).toBe(true);
                expect(modal.modal.classList.contains('flex')).toBe(false);
            });

            it('open() then close() returns to hidden state', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                modal.open();
                modal.close();

                expect(modal.modal.classList.contains('hidden')).toBe(true);
                expect(modal.modal.classList.contains('flex')).toBe(false);
            });
        });

        describe('event handling — close button', () => {
            it('closes modal when close button is clicked', () => {
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
                expect(modal.modal.classList.contains('flex')).toBe(false);
            });

            it('does not throw when close button is absent', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');

                expect(() => new Modal(el)).not.toThrow();
            });

            it('close button reference is null when not present', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                expect(modal.closeBtn).toBeNull();
            });
        });

        describe('event handling — overlay', () => {
            it('closes modal when overlay is clicked', () => {
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

            it('does not throw when overlay is absent', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');

                expect(() => new Modal(el)).not.toThrow();
            });

            it('overlay reference is null when not present', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                expect(modal.overlay).toBeNull();
            });
        });

        describe('event handling — combined close triggers', () => {
            it('both close button and overlay close the modal independently', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                        <button data-modal-close>Close</button>
                        <div data-modal-overlay></div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                // Close via button
                modal.open();
                modal.closeBtn.click();
                expect(modal.modal.classList.contains('hidden')).toBe(true);

                // Close via overlay
                modal.open();
                modal.overlay.click();
                expect(modal.modal.classList.contains('hidden')).toBe(true);
            });
        });

        describe('init() state', () => {
            it('finds modal, closeBtn, and overlay elements on init', () => {
                document.body.innerHTML = `
                    <div data-component="modal">
                        <div data-modal class="hidden">Modal Content</div>
                        <button data-modal-close>Close</button>
                        <div data-modal-overlay></div>
                    </div>
                `;
                const el = document.querySelector('[data-component="modal"]');
                const modal = new Modal(el);

                expect(modal.modal).not.toBeNull();
                expect(modal.closeBtn).not.toBeNull();
                expect(modal.overlay).not.toBeNull();
            });
        });
    });

    // =======================================================================
    // DROPDOWN
    // =======================================================================

    describe('Dropdown', () => {

        describe('toggle()', () => {
            it('shows menu when toggled from hidden state', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                dropdown.toggle();

                expect(dropdown.menu.classList.contains('hidden')).toBe(false);
            });

            it('hides menu when toggled from visible state', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu>Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                dropdown.toggle();

                expect(dropdown.menu.classList.contains('hidden')).toBe(true);
            });

            it('toggles correctly across multiple calls', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                dropdown.toggle(); // show
                expect(dropdown.menu.classList.contains('hidden')).toBe(false);

                dropdown.toggle(); // hide
                expect(dropdown.menu.classList.contains('hidden')).toBe(true);

                dropdown.toggle(); // show again
                expect(dropdown.menu.classList.contains('hidden')).toBe(false);
            });
        });

        describe('event handling — button click', () => {
            it('shows menu when button is clicked', () => {
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
            });

            it('hides menu when button is clicked again', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                dropdown.button.click(); // show
                dropdown.button.click(); // hide

                expect(dropdown.menu.classList.contains('hidden')).toBe(true);
            });

            it('calls stopPropagation on button click to prevent document close', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                const event = new Event('click', { bubbles: true });
                const stopPropagationSpy = vi.spyOn(event, 'stopPropagation');

                dropdown.button.dispatchEvent(event);

                expect(stopPropagationSpy).toHaveBeenCalled();
            });
        });

        describe('event handling — document click (outside)', () => {
            it('closes menu when clicking outside the dropdown', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                    <div id="outside">Outside</div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                // Open the dropdown
                dropdown.button.click();
                expect(dropdown.menu.classList.contains('hidden')).toBe(false);

                // Simulate click outside
                document.getElementById('outside').click();

                expect(dropdown.menu.classList.contains('hidden')).toBe(true);
            });

            it('closes menu on any document click', () => {
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

                // Click anywhere on document
                document.dispatchEvent(new Event('click'));

                expect(dropdown.menu.classList.contains('hidden')).toBe(true);
            });
        });

        describe('init() state', () => {
            it('finds button and menu elements on init', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <button data-dropdown-button>Toggle</button>
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                expect(dropdown.button).not.toBeNull();
                expect(dropdown.menu).not.toBeNull();
            });

            it('does not throw when button is absent', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');

                expect(() => new Dropdown(el)).not.toThrow();
            });

            it('button reference is null when not present', () => {
                document.body.innerHTML = `
                    <div data-component="dropdown">
                        <div data-dropdown-menu class="hidden">Menu</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="dropdown"]');
                const dropdown = new Dropdown(el);

                expect(dropdown.button).toBeNull();
            });
        });
    });

    // =======================================================================
    // TABS
    // =======================================================================

    describe('Tabs', () => {

        describe('switchTab()', () => {
            it('activates the target tab button with active class', () => {
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
            });

            it('deactivates non-target tab buttons (removes active class)', () => {
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

                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
            });

            it('shows the target tab content (removes hidden class)', () => {
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

                expect(tabs.tabContents[1].classList.contains('hidden')).toBe(false);
            });

            it('hides non-target tab content (adds hidden class)', () => {
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

                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(true);
            });

            it('handles switching back and forth between tabs', () => {
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
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);

                tabs.switchTab('tab1');
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(true);
                expect(tabs.tabButtons[1].classList.contains('active')).toBe(false);
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(false);
                expect(tabs.tabContents[1].classList.contains('hidden')).toBe(true);
            });

            it('handles three or more tabs correctly', () => {
                document.body.innerHTML = `
                    <div data-component="tabs">
                        <button data-tab-button="a" class="active">A</button>
                        <button data-tab-button="b">B</button>
                        <button data-tab-button="c">C</button>
                        <div data-tab-content="a">Content A</div>
                        <div data-tab-content="b" class="hidden">Content B</div>
                        <div data-tab-content="c" class="hidden">Content C</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="tabs"]');
                const tabs = new Tabs(el);

                tabs.switchTab('c');

                // Only tab C should be active
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
                expect(tabs.tabButtons[1].classList.contains('active')).toBe(false);
                expect(tabs.tabButtons[2].classList.contains('active')).toBe(true);

                // Only content C should be visible
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(true);
                expect(tabs.tabContents[1].classList.contains('hidden')).toBe(true);
                expect(tabs.tabContents[2].classList.contains('hidden')).toBe(false);
            });

            it('handles switching to a non-existent target gracefully', () => {
                document.body.innerHTML = `
                    <div data-component="tabs">
                        <button data-tab-button="tab1" class="active">Tab 1</button>
                        <div data-tab-content="tab1">Content 1</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="tabs"]');
                const tabs = new Tabs(el);

                // Should not throw
                expect(() => tabs.switchTab('nonexistent')).not.toThrow();

                // All buttons should lose active class
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
                // All content should be hidden
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(true);
            });
        });

        describe('event handling — tab button click', () => {
            it('switches tab when a tab button is clicked', () => {
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

                tabs.tabButtons[1].click();

                expect(tabs.tabButtons[1].classList.contains('active')).toBe(true);
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
                expect(tabs.tabContents[1].classList.contains('hidden')).toBe(false);
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(true);
            });

            it('clicking the already active tab keeps it active', () => {
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

                tabs.tabButtons[0].click();

                expect(tabs.tabButtons[0].classList.contains('active')).toBe(true);
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(false);
            });

            it('each tab button click triggers the correct switch', () => {
                document.body.innerHTML = `
                    <div data-component="tabs">
                        <button data-tab-button="home" class="active">Home</button>
                        <button data-tab-button="profile">Profile</button>
                        <button data-tab-button="settings">Settings</button>
                        <div data-tab-content="home">Home Content</div>
                        <div data-tab-content="profile" class="hidden">Profile Content</div>
                        <div data-tab-content="settings" class="hidden">Settings Content</div>
                    </div>
                `;
                const el = document.querySelector('[data-component="tabs"]');
                const tabs = new Tabs(el);

                // Click profile
                tabs.tabButtons[1].click();
                expect(tabs.tabButtons[1].classList.contains('active')).toBe(true);
                expect(tabs.tabContents[1].classList.contains('hidden')).toBe(false);
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
                expect(tabs.tabButtons[2].classList.contains('active')).toBe(false);

                // Click settings
                tabs.tabButtons[2].click();
                expect(tabs.tabButtons[2].classList.contains('active')).toBe(true);
                expect(tabs.tabContents[2].classList.contains('hidden')).toBe(false);
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(false);
                expect(tabs.tabButtons[1].classList.contains('active')).toBe(false);

                // Click home
                tabs.tabButtons[0].click();
                expect(tabs.tabButtons[0].classList.contains('active')).toBe(true);
                expect(tabs.tabContents[0].classList.contains('hidden')).toBe(false);
                expect(tabs.tabButtons[1].classList.contains('active')).toBe(false);
                expect(tabs.tabButtons[2].classList.contains('active')).toBe(false);
            });
        });

        describe('init() state', () => {
            it('finds all tab buttons and tab contents on init', () => {
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

                expect(tabs.tabButtons).toHaveLength(2);
                expect(tabs.tabContents).toHaveLength(2);
            });

            it('handles empty tabs (no buttons or content)', () => {
                document.body.innerHTML = `
                    <div data-component="tabs">
                    </div>
                `;
                const el = document.querySelector('[data-component="tabs"]');

                expect(() => new Tabs(el)).not.toThrow();

                const tabs = new Tabs(el);
                expect(tabs.tabButtons).toHaveLength(0);
                expect(tabs.tabContents).toHaveLength(0);
            });
        });
    });

    // =======================================================================
    // ACCORDION
    // =======================================================================

    describe('Accordion', () => {
        it('opens accordion item when header is clicked', () => {
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

        it('closes other items when opening a new one', () => {
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
            accordion.items[1].querySelector('[data-accordion-header]').click();

            expect(accordion.items[0].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(true);
            expect(accordion.items[1].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(false);
        });

        it('toggles an open item closed when clicking its header again', () => {
            document.body.innerHTML = `
                <div data-component="accordion">
                    <div data-accordion-item>
                        <button data-accordion-header>Header 1</button>
                        <div data-accordion-content class="hidden">Content 1</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="accordion"]');
            const accordion = new Accordion(el);

            const header = accordion.items[0].querySelector('[data-accordion-header]');

            header.click(); // open
            expect(accordion.items[0].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(false);

            header.click(); // close
            expect(accordion.items[0].querySelector('[data-accordion-content]').classList.contains('hidden')).toBe(true);
        });
    });

    // =======================================================================
    // TOGGLE SWITCH
    // =======================================================================

    describe('ToggleSwitch', () => {
        it('shows target element when toggle is checked', () => {
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

        it('hides target element when toggle is unchecked', () => {
            document.body.innerHTML = `
                <div data-component="toggle-switch">
                    <input type="checkbox" data-toggle="target1" checked>
                </div>
                <div data-toggle-target="target1">Target</div>
            `;
            const el = document.querySelector('[data-component="toggle-switch"]');
            const toggle = new ToggleSwitch(el);

            toggle.toggle.checked = false;
            toggle.toggle.dispatchEvent(new Event('change'));

            const target = document.querySelector('[data-toggle-target="target1"]');
            expect(target.classList.contains('hidden')).toBe(true);
        });

        it('does not throw when toggle element is absent', () => {
            document.body.innerHTML = `
                <div data-component="toggle-switch">
                </div>
            `;
            const el = document.querySelector('[data-component="toggle-switch"]');

            expect(() => new ToggleSwitch(el)).not.toThrow();
        });

        it('does not throw when target element does not exist', () => {
            document.body.innerHTML = `
                <div data-component="toggle-switch">
                    <input type="checkbox" data-toggle="nonexistent">
                </div>
            `;
            const el = document.querySelector('[data-component="toggle-switch"]');
            const toggle = new ToggleSwitch(el);

            toggle.toggle.checked = true;
            expect(() => toggle.toggle.dispatchEvent(new Event('change'))).not.toThrow();
        });
    });

    // =======================================================================
    // SEARCH SELECT
    // =======================================================================

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

        it('shows all items when query is empty', () => {
            document.body.innerHTML = `
                <div data-component="search-select">
                    <input data-search-select type="text">
                    <div data-search-options>
                        <div data-search-item>Apple</div>
                        <div data-search-item>Banana</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="search-select"]');
            const searchSelect = new SearchSelect(el);

            searchSelect.input.value = '';
            searchSelect.input.dispatchEvent(new Event('input'));

            const items = searchSelect.options.querySelectorAll('[data-search-item]');
            expect(items[0].style.display).toBe('');
            expect(items[1].style.display).toBe('');
        });

        it('is case-insensitive', () => {
            document.body.innerHTML = `
                <div data-component="search-select">
                    <input data-search-select type="text">
                    <div data-search-options>
                        <div data-search-item>Apple</div>
                        <div data-search-item>BANANA</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="search-select"]');
            const searchSelect = new SearchSelect(el);

            searchSelect.input.value = 'apple';
            searchSelect.input.dispatchEvent(new Event('input'));

            const items = searchSelect.options.querySelectorAll('[data-search-item]');
            expect(items[0].style.display).toBe('');
            expect(items[1].style.display).toBe('none');
        });

        it('does not throw when input is absent', () => {
            document.body.innerHTML = `
                <div data-component="search-select">
                    <div data-search-options>
                        <div data-search-item>Apple</div>
                    </div>
                </div>
            `;
            const el = document.querySelector('[data-component="search-select"]');

            expect(() => new SearchSelect(el)).not.toThrow();
        });
    });
});
