/**
 * Session Manager - Advanced multi-tab session handling
 * Handles session expiration warnings, automatic extensions, and multi-tab coordination
 */
class SessionManager {
    constructor(options = {}) {
        this.config = {
            warningTime: 300, // 5 minutes in seconds
            heartbeatInterval: 60, // 1 minute
            autoExtendThreshold: 600, // 10 minutes
            checkInterval: 30, // 30 seconds
            ...options,
        };

        this.isAuthenticated = false;
        this.sessionStatus = {};
        this.warningActive = false;
        this.intervals = {};
        this.lastActivity = Date.now();
        this.tabId = this.generateTabId();

        this.init();
    }

    /**
     * Clear expired parameter from URL if appropriate
     */
    clearExpiredParameter() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('expired') && window.location.pathname === '/login') {
            // Only clear if user is authenticated or enough time has passed since expiry
            if (this.isAuthenticated) {
                urlParams.delete('expired');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }
        }
    }

    /**
     * Initialize session manager
     */
    async init() {
        try {
            // Load session configuration from server
            await this.loadConfig();

            // Clear expired parameter if user is authenticated
            this.clearExpiredParameter();

            // Start monitoring if authenticated
            if (this.isAuthenticated) {
                this.startMonitoring();
                this.setupActivityTracking();
                this.setupMultiTabCoordination();
                this.createSessionIndicator();
            }

            // console.log('SessionManager initialized', {
            //     tabId: this.tabId,
            //     authenticated: this.isAuthenticated,
            //     config: this.config
            // });
        } catch (error) {
            console.error('Failed to initialize SessionManager:', error);
        }
    }

    /**
     * Load session configuration from server
     */
    async loadConfig() {
        try {
            const response = await fetch('/api/session/config');
            const serverConfig = await response.json();

            this.config = { ...this.config, ...serverConfig };
            this.isAuthenticated = serverConfig.authenticated;
        } catch (error) {
            console.warn('Failed to load session config:', error);
        }
    }

    /**
     * Start session monitoring
     */
    startMonitoring() {
        // Regular status check
        this.intervals.statusCheck = setInterval(() => {
            this.checkSessionStatus();
        }, this.config.checkInterval * 1000);

        // Heartbeat
        this.intervals.heartbeat = setInterval(() => {
            this.sendHeartbeat();
        }, this.config.heartbeatInterval * 1000);

        // Initial status check
        this.checkSessionStatus();
    }

    /**
     * Check current session status
     */
    async checkSessionStatus() {
        try {
            const response = await fetch('/api/session/status');
            const status = await response.json();

            if (!status.authenticated) {
                this.handleSessionExpired();
                return;
            }

            this.sessionStatus = status;
            this.updateSessionIndicator(status);
            this.handleWarningState(status);
            this.broadcastToTabs('sessionStatus', status);
        } catch (error) {
            console.warn('Session status check failed:', error);
        }
    }

    /**
     * Send heartbeat to server
     */
    async sendHeartbeat() {
        try {
            const response = await fetch('/api/session/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    tab_id: this.tabId,
                    last_activity: this.lastActivity,
                }),
            });

            const result = await response.json();

            if (!result.authenticated) {
                this.handleSessionExpired();
            }
        } catch (error) {
            console.warn('Heartbeat failed:', error);
        }
    }

    /**
     * Handle warning state
     */
    handleWarningState(status) {
        if (status.is_warning && !this.warningActive) {
            this.showSessionWarning(status);
        } else if (!status.is_warning && this.warningActive) {
            this.hideSessionWarning();
        }
    }

    /**
     * Show session expiration warning
     */
    showSessionWarning(status) {
        this.warningActive = true;

        // Remove existing warning
        this.hideSessionWarning();

        // Create warning modal
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <h3>Session Expiring Soon</h3>
                <p>Your session will expire in <span id="session-countdown">${Math.ceil(status.time_remaining_minutes)}</span> minutes.</p>
                <p>Would you like to extend your session?</p>
                <div class="session-warning-buttons">
                    <button id="extend-session-btn" class="btn btn-primary">Extend Session</button>
                    <button id="logout-session-btn" class="btn btn-secondary">Logout Now</button>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-warning-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            }
            .session-warning-content {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            .session-warning-buttons {
                margin-top: 1rem;
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            .session-warning-buttons .btn {
                padding: 0.5rem 1rem;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .btn-primary {
                background: #007bff;
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Setup event listeners
        document.getElementById('extend-session-btn').addEventListener('click', () => {
            this.extendSession();
        });

        document.getElementById('logout-session-btn').addEventListener('click', () => {
            window.location.href = '/logout';
        });

        // Start countdown
        this.startWarningCountdown(status.time_remaining);

        // Broadcast warning to other tabs
        if (window.sessionStorageManager) {
            window.sessionStorageManager.broadcastMessage('sessionWarning', {
                active: true,
                timeRemaining: status.time_remaining,
            });
        }
    }

    /**
     * Hide session warning
     */
    hideSessionWarning() {
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }

        if (this.intervals.warningCountdown) {
            clearInterval(this.intervals.warningCountdown);
        }

        this.warningActive = false;
        if (window.sessionStorageManager) {
            window.sessionStorageManager.broadcastMessage('sessionWarning', { active: false });
        }
    }

    /**
     * Start warning countdown
     */
    startWarningCountdown(timeRemaining) {
        const countdownElement = document.getElementById('session-countdown');
        if (!countdownElement) return;

        let remaining = Math.ceil(timeRemaining);

        this.intervals.warningCountdown = setInterval(() => {
            remaining--;
            const minutes = Math.ceil(remaining / 60);

            if (countdownElement) {
                countdownElement.textContent = minutes;
            }

            if (remaining <= 0) {
                this.handleSessionExpired();
            }
        }, 1000);
    }

    /**
     * Extend session
     */
    async extendSession() {
        try {
            const response = await fetch('/api/session/extend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });

            const result = await response.json();

            if (result.success) {
                this.hideSessionWarning();
                this.showNotification('Session extended successfully!', 'success');
                if (window.sessionStorageManager) {
                    window.sessionStorageManager.broadcastMessage('sessionExtended', result);
                    window.sessionStorageManager.handleSessionExtended();
                }
            } else {
                this.showNotification('Failed to extend session', 'error');
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
            this.showNotification('Failed to extend session', 'error');
        }
    }

    /**
     * Handle session expired
     */
    handleSessionExpired() {
        this.cleanup();

        // Preserve form data and notify other tabs
        if (window.sessionStorageManager) {
            window.sessionStorageManager.handleSessionExpiry();
        }

        // Show expired message and redirect
        this.showNotification('Your session has expired. You will be redirected to login.', 'warning');

        setTimeout(() => {
            window.location.href = '/login?expired=1';
        }, 3000);
    }

    /**
     * Setup activity tracking
     */
    setupActivityTracking() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

        const trackActivity = () => {
            this.lastActivity = Date.now();
            localStorage.setItem('lastActivity', this.lastActivity.toString());
        };

        events.forEach((event) => {
            document.addEventListener(event, trackActivity, true);
        });

        // Auto-extend session on activity
        setInterval(() => {
            const timeSinceActivity = Date.now() - this.lastActivity;
            const shouldAutoExtend = timeSinceActivity < this.config.autoExtendThreshold * 1000;

            if (shouldAutoExtend && this.sessionStatus.is_warning) {
                this.extendSession();
            }
        }, 30000); // Check every 30 seconds
    }

    /**
     * Setup multi-tab coordination using SessionStorageManager
     */
    setupMultiTabCoordination() {
        // Wait for SessionStorageManager to be available
        if (window.sessionStorageManager) {
            this.setupStorageListeners();
        } else {
            // Wait for initialization
            const checkManager = () => {
                if (window.sessionStorageManager) {
                    this.setupStorageListeners();
                } else {
                    setTimeout(checkManager, 100);
                }
            };
            checkManager();
        }
    }

    /**
     * Setup storage event listeners
     */
    setupStorageListeners() {
        // Listen for session events from other tabs
        window.sessionStorageManager.on('sessionExpired', (message) => {
            this.handleSessionExpired();
        });

        window.sessionStorageManager.on('sessionExtended', (message) => {
            this.hideSessionWarning();
            this.showNotification('Session extended from another tab', 'info');
        });

        window.sessionStorageManager.on('sessionWarning', (message) => {
            if (message.data.active && !this.warningActive) {
                this.showSessionWarning({
                    time_remaining: message.data.timeRemaining,
                    time_remaining_minutes: message.data.timeRemaining / 60,
                });
            }
        });
    }

    /**
     * Broadcast message to other tabs
     */
    broadcastToTabs(action, data) {
        const message = {
            tabId: this.tabId,
            timestamp: Date.now(),
            data: data,
        };

        localStorage.setItem(`sessionManager_${action}`, JSON.stringify(message));

        // Clean up old messages
        setTimeout(() => {
            localStorage.removeItem(`sessionManager_${action}`);
        }, 5000);
    }

    /**
     * Handle messages from other tabs
     */
    handleTabMessage(action, message) {
        // Ignore messages from this tab
        if (message.tabId === this.tabId) return;

        switch (action) {
            case 'sessionExpired':
                this.handleSessionExpired();
                break;
            case 'sessionExtended':
                this.hideSessionWarning();
                this.showNotification('Session extended from another tab', 'info');
                break;
            case 'sessionWarning':
                if (message.data.active && !this.warningActive) {
                    this.showSessionWarning({
                        time_remaining: message.data.timeRemaining,
                        time_remaining_minutes: message.data.timeRemaining / 60,
                    });
                }
                break;
        }
    }

    /**
     * Create session status indicator
     */
    createSessionIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'session-status-indicator';
        indicator.className = 'session-status-indicator';
        indicator.innerHTML = `
            <div class="session-indicator-content">
                <span class="session-status-dot"></span>
                <span class="session-status-text">Session Active</span>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-status-indicator {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: white;
                padding: 8px 12px;
                border-radius: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border: 1px solid #ddd;
                font-size: 12px;
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .session-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #28a745;
                animation: pulse 2s infinite;
            }
            .session-status-dot.warning {
                background: #ffc107;
            }
            .session-status-dot.error {
                background: #dc3545;
            }
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        `;

        if (!document.querySelector('#session-indicator-styles')) {
            style.id = 'session-indicator-styles';
            document.head.appendChild(style);
        }

        document.body.appendChild(indicator);
    }

    /**
     * Update session status indicator
     */
    updateSessionIndicator(status) {
        const indicator = document.getElementById('session-status-indicator');
        if (!indicator) return;

        const dot = indicator.querySelector('.session-status-dot');
        const text = indicator.querySelector('.session-status-text');

        if (status.is_warning) {
            dot.className = 'session-status-dot warning';
            text.textContent = `Expires in ${status.time_remaining_minutes}m`;
        } else {
            dot.className = 'session-status-dot';
            text.textContent = 'Session Active';
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `session-notification ${type}`;
        notification.textContent = message;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 4px;
                color: white;
                font-weight: bold;
                z-index: 10001;
                max-width: 300px;
            }
            .session-notification.success { background: #28a745; }
            .session-notification.error { background: #dc3545; }
            .session-notification.warning { background: #ffc107; color: #333; }
            .session-notification.info { background: #17a2b8; }
        `;

        if (!document.querySelector('#notification-styles')) {
            style.id = 'notification-styles';
            document.head.appendChild(style);
        }

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Generate unique tab ID
     */
    generateTabId() {
        return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Cleanup intervals and listeners
     */
    cleanup() {
        Object.values(this.intervals).forEach((interval) => {
            if (interval) clearInterval(interval);
        });

        this.intervals = {};
        this.hideSessionWarning();
    }

    /**
     * Destroy session manager
     */
    destroy() {
        this.cleanup();

        const indicator = document.getElementById('session-status-indicator');
        if (indicator) indicator.remove();

        const styles = document.querySelector('#session-indicator-styles');
        if (styles) styles.remove();
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sessionManager = new SessionManager();
    });
} else {
    window.sessionManager = new SessionManager();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
}
