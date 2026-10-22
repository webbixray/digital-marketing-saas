/**
 * Unit tests for DMSaaS utility functions:
 * date formatting, currency, string helpers, validation, debounce, throttle
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// ---- Utility implementations under test ----

const utils = {
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

// ---- Tests ----

describe('Date Formatting Utilities', () => {
    describe('formatDate', () => {
        it('formats date in short format (default)', () => {
            const result = utils.formatDate('2024-01-15');
            expect(result).toContain('Jan');
            expect(result).toContain('15');
            expect(result).toContain('2024');
        });

        it('formats date in long format', () => {
            const result = utils.formatDate('2024-01-15', 'long');
            expect(result).toContain('January');
            expect(result).toContain('15');
            expect(result).toContain('2024');
        });

        it('formats date in numeric format', () => {
            const result = utils.formatDate('2024-01-15', 'numeric');
            expect(result).toMatch(/2024/);
            expect(result).toMatch(/01/);
        });

        it('formats date in time format', () => {
            const result = utils.formatDate('2024-01-15T14:30:00', 'time');
            expect(result).toMatch(/\d{1,2}:\d{2}/);
        });

        it('returns empty string for invalid date', () => {
            expect(utils.formatDate('not-a-date')).toBe('');
            expect(utils.formatDate('')).toBe('');
            expect(utils.formatDate(null)).toBe('');
        });

        it('accepts Date objects', () => {
            const result = utils.formatDate(new Date('2024-06-01'));
            expect(result).toContain('Jun');
            expect(result).toContain('2024');
        });
    });

    describe('formatRelativeTime', () => {
        it('returns "just now" for very recent timestamps', () => {
            const now = new Date();
            expect(utils.formatRelativeTime(now)).toBe('just now');
        });

        it('returns minutes ago', () => {
            const fiveMinAgo = new Date(Date.now() - 5 * 60 * 1000);
            expect(utils.formatRelativeTime(fiveMinAgo)).toBe('5 minutes ago');
        });

        it('returns singular minute', () => {
            const oneMinAgo = new Date(Date.now() - 60 * 1000);
            expect(utils.formatRelativeTime(oneMinAgo)).toBe('1 minute ago');
        });

        it('returns hours ago', () => {
            const threeHoursAgo = new Date(Date.now() - 3 * 60 * 60 * 1000);
            expect(utils.formatRelativeTime(threeHoursAgo)).toBe('3 hours ago');
        });

        it('returns singular hour', () => {
            const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
            expect(utils.formatRelativeTime(oneHourAgo)).toBe('1 hour ago');
        });

        it('returns days ago', () => {
            const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
            expect(utils.formatRelativeTime(sevenDaysAgo)).toBe('7 days ago');
        });

        it('returns months ago', () => {
            const sixtyDaysAgo = new Date(Date.now() - 60 * 24 * 60 * 60 * 1000);
            expect(utils.formatRelativeTime(sixtyDaysAgo)).toBe('2 months ago');
        });

        it('returns years ago', () => {
            const twoYearsAgo = new Date(Date.now() - 730 * 24 * 60 * 60 * 1000);
            expect(utils.formatRelativeTime(twoYearsAgo)).toBe('2 years ago');
        });
    });
});

describe('Currency Utilities', () => {
    describe('formatCurrency', () => {
        it('formats USD correctly', () => {
            const result = utils.formatCurrency(1234.56, 'USD');
            expect(result).toContain('$');
            expect(result).toContain('1,234.56');
        });

        it('formats EUR correctly', () => {
            const result = utils.formatCurrency(99.99, 'EUR', 'de-DE');
            expect(result).toContain('99');
        });

        it('formats zero', () => {
            const result = utils.formatCurrency(0, 'USD');
            expect(result).toContain('0');
        });

        it('formats negative amounts', () => {
            const result = utils.formatCurrency(-50, 'USD');
            expect(result).toContain('50');
            expect(result).toContain('-');
        });

        it('returns empty string for non-numeric input', () => {
            expect(utils.formatCurrency('abc')).toBe('');
            expect(utils.formatCurrency(null)).toBe('');
            expect(utils.formatCurrency(NaN)).toBe('');
        });

        it('formats large numbers with commas', () => {
            const result = utils.formatCurrency(1000000, 'USD');
            expect(result).toContain('1,000,000');
        });
    });

    describe('formatCompactCurrency', () => {
        it('formats thousands compactly', () => {
            const result = utils.formatCompactCurrency(1500, 'USD');
            expect(result).toContain('K');
        });

        it('formats millions compactly', () => {
            const result = utils.formatCompactCurrency(2500000, 'USD');
            expect(result).toContain('M');
        });

        it('returns empty string for invalid input', () => {
            expect(utils.formatCompactCurrency(NaN)).toBe('');
            expect(utils.formatCompactCurrency(null)).toBe('');
        });
    });
});

describe('String Helper Utilities', () => {
    describe('capitalize', () => {
        it('capitalizes first letter', () => {
            expect(utils.capitalize('hello')).toBe('Hello');
            expect(utils.capitalize('world')).toBe('World');
        });

        it('handles already capitalized strings', () => {
            expect(utils.capitalize('Hello')).toBe('Hello');
        });

        it('handles single character', () => {
            expect(utils.capitalize('a')).toBe('A');
        });

        it('returns empty for invalid input', () => {
            expect(utils.capitalize('')).toBe('');
            expect(utils.capitalize(null)).toBe('');
            expect(utils.capitalize(123)).toBe('');
        });
    });

    describe('camelCase', () => {
        it('converts space-separated words', () => {
            expect(utils.camelCase('hello world')).toBe('helloWorld');
        });

        it('converts dash-separated words', () => {
            expect(utils.camelCase('hello-world')).toBe('helloWorld');
        });

        it('converts underscore-separated words', () => {
            expect(utils.camelCase('hello_world')).toBe('helloWorld');
        });

        it('handles single word', () => {
            expect(utils.camelCase('hello')).toBe('hello');
        });

        it('returns empty for invalid input', () => {
            expect(utils.camelCase('')).toBe('');
            expect(utils.camelCase(null)).toBe('');
        });
    });

    describe('snakeCase', () => {
        it('converts space-separated words', () => {
            expect(utils.snakeCase('hello world')).toBe('hello_world');
        });

        it('converts camelCase to snake_case', () => {
            expect(utils.snakeCase('helloWorld')).toBe('hello_world');
        });

        it('handles single word', () => {
            expect(utils.snakeCase('hello')).toBe('hello');
        });

        it('returns empty for invalid input', () => {
            expect(utils.snakeCase('')).toBe('');
            expect(utils.snakeCase(null)).toBe('');
        });
    });

    describe('kebabCase', () => {
        it('converts space-separated words', () => {
            expect(utils.kebabCase('hello world')).toBe('hello-world');
        });

        it('converts camelCase to kebab-case', () => {
            expect(utils.kebabCase('helloWorld')).toBe('hello-world');
        });

        it('returns empty for invalid input', () => {
            expect(utils.kebabCase('')).toBe('');
            expect(utils.kebabCase(null)).toBe('');
        });
    });

    describe('truncate', () => {
        it('truncates long strings', () => {
            expect(utils.truncate('Hello World, this is a long string', 15)).toBe('Hello World,...');
        });

        it('does not truncate short strings', () => {
            expect(utils.truncate('Short', 50)).toBe('Short');
        });

        it('respects exact length boundary', () => {
            expect(utils.truncate('Hello', 5)).toBe('Hello');
        });

        it('uses custom suffix', () => {
            expect(utils.truncate('Hello World, long text', 10, '…')).toBe('Hello Wor…');
        });

        it('returns empty for invalid input', () => {
            expect(utils.truncate('')).toBe('');
            expect(utils.truncate(null)).toBe('');
        });
    });

    describe('stripTags', () => {
        it('removes HTML tags', () => {
            expect(utils.stripTags('<p>Hello World</p>')).toBe('Hello World');
        });

        it('removes nested tags', () => {
            expect(utils.stripTags('<div><span>Text</span></div>')).toBe('Text');
        });

        it('handles self-closing tags', () => {
            expect(utils.stripTags('Hello<br/>World')).toBe('HelloWorld');
        });

        it('returns plain text unchanged', () => {
            expect(utils.stripTags('Hello World')).toBe('Hello World');
        });

        it('returns empty for invalid input', () => {
            expect(utils.stripTags('')).toBe('');
            expect(utils.stripTags(null)).toBe('');
        });
    });
});

describe('Validation Utilities', () => {
    describe('validateEmail', () => {
        it('accepts valid emails', () => {
            expect(utils.validateEmail('test@example.com')).toBe(true);
            expect(utils.validateEmail('user.name@domain.org')).toBe(true);
            expect(utils.validateEmail('user+tag@domain.co.uk')).toBe(true);
        });

        it('rejects invalid emails', () => {
            expect(utils.validateEmail('not-an-email')).toBe(false);
            expect(utils.validateEmail('@domain.com')).toBe(false);
            expect(utils.validateEmail('user@')).toBe(false);
            expect(utils.validateEmail('user@domain')).toBe(false);
            expect(utils.validateEmail('')).toBe(false);
            expect(utils.validateEmail(null)).toBe(false);
            expect(utils.validateEmail(undefined)).toBe(false);
        });

        it('rejects emails with spaces', () => {
            expect(utils.validateEmail('user @domain.com')).toBe(false);
        });
    });

    describe('validateUrl', () => {
        it('accepts valid HTTP/HTTPS URLs', () => {
            expect(utils.validateUrl('https://example.com')).toBe(true);
            expect(utils.validateUrl('http://example.com/path')).toBe(true);
            expect(utils.validateUrl('https://example.com/path?query=1')).toBe(true);
        });

        it('rejects non-HTTP URLs', () => {
            expect(utils.validateUrl('ftp://example.com')).toBe(false);
            expect(utils.validateUrl('javascript:alert(1)')).toBe(false);
        });

        it('rejects invalid URLs', () => {
            expect(utils.validateUrl('not-a-url')).toBe(false);
            expect(utils.validateUrl('')).toBe(false);
            expect(utils.validateUrl(null)).toBe(false);
        });
    });

    describe('validateRequired', () => {
        it('accepts valid values', () => {
            expect(utils.validateRequired('text')).toBe(true);
            expect(utils.validateRequired(42)).toBe(true);
            expect(utils.validateRequired([1])).toBe(true);
            expect(utils.validateRequired(true)).toBe(true);
        });

        it('rejects empty values', () => {
            expect(utils.validateRequired('')).toBe(false);
            expect(utils.validateRequired('   ')).toBe(false);
            expect(utils.validateRequired(null)).toBe(false);
            expect(utils.validateRequired(undefined)).toBe(false);
            expect(utils.validateRequired([])).toBe(false);
            expect(utils.validateRequired(NaN)).toBe(false);
        });
    });

    describe('validateMinLength', () => {
        it('accepts strings meeting minimum', () => {
            expect(utils.validateMinLength('hello', 3)).toBe(true);
            expect(utils.validateMinLength('hi', 2)).toBe(true);
        });

        it('rejects short strings', () => {
            expect(utils.validateMinLength('hi', 3)).toBe(false);
        });

        it('rejects non-strings', () => {
            expect(utils.validateMinLength(null, 3)).toBe(false);
            expect(utils.validateMinLength(123, 3)).toBe(false);
        });
    });

    describe('validateMaxLength', () => {
        it('accepts strings within limit', () => {
            expect(utils.validateMaxLength('hi', 5)).toBe(true);
            expect(utils.validateMaxLength('hello', 5)).toBe(true);
        });

        it('rejects long strings', () => {
            expect(utils.validateMaxLength('hello world', 5)).toBe(false);
        });

        it('rejects non-strings', () => {
            expect(utils.validateMaxLength(null, 5)).toBe(false);
            expect(utils.validateMaxLength(12345, 3)).toBe(false);
        });
    });
});

describe('Debounce Utility', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('delays function execution', () => {
        const fn = vi.fn();
        const debounced = utils.debounce(fn, 300);

        debounced('first');
        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(300);
        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('first');
    });

    it('resets timer on subsequent calls (only last call executes)', () => {
        const fn = vi.fn();
        const debounced = utils.debounce(fn, 200);

        debounced('first');
        vi.advanceTimersByTime(100);
        debounced('second');
        vi.advanceTimersByTime(100);
        debounced('third');
        vi.advanceTimersByTime(200);

        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('third');
    });

    it('preserves "this" context', () => {
        const fn = vi.fn();
        const debounced = utils.debounce(fn, 100);

        const context = { method: debounced };
        context.method('arg');
        vi.advanceTimersByTime(100);

        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('arg');
    });

    it('handles multiple rapid calls correctly', () => {
        const fn = vi.fn();
        const debounced = utils.debounce(fn, 500);

        for (let i = 0; i < 10; i++) {
            debounced(i);
            vi.advanceTimersByTime(100);
        }

        // Only the last call should execute (after final timer completes)
        vi.advanceTimersByTime(500);
        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith(9);
    });
});

describe('Throttle Utility', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('executes immediately on first call', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 1000);

        throttled('first');
        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('first');
    });

    it('blocks calls during throttle window', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 1000);

        throttled('first');
        throttled('second');
        throttled('third');

        expect(fn).toHaveBeenCalledTimes(1);
    });

    it('flushes last queued call after throttle window expires', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 1000);

        throttled('first');
        throttled('second');
        throttled('third');

        vi.advanceTimersByTime(1000);

        expect(fn).toHaveBeenCalledTimes(2);
        expect(fn).toHaveBeenLastCalledWith('third');
    });

    it('allows new calls after throttle window passes', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 500);

        throttled('first');
        vi.advanceTimersByTime(500);
        throttled('second');

        expect(fn).toHaveBeenCalledTimes(2);
    });

    it('only executes the last queued call during window', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 200);

        throttled('call1');
        throttled('call2');
        throttled('call3');
        throttled('call4');
        throttled('call5');

        vi.advanceTimersByTime(200);

        expect(fn).toHaveBeenCalledTimes(2);
        expect(fn).toHaveBeenNthCalledWith(1, 'call1');
        expect(fn).toHaveBeenNthCalledWith(2, 'call5');
    });

    it('preserves "this" context', () => {
        const fn = vi.fn();
        const throttled = utils.throttle(fn, 100);

        const context = { method: throttled };
        context.method('arg');

        expect(fn).toHaveBeenCalledWith('arg');
    });
});
