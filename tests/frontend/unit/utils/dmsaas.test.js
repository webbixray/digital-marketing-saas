/**
 * Tests for window.dmsaas utility functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// Mock the dmsaas utilities (extracted from unified.js for testing)
const dmsaas = {
    toast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${this.toastClass(type)}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    toastClass(type) {
        const classes = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500',
        };
        return classes[type] || classes.info;
    },

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    },

    formatDate(date) {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(date));
    },

    timeAgo(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 },
        ];
        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count >= 1) {
                return `${count} ${interval.label}${count > 1 ? 's' : ''} ago`;
            }
        }
        return 'just now';
    },

    confirm(message) {
        return confirm(message || 'Are you sure?');
    },

    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            return true;
        }
    },

    setLoading(btn, loading = true) {
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-1">⟳</span> Loading...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        }
    },

    filterTable(searchInput, tableSelector) {
        const value = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll(`${tableSelector} tbody tr`);
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    },

    initCharCounter(inputSelector, counterSelector, maxLength = null) {
        const input = document.querySelector(inputSelector);
        const counter = document.querySelector(counterSelector);
        if (!input || !counter) return;

        const update = () => {
            const len = input.value.length;
            counter.textContent = maxLength ? `${len}/${maxLength}` : `${len}`;
        };
        input.addEventListener('input', update);
        update();
    },
};

describe('dmsaas utilities', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    describe('toast', () => {
        it('creates a toast element with message', () => {
            dmsaas.toast('Hello World', 'success');
            const toast = document.querySelector('.fixed.top-4.right-4');
            expect(toast).toBeTruthy();
            expect(toast.textContent).toBe('Hello World');
        });

        it('applies correct class for success type', () => {
            dmsaas.toast('Success!', 'success');
            const toast = document.querySelector('.fixed.top-4.right-4');
            expect(toast.className).toContain('bg-green-500');
        });

        it('applies correct class for error type', () => {
            dmsaas.toast('Error!', 'error');
            const toast = document.querySelector('.fixed.top-4.right-4');
            expect(toast.className).toContain('bg-red-500');
        });

        it('defaults to success type', () => {
            dmsaas.toast('Default');
            const toast = document.querySelector('.fixed.top-4.right-4');
            expect(toast.className).toContain('bg-green-500');
        });
    });

    describe('formatNumber', () => {
        it('formats number with commas', () => {
            expect(dmsaas.formatNumber(1000)).toBe('1,000');
            expect(dmsaas.formatNumber(1000000)).toBe('1,000,000');
            expect(dmsaas.formatNumber(1234567)).toBe('1,234,567');
        });

        it('handles zero', () => {
            expect(dmsaas.formatNumber(0)).toBe('0');
        });
    });

    describe('formatDate', () => {
        it('formats date correctly', () => {
            const date = new Date('2024-01-15');
            const formatted = dmsaas.formatDate(date);
            expect(formatted).toContain('Jan');
            expect(formatted).toContain('15');
            expect(formatted).toContain('2024');
        });
    });

    describe('timeAgo', () => {
        it('returns "just now" for current time', () => {
            expect(dmsaas.timeAgo(new Date())).toBe('just now');
        });

        it('returns minutes ago', () => {
            const fiveMinAgo = new Date(Date.now() - 5 * 60 * 1000);
            expect(dmsaas.timeAgo(fiveMinAgo)).toBe('5 minutes ago');
        });

        it('returns hours ago', () => {
            const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
            expect(dmsaas.timeAgo(twoHoursAgo)).toBe('2 hours ago');
        });

        it('returns days ago', () => {
            const threeDaysAgo = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000);
            expect(dmsaas.timeAgo(threeDaysAgo)).toBe('3 days ago');
        });
    });

    describe('setLoading', () => {
        it('sets loading state on button', () => {
            const btn = document.createElement('button');
            btn.textContent = 'Submit';
            document.body.appendChild(btn);

            dmsaas.setLoading(btn, true);
            expect(btn.disabled).toBe(true);
            expect(btn.innerHTML).toContain('Loading...');
            expect(btn.dataset.originalText).toBe('Submit');
        });

        it('removes loading state', () => {
            const btn = document.createElement('button');
            btn.textContent = 'Submit';
            document.body.appendChild(btn);

            dmsaas.setLoading(btn, true);
            dmsaas.setLoading(btn, false);
            expect(btn.disabled).toBe(false);
            expect(btn.innerHTML).toBe('Submit');
        });
    });

    describe('filterTable', () => {
        it('filters table rows by search input', () => {
            document.body.innerHTML = `
                <input type="text" id="search" value="test">
                <table id="my-table">
                    <tbody>
                        <tr><td>Test Row 1</td></tr>
                        <tr><td>Another Row</td></tr>
                        <tr><td>Test Row 2</td></tr>
                    </tbody>
                </table>
            `;

            const searchInput = document.querySelector('#search');
            dmsaas.filterTable(searchInput, '#my-table');

            const rows = document.querySelectorAll('#my-table tbody tr');
            expect(rows[0].style.display).toBe('');
            expect(rows[1].style.display).toBe('none');
            expect(rows[2].style.display).toBe('');
        });
    });

    describe('initCharCounter', () => {
        it('initializes character counter', () => {
            document.body.innerHTML = `
                <textarea id="content">Hello</textarea>
                <span id="counter"></span>
            `;

            dmsaas.initCharCounter('#content', '#counter', 100);
            const counter = document.querySelector('#counter');
            expect(counter.textContent).toBe('5/100');
        });

        it('updates counter on input', () => {
            document.body.innerHTML = `
                <textarea id="content">Hello</textarea>
                <span id="counter"></span>
            `;

            dmsaas.initCharCounter('#content', '#counter');
            const input = document.querySelector('#content');
            const counter = document.querySelector('#counter');

            input.value = 'Hello World';
            input.dispatchEvent(new Event('input'));
            expect(counter.textContent).toBe('11');
        });
    });
});
