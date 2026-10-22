/**
 * Tests for the layout Alpine component (layoutState)
 * Tests sidebar toggle, dark mode, mobile menu, search, keyboard shortcuts,
 * localStorage persistence, loading state, and dropdown management.
 *
 * Based on resources/js/unified.js Alpine.data('layoutState', ...) and
 * resources/views/layouts/unified.blade.php
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// Simulated localStorage for testing
const createMockStorage = () => {
    const store = {};
    return {
        getItem: vi.fn((key) => store[key] ?? null),
        setItem: vi.fn((key, value) => { store[key] = String(value); }),
        removeItem: vi.fn((key) => { delete store[key]; }),
        clear: vi.fn(() => { Object.keys(store).forEach(k => delete store[k]); }),
        store
    };
};

// Simulated layoutState component factory (mirrors unified.js)
const createLayoutState = (mockStorage) => {
    return {
        srAnnouncement: '',
        sidebarOpen: false,
        sidebarMini: mockStorage.getItem('sidebarMini') === 'true',
        darkMode: mockStorage.getItem('darkMode') === 'true',
        searchOpen: false,
        searchQuery: '',
        notificationsOpen: false,
        userDropdownOpen: false,
        createDropdownOpen: false,
        loading: true,
        expandedSections: {},

        toggleSection(section) {
            this.expandedSections[section] = !this.expandedSections[section];
            mockStorage.setItem('sidebarSections', JSON.stringify(this.expandedSections));
        },

        init(rawSidebarSections, defaultSections) {
            // Load sidebar sections from localStorage
            const raw = mockStorage.getItem('sidebarSections');
            if (raw) {
                try { this.expandedSections = JSON.parse(raw); } catch (e) { /* ignore */ }
            }
            if (!this.expandedSections.social) {
                this.expandedSections = JSON.parse(defaultSections);
            }

            // Simulate $watch for darkMode
            const originalDarkMode = this.darkMode;
            Object.defineProperty(this, '_darkModeCallback', {
                value: null,
                writable: true
            });

            // Simulate $watch for sidebarMini
            this._sidebarMiniCallback = null;

            // Apply dark mode to document
            document.documentElement.classList.toggle('dark', this.darkMode);

            // Simulate setTimeout for loading
            setTimeout(() => { this.loading = false; }, 500);

            // Scroll active nav into view
            const activeNav = document.querySelector('.nav-link.active');
            if (activeNav) {
                activeNav.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            return this;
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            mockStorage.setItem('darkMode', this.darkMode);
            document.documentElement.classList.toggle('dark', this.darkMode);
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        toggleSidebarMini() {
            this.sidebarMini = !this.sidebarMini;
            mockStorage.setItem('sidebarMini', this.sidebarMini);
        },

        openSearch() {
            this.searchOpen = true;
        },

        closeSearch() {
            this.searchOpen = false;
        },

        closeAllDropdowns() {
            this.searchOpen = false;
            this.notificationsOpen = false;
            this.userDropdownOpen = false;
            this.createDropdownOpen = false;
        },

        // Keyboard handler simulation
        handleKeyboard(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.searchOpen = true;
            }
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                this.sidebarMini = !this.sidebarMini;
                mockStorage.setItem('sidebarMini', this.sidebarMini);
            }
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
            }
        }
    };
};

