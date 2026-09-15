/**
 * FormValidator - Client-side form validation
 */
export class FormValidator {
    constructor(form, options = {}) {
        this.form = form;
        this.options = {
            errorClass: 'text-sm text-red-600 mt-1',
            inputErrorClass: 'border-red-500 focus:border-red-500 focus:ring-red-500',
            inputSuccessClass: 'border-green-500 focus:border-green-500 focus:ring-green-500',
            validateOnBlur: true,
            validateOnInput: false,
            ...options,
        };
        this.errors = {};
        this.validators = {};

        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        if (this.options.validateOnBlur) {
            this.form.addEventListener('blur', (e) => this.handleBlur(e), true);
        }
        if (this.options.validateOnInput) {
            this.form.addEventListener('input', (e) => this.handleInput(e), true);
        }
    }

    handleSubmit(e) {
        this.errors = {};
        this.validateAll();
        if (Object.keys(this.errors).length > 0) {
            e.preventDefault();
            this.showErrors();
            return false;
        }
        return true;
    }

    handleBlur(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            this.validateField(e.target);
        }
    }

    handleInput(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            this.validateField(e.target);
        }
    }

    validateAll() {
        const fields = this.form.querySelectorAll('input, textarea, select');
        fields.forEach((field) => this.validateField(field));
    }

    validateField(field) {
        const name = field.name;
        const value = field.value.trim();
        const rules = field.dataset.validate || '';
        const rulesArray = rules.split('|');

        // Skip validation for empty optional fields
        if (!value && !rules.includes('required')) {
            this.clearFieldError(field);
            return true;
        }

        let isValid = true;
        let errorMessage = '';

        for (const rule of rulesArray) {
            const [ruleName, ...params] = rule.split(':');

            switch (ruleName) {
                case 'required':
                    if (!value) {
                        isValid = false;
                        errorMessage = `${this.getFieldName(name)} is required`;
                    }
                    break;
                case 'email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address';
                    }
                    break;
                case 'min':
                    const minLen = parseInt(params[0]);
                    if (value && value.length < minLen) {
                        isValid = false;
                        errorMessage = `${this.getFieldName(name)} must be at least ${minLen} characters`;
                    }
                    break;
                case 'max':
                    const maxLen = parseInt(params[0]);
                    if (value && value.length > maxLen) {
                        isValid = false;
                        errorMessage = `${this.getFieldName(name)} must be less than ${maxLen} characters`;
                    }
                    break;
                case 'numeric':
                    if (value && !/^\d+$/.test(value)) {
                        isValid = false;
                        errorMessage = `${this.getFieldName(name)} must be numeric`;
                    }
                    break;
            }

            if (!isValid) break;
        }

        if (!isValid) {
            this.errors[name] = errorMessage;
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }

        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        field.classList.add(...this.options.inputErrorClass.split(' '));
        const errorEl = document.createElement('p');
        errorEl.className = this.options.errorClass;
        errorEl.textContent = message;
        errorEl.dataset.errorFor = field.name;
        field.parentNode.appendChild(errorEl);
    }

    clearFieldError(field) {
        field.classList.remove(...this.options.inputErrorClass.split(' '));
        const existingError = field.parentNode.querySelector(`[data-error-for="${field.name}"]`);
        if (existingError) existingError.remove();
    }

    showErrors() {
        const firstField = Object.keys(this.errors)[0];
        if (firstField) {
            const field = this.form.querySelector(`[name="${firstField}"]`);
            if (field) field.focus();
        }
    }

    getFieldName(name) {
        return name.replace(/[_-]/g, ' ').replace(/([A-Z])/g, ' $1').trim();
    }
}
