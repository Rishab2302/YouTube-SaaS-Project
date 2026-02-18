/**
 * TaskFlow Application JavaScript
 * Handles theme toggling, HTMX integration, and Alpine.js interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('TaskFlow application initialized');

    // Initialize theme system
    initializeThemeToggle();

    // Initialize HTMX listeners
    initializeHTMXListeners();

    // Initialize toast notifications
    initializeToastSystem();

    // Initialize Bootstrap components
    initializeBootstrapComponents();
});

/**
 * Bootstrap 5.3 Color Mode Toggler
 * Based on official Bootstrap documentation
 */
function initializeThemeToggle() {
    const getStoredTheme = () => localStorage.getItem('theme');
    const setStoredTheme = theme => localStorage.setItem('theme', theme);

    const getPreferredTheme = () => {
        const storedTheme = getStoredTheme();
        if (storedTheme) {
            return storedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const setTheme = theme => {
        if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', theme);
        }
    };

    // Set theme on load
    setTheme(getPreferredTheme());

    const showActiveTheme = (theme, focus = false) => {
        const themeSwitchers = document.querySelectorAll('[data-bs-theme-value]');
        const activeThemeIcon = document.querySelector('#theme-toggle i, #auth-theme-btn i');

        if (!themeSwitchers.length) return;

        // Update all theme switcher buttons
        themeSwitchers.forEach(element => {
            element.classList.remove('active');
            element.setAttribute('aria-pressed', 'false');

            if (element.getAttribute('data-bs-theme-value') === theme) {
                element.classList.add('active');
                element.setAttribute('aria-pressed', 'true');
                if (focus) element.focus();
            }
        });

        // Update the theme toggle icon
        if (activeThemeIcon) {
            const iconMap = {
                'light': 'bi-sun-fill',
                'dark': 'bi-moon-stars-fill',
                'auto': 'bi-circle-half'
            };

            // Remove all theme icons
            activeThemeIcon.className = activeThemeIcon.className.replace(/bi-sun-fill|bi-moon-stars-fill|bi-circle-half/g, '');
            activeThemeIcon.classList.add(iconMap[theme] || iconMap.auto);
        }
    };

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const storedTheme = getStoredTheme();
        if (storedTheme !== 'light' && storedTheme !== 'dark') {
            setTheme(getPreferredTheme());
        }
    });

    // Add click listeners to theme switchers
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('[data-bs-theme-value]');
        if (!toggle) return;

        e.preventDefault();
        const theme = toggle.getAttribute('data-bs-theme-value');

        setStoredTheme(theme);
        setTheme(theme);
        showActiveTheme(theme, true);

        // Close dropdown
        const dropdown = bootstrap.Dropdown.getInstance(toggle.closest('.dropdown-toggle'));
        if (dropdown) {
            dropdown.hide();
        }
    });

    showActiveTheme(getPreferredTheme());
}

/**
 * HTMX Event Listeners and Configuration
 */
function initializeHTMXListeners() {
    // Reinitialize Bootstrap components after HTMX swaps
    document.body.addEventListener('htmx:afterSwap', function(evt) {
        // Reinitialize tooltips in the swapped content
        const tooltipTriggerList = evt.detail.target.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Reinitialize popovers
        const popoverTriggerList = evt.detail.target.querySelectorAll('[data-bs-toggle="popover"]');
        popoverTriggerList.forEach(popoverTriggerEl => {
            new bootstrap.Popover(popoverTriggerEl);
        });

        // Focus first input in forms
        const firstInput = evt.detail.target.querySelector('input[type="text"], input[type="email"], textarea');
        if (firstInput) {
            firstInput.focus();
        }
    });

    // Handle HTMX errors
    document.body.addEventListener('htmx:responseError', function(evt) {
        console.error('HTMX Error:', evt.detail);
        showToast('error', 'Request failed. Please try again.');
    });

    // Handle HTMX network errors
    document.body.addEventListener('htmx:sendError', function(evt) {
        console.error('HTMX Network Error:', evt.detail);
        showToast('error', 'Network error. Please check your connection.');
    });

    // Show loading state for longer requests
    document.body.addEventListener('htmx:beforeRequest', function(evt) {
        const trigger = evt.detail.elt;

        // Add loading state to buttons
        if (trigger.tagName === 'BUTTON' || trigger.type === 'submit') {
            const originalText = trigger.innerHTML;
            trigger.dataset.originalText = originalText;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...';
            trigger.disabled = true;
        }
    });

    // Remove loading state after request
    document.body.addEventListener('htmx:afterRequest', function(evt) {
        const trigger = evt.detail.elt;

        // Restore button state
        if (trigger.tagName === 'BUTTON' || trigger.type === 'submit') {
            if (trigger.dataset.originalText) {
                trigger.innerHTML = trigger.dataset.originalText;
                delete trigger.dataset.originalText;
            }
            trigger.disabled = false;
        }
    });

    // Handle custom HTMX events
    document.body.addEventListener('showToast', function(evt) {
        const detail = evt.detail.value || evt.detail;
        showToast(detail.type || 'info', detail.message || 'Action completed');
    });

    // Configure HTMX
    if (typeof htmx !== 'undefined') {
        // Set global configuration
        htmx.config.globalViewTransitions = true;
        htmx.config.refreshOnHistoryMiss = true;
        htmx.config.defaultSwapDelay = 100;
        htmx.config.defaultSettleDelay = 100;
    }
}

/**
 * Toast Notification System
 */
function initializeToastSystem() {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Listen for toast events from Alpine.js
    window.addEventListener('showToast', function(event) {
        const { type, message } = event.detail;
        showToast(type, message);
    });
}

/**
 * Show a toast notification
 */
function showToast(type = 'info', message = '', duration = 5000) {
    const toastContainer = document.getElementById('toast-container') ||
                          document.querySelector('.toast-container');

    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }

    const toastId = 'toast-' + Date.now();
    const iconMap = {
        'success': 'bi-check-circle-fill',
        'error': 'bi-exclamation-triangle-fill',
        'warning': 'bi-exclamation-triangle-fill',
        'info': 'bi-info-circle-fill'
    };

    const colorMap = {
        'success': 'text-bg-success',
        'error': 'text-bg-danger',
        'warning': 'text-bg-warning',
        'info': 'text-bg-info'
    };

    const toastHTML = `
        <div class="toast ${colorMap[type] || colorMap.info}" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="${iconMap[type] || iconMap.info} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });

    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });

    toast.show();
}

/**
 * Initialize Bootstrap Components
 */
function initializeBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Initialize popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Utility Functions
 */

// Format dates consistently
function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };

    return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
}

// Format relative time (e.g., "2 days ago")
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + ' days ago';

    return formatDate(dateString);
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Copy to clipboard utility
function copyToClipboard(text, successMessage = 'Copied to clipboard') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('success', successMessage);
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showToast('error', 'Failed to copy to clipboard');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            showToast('success', successMessage);
        } catch (err) {
            console.error('Failed to copy: ', err);
            showToast('error', 'Failed to copy to clipboard');
        }

        document.body.removeChild(textArea);
    }
}

// Global functions available to all pages
window.TaskFlow = {
    showToast,
    formatDate,
    formatRelativeTime,
    copyToClipboard,
    debounce
};

console.log('TaskFlow JavaScript initialized successfully');