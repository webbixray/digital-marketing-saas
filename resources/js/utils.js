/**
 * DMSaaS utility functions
 * Date formatting, currency, string helpers, validation, debounce, throttle
 */

export const utils = {
    /**
     * Date formatting
     */
    formatDate(date, format = 'short') {
        if (!date && date !== 0) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        const formats = {
            short: { month: 'short', day: 'numeric', year: 'numeric' },
            long: { month: 'long', day: 'numeric', year: 'numeric' },
            numeric: { year: 'numeric', month: '2-digit', day: '2-digit' },
            time: { hour: '2-digit', minute: '2-digit' },
        };
        return new Intl.DateTimeFormat('en-US', formats[format] || formats.short).format(d);
    },

    formatRelativeTime(date) {
        const now = new Date();
        const then = new Date(date);
        const diffMs = now - then;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHr / 24);

        if (diffSec < 60) return 'just now';
        if (diffMin < 60) return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
        if (diffHr < 24) return `${diffHr} hour${diffHr > 1 ? 's' : ''} ago`;
        if (diffDay < 30) return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
        if (diffDay < 365) return `${Math.floor(diffDay / 30)} month${diffDay >= 60 ? 's' : ''} ago`;
        return `${Math.floor(diffDay / 365)} year${diffDay >= 730 ? 's' : ''} ago`;
    },

    /**
     * Currency formatting
     */
    formatCurrency(amount, currency = 'USD', locale = 'en-US') {
        if (typeof amount !== 'number' || isNaN(amount)) return '';
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
        }).format(amount);
    },

    formatCompactCurrency(amount, currency = 'USD') {
        if (typeof amount !== 'number' || isNaN(amount)) return '';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            notation: 'compact',
            maximumFractionDigits: 1,
        }).format(amount);
    },

    /**
     * String helpers
     */
    capitalize(str) {
        if (!str || typeof str !== 'string') return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    },

    camelCase(str) {
        if (!str || typeof str !== 'string') return '';
        return str
            .toLowerCase()
            .replace(/[^a-zA-Z0-9]+(.)/g, (_, chr) => chr.toUpperCase());
    },

    snakeCase(str) {
        if (!str || typeof str !== 'string') return '';
        return str
            .replace(/\s+/g, '_')
            .replace(/([a-z])([A-Z])/g, '$1_$2')
            .replace(/[^a-zA-Z0-9_]/g, '')
            .toLowerCase();
    },

    kebabCase(str) {
        if (!str || typeof str !== 'string') return '';
        return str
            .replace(/\s+/g, '-')
            .replace(/([a-z])([A-Z])/g, '$1-$2')
            .replace(/[^a-zA-Z0-9-]/g, '')
            .toLowerCase();
    },

    truncate(str, length = 50, suffix = '...') {
        if (!str || typeof str !== 'string') return '';
        if (str.length <= length) return str;
        return str.slice(0, length - suffix.length) + suffix;
    },

    stripTags(str) {
        if (!str || typeof str !== 'string') return '';
        return str.replace(/<[^>]*>/g, '');
    },

    /**
     * Validation
     */
    validateEmail(email) {
        if (!email || typeof email !== 'string') return false;
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    validateUrl(url) {
        if (!url || typeof url !== 'string') return false;
        try {
            const parsed = new URL(url);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch {
            return false;
        }
    },

    validateRequired(value) {
        if (value === null || value === undefined) return false;
        if (typeof value === 'string') return value.trim().length > 0;
        if (typeof value === 'number') return !isNaN(value);
        if (Array.isArray(value)) return value.length > 0;
        return Boolean(value);
    },

    validateMinLength(value, min) {
        if (!value || typeof value !== 'string') return false;
        return value.length >= min;
    },

    validateMaxLength(value, max) {
        if (!value || typeof value !== 'string') return false;
        return value.length <= max;
    },

    /**
     * Debounce
     */
    debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    /**
     * Throttle
     */
    throttle(fn, limit) {
        let inThrottle = false;
        let lastArgs = null;
        return function (...args) {
            if (!inThrottle) {
                fn.apply(this, args);
                inThrottle = true;
                setTimeout(() => {
                    inThrottle = false;
                    if (lastArgs) {
                        fn.apply(this, lastArgs);
                        lastArgs = null;
                    }
                }, limit);
            } else {
                lastArgs = args;
            }
        };
    },
};

export default utils;
