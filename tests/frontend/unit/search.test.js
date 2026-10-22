/**
 * Unit tests for search utility functions:
 * query validation, result grouping, type labels, URL generation
 */
import { describe, it, expect, vi } from 'vitest'

// ---- Utility implementations under test ----

const searchUtils = {
    validateQuery(query) {
        if (!query || typeof query !== 'string') {
            return { valid: false, error: 'Query is required' };
        }
        const trimmed = query.trim();
        if (trimmed.length < 2) {
            return { valid: false, error: 'Query must be at least 2 characters' };
        }
        if (trimmed.length > 255) {
            return { valid: false, error: 'Query must be less than 255 characters' };
        }
        return { valid: true, query: trimmed };
    },

    sanitizeQuery(query) {
        if (!query) return '';
        return query.trim().replace(/[^\w\s@.-]/g, '');
    },

    groupResults(results) {
        const grouped = {};
        for (const [type, items] of Object.entries(results)) {
            if (items.length > 0) {
                grouped[type] = items;
            }
        }
        return grouped;
    },

    flattenResults(results) {
        const flat = [];
        for (const [group, items] of Object.entries(results)) {
            if (items.length === 0) continue;
            flat.push({ type: 'header', group, isHeader: true });
            items.forEach(item => flat.push({ ...item, group, isHeader: false }));
        }
        return flat;
    },

    getTypeLabel(type) {
        const labels = {
            all: 'All Results',
            posts: 'Posts',
            campaigns: 'Campaigns',
            clients: 'Clients',
            content: 'Content',
            analytics: 'Analytics',
        };
        return labels[type] || 'Unknown';
    },

    getResultIcon(type) {
        const icons = {
            post: 'fas fa-pen-nib',
            campaign: 'fas fa-bullhorn',
            client: 'fas fa-users',
            content: 'fas fa-folder-open',
            content_template: 'fas fa-copy',
            analytics: 'fas fa-chart-line',
            invoice: 'fas fa-file-invoice',
        };
        return icons[type] || 'fas fa-search';
    },

    buildSearchUrl(baseUrl, params) {
        const url = new URL(baseUrl, 'http://localhost');
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, String(value));
            }
        });
        return url.toString();
    },

    highlightMatch(text, query) {
        if (!query || !text) return text;
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escaped})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    },

    debounce(fn, delay = 300) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    getEmptyStates(type) {
        const states = {
            all: 'Try searching for posts, campaigns, clients, or content',
            posts: 'No posts found. Try a different keyword.',
            campaigns: 'No campaigns found. Try a different keyword.',
            clients: 'No clients found. Try a different keyword.',
            content: 'No content found. Try a different keyword.',
            analytics: 'No analytics data found. Try a different keyword.',
        };
        return states[type] || states.all;
    },

    formatResultsCount(count) {
        if (count === 0) return 'No results';
        if (count === 1) return '1 result';
        if (count > 999) return '999+ results';
        return `${count} results`;
    },
};

// ---- Tests ----

describe('Search Query Validation', () => {
    it('accepts valid queries', () => {
        const result = searchUtils.validateQuery('test query');
        expect(result.valid).toBe(true);
        expect(result.query).toBe('test query');
    });

    it('rejects empty queries', () => {
        expect(searchUtils.validateQuery('').valid).toBe(false);
        expect(searchUtils.validateQuery(null).valid).toBe(false);
        expect(searchUtils.validateQuery(undefined).valid).toBe(false);
    });

    it('rejects queries shorter than 2 chars', () => {
        const result = searchUtils.validateQuery('a');
        expect(result.valid).toBe(false);
        expect(result.error).toContain('at least 2 characters');
    });

    it('rejects queries longer than 255 chars', () => {
        const result = searchUtils.validateQuery('x'.repeat(256));
        expect(result.valid).toBe(false);
        expect(result.error).toContain('less than 255');
    });

    it('trims whitespace from queries', () => {
        const result = searchUtils.validateQuery('  hello world  ');
        expect(result.valid).toBe(true);
        expect(result.query).toBe('hello world');
    });
});

describe('Query Sanitization', () => {
    it('trims whitespace', () => {
        expect(searchUtils.sanitizeQuery('  hello  ')).toBe('hello');
    });

    it('handles null/undefined', () => {
        expect(searchUtils.sanitizeQuery(null)).toBe('');
        expect(searchUtils.sanitizeQuery(undefined)).toBe('');
    });

    it('preserves safe characters', () => {
        expect(searchUtils.sanitizeQuery('test@example.com')).toBe('test@example.com');
        expect(searchUtils.sanitizeQuery('hello-world')).toBe('hello-world');
    });
});

