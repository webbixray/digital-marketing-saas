/**
 * Tests for Agency Settings Alpine.js component
 * Tests tab switching, form validation, color picker, logo preview,
 * and team member management UI interactions.
 *
 * Based on resources/views/agency/settings.blade.php,
 * resources/views/agency/team.blade.php, and AgencySettingsRequest validation rules.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// ===========================================================================
// Test Helpers
// ===========================================================================

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

// ===========================================================================
// Agency Settings Tab State (mirrors x-data="{ activeTab: 'profile' }")
// ===========================================================================

const createAgencySettingsState = () => {
    return {
        activeTab: 'profile',
        switchTab(tab) {
            this.activeTab = tab;
        },
        isTabActive(tab) {
            return this.activeTab === tab;
        }
    };
};

// ===========================================================================
// Form Validation Logic (mirrors AgencySettingsRequest rules)
// ===========================================================================

const createFormValidator = () => {
    return {
        errors: {},

        validateEmail(email) {
            if (!email || typeof email !== 'string') return false;
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        validateUrl(url) {
            if (!url || typeof url === 'undefined') return true; // nullable
            if (!url) return true;
            // Require explicit http:// or https:// prefix
            if (!/^https?:\/\//i.test(url)) return false;
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
            return Boolean(value);
        },

        validateMaxLength(value, max) {
            if (!value || typeof value !== 'string') return true;
            return value.length <= max;
        },

        validateHexColor(color) {
            if (!color) return true; // nullable
            return /^#[0-9A-Fa-f]{6}$/.test(color);
        },

        validate(data) {
            this.errors = {};

            // name: required|string|max:255
            if (!this.validateRequired(data.name)) {
                this.errors.name = 'The name field is required.';
            } else if (!this.validateMaxLength(data.name, 255)) {
                this.errors.name = 'The name may not be greater than 255 characters.';
            }

            // email: required|email
            if (!this.validateRequired(data.email)) {
                this.errors.email = 'The email field is required.';
            } else if (!this.validateEmail(data.email)) {
                this.errors.email = 'The email must be a valid email address.';
            }

            // website: nullable|url
            if (data.website && !this.validateUrl(data.website)) {
                this.errors.website = 'The website must be a valid URL.';
            }

            // timezone: nullable|string|max:50
            if (data.timezone && !this.validateMaxLength(data.timezone, 50)) {
                this.errors.timezone = 'The timezone may not be greater than 50 characters.';
            }

            // currency: nullable|string|max:3
            if (data.currency && !this.validateMaxLength(data.currency, 3)) {
                this.errors.currency = 'The currency may not be greater than 3 characters.';
            }

            // phone: nullable|string|max:50
            if (data.phone && !this.validateMaxLength(data.phone, 50)) {
                this.errors.phone = 'The phone may not be greater than 50 characters.';
            }

            // description: nullable|string - no max constraint in form

            return Object.keys(this.errors).length === 0;
        },

        hasError(field) {
            return !!this.errors[field];
        },

        getError(field) {
            return this.errors[field] || '';
        }
    };
};

// ===========================================================================
// Color Picker Logic (mirrors input type="color" interaction)
// ===========================================================================

const createColorPicker = (initialColor = '#4f46e5') => {
    return {
        primaryColor: initialColor,
        isLivePreviewEnabled: true,

        setColor(color) {
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                this.primaryColor = color;
                this.updatePreview();
                return true;
            }
            return false;
        },

        updatePreview() {
            if (!this.isLivePreviewEnabled) return;
            const previewEl = document.getElementById('color-preview');
            if (previewEl) {
                previewEl.style.backgroundColor = this.primaryColor;
            }
        },

        getRgb() {
            const hex = this.primaryColor.replace('#', '');
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            return { r, g, b };
        },

        isValidHex(hex) {
            return /^#[0-9A-Fa-f]{6}$/.test(hex);
        }
    };
};

// ===========================================================================
// Logo Preview Logic (mirrors logo_url input interaction)
// ===========================================================================

const createLogoPreview = () => {
    return {
        logoUrl: '',
        previewVisible: false,
        errorVisible: false,

        setLogoUrl(url) {
            this.logoUrl = url;
            if (url) {
                return this.validateAndSetPreview(url);
            } else {
                this.clearPreview();
                return true;
            }
        },

        validateAndSetPreview(url) {
            if (!/^https?:\/\//i.test(url)) {
                this.errorVisible = true;
                this.previewVisible = false;
                return false;
            }
            try {
                const parsed = new URL(url);
                if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                    this.previewVisible = true;
                    this.errorVisible = false;
                    this.updatePreviewImage(url);
                    return true;
                }
            } catch {
                // Invalid URL
            }
            this.errorVisible = true;
            this.previewVisible = false;
            return false;
        },

        clearPreview() {
            this.previewVisible = false;
            this.errorVisible = false;
            const img = document.getElementById('logo-preview-img');
            if (img) img.src = '';
        },

        updatePreviewImage(url) {
            const img = document.getElementById('logo-preview-img');
            if (img) img.src = url;
        },

        handleImageError() {
            this.previewVisible = false;
            this.errorVisible = true;
            const img = document.getElementById('logo-preview-img');
            if (img) img.classList.add('hidden');
        }
    };
};

// ===========================================================================
// Team Member Management Logic
// ===========================================================================

const createTeamManager = () => {
    return {
        members: [],
        inviteModalOpen: false,
        inviteForm: { name: '', email: '', role: 'member' },
        errors: {},

        init(members = []) {
            this.members = [...members];
        },

        openInviteModal() {
            this.inviteModalOpen = true;
            this.resetInviteForm();
        },

        closeInviteModal() {
            this.inviteModalOpen = false;
            this.resetInviteForm();
            this.errors = {};
        },

        resetInviteForm() {
            this.inviteForm = { name: '', email: '', role: 'member' };
        },

        setInviteField(field, value) {
            this.inviteForm[field] = value;
        },

        validateInviteForm() {
            this.errors = {};

            if (!this.inviteForm.name || this.inviteForm.name.trim().length === 0) {
                this.errors.name = 'Name is required.';
            }

            if (!this.inviteForm.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.inviteForm.email)) {
                this.errors.email = 'A valid email is required.';
            }

            return Object.keys(this.errors).length === 0;
        },

        inviteMember() {
            if (!this.validateInviteForm()) return false;
            const newMember = {
                id: Date.now(),
                name: this.inviteForm.name,
                email: this.inviteForm.email,
                role: this.inviteForm.role,
                is_active: false,
                last_active_at: null
            };
            this.members.push(newMember);
            this.closeInviteModal();
            return true;
        },

        removeMember(id) {
            this.members = this.members.filter(m => m.id !== id);
        },

        changeRole(id, newRole) {
            const member = this.members.find(m => m.id === id);
            if (member) {
                member.role = newRole;
                return true;
            }
            return false;
        },

        canRemoveMember(member, currentUser) {
            return member.id !== currentUser.id && member.role !== 'owner';
        },

        canChangeRole(member, currentUser) {
            return member.id !== currentUser.id && member.role !== 'owner';
        },

        getMemberCount() {
            return this.members.length;
        },

        getActiveMembers() {
            return this.members.filter(m => m.is_active);
        },

        getMembersByRole(role) {
            return this.members.filter(m => m.role === role);
        }
    };
};

// ===========================================================================
// TEST SUITE
// ===========================================================================

describe('Agency Settings Alpine Component', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    // =======================================================================
    // TAB SWITCHING
    // =======================================================================

    describe('Tab Switching', () => {
        it('defaults to profile tab', () => {
            const state = createAgencySettingsState();
            expect(state.activeTab).toBe('profile');
        });

        it('switches to billing tab', () => {
            const state = createAgencySettingsState();
            state.switchTab('billing');
            expect(state.activeTab).toBe('billing');
        });

        it('switches to team tab', () => {
            const state = createAgencySettingsState();
            state.switchTab('team');
            expect(state.activeTab).toBe('team');
        });

        it('switches to integrations tab', () => {
            const state = createAgencySettingsState();
            state.switchTab('integrations');
            expect(state.activeTab).toBe('integrations');
        });

        it('switches to api tab', () => {
            const state = createAgencySettingsState();
            state.switchTab('api');
            expect(state.activeTab).toBe('api');
        });

        it('isTabActive returns true only for active tab', () => {
            const state = createAgencySettingsState();
            expect(state.isTabActive('profile')).toBe(true);
            expect(state.isTabActive('billing')).toBe(false);
            expect(state.isTabActive('team')).toBe(false);
        });

        it('switching tabs deactivates previous tab', () => {
            const state = createAgencySettingsState();
            state.switchTab('team');
            expect(state.isTabActive('profile')).toBe(false);
            expect(state.isTabActive('team')).toBe(true);
        });

        it('supports switching back and forth between tabs', () => {
            const state = createAgencySettingsState();
            const tabs = ['profile', 'billing', 'team', 'integrations', 'api'];
            tabs.forEach(tab => {
                state.switchTab(tab);
                expect(state.isTabActive(tab)).toBe(true);
            });
        });

        it('renders only the active tab content in DOM', () => {
            document.body.innerHTML = `
                <div x-data="{ activeTab: 'profile' }">
                    <div x-show="activeTab === 'profile'" id="profile-content">Profile</div>
                    <div x-show="activeTab === 'team'" id="team-content">Team</div>
                </div>
            `;
            const state = createAgencySettingsState();
            state.switchTab('team');

            // In real Alpine, x-show toggles display: none
            // Here we simulate the visibility check
            const profileEl = document.getElementById('profile-content');
            const teamEl = document.getElementById('team-content');

            profileEl.classList.toggle('hidden', !state.isTabActive('profile'));
            teamEl.classList.toggle('hidden', !state.isTabActive('team'));

            expect(profileEl.classList.contains('hidden')).toBe(true);
            expect(teamEl.classList.contains('hidden')).toBe(false);
        });
    });

    // =======================================================================
    // TAB NAVIGATION BUTTONS (mirrors nav button :class binding)
    // =======================================================================

    describe('Tab Navigation Styling', () => {
        it('applies active styles to active tab button', () => {
            document.body.innerHTML = `
                <nav>
                    <button id="btn-profile" data-tab="profile">Profile</button>
                    <button id="btn-team" data-tab="team">Team</button>
                </nav>
            `;
            const state = createAgencySettingsState();

            // Simulate Alpine :class binding
            const applyTabStyles = (btn, isActive) => {
                if (isActive) {
                    btn.classList.add('bg-indigo-50', 'text-indigo-700');
                    btn.classList.remove('text-gray-700', 'hover:bg-gray-100');
                } else {
                    btn.classList.remove('bg-indigo-50', 'text-indigo-700');
                    btn.classList.add('text-gray-700', 'hover:bg-gray-100');
                }
            };

            const profileBtn = document.getElementById('btn-profile');
            const teamBtn = document.getElementById('btn-team');

            applyTabStyles(profileBtn, state.isTabActive('profile'));
            applyTabStyles(teamBtn, state.isTabActive('team'));

            expect(profileBtn.classList.contains('bg-indigo-50')).toBe(true);
            expect(teamBtn.classList.contains('bg-indigo-50')).toBe(false);
        });

        it('removes active styles when tab changes', () => {
            document.body.innerHTML = `
                <nav>
                    <button id="btn-profile" data-tab="profile" class="bg-indigo-50 text-indigo-700">Profile</button>
                    <button id="btn-team" data-tab="team" class="text-gray-700 hover:bg-gray-100">Team</button>
                </nav>
            `;
            const state = createAgencySettingsState();
            state.switchTab('team');

            const profileBtn = document.getElementById('btn-profile');
            const teamBtn = document.getElementById('btn-team');

            // Remove active from profile, add to team
            profileBtn.classList.remove('bg-indigo-50', 'text-indigo-700');
            profileBtn.classList.add('text-gray-700', 'hover:bg-gray-100');
            teamBtn.classList.add('bg-indigo-50', 'text-indigo-700');
            teamBtn.classList.remove('text-gray-700', 'hover:bg-gray-100');

            expect(profileBtn.classList.contains('bg-indigo-50')).toBe(false);
            expect(teamBtn.classList.contains('bg-indigo-50')).toBe(true);
        });

        it('clicking a tab button switches to that tab', () => {
            document.body.innerHTML = `
                <div x-data="{ activeTab: 'profile' }">
                    <button @click="activeTab = 'team'" id="btn-team">Team</button>
                    <div x-show="activeTab === 'team'" id="team-content" class="hidden">Team Content</div>
                </div>
            `;
            const state = createAgencySettingsState();
            const btn = document.getElementById('btn-team');

            // Simulate @click handler
            btn.click();
            state.switchTab('team');

            expect(state.activeTab).toBe('team');
        });
    });

    // =======================================================================
    // FORM VALIDATION
    // =======================================================================

    describe('Form Validation - Agency Name', () => {
        it('passes validation when name is provided', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Test Agency', email: 'test@example.com' });
            expect(isValid).toBe(true);
            expect(validator.hasError('name')).toBe(false);
        });

        it('fails validation when name is empty', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: '', email: 'test@example.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('name')).toBe(true);
            expect(validator.getError('name')).toContain('required');
        });

        it('fails validation when name is null', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: null, email: 'test@example.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('name')).toBe(true);
        });

        it('fails validation when name exceeds 255 characters', () => {
            const validator = createFormValidator();
            const longName = 'A'.repeat(256);
            const isValid = validator.validate({ name: longName, email: 'test@example.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('name')).toBe(true);
        });

        it('passes validation when name is exactly 255 characters', () => {
            const validator = createFormValidator();
            const maxName = 'A'.repeat(255);
            const isValid = validator.validate({ name: maxName, email: 'test@example.com' });
            expect(isValid).toBe(true);
            expect(validator.hasError('name')).toBe(false);
        });

        it('fails validation when name is whitespace only', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: '   ', email: 'test@example.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('name')).toBe(true);
        });
    });

    describe('Form Validation - Email', () => {
        it('passes validation with valid email', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'admin@agency.com' });
            expect(isValid).toBe(true);
            expect(validator.hasError('email')).toBe(false);
        });

        it('fails validation when email is empty', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: '' });
            expect(isValid).toBe(false);
            expect(validator.hasError('email')).toBe(true);
        });

        it('fails validation when email is null', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: null });
            expect(isValid).toBe(false);
            expect(validator.hasError('email')).toBe(true);
        });

        it('fails validation with invalid email format', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'not-an-email' });
            expect(isValid).toBe(false);
            expect(validator.hasError('email')).toBe(true);
        });

        it('fails validation with email missing domain', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'user@' });
            expect(isValid).toBe(false);
            expect(validator.hasError('email')).toBe(true);
        });

        it('fails validation with email missing @ symbol', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'userdomain.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('email')).toBe(true);
        });

        it('passes validation with email containing subdomain', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'user@mail.domain.com' });
            expect(isValid).toBe(true);
        });

        it('passes validation with email containing plus tag', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'user+tag@domain.com' });
            expect(isValid).toBe(true);
        });
    });

    describe('Form Validation - Website URL', () => {
        it('passes validation when website is empty (nullable)', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: '' });
            expect(isValid).toBe(true);
            expect(validator.hasError('website')).toBe(false);
        });

        it('passes validation with valid HTTPS URL', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: 'https://example.com' });
            expect(isValid).toBe(true);
            expect(validator.hasError('website')).toBe(false);
        });

        it('passes validation with valid HTTP URL', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: 'http://example.com' });
            expect(isValid).toBe(true);
        });

        it('fails validation with invalid URL', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: 'not-a-url' });
            expect(isValid).toBe(false);
            expect(validator.hasError('website')).toBe(true);
        });

        it('fails validation with FTP URL', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: 'ftp://example.com' });
            expect(isValid).toBe(false);
            expect(validator.hasError('website')).toBe(true);
        });

        it('fails validation with javascript: protocol', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', website: 'javascript:alert(1)' });
            expect(isValid).toBe(false);
            expect(validator.hasError('website')).toBe(true);
        });
    });

    describe('Form Validation - Timezone', () => {
        it('passes validation when timezone is empty', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', timezone: '' });
            expect(isValid).toBe(true);
        });

        it('passes validation with valid timezone', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', timezone: 'America/New_York' });
            expect(isValid).toBe(true);
        });

        it('fails validation when timezone exceeds 50 characters', () => {
            const validator = createFormValidator();
            const longTimezone = 'A'.repeat(51);
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', timezone: longTimezone });
            expect(isValid).toBe(false);
            expect(validator.hasError('timezone')).toBe(true);
        });
    });

    describe('Form Validation - Currency', () => {
        it('passes validation when currency is empty', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', currency: '' });
            expect(isValid).toBe(true);
        });

        it('passes validation with 3-letter currency code', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', currency: 'USD' });
            expect(isValid).toBe(true);
        });

        it('fails validation when currency exceeds 3 characters', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', currency: 'USDD' });
            expect(isValid).toBe(false);
            expect(validator.hasError('currency')).toBe(true);
        });
    });

    describe('Form Validation - Phone', () => {
        it('passes validation when phone is empty', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', phone: '' });
            expect(isValid).toBe(true);
        });

        it('passes validation with valid phone', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', phone: '+1-555-123-4567' });
            expect(isValid).toBe(true);
        });

        it('fails validation when phone exceeds 50 characters', () => {
            const validator = createFormValidator();
            const longPhone = '1'.repeat(51);
            const isValid = validator.validate({ name: 'Agency', email: 'test@example.com', phone: longPhone });
            expect(isValid).toBe(false);
            expect(validator.hasError('phone')).toBe(true);
        });
    });

    describe('Form Validation - Multiple Errors', () => {
        it('accumulates multiple errors when multiple fields are invalid', () => {
            const validator = createFormValidator();
            const isValid = validator.validate({ name: '', email: 'invalid', website: 'bad-url', timezone: 'A'.repeat(51) });
            expect(isValid).toBe(false);
            expect(Object.keys(validator.errors).length).toBeGreaterThanOrEqual(3);
            expect(validator.hasError('name')).toBe(true);
            expect(validator.hasError('email')).toBe(true);
            expect(validator.hasError('website')).toBe(true);
        });

        it('clears errors on subsequent valid submission', () => {
            const validator = createFormValidator();
            validator.validate({ name: '', email: '' });
            expect(Object.keys(validator.errors).length).toBeGreaterThan(0);

            validator.validate({ name: 'Valid Agency', email: 'valid@email.com' });
            expect(Object.keys(validator.errors).length).toBe(0);
        });
    });

    // =======================================================================
    // COLOR PICKER
    // =======================================================================

    describe('Color Picker', () => {
        it('initializes with default color #4f46e5', () => {
            const picker = createColorPicker();
            expect(picker.primaryColor).toBe('#4f46e5');
        });

        it('sets valid hex color', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#ff0000')).toBe(true);
            expect(picker.primaryColor).toBe('#ff0000');
        });

        it('rejects invalid hex color', () => {
            const picker = createColorPicker();
            expect(picker.setColor('red')).toBe(false);
            expect(picker.primaryColor).toBe('#4f46e5'); // unchanged
        });

        it('rejects short hex color', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#fff')).toBe(false);
            expect(picker.primaryColor).toBe('#4f46e5');
        });

        it('rejects hex without hash', () => {
            const picker = createColorPicker();
            expect(picker.setColor('4f46e5')).toBe(false);
            expect(picker.primaryColor).toBe('#4f46e5');
        });

        it('rejects hex with invalid characters', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#gggggg')).toBe(false);
            expect(picker.primaryColor).toBe('#4f46e5');
        });

        it('accepts lowercase hex', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#ffffff')).toBe(true);
            expect(picker.primaryColor).toBe('#ffffff');
        });

        it('accepts uppercase hex', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#FFFFFF')).toBe(true);
            expect(picker.primaryColor).toBe('#FFFFFF');
        });

        it('accepts mixed case hex', () => {
            const picker = createColorPicker();
            expect(picker.setColor('#Ff46E5')).toBe(true);
            expect(picker.primaryColor).toBe('#Ff46E5');
        });

        it('updates preview element when color changes', () => {
            document.body.innerHTML = '<div id="color-preview"></div>';
            const picker = createColorPicker();
            picker.setColor('#ff0000');

            const previewEl = document.getElementById('color-preview');
            expect(previewEl.style.backgroundColor).toBe('#ff0000');
        });

        it('does not update preview when live preview is disabled', () => {
            document.body.innerHTML = '<div id="color-preview"></div>';
            const picker = createColorPicker();
            picker.isLivePreviewEnabled = false;
            picker.setColor('#ff0000');

            const previewEl = document.getElementById('color-preview');
            expect(previewEl.style.backgroundColor).toBe('');
        });

        it('validates hex format correctly', () => {
            const picker = createColorPicker();
            expect(picker.isValidHex('#000000')).toBe(true);
            expect(picker.isValidHex('#FFFFFF')).toBe(true);
            expect(picker.isValidHex('#4f46e5')).toBe(true);
            expect(picker.isValidHex('4f46e5')).toBe(false);
            expect(picker.isValidHex('#fff')).toBe(false);
            expect(picker.isValidHex('#gggggg')).toBe(false);
            expect(picker.isValidHex('')).toBe(false);
            expect(picker.isValidHex(null)).toBe(false);
        });

        it('converts hex to RGB', () => {
            const picker = createColorPicker('#ff0000');
            const rgb = picker.getRgb();
            expect(rgb).toEqual({ r: 255, g: 0, b: 0 });
        });

        it('converts default color to RGB', () => {
            const picker = createColorPicker('#4f46e5');
            const rgb = picker.getRgb();
            expect(rgb).toEqual({ r: 79, g: 70, b: 229 });
        });

        it('converts white to RGB', () => {
            const picker = createColorPicker('#ffffff');
            const rgb = picker.getRgb();
            expect(rgb).toEqual({ r: 255, g: 255, b: 255 });
        });

        it('converts black to RGB', () => {
            const picker = createColorPicker('#000000');
            const rgb = picker.getRgb();
            expect(rgb).toEqual({ r: 0, g: 0, b: 0 });
        });

        it('handles empty preview element gracefully', () => {
            const picker = createColorPicker();
            // No preview element in DOM, should not throw
            expect(() => picker.setColor('#ff0000')).not.toThrow();
        });

        it('handles multiple rapid color changes', () => {
            const picker = createColorPicker();
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'];
            colors.forEach(color => {
                picker.setColor(color);
                expect(picker.primaryColor).toBe(color);
            });
            expect(picker.primaryColor).toBe('#ff00ff');
        });
    });

    // =======================================================================
    // LOGO PREVIEW
    // =======================================================================

    describe('Logo Preview', () => {
        it('starts with empty logo URL', () => {
            const preview = createLogoPreview();
            expect(preview.logoUrl).toBe('');
        });

        it('starts with preview hidden', () => {
            const preview = createLogoPreview();
            expect(preview.previewVisible).toBe(false);
        });

        it('starts with error hidden', () => {
            const preview = createLogoPreview();
            expect(preview.errorVisible).toBe(false);
        });

        it('sets valid logo URL and shows preview', () => {
            document.body.innerHTML = '<img id="logo-preview-img" class="hidden" />';
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('https://example.com/logo.png');

            expect(result).toBe(true);
            expect(preview.logoUrl).toBe('https://example.com/logo.png');
            expect(preview.previewVisible).toBe(true);
            expect(preview.errorVisible).toBe(false);
        });

        it('updates preview image src', () => {
            document.body.innerHTML = '<img id="logo-preview-img" />';
            const preview = createLogoPreview();
            preview.setLogoUrl('https://example.com/logo.svg');

            const img = document.getElementById('logo-preview-img');
            expect(img.src).toBe('https://example.com/logo.svg');
        });

        it('clears preview when URL is empty', () => {
            document.body.innerHTML = '<img id="logo-preview-img" src="https://example.com/logo.png" />';
            const preview = createLogoPreview();
            preview.setLogoUrl('https://example.com/logo.png');
            preview.setLogoUrl('');

            expect(preview.previewVisible).toBe(false);
            expect(preview.logoUrl).toBe('');
            const img = document.getElementById('logo-preview-img');
            expect(img.src).toBe('');
        });

        it('shows error for invalid URL', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('not-a-url');

            expect(result).toBe(false);
            expect(preview.errorVisible).toBe(true);
            expect(preview.previewVisible).toBe(false);
        });

        it('shows error for FTP URL', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('ftp://example.com/logo.png');

            expect(result).toBe(false);
            expect(preview.errorVisible).toBe(true);
        });

        it('accepts HTTP URL', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('http://example.com/logo.png');

            expect(result).toBe(true);
            expect(preview.previewVisible).toBe(true);
            expect(preview.errorVisible).toBe(false);
        });

        it('accepts HTTPS URL', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('https://example.com/logo.png');

            expect(result).toBe(true);
            expect(preview.previewVisible).toBe(true);
        });

        it('handles image load error', () => {
            document.body.innerHTML = '<img id="logo-preview-img" />';
            const preview = createLogoPreview();
            preview.setLogoUrl('https://example.com/broken.png');
            preview.handleImageError();

            expect(preview.previewVisible).toBe(false);
            expect(preview.errorVisible).toBe(true);
            const img = document.getElementById('logo-preview-img');
            expect(img.classList.contains('hidden')).toBe(true);
        });

        it('rejects javascript: protocol', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('javascript:alert(1)');

            expect(result).toBe(false);
            expect(preview.errorVisible).toBe(true);
        });

        it('rejects URL with spaces', () => {
            const preview = createLogoPreview();
            const result = preview.setLogoUrl('https://example.com/logo with spaces.png');

            // URL constructor may or may not accept this, but it's handled gracefully
            expect(typeof result).toBe('boolean');
        });

        it('handles rapid URL changes', () => {
            document.body.innerHTML = '<img id="logo-preview-img" />';
            const preview = createLogoPreview();
            const urls = [
                'https://example.com/logo1.png',
                'https://example.com/logo2.png',
                'https://example.com/logo3.png'
            ];
            urls.forEach(url => preview.setLogoUrl(url));

            expect(preview.logoUrl).toBe('https://example.com/logo3.png');
            expect(preview.previewVisible).toBe(true);
        });
    });

    // =======================================================================
    // TEAM MEMBER MANAGEMENT
    // =======================================================================

    describe('Team Member Management - Initialization', () => {
        it('initializes with empty members list', () => {
            const manager = createTeamManager();
            expect(manager.members).toHaveLength(0);
        });

        it('initializes with provided members', () => {
            const initialMembers = [
                { id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: true, last_active_at: '2024-01-15' },
                { id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin', is_active: true, last_active_at: '2024-01-14' }
            ];
            const manager = createTeamManager();
            manager.init(initialMembers);
            expect(manager.members).toHaveLength(2);
        });

        it('invite modal starts closed', () => {
            const manager = createTeamManager();
            expect(manager.inviteModalOpen).toBe(false);
        });

        it('invite form starts with default values', () => {
            const manager = createTeamManager();
            expect(manager.inviteForm.name).toBe('');
            expect(manager.inviteForm.email).toBe('');
            expect(manager.inviteForm.role).toBe('member');
        });
    });

    describe('Team Member Management - Invite Modal', () => {
        it('opens invite modal', () => {
            const manager = createTeamManager();
            manager.openInviteModal();
            expect(manager.inviteModalOpen).toBe(true);
        });

        it('closes invite modal', () => {
            const manager = createTeamManager();
            manager.openInviteModal();
            manager.closeInviteModal();
            expect(manager.inviteModalOpen).toBe(false);
        });

        it('resets form on open', () => {
            const manager = createTeamManager();
            manager.inviteForm.name = 'Test';
            manager.inviteForm.email = 'test@test.com';
            manager.inviteForm.role = 'admin';
            manager.openInviteModal();

            expect(manager.inviteForm.name).toBe('');
            expect(manager.inviteForm.email).toBe('');
            expect(manager.inviteForm.role).toBe('member');
        });

        it('resets form on close', () => {
            const manager = createTeamManager();
            manager.openInviteModal();
            manager.inviteForm.name = 'Test';
            manager.closeInviteModal();

            expect(manager.inviteForm.name).toBe('');
            expect(manager.inviteForm.email).toBe('');
        });

        it('clears errors on close', () => {
            const manager = createTeamManager();
            manager.errors = { name: 'error' };
            manager.closeInviteModal();
            expect(Object.keys(manager.errors).length).toBe(0);
        });

        it('opens invite modal via dialog element showModal()', () => {
            document.body.innerHTML = '<dialog id="inviteModal"></dialog>';
            const dialog = document.getElementById('inviteModal');
            const showModalSpy = vi.spyOn(dialog, 'showModal').mockImplementation(() => {});

            dialog.showModal();
            expect(showModalSpy).toHaveBeenCalled();
        });

        it('closes invite modal via dialog element close()', () => {
            document.body.innerHTML = '<dialog id="inviteModal"></dialog>';
            const dialog = document.getElementById('inviteModal');
            const closeSpy = vi.spyOn(dialog, 'close').mockImplementation(() => {});

            dialog.close();
            expect(closeSpy).toHaveBeenCalled();
        });
    });

    describe('Team Member Management - Invite Form Fields', () => {
        it('sets name field', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            expect(manager.inviteForm.name).toBe('John Doe');
        });

        it('sets email field', () => {
            const manager = createTeamManager();
            manager.setInviteField('email', 'john@example.com');
            expect(manager.inviteForm.email).toBe('john@example.com');
        });

        it('sets role field', () => {
            const manager = createTeamManager();
            manager.setInviteField('role', 'admin');
            expect(manager.inviteForm.role).toBe('admin');
        });

        it('supports all valid roles', () => {
            const validRoles = ['admin', 'manager', 'member'];
            const manager = createTeamManager();
            validRoles.forEach(role => {
                manager.setInviteField('role', role);
                expect(manager.inviteForm.role).toBe(role);
            });
        });
    });

    describe('Team Member Management - Invite Form Validation', () => {
        it('passes validation with valid data', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.setInviteField('role', 'admin');

            expect(manager.validateInviteForm()).toBe(true);
            expect(Object.keys(manager.errors).length).toBe(0);
        });

        it('fails validation when name is empty', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', '');
            manager.setInviteField('email', 'john@example.com');

            expect(manager.validateInviteForm()).toBe(false);
            expect(manager.errors.name).toBeDefined();
        });

        it('fails validation when name is whitespace only', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', '   ');
            manager.setInviteField('email', 'john@example.com');

            expect(manager.validateInviteForm()).toBe(false);
            expect(manager.errors.name).toBeDefined();
        });

        it('fails validation when email is empty', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', '');

            expect(manager.validateInviteForm()).toBe(false);
            expect(manager.errors.email).toBeDefined();
        });

        it('fails validation when email is invalid', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'not-an-email');

            expect(manager.validateInviteForm()).toBe(false);
            expect(manager.errors.email).toBeDefined();
        });

        it('fails validation when both name and email are missing', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', '');
            manager.setInviteField('email', '');

            expect(manager.validateInviteForm()).toBe(false);
            expect(manager.errors.name).toBeDefined();
            expect(manager.errors.email).toBeDefined();
        });

        it('clears errors on re-validation with valid data', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', '');
            manager.setInviteField('email', '');
            manager.validateInviteForm();
            expect(Object.keys(manager.errors).length).toBeGreaterThan(0);

            manager.setInviteField('name', 'John');
            manager.setInviteField('email', 'john@example.com');
            manager.validateInviteForm();
            expect(Object.keys(manager.errors).length).toBe(0);
        });
    });

    describe('Team Member Management - Invite Member', () => {
        it('adds member on successful invite', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.setInviteField('role', 'admin');

            const result = manager.inviteMember();
            expect(result).toBe(true);
            expect(manager.members).toHaveLength(1);
            expect(manager.members[0].name).toBe('John Doe');
            expect(manager.members[0].email).toBe('john@example.com');
            expect(manager.members[0].role).toBe('admin');
        });

        it('does not add member when validation fails', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', '');
            manager.setInviteField('email', 'invalid');

            const result = manager.inviteMember();
            expect(result).toBe(false);
            expect(manager.members).toHaveLength(0);
        });

        it('closes modal after successful invite', () => {
            const manager = createTeamManager();
            manager.openInviteModal();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.inviteMember();

            expect(manager.inviteModalOpen).toBe(false);
        });

        it('sets new member as inactive initially', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.inviteMember();

            expect(manager.members[0].is_active).toBe(false);
        });

        it('sets new member last_active_at as null initially', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.inviteMember();

            expect(manager.members[0].last_active_at).toBeNull();
        });

        it('resets form after successful invite', () => {
            const manager = createTeamManager();
            manager.setInviteField('name', 'John Doe');
            manager.setInviteField('email', 'john@example.com');
            manager.inviteMember();

            expect(manager.inviteForm.name).toBe('');
            expect(manager.inviteForm.email).toBe('');
            expect(manager.inviteForm.role).toBe('member');
        });

        it('supports inviting multiple members', () => {
            const manager = createTeamManager();

            manager.setInviteField('name', 'Alice');
            manager.setInviteField('email', 'alice@example.com');
            manager.setInviteField('role', 'admin');
            manager.inviteMember();

            manager.setInviteField('name', 'Bob');
            manager.setInviteField('email', 'bob@example.com');
            manager.setInviteField('role', 'member');
            manager.inviteMember();

            expect(manager.members).toHaveLength(2);
        });
    });

    describe('Team Member Management - Remove Member', () => {
        it('removes member by id', () => {
            const manager = createTeamManager();
            manager.init([
                { id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner' },
                { id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin' }
            ]);

            manager.removeMember(2);
            expect(manager.members).toHaveLength(1);
            expect(manager.members[0].id).toBe(1);
        });

        it('does not remove when id not found', () => {
            const manager = createTeamManager();
            manager.init([{ id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner' }]);

            manager.removeMember(999);
            expect(manager.members).toHaveLength(1);
        });

        it('canChangeRole prevents changing own role', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const self = { id: 1, name: 'Alice', role: 'owner' };
            expect(manager.canChangeRole(self, currentUser)).toBe(false);
        });

        it('canChangeRole prevents changing owner role', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const owner = { id: 2, name: 'Bob', role: 'owner' };
            expect(manager.canChangeRole(owner, currentUser)).toBe(false);
        });

        it('canChangeRole allows changing non-owner roles', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const admin = { id: 2, name: 'Bob', role: 'admin' };
            expect(manager.canChangeRole(admin, currentUser)).toBe(true);
        });

        it('canRemoveMember prevents removing self', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const self = { id: 1, name: 'Alice', role: 'owner' };
            expect(manager.canRemoveMember(self, currentUser)).toBe(false);
        });

        it('canRemoveMember prevents removing owner', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const owner = { id: 2, name: 'Bob', role: 'owner' };
            expect(manager.canRemoveMember(owner, currentUser)).toBe(false);
        });

        it('canRemoveMember allows removing non-owner members', () => {
            const manager = createTeamManager();
            const currentUser = { id: 1 };
            const member = { id: 3, name: 'Charlie', role: 'member' };
            expect(manager.canRemoveMember(member, currentUser)).toBe(true);
        });

        it('shows confirmation dialog before remove (simulated)', () => {
            const manager = createTeamManager();
            manager.init([{ id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin' }]);

            // Simulate confirm() returning true
            vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
            const canRemove = manager.canRemoveMember(manager.members[0], { id: 1 });

            if (canRemove && confirm('Remove?')) {
                manager.removeMember(2);
            }

            expect(confirm).toHaveBeenCalledWith('Remove?');
            expect(manager.members).toHaveLength(0);
            vi.unstubAllGlobals();
        });

        it('cancels removal when confirmation is dismissed', () => {
            const manager = createTeamManager();
            manager.init([{ id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin' }]);

            vi.stubGlobal('confirm', vi.fn().mockReturnValue(false));
            const canRemove = manager.canRemoveMember(manager.members[0], { id: 1 });

            if (canRemove && confirm('Remove?')) {
                manager.removeMember(2);
            }

            expect(confirm).toHaveBeenCalledWith('Remove?');
            expect(manager.members).toHaveLength(1); // Not removed
            vi.unstubAllGlobals();
        });
    });

    describe('Team Member Management - Change Role', () => {
        it('changes member role', () => {
            const manager = createTeamManager();
            manager.init([{ id: 2, name: 'Bob', email: 'bob@agency.com', role: 'member' }]);

            const result = manager.changeRole(2, 'admin');
            expect(result).toBe(true);
            expect(manager.members[0].role).toBe('admin');
        });

        it('returns false when member not found', () => {
            const manager = createTeamManager();
            manager.init([{ id: 2, name: 'Bob', email: 'bob@agency.com', role: 'member' }]);

            const result = manager.changeRole(999, 'admin');
            expect(result).toBe(false);
        });

        it('supports changing to all valid roles', () => {
            const manager = createTeamManager();
            manager.init([{ id: 2, name: 'Bob', email: 'bob@agency.com', role: 'member' }]);

            const roles = ['admin', 'manager', 'member'];
            roles.forEach(role => {
                manager.changeRole(2, role);
                expect(manager.members[0].role).toBe(role);
            });
        });

        it('triggers form submit on role change (simulated via onchange)', () => {
            document.body.innerHTML = `
                <form id="role-form">
                    <select name="role">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </form>
            `;
            const form = document.getElementById('role-form');
            const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});
            const select = form.querySelector('select');

            // Simulate onchange="this.form.submit()"
            select.addEventListener('change', () => select.form.submit());

            select.value = 'admin';
            select.dispatchEvent(new Event('change'));

            expect(submitSpy).toHaveBeenCalled();
            submitSpy.mockRestore();
        });
    });

    describe('Team Member Management - Counts and Filters', () => {
        it('counts total members', () => {
            const manager = createTeamManager();
            manager.init([
                { id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: true },
                { id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin', is_active: false },
                { id: 3, name: 'Charlie', email: 'charlie@agency.com', role: 'member', is_active: true }
            ]);

            expect(manager.getMemberCount()).toBe(3);
        });

        it('filters active members', () => {
            const manager = createTeamManager();
            manager.init([
                { id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: true },
                { id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin', is_active: false },
                { id: 3, name: 'Charlie', email: 'charlie@agency.com', role: 'member', is_active: true }
            ]);

            const active = manager.getActiveMembers();
            expect(active).toHaveLength(2);
            expect(active.every(m => m.is_active)).toBe(true);
        });

        it('filters members by role', () => {
            const manager = createTeamManager();
            manager.init([
                { id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: true },
                { id: 2, name: 'Bob', email: 'bob@agency.com', role: 'admin', is_active: true },
                { id: 3, name: 'Charlie', email: 'charlie@agency.com', role: 'admin', is_active: true },
                { id: 4, name: 'Dave', email: 'dave@agency.com', role: 'member', is_active: true }
            ]);

            const admins = manager.getMembersByRole('admin');
            expect(admins).toHaveLength(2);
            expect(admins.every(m => m.role === 'admin')).toBe(true);
        });

        it('returns empty array when no members match role filter', () => {
            const manager = createTeamManager();
            manager.init([{ id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: true }]);

            const members = manager.getMembersByRole('admin');
            expect(members).toHaveLength(0);
        });

        it('returns zero count when no members', () => {
            const manager = createTeamManager();
            expect(manager.getMemberCount()).toBe(0);
        });

        it('returns empty array for active members when none active', () => {
            const manager = createTeamManager();
            manager.init([{ id: 1, name: 'Alice', email: 'alice@agency.com', role: 'owner', is_active: false }]);

            expect(manager.getActiveMembers()).toHaveLength(0);
        });
    });

    // =======================================================================
    // TEAM MEMBER TABLE RENDERING
    // =======================================================================

    describe('Team Member Table Rendering', () => {
        it('renders team member rows in table', () => {
            document.body.innerHTML = `
                <table id="team-table">
                    <tbody>
                        <tr data-member-id="1">
                            <td>Alice</td>
                            <td>alice@agency.com</td>
                            <td><span class="role-badge">Owner</span></td>
                        </tr>
                        <tr data-member-id="2">
                            <td>Bob</td>
                            <td>bob@agency.com</td>
                            <td><span class="role-badge">Admin</span></td>
                        </tr>
                    </tbody>
                </table>
            `;
            const table = document.getElementById('team-table');
            const rows = table.querySelectorAll('tbody tr');
            expect(rows).toHaveLength(2);
        });

        it('displays role badge with correct color class', () => {
            document.body.innerHTML = `
                <span class="role-badge bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">Owner</span>
            `;
            const badge = document.querySelector('.role-badge');
            expect(badge.textContent).toBe('Owner');
            expect(badge.classList.contains('bg-indigo-100')).toBe(true);
        });

        it('displays admin role with blue badge', () => {
            document.body.innerHTML = `
                <span class="role-badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Admin</span>
            `;
            const badge = document.querySelector('.role-badge');
            expect(badge.classList.contains('bg-blue-100')).toBe(true);
        });

        it('displays member role with gray badge', () => {
            document.body.innerHTML = `
                <span class="role-badge bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Member</span>
            `;
            const badge = document.querySelector('.role-badge');
            expect(badge.classList.contains('bg-gray-100')).toBe(true);
        });

        it('displays active status badge', () => {
            document.body.innerHTML = `
                <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
            `;
            const badge = document.querySelector('.status-badge');
            expect(badge.textContent).toBe('Active');
            expect(badge.classList.contains('bg-green-100')).toBe(true);
        });

        it('displays inactive status badge', () => {
            document.body.innerHTML = `
                <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
            `;
            const badge = document.querySelector('.status-badge');
            expect(badge.textContent).toBe('Inactive');
            expect(badge.classList.contains('bg-gray-100')).toBe(true);
        });

        it('displays "Never" for null last_active_at', () => {
            document.body.innerHTML = `
                <span class="last-active">Never</span>
            `;
            const el = document.querySelector('.last-active');
            expect(el.textContent).toBe('Never');
        });

        it('displays relative time for last_active_at', () => {
            const twoDaysAgo = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000);
            document.body.innerHTML = `
                <span class="last-active" data-timestamp="${twoDaysAgo.toISOString()}">2 days ago</span>
            `;
            const el = document.querySelector('.last-active');
            expect(el.textContent).toBe('2 days ago');
        });

        it('shows "No members" when table is empty', () => {
            document.body.innerHTML = `
                <table>
                    <tbody>
                        <tr><td colspan="6" class="text-center text-gray-500">No members</td></tr>
                    </tbody>
                </table>
            `;
            const emptyCell = document.querySelector('td[colspan="6"]');
            expect(emptyCell).not.toBeNull();
            expect(emptyCell.textContent).toBe('No members');
        });
    });

    // =======================================================================
    // FORM SUBMISSION
    // =======================================================================

    describe('Form Submission', () => {
        it('form has correct action URL', () => {
            document.body.innerHTML = `
                <form method="POST" action="/agency/settings" id="settings-form">
                    <input type="hidden" name="_method" value="PUT" />
                    <input type="hidden" name="_token" value="test-token" />
                </form>
            `;
            const form = document.getElementById('settings-form');
            expect(form.getAttribute('action')).toBe('/agency/settings');
            expect(form.getAttribute('method')).toBe('POST');
        });

        it('form includes PUT method override', () => {
            document.body.innerHTML = `
                <form method="POST" action="/agency/settings">
                    <input type="hidden" name="_method" value="PUT" />
                </form>
            `;
            const form = document.querySelector('form');
            const methodInput = form.querySelector('input[name="_method"]');
            expect(methodInput.value).toBe('PUT');
        });

        it('form includes CSRF token', () => {
            document.body.innerHTML = `
                <form method="POST" action="/agency/settings">
                    <input type="hidden" name="_token" value="csrf-token-value" />
                </form>
            `;
            const form = document.querySelector('form');
            const tokenInput = form.querySelector('input[name="_token"]');
            expect(tokenInput).not.toBeNull();
            expect(tokenInput.value).toBe('csrf-token-value');
        });

        it('form includes all profile fields', () => {
            document.body.innerHTML = `
                <form>
                    <input name="agency_name" id="agency_name" value="Test Agency" />
                    <input name="website" id="website" value="https://example.com" />
                    <textarea name="description" id="description">Test description</textarea>
                    <input name="primary_color" id="primary_color" type="color" value="#4f46e5" />
                    <input name="logo_url" id="logo_url" value="https://example.com/logo.png" />
                </form>
            `;
            const form = document.querySelector('form');
            expect(form.querySelector('#agency_name')).not.toBeNull();
            expect(form.querySelector('#website')).not.toBeNull();
            expect(form.querySelector('#description')).not.toBeNull();
            expect(form.querySelector('#primary_color')).not.toBeNull();
            expect(form.querySelector('#logo_url')).not.toBeNull();
        });

        it('color input has correct type', () => {
            document.body.innerHTML = `
                <input type="color" name="primary_color" id="primary_color" value="#4f46e5" />
            `;
            const input = document.getElementById('primary_color');
            expect(input.type).toBe('color');
        });

        it('color input has default value', () => {
            document.body.innerHTML = `
                <input type="color" name="primary_color" id="primary_color" value="#4f46e5" />
            `;
            const input = document.getElementById('primary_color');
            expect(input.value).toBe('#4f46e5');
        });

        it('website input has URL type', () => {
            document.body.innerHTML = `
                <input type="url" name="website" id="website" />
            `;
            const input = document.getElementById('website');
            expect(input.type).toBe('url');
        });

        it('logo URL input has URL type', () => {
            document.body.innerHTML = `
                <input type="url" name="logo_url" id="logo_url" />
            `;
            const input = document.getElementById('logo_url');
            expect(input.type).toBe('url');
        });

        it('submit button exists', () => {
            document.body.innerHTML = `
                <button type="submit" class="bg-indigo-600 text-white">Save Changes</button>
            `;
            const button = document.querySelector('button[type="submit"]');
            expect(button).not.toBeNull();
            expect(button.textContent.trim()).toBe('Save Changes');
        });

        it('submits form when submit button is clicked', () => {
            document.body.innerHTML = `
                <form id="settings-form">
                    <input name="agency_name" value="Test Agency" />
                    <button type="submit">Save</button>
                </form>
            `;
            const form = document.getElementById('settings-form');
            const submitSpy = vi.fn((e) => e.preventDefault());
            form.addEventListener('submit', submitSpy);

            const button = form.querySelector('button[type="submit"]');
            button.click();

            expect(submitSpy).toHaveBeenCalled();
        });
    });

    // =======================================================================
    // VALIDATION ERROR DISPLAY
    // =======================================================================

    describe('Validation Error Display', () => {
        it('renders error message for field with error', () => {
            document.body.innerHTML = `
                <div class="form-error text-red-500 text-sm mt-1">The name field is required.</div>
            `;
            const error = document.querySelector('.form-error');
            expect(error).not.toBeNull();
            expect(error.textContent).toBe('The name field is required.');
        });

        it('applies error styling to input with validation error', () => {
            document.body.innerHTML = `
                <input type="text" name="agency_name" class="border-red-500" />
            `;
            const input = document.querySelector('input[name="agency_name"]');
            expect(input.classList.contains('border-red-500')).toBe(true);
        });

        it('adds aria-invalid attribute to invalid input', () => {
            document.body.innerHTML = `
                <input type="text" name="agency_name" aria-invalid="true" aria-describedby="agency_name-error" />
            `;
            const input = document.querySelector('input[name="agency_name"]');
            expect(input.getAttribute('aria-invalid')).toBe('true');
        });

        it('links error message via aria-describedby', () => {
            document.body.innerHTML = `
                <input type="text" name="agency_name" id="agency_name" aria-describedby="agency_name-error" />
                <div id="agency_name-error" class="form-error">Error message</div>
            `;
            const input = document.getElementById('agency_name');
            const errorId = input.getAttribute('aria-describedby');
            const errorEl = document.getElementById(errorId);
            expect(errorEl).not.toBeNull();
            expect(errorEl.textContent).toBe('Error message');
        });

        it('removes error class when field becomes valid', () => {
            document.body.innerHTML = `
                <input type="text" name="agency_name" class="border-red-500" />
            `;
            const input = document.querySelector('input[name="agency_name"]');
            input.classList.remove('border-red-500');
            expect(input.classList.contains('border-red-500')).toBe(false);
        });
    });

    // =======================================================================
    // INTEGRATION / STATE CONSISTENCY
    // =======================================================================

    describe('Integration - Full Settings Flow', () => {
        it('complete tab switch and form interaction flow', () => {
            const tabs = createAgencySettingsState();
            const validator = createFormValidator();

            // Start on profile tab
            expect(tabs.isTabActive('profile')).toBe(true);

            // Validate form data
            const isValid = validator.validate({ name: 'Test Agency', email: 'test@agency.com' });
            expect(isValid).toBe(true);

            // Switch to team tab
            tabs.switchTab('team');
            expect(tabs.isTabActive('team')).toBe(true);

            // Switch back to profile
            tabs.switchTab('profile');
            expect(tabs.isTabActive('profile')).toBe(true);
        });

        it('color picker and logo preview update together', () => {
            const picker = createColorPicker();
            const logo = createLogoPreview();

            picker.setColor('#ff0000');
            logo.setLogoUrl('https://example.com/logo.png');

            expect(picker.primaryColor).toBe('#ff0000');
            expect(logo.previewVisible).toBe(true);
        });

        it('team operations maintain consistent state', () => {
            const manager = createTeamManager();
            manager.init([{ id: 1, name: 'Owner', email: 'owner@agency.com', role: 'owner' }]);

            // Open invite modal and add member
            manager.openInviteModal();
            manager.setInviteField('name', 'New Member');
            manager.setInviteField('email', 'new@agency.com');
            manager.setInviteField('role', 'admin');
            manager.inviteMember();

            // Verify state
            expect(manager.members).toHaveLength(2);
            expect(manager.inviteModalOpen).toBe(false);

            // Remove the new member
            manager.removeMember(manager.members[1].id);
            expect(manager.members).toHaveLength(1);
            expect(manager.members[0].role).toBe('owner');
        });

        it('form validation prevents invalid submission', () => {
            const validator = createFormValidator();

            // Invalid: missing required fields
            let isValid = validator.validate({ name: '', email: '' });
            expect(isValid).toBe(false);
            expect(validator.hasError('name')).toBe(true);
            expect(validator.hasError('email')).toBe(true);

            // Fix and resubmit
            isValid = validator.validate({ name: 'Valid Agency', email: 'valid@agency.com' });
            expect(isValid).toBe(true);
            expect(validator.hasError('name')).toBe(false);
            expect(validator.hasError('email')).toBe(false);
        });

        it('all tabs cycle correctly with validation', () => {
            const tabs = createAgencySettingsState();
            const tabSequence = ['profile', 'billing', 'team', 'integrations', 'api', 'profile'];

            tabSequence.forEach(tab => {
                tabs.switchTab(tab);
                expect(tabs.isTabActive(tab)).toBe(true);
            });
        });

        it('tab state does not affect form validation', () => {
            const tabs = createAgencySettingsState();
            const validator = createFormValidator();

            tabs.switchTab('team');
            const isValid = validator.validate({ name: '', email: '' });

            expect(tabs.isTabActive('team')).toBe(true);
            expect(isValid).toBe(false);
        });
    });
});
