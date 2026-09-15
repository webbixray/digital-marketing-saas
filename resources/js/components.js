/**
 * DMSaaS JavaScript Component System
 * A lightweight component system for Laravel Blade templates
 */

// Component Registry
const components = {};

/**
 * Register a component
 */
function registerComponent(name, config) {
    components[name] = config;
}

/**
 * Initialize all components
 */
function initComponents() {
    document.querySelectorAll('[data-component]').forEach(el => {
        const name = el.dataset.component;
        if (components[name]) {
            new components[name](el);
        }
    });
}

/**
 * Base Component Class
 */
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

/**
 * Modal Component
 */
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

/**
 * Dropdown Component
 */
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

/**
 * Tabs Component
 */
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

/**
 * Accordion Component
 */
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

/**
 * Tooltip Component
 */
class Tooltip extends Component {
    init() {
        this.tooltip = this.find('[data-tooltip]');
        if (this.tooltip) {
            this.tooltip.addEventListener('mouseenter', () => this.show());
            this.tooltip.addEventListener('mouseleave', () => this.hide());
        }
    }

    show() {
        const text = this.tooltip.dataset.tooltip;
        const tooltip = document.createElement('div');
        tooltip.className = 'fixed z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg pointer-events-none';
        tooltip.textContent = text;
        tooltip.id = 'tooltip';
        document.body.appendChild(tooltip);

        const rect = this.tooltip.getBoundingClientRect();
        tooltip.style.top = `${rect.top - 30}px`;
        tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
    }

    hide() {
        const tooltip = document.getElementById('tooltip');
        if (tooltip) tooltip.remove();
    }
}

/**
 * Confirm Dialog Component
 */
class ConfirmDialog extends Component {
    init() {
        this.buttons = this.findAll('[data-confirm]');
        this.buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const message = btn.dataset.confirm || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }
}

/**
 * Auto-dismiss Alert Component
 */
class AutoDismissAlert extends Component {
    init() {
        this.alerts = this.findAll('[data-auto-dismiss]');
        this.alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    }
}

/**
 * Form Validator Component
 */
class FormValidatorComponent extends Component {
    init() {
        this.form = this.element;
        this.errorClass = 'text-sm text-red-600 mt-1';
        this.inputErrorClass = 'border-red-500 focus:border-red-500 focus:ring-red-500';

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        const errors = {};
        const fields = this.form.querySelectorAll('input, textarea, select');

        fields.forEach(field => {
            const rules = (field.dataset.validate || '').split('|');
            const value = field.value.trim();

            for (const rule of rules) {
                const [ruleName, ...params] = rule.split(':');
                let error = null;

                if (ruleName === 'required' && !value) {
                    error = 'This field is required';
                } else if (ruleName === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    error = 'Please enter a valid email address';
                } else if (ruleName === 'min' && value && value.length < parseInt(params[0])) {
                    error = `Must be at least ${params[0]} characters`;
                }

                if (error) {
                    errors[field.name] = error;
                    this.showFieldError(field, error);
                    break;
                }
            }
        });

        if (Object.keys(errors).length > 0) {
            e.preventDefault();
        }
    }

    showFieldError(field, message) {
        field.classList.add(...this.inputErrorClass.split(' '));
        const errorEl = document.createElement('p');
        errorEl.className = this.errorClass;
        errorEl.textContent = message;
        field.parentNode.appendChild(errorEl);
    }
}

/**
 * Toggle Switch Component
 */
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

/**
 * Search Select Component
 */
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

// Register components
registerComponent('modal', Modal);
registerComponent('dropdown', Dropdown);
registerComponent('tabs', Tabs);
registerComponent('accordion', Accordion);
registerComponent('tooltip', Tooltip);
registerComponent('confirm-dialog', ConfirmDialog);
registerComponent('auto-dismiss', AutoDismissAlert);
registerComponent('form-validator', FormValidatorComponent);
registerComponent('toggle-switch', ToggleSwitch);
registerComponent('search-select', SearchSelect);

// Export
window.DMSaaS_Components = {
    registerComponent,
    initComponents,
    Component,
    Modal,
    Dropdown,
    Tabs,
    Accordion,
    Tooltip,
    ConfirmDialog,
    AutoDismissAlert,
    FormValidatorComponent,
    ToggleSwitch,
    SearchSelect,
};