describe('Result Grouping', () => {
    it('groups non-empty results', () => {
        const input = {
            posts: [{ id: 1, title: 'Post' }],
            campaigns: [],
            clients: [{ id: 2, title: 'Client' }],
        };
        const result = searchUtils.groupResults(input);
        expect(Object.keys(result)).toEqual(['posts', 'clients']);
    });

    it('flattens results into selectable items', () => {
        const input = {
            posts: [{ id: 1, title: 'Post 1' }],
            campaigns: [{ id: 2, title: 'Campaign 1' }],
        };
        const flat = searchUtils.flattenResults(input);
        expect(flat.length).toBe(4); // 2 headers + 2 items
        expect(flat[0].isHeader).toBe(true);
        expect(flat[1].isHeader).toBe(false);
    });

    it('skips empty groups when flattening', () => {
        const input = {
            posts: [{ id: 1 }],
            campaigns: [],
        };
        const flat = searchUtils.flattenResults(input);
        expect(flat.length).toBe(2); // 1 header + 1 item
    });
});

describe('Type Labels', () => {
    it('returns correct labels', () => {
        expect(searchUtils.getTypeLabel('all')).toBe('All Results');
        expect(searchUtils.getTypeLabel('posts')).toBe('Posts');
        expect(searchUtils.getTypeLabel('campaigns')).toBe('Campaigns');
        expect(searchUtils.getTypeLabel('clients')).toBe('Clients');
        expect(searchUtils.getTypeLabel('content')).toBe('Content');
        expect(searchUtils.getTypeLabel('analytics')).toBe('Analytics');
    });

    it('returns Unknown for unrecognized types', () => {
        expect(searchUtils.getTypeLabel('unknown')).toBe('Unknown');
    });
});

describe('Result Icons', () => {
    it('returns correct icons', () => {
        expect(searchUtils.getResultIcon('post')).toBe('fas fa-pen-nib');
        expect(searchUtils.getResultIcon('campaign')).toBe('fas fa-bullhorn');
        expect(searchUtils.getResultIcon('client')).toBe('fas fa-users');
        expect(searchUtils.getResultIcon('content')).toBe('fas fa-folder-open');
    });

    it('returns default icon for unknown type', () => {
        expect(searchUtils.getResultIcon('unknown')).toBe('fas fa-search');
    });
});

describe('URL Building', () => {
    it('builds search URL with params', () => {
        const url = searchUtils.buildSearchUrl('/search', { q: 'test', type: 'posts' });
        expect(url).toContain('q=test');
        expect(url).toContain('type=posts');
    });

    it('omits empty params', () => {
        const url = searchUtils.buildSearchUrl('/search', { q: 'test', type: '' });
        expect(url).toContain('q=test');
        expect(url).not.toContain('type');
    });
});

describe('Text Highlighting', () => {
    it('wraps matches in mark tags', () => {
        const result = searchUtils.highlightMatch('hello world', 'world');
        expect(result).toBe('hello <mark>world</mark>');
    });

    it('handles case-insensitive matching', () => {
        const result = searchUtils.highlightMatch('Hello World', 'hello');
        expect(result).toBe('<mark>Hello</mark> World');
    });

    it('handles no match', () => {
        const result = searchUtils.highlightMatch('hello world', 'xyz');
        expect(result).toBe('hello world');
    });

    it('escapes special regex characters', () => {
        const result = searchUtils.highlightMatch('test (1)', '(1)');
        expect(result).toBe('test <mark>(1)</mark>');
    });
});

describe('Results Count Formatting', () => {
    it('formats zero results', () => {
        expect(searchUtils.formatResultsCount(0)).toBe('No results');
    });

    it('formats single result', () => {
        expect(searchUtils.formatResultsCount(1)).toBe('1 result');
    });

    it('formats multiple results', () => {
        expect(searchUtils.formatResultsCount(5)).toBe('5 results');
    });

    it('caps at 999+', () => {
        expect(searchUtils.formatResultsCount(1000)).toBe('999+ results');
    });
});

describe('Empty States', () => {
    it('returns type-specific messages', () => {
        expect(searchUtils.getEmptyStates('posts')).toContain('No posts');
        expect(searchUtils.getEmptyStates('campaigns')).toContain('No campaigns');
    });

    it('falls back to default message', () => {
        expect(searchUtils.getEmptyStates('unknown')).toBe(searchUtils.getEmptyStates('all'));
    });
});

describe('Debounce', () => {
    it('debounces function calls', () => {
        vi.useFakeTimers();
        const fn = vi.fn();
        const debounced = searchUtils.debounce(fn, 300);

        debounced('a');
        debounced('b');
        debounced('c');

        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(300);

        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('c');

        vi.useRealTimers();
    });
});
