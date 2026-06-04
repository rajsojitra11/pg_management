/**
 * Session Storage Manager - Multi-tab coordination system
 * Handles session data synchronization across multiple browser tabs/windows
 */
class SessionStorageManager {
    constructor() {
        this.storagePrefix = 'session_';
        this.tabId = this.generateTabId();
        this.listeners = {};

        this.init();
    }

    /**
     * Initialize storage manager
     */
    init() {
        // Listen for storage events (cross-tab communication)
        window.addEventListener('storage', (event) => {
            if (event.key?.startsWith(this.storagePrefix)) {
                this.handleStorageEvent(event);
            }
        });

        // Register this tab
        this.registerTab();

        // Cleanup inactive tabs periodically
        setInterval(() => {
            this.cleanupInactiveTabs();
        }, 30000); // Every 30 seconds
    }

    /**
     * Generate unique tab ID
     */
    generateTabId() {
        return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Register this tab as active
     */
    registerTab() {
        const tabData = {
            id: this.tabId,
            url: window.location.href,
            title: document.title,
            timestamp: Date.now(),
        };

        localStorage.setItem(`${this.storagePrefix}tab_${this.tabId}`, JSON.stringify(tabData));
        this.broadcastMessage('tabRegistered', tabData);
    }

    /**
     * Broadcast message to other tabs
     */
    broadcastMessage(action, data) {
        const message = {
            tabId: this.tabId,
            action: action,
            data: data,
            timestamp: Date.now(),
        };

        localStorage.setItem(`${this.storagePrefix}message_${action}`, JSON.stringify(message));

        // Clean up message after 5 seconds
        setTimeout(() => {
            localStorage.removeItem(`${this.storagePrefix}message_${action}`);
        }, 5000);
    }

    /**
     * Handle storage events from other tabs
     */
    handleStorageEvent(event) {
        if (event.key.includes('_message_')) {
            const action = event.key.split('_message_')[1];
            const message = JSON.parse(event.newValue || '{}');

            // Ignore messages from this tab
            if (message.tabId === this.tabId) return;

            this.triggerListener(action, message);
        }
    }

    /**
     * Add event listener for cross-tab messages
     */
    on(action, callback) {
        if (!this.listeners[action]) {
            this.listeners[action] = [];
        }
        this.listeners[action].push(callback);
    }

    /**
     * Remove event listener
     */
    off(action, callback) {
        if (this.listeners[action]) {
            this.listeners[action] = this.listeners[action].filter((cb) => cb !== callback);
        }
    }

    /**
     * Trigger listeners for an action
     */
    triggerListener(action, message) {
        if (this.listeners[action]) {
            this.listeners[action].forEach((callback) => {
                try {
                    callback(message);
                } catch (error) {
                    console.error('Error in session storage listener:', error);
                }
            });
        }
    }

    /**
     * Get all active tabs
     */
    getActiveTabs() {
        const tabs = [];
        const now = Date.now();
        const maxAge = 60000; // 1 minute

        for (let key in localStorage) {
            if (key.startsWith(`${this.storagePrefix}tab_`)) {
                try {
                    const tabData = JSON.parse(localStorage.getItem(key));
                    if (now - tabData.timestamp < maxAge) {
                        tabs.push(tabData);
                    }
                } catch (error) {
                    // Invalid tab data, remove it
                    localStorage.removeItem(key);
                }
            }
        }

        return tabs;
    }

    /**
     * Clean up inactive tabs
     */
    cleanupInactiveTabs() {
        const now = Date.now();
        const maxAge = 120000; // 2 minutes

        for (let key in localStorage) {
            if (key.startsWith(`${this.storagePrefix}tab_`)) {
                try {
                    const tabData = JSON.parse(localStorage.getItem(key));
                    if (now - tabData.timestamp > maxAge) {
                        localStorage.removeItem(key);
                    }
                } catch (error) {
                    localStorage.removeItem(key);
                }
            }
        }

        // Update this tab's timestamp
        this.registerTab();
    }

    /**
     * Sync form data across tabs
     */
    syncFormData(formId, data) {
        this.broadcastMessage('formDataSync', { formId, data });
    }

    /**
     * Handle form data preservation during session expiry
     */
    preserveFormData() {
        const forms = document.querySelectorAll('form[data-preserve="true"]');
        const formData = {};

        forms.forEach((form) => {
            const formId = form.id || form.dataset.formId;
            if (formId) {
                formData[formId] = this.extractFormData(form);
            }
        });

        if (Object.keys(formData).length > 0) {
            localStorage.setItem(`${this.storagePrefix}preserved_forms`, JSON.stringify(formData));
            this.broadcastMessage('formsPreserved', formData);
        }
    }

    /**
     * Restore preserved form data
     */
    restoreFormData() {
        const preservedData = localStorage.getItem(`${this.storagePrefix}preserved_forms`);
        if (!preservedData) return;

        try {
            const formData = JSON.parse(preservedData);

            for (let formId in formData) {
                const form = document.getElementById(formId) || document.querySelector(`[data-form-id="${formId}"]`);
                if (form) {
                    this.populateForm(form, formData[formId]);
                }
            }

            // Clear preserved data after restoration
            localStorage.removeItem(`${this.storagePrefix}preserved_forms`);
        } catch (error) {
            console.error('Error restoring form data:', error);
        }
    }

    /**
     * Extract form data
     */
    extractFormData(form) {
        const data = {};
        const formData = new FormData(form);

        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                // Handle multiple values (checkboxes, select multiple)
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        return data;
    }

    /**
     * Populate form with data
     */
    populateForm(form, data) {
        for (let key in data) {
            const elements = form.querySelectorAll(`[name="${key}"]`);

            elements.forEach((element) => {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    const values = Array.isArray(data[key]) ? data[key] : [data[key]];
                    element.checked = values.includes(element.value);
                } else if (element.tagName === 'SELECT') {
                    element.value = data[key];
                } else {
                    element.value = data[key];
                }
            });
        }
    }

    /**
     * Handle session expiry across tabs
     */
    handleSessionExpiry() {
        this.preserveFormData();
        this.broadcastMessage('sessionExpired', { timestamp: Date.now() });
    }

    /**
     * Handle session extension across tabs
     */
    handleSessionExtended() {
        this.broadcastMessage('sessionExtended', { timestamp: Date.now() });
    }

    /**
     * Destroy session storage manager
     */
    destroy() {
        // Remove this tab's registration
        localStorage.removeItem(`${this.storagePrefix}tab_${this.tabId}`);
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sessionStorageManager = new SessionStorageManager();
    });
} else {
    window.sessionStorageManager = new SessionStorageManager();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.sessionStorageManager) {
        window.sessionStorageManager.destroy();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionStorageManager;
}
