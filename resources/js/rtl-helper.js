/**
 * RTL Helper JavaScript for Digital Marketing SaaS v7.0
 *
 * Provides utilities for detecting RTL locale, flipping icon directions,
 * adjusting dropdown positions, and mirroring sidebar behavior.
 */

/**
 * Detect if the current document direction is RTL.
 *
 * @returns {boolean} True if the document direction is 'rtl'.
 */
function detectRTL() {
    return document.documentElement.dir === 'rtl';
}

/**
 * Flip icon direction for RTL layouts.
 *
 * Adds a CSS transform to mirror icons that have directional meaning
 * (arrows, chevrons, etc.). Automatically runs on DOMContentLoaded
 * and after Alpine.js renders new content.
 *
 * @param {HTMLElement} [context=document] - The root element to search within.
 */
function flipIconDirection(context = document) {
    if (!detectRTL()) {
        return;
    }

    const flipSelectors = [
        '.fa-arrow-left',
        '.fa-arrow-right',
        '.fa-chevron-left',
        '.fa-chevron-right',
        '.fa-caret-left',
        '.fa-caret-right',
        '.fa-angle-left',
        '.fa-angle-right',
        '.fa-angle-double-left',
        '.fa-angle-double-right',
        '.fa-sign-out-alt',
        '.fa-reply',
        '.fa-share',
        '.fa-paper-plane',
        '.fa-external-link-alt',
    ];

    const icons = context.querySelectorAll(flipSelectors.join(','));
    icons.forEach((icon) => {
        icon.style.transform = 'scaleX(-1)';
    });
}

/**
 * Adjust dropdown menu positions for RTL.
 *
 * In RTL mode, dropdowns anchored to `right: 0` in LTR need to be
 * anchored to `left: 0` instead so they don't overflow the viewport.
 *
 * @param {HTMLElement} [context=document] - The root element to search within.
 */
function adjustDropdowns(context = document) {
    if (!detectRTL()) {
        return;
    }

    const dropdowns = context.querySelectorAll('.absolute.right-0');
    dropdowns.forEach((dropdown) => {
        dropdown.classList.remove('right-0');
        dropdown.classList.add('left-0');
    });
}

/**
 * Mirror sidebar behavior for RTL.
 *
 * In RTL, the sidebar is on the right side. The toggle logic and
 * translation directions need to be reversed from the LTR behavior.
 *
 * @returns {Object} Sidebar state and helper methods for Alpine.
 */
function mirrorSidebar() {
    const isRTL = detectRTL();

    return {
        isRTL,
        sidebarOpen: false,

        /**
         * Get the correct CSS class for sidebar visibility.
         */
        sidebarTranslateClass() {
            if (!isRTL) {
                return this.sidebarOpen ? 'translate-x-0' : '-translate-x-full';
            }
            return this.sidebarOpen ? 'translate-x-0' : 'translate-x-full';
        },

        /**
         * Get the correct sidebar position class.
         */
        sidebarPositionClass() {
            return isRTL ? 'right-0' : 'left-0';
        },
    };
}

/**
 * Apply all RTL adjustments at once.
 *
 * Convenience function that runs flipIconDirection, adjustDropdowns,
 * and any other needed transformations.
 *
 * @param {HTMLElement} [context=document] - The root element to search within.
 */
function applyRTLAdjustments(context = document) {
    if (!detectRTL()) {
        return;
    }

    flipIconDirection(context);
    adjustDropdowns(context);

    // Announce RTL mode to screen readers for debugging
    const announcement = document.getElementById('sr-announcements');
    if (announcement) {
        announcement.textContent = 'RTL layout active';
    }
}

/**
 * Initialize RTL helpers on page load.
 */
document.addEventListener('DOMContentLoaded', () => {
    applyRTLAdjustments();
});

/**
 * Re-apply after Alpine.js has rendered.
 * Alpine dispatches 'alpine:initialized' when ready.
 */
document.addEventListener('alpine:initialized', () => {
    applyRTLAdjustments();
});

/**
 * Expose helpers globally for Alpine components and inline scripts.
 */
window.RTLHelper = {
    detectRTL,
    flipIconDirection,
    adjustDropdowns,
    mirrorSidebar,
    applyRTLAdjustments,
};