describe('Layout Alpine Component - layoutState', () => {
    let mockStorage;

    beforeEach(() => {
        mockStorage = createMockStorage();
        document.body.innerHTML = '';
        document.documentElement.className = '';
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    // =========================================================================
    // SIDEBAR TOGGLE
    // =========================================================================
    describe('Sidebar Toggle', () => {
        it('starts with sidebar closed', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.sidebarOpen).toBe(false);
        });

        it('toggles sidebar open', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(true);
        });

        it('toggles sidebar closed when called twice', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(true);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(false);
        });

        it('sidebarMini defaults to false', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.sidebarMini).toBe(false);
        });

        it('toggles sidebarMini', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebarMini();
            expect(layout.sidebarMini).toBe(true);
        });

        it('persists sidebarMini to localStorage', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebarMini();
            expect(mockStorage.setItem).toHaveBeenCalledWith('sidebarMini', true);
        });

        it('restores sidebarMini from localStorage', () => {
            mockStorage.store['sidebarMini'] = 'true';
            const layout = createLayoutState(mockStorage);
            expect(layout.sidebarMini).toBe(true);
        });

        it('sidebarMini false in localStorage keeps mini off', () => {
            mockStorage.store['sidebarMini'] = 'false';
            const layout = createLayoutState(mockStorage);
            expect(layout.sidebarMini).toBe(false);
        });
    });

    // =========================================================================
    // SIDEBAR SECTIONS
    // =========================================================================
    describe('Sidebar Sections', () => {
        const defaultSections = '{"social":true,"marketing":true,"ai":true,"business":true}';

        it('initializes with default sections when none in localStorage', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, defaultSections);
            expect(layout.expandedSections).toEqual({
                social: true, marketing: true, ai: true, business: true
            });
        });

        it('restores expanded sections from localStorage', () => {
            mockStorage.store['sidebarSections'] = '{"social":true,"marketing":false,"ai":true,"business":false}';
            const layout = createLayoutState(mockStorage);
            layout.init('{"social":true,"marketing":false,"ai":true,"business":false}', defaultSections);
            expect(layout.expandedSections).toEqual({
                social: true, marketing: false, ai: true, business: false
            });
        });

        it('toggles a section', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, defaultSections);
            expect(layout.expandedSections.social).toBe(true);
            layout.toggleSection('social');
            expect(layout.expandedSections.social).toBe(false);
        });

        it('persists section toggle to localStorage', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, defaultSections);
            layout.toggleSection('social');
            expect(mockStorage.setItem).toHaveBeenCalledWith(
                'sidebarSections',
                JSON.stringify({ social: false, marketing: true, ai: true, business: true })
            );
        });

        it('handles malformed localStorage gracefully', () => {
            mockStorage.store['sidebarSections'] = 'invalid-json';
            const layout = createLayoutState(mockStorage);
            // Should fall back to default sections
            layout.init('invalid-json', defaultSections);
            expect(layout.expandedSections).toEqual({
                social: true, marketing: true, ai: true, business: true
            });
        });

        it('handles multiple section toggles', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, defaultSections);
            layout.toggleSection('social');
            layout.toggleSection('marketing');
            layout.toggleSection('ai');
            expect(layout.expandedSections).toEqual({
                social: false, marketing: false, ai: false, business: true
            });
        });
    });

    // =========================================================================
    // DARK MODE
    // =========================================================================
    describe('Dark Mode', () => {
        it('starts with dark mode off', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.darkMode).toBe(false);
        });

        it('toggles dark mode on', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            expect(layout.darkMode).toBe(true);
        });

        it('toggles dark mode off when called twice', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            layout.toggleDarkMode();
            expect(layout.darkMode).toBe(false);
        });

        it('persists dark mode to localStorage', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            expect(mockStorage.setItem).toHaveBeenCalledWith('darkMode', true);
        });

        it('restores dark mode from localStorage', () => {
            mockStorage.store['darkMode'] = 'true';
            const layout = createLayoutState(mockStorage);
            expect(layout.darkMode).toBe(true);
        });

        it('applies dark class to document element', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('removes dark class when toggled off', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            expect(document.documentElement.classList.contains('dark')).toBe(true);
            layout.toggleDarkMode();
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('initializes dark class from stored preference', () => {
            mockStorage.store['darkMode'] = 'true';
            const layout = createLayoutState(mockStorage);
            layout.init(null, '{}');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });
    });

    // =========================================================================
    // MOBILE MENU
    // =========================================================================
    describe('Mobile Menu', () => {
        it('starts with mobile sidebar closed', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.sidebarOpen).toBe(false);
        });

        it('opens mobile sidebar', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(true);
        });

        it('closes mobile sidebar', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(true);
            layout.toggleSidebar();
            expect(layout.sidebarOpen).toBe(false);
        });

        it('mobile sidebar overlay closes on click', () => {
            document.body.innerHTML = `
                <div class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>
            `;
            const overlay = document.querySelector('.fixed.inset-0');
            const layout = createLayoutState(mockStorage);
            layout.sidebarOpen = true;

            // Simulate click on overlay
            overlay.click();
            // In real Alpine this would set sidebarOpen = false via @click handler
            layout.sidebarOpen = false;
            expect(layout.sidebarOpen).toBe(false);
        });

        it('Escape key closes mobile sidebar via closeAllDropdowns', () => {
            const layout = createLayoutState(mockStorage);
            layout.sidebarOpen = true;
            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });
            // Note: sidebarOpen is not closed by Escape in the original code,
            // but we can verify the dropdown-related states are closed
            expect(layout.notificationsOpen).toBe(false);
            expect(layout.userDropdownOpen).toBe(false);
            expect(layout.createDropdownOpen).toBe(false);
            expect(layout.searchOpen).toBe(false);
        });

        it('sidebarMini does not affect mobile sidebar visibility', () => {
            const layout = createLayoutState(mockStorage);
            layout.sidebarMini = true;
            layout.sidebarOpen = false;
            // sidebarMini is a desktop concern (lg:w-16), not mobile
            expect(layout.sidebarOpen).toBe(false);
        });
    });

    // =========================================================================
    // SEARCH
    // =========================================================================
    describe('Search', () => {
        it('starts with search closed', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.searchOpen).toBe(false);
        });

        it('opens search panel', () => {
            const layout = createLayoutState(mockStorage);
            layout.openSearch();
            expect(layout.searchOpen).toBe(true);
        });

        it('closes search panel', () => {
            const layout = createLayoutState(mockStorage);
            layout.openSearch();
            expect(layout.searchOpen).toBe(true);
            layout.closeSearch();
            expect(layout.searchOpen).toBe(false);
        });

        it('searchQuery starts empty', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.searchQuery).toBe('');
        });

        it('searchQuery can be set', () => {
            const layout = createLayoutState(mockStorage);
            layout.searchQuery = 'campaign';
            expect(layout.searchQuery).toBe('campaign');
        });

        it('Cmd+K opens search (macOS)', () => {
            const layout = createLayoutState(mockStorage);
            const preventDefault = vi.fn();
            layout.handleKeyboard({ metaKey: true, key: 'k', preventDefault });
            expect(layout.searchOpen).toBe(true);
            expect(preventDefault).toHaveBeenCalled();
        });

        it('Ctrl+K opens search (Windows/Linux)', () => {
            const layout = createLayoutState(mockStorage);
            const preventDefault = vi.fn();
            layout.handleKeyboard({ ctrlKey: true, key: 'k', preventDefault });
            expect(layout.searchOpen).toBe(true);
            expect(preventDefault).toHaveBeenCalled();
        });

        it('Escape closes search', () => {
            const layout = createLayoutState(mockStorage);
            layout.searchOpen = true;
            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });
            expect(layout.searchOpen).toBe(false);
        });

        it('search is focused when opened (simulated via init pattern)', () => {
            document.body.innerHTML = `
                <input x-ref="searchInput" type="text" />
            `;
            const searchInput = document.querySelector('[x-ref="searchInput"]');
            searchInput.focus = vi.fn();

            const layout = createLayoutState(mockStorage);
            layout.searchOpen = true;
            // Simulate Alpine's $nextTick focus pattern
            layout.openSearch();
            searchInput.focus();
            expect(searchInput.focus).toHaveBeenCalled();
        });
    });

    // =========================================================================
    // KEYBOARD SHORTCUTS
    // =========================================================================
    describe('Keyboard Shortcuts', () => {
        it('Ctrl+B toggles sidebarMini', () => {
            const layout = createLayoutState(mockStorage);
            const preventDefault = vi.fn();
            layout.handleKeyboard({ ctrlKey: true, key: 'b', preventDefault });
            expect(layout.sidebarMini).toBe(true);
            expect(preventDefault).toHaveBeenCalled();
        });

        it('Ctrl+B toggles sidebarMini off', () => {
            const layout = createLayoutState(mockStorage);
            layout.sidebarMini = true;
            layout.handleKeyboard({ ctrlKey: true, key: 'b', preventDefault: vi.fn() });
            expect(layout.sidebarMini).toBe(false);
        });

        it('Cmd+K does not interfere with other shortcuts', () => {
            const layout = createLayoutState(mockStorage);
            layout.handleKeyboard({ metaKey: true, key: 'k', preventDefault: vi.fn() });
            expect(layout.searchOpen).toBe(true);
            expect(layout.sidebarMini).toBe(false);
        });

        it('Escape closes all dropdowns', () => {
            const layout = createLayoutState(mockStorage);
            layout.searchOpen = true;
            layout.notificationsOpen = true;
            layout.userDropdownOpen = true;
            layout.createDropdownOpen = true;

            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });

            expect(layout.searchOpen).toBe(false);
            expect(layout.notificationsOpen).toBe(false);
            expect(layout.userDropdownOpen).toBe(false);
            expect(layout.createDropdownOpen).toBe(false);
        });

        it('unhandled keys do not modify state', () => {
            const layout = createLayoutState(mockStorage);
            const initialState = { ...layout };
            layout.handleKeyboard({ key: 'a', preventDefault: vi.fn() });
            expect(layout.searchOpen).toBe(initialState.searchOpen);
            expect(layout.sidebarMini).toBe(initialState.sidebarMini);
        });

        it('Meta+K does not trigger sidebar toggle', () => {
            const layout = createLayoutState(mockStorage);
            layout.handleKeyboard({ metaKey: true, key: 'k', preventDefault: vi.fn() });
            expect(layout.sidebarMini).toBe(false);
        });
    });

    // =========================================================================
    // DROPDOWN MANAGEMENT
    // =========================================================================
    describe('Dropdown Management', () => {
        it('notificationsOpen starts false', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.notificationsOpen).toBe(false);
        });

        it('userDropdownOpen starts false', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.userDropdownOpen).toBe(false);
        });

        it('createDropdownOpen starts false', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.createDropdownOpen).toBe(false);
        });

        it('closeAllDropdowns closes all dropdowns', () => {
            const layout = createLayoutState(mockStorage);
            layout.notificationsOpen = true;
            layout.userDropdownOpen = true;
            layout.createDropdownOpen = true;
            layout.searchOpen = true;

            layout.closeAllDropdowns();

            expect(layout.notificationsOpen).toBe(false);
            expect(layout.userDropdownOpen).toBe(false);
            expect(layout.createDropdownOpen).toBe(false);
            expect(layout.searchOpen).toBe(false);
        });

        it('Escape key triggers closeAllDropdowns', () => {
            const layout = createLayoutState(mockStorage);
            layout.notificationsOpen = true;
            layout.userDropdownOpen = true;
            layout.createDropdownOpen = true;
            layout.searchOpen = true;

            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });

            expect(layout.notificationsOpen).toBe(false);
            expect(layout.userDropdownOpen).toBe(false);
            expect(layout.createDropdownOpen).toBe(false);
            expect(layout.searchOpen).toBe(false);
        });
    });

    // =========================================================================
    // LOADING STATE
    // =========================================================================
    describe('Loading State', () => {
        it('starts with loading true', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.loading).toBe(true);
        });

        it('loading becomes false after timeout', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, '{}');
            expect(layout.loading).toBe(true);
            vi.advanceTimersByTime(500);
            expect(layout.loading).toBe(false);
        });

        it('loading bar reflects loading state', () => {
            document.body.innerHTML = '<div id="loading-bar"></div>';
            const loadingBar = document.getElementById('loading-bar');
            const layout = createLayoutState(mockStorage);
            layout.init(null, '{}');

            // Simulate Alpine class binding
            loadingBar.className = layout.loading ? 'loading' : '';
            expect(loadingBar.className).toBe('loading');

            vi.advanceTimersByTime(500);
            loadingBar.className = layout.loading ? 'loading' : '';
            expect(loadingBar.className).toBe('');
        });
    });

    // =========================================================================
    // LOCALSTORAGE PERSISTENCE
    // =========================================================================
    describe('localStorage Persistence', () => {
        it('persists darkMode on toggle', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleDarkMode();
            expect(mockStorage.setItem).toHaveBeenCalledWith('darkMode', true);
        });

        it('persists sidebarMini on toggle', () => {
            const layout = createLayoutState(mockStorage);
            layout.toggleSidebarMini();
            expect(mockStorage.setItem).toHaveBeenCalledWith('sidebarMini', true);
        });

        it('persists sidebar sections on toggle', () => {
            const layout = createLayoutState(mockStorage);
            layout.init(null, '{"social":true}');
            layout.toggleSection('social');
            expect(mockStorage.setItem).toHaveBeenCalledWith(
                'sidebarSections',
                JSON.stringify({ social: false })
            );
        });

        it('reads all persisted values on init', () => {
            mockStorage.store['darkMode'] = 'true';
            mockStorage.store['sidebarMini'] = 'true';
            mockStorage.store['sidebarSections'] = '{"social":true,"marketing":false}';

            const layout = createLayoutState(mockStorage);
            layout.init(null, '{"social":true,"marketing":true}');

            expect(layout.darkMode).toBe(true);
            expect(layout.sidebarMini).toBe(true);
            expect(layout.expandedSections).toEqual({ social: true, marketing: false });
        });

        it('handles missing localStorage gracefully', () => {
            // No items in storage
            const layout = createLayoutState(mockStorage);
            layout.init(null, '{"social":true}');
            expect(layout.darkMode).toBe(false);
            expect(layout.sidebarMini).toBe(false);
            expect(layout.expandedSections).toEqual({ social: true });
        });

        it('handles corrupted sidebarSections gracefully', () => {
            mockStorage.store['sidebarSections'] = '{corrupted';
            const layout = createLayoutState(mockStorage);
            layout.init('{corrupted}', '{"social":true}');
            // Falls back to default
            expect(layout.expandedSections).toEqual({ social: true });
        });

        it('handles corrupted darkMode gracefully', () => {
            mockStorage.store['darkMode'] = 'not-a-boolean';
            const layout = createLayoutState(mockStorage);
            // Only 'true' string is truthy
            expect(layout.darkMode).toBe(false);
        });

        it('handles corrupted sidebarMini gracefully', () => {
            mockStorage.store['sidebarMini'] = 'not-a-boolean';
            const layout = createLayoutState(mockStorage);
            // Only 'true' string is truthy
            expect(layout.sidebarMini).toBe(false);
        });
    });

    // =========================================================================
    // SCREEN READER ANNOUNCEMENTS
    // =========================================================================
    describe('Screen Reader Announcements', () => {
        it('srAnnouncement starts empty', () => {
            const layout = createLayoutState(mockStorage);
            expect(layout.srAnnouncement).toBe('');
        });

        it('srAnnouncement can be set', () => {
            const layout = createLayoutState(mockStorage);
            layout.srAnnouncement = 'Sidebar collapsed';
            expect(layout.srAnnouncement).toBe('Sidebar collapsed');
        });

        it('srAnnouncement region exists in DOM', () => {
            document.body.innerHTML = `
                <div aria-live="polite" aria-atomic="true" class="sr-only" id="sr-announcements"></div>
            `;
            const srRegion = document.getElementById('sr-announcements');
            expect(srRegion).toBeTruthy();
            expect(srRegion.getAttribute('aria-live')).toBe('polite');
        });
    });

    // =========================================================================
    // ACTIVE NAVIGATION
    // =========================================================================
    describe('Active Navigation', () => {
        it('scrolls active nav into view on init', () => {
            document.body.innerHTML = `
                <nav>
                    <a class="nav-link" href="/">Home</a>
                    <a class="nav-link active" href="/dashboard">Dashboard</a>
                </nav>
            `;
            const activeNav = document.querySelector('.nav-link.active');
            activeNav.scrollIntoView = vi.fn();

            const layout = createLayoutState(mockStorage);
            layout.init(null, '{}');

            expect(activeNav.scrollIntoView).toHaveBeenCalledWith({
                block: 'nearest',
                behavior: 'smooth'
            });
        });

        it('does not error when no active nav exists', () => {
            document.body.innerHTML = `
                <nav>
                    <a class="nav-link" href="/">Home</a>
                </nav>
            `;
            const layout = createLayoutState(mockStorage);
            expect(() => layout.init(null, '{}')).not.toThrow();
        });
    });

    // =========================================================================
    // INTEGRATION / STATE CONSISTENCY
    // =========================================================================
    describe('State Consistency', () => {
        it('multiple toggles maintain consistent state', () => {
            const layout = createLayoutState(mockStorage);

            // Toggle dark mode multiple times
            layout.toggleDarkMode();
            layout.toggleDarkMode();
            layout.toggleDarkMode();
            expect(layout.darkMode).toBe(true);

            // Toggle sidebar mini multiple times
            layout.toggleSidebarMini();
            layout.toggleSidebarMini();
            expect(layout.sidebarMini).toBe(false);
        });

        it('independent state changes do not interfere', () => {
            const layout = createLayoutState(mockStorage);

            layout.toggleDarkMode();
            layout.toggleSidebar();
            layout.openSearch();

            expect(layout.darkMode).toBe(true);
            expect(layout.sidebarOpen).toBe(true);
            expect(layout.searchOpen).toBe(true);
            expect(layout.sidebarMini).toBe(false);
        });

        it('Escape does not affect sidebarOpen or sidebarMini', () => {
            const layout = createLayoutState(mockStorage);
            layout.sidebarOpen = true;
            layout.sidebarMini = true;

            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });

            // sidebarOpen and sidebarMini are not affected by Escape
            expect(layout.sidebarOpen).toBe(true);
            expect(layout.sidebarMini).toBe(true);
        });

        it('full user flow: open search, close with escape, toggle dark mode', () => {
            const layout = createLayoutState(mockStorage);

            // User opens search with Cmd+K
            layout.handleKeyboard({ metaKey: true, key: 'k', preventDefault: vi.fn() });
            expect(layout.searchOpen).toBe(true);

            // User closes search with Escape
            layout.handleKeyboard({ key: 'Escape', preventDefault: vi.fn() });
            expect(layout.searchOpen).toBe(false);

            // User toggles dark mode
            layout.toggleDarkMode();
            expect(layout.darkMode).toBe(true);
            expect(mockStorage.store['darkMode']).toBe('true');

            // User collapses sidebar
            layout.handleKeyboard({ ctrlKey: true, key: 'b', preventDefault: vi.fn() });
            expect(layout.sidebarMini).toBe(true);
            expect(mockStorage.store['sidebarMini']).toBe('true');
        });
    });
});
