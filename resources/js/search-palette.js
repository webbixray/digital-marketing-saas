/**
 * Global Search & Command Palette (v7.0)
 * Alpine.js component for Cmd+K command palette
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('commandPalette', () => ({
        open: false,
        query: '',
        selectedIndex: 0,
        results: [],
        loading: false,
        recent: [],
        selectedGroup: null,

        groups: {
            posts: { label: 'Posts', icon: 'fas fa-pen-nib' },
            campaigns: { label: 'Campaigns', icon: 'fas fa-bullhorn' },
            clients: { label: 'Clients', icon: 'fas fa-users' },
            content: { label: 'Content', icon: 'fas fa-folder-open' },
            analytics: { label: 'Analytics', icon: 'fas fa-chart-line' },
        },

        init() {
            // Fetch recent searches — URL provided by the layout via data attribute
            // (this is a plain JS file; Blade directives never compile here).
            const recentUrl = document.body.dataset.searchRecentUrl
                || document.querySelector('meta[name="search-recent-url"]')?.content;
            if (recentUrl) {
                fetch(recentUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => { this.recent = data.recent || []; })
                    .catch(() => {});
            }

            // Global Cmd+K listener
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = !this.open;
                }
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                }
            });
        },

        get flatResults() {
            const flat = [];
            for (const [group, items] of Object.entries(this.results)) {
                if (items.length === 0) continue;
                flat.push({ type: 'header', group, label: this.groups[group]?.label || group });
                items.forEach(item => flat.push({ ...item, type: 'item' }));
            }
            return flat;
        },

        get currentItem() {
            return this.flatResults[this.selectedIndex];
        },

        handleKeydown(e) {
            const flat = this.flatResults;
            if (flat.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, flat.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const item = flat[this.selectedIndex];
                if (item && item.type === 'item') {
                    this.navigate(item);
                }
            }
        },

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            this.loading = true;

            try {
                // URL + CSRF provided by the layout via meta tags (plain JS file,
                // Blade directives never compile here).
                const searchUrl = document.querySelector('meta[name="search-api-url"]')?.content
                    || '/api/v1/search';
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(this.query)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                const data = await response.json();
                this.results = data.data || {};
                this.selectedIndex = 0;
            } catch (err) {
                console.error('Command palette search failed:', err);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        navigate(item) {
            if (item.url) {
                window.location.href = item.url;
            }
            this.open = false;
            this.query = '';
        },

        focusInput() {
            this.$nextTick(() => {
                this.$refs.paletteInput?.focus();
            });
        },
    }));
});
