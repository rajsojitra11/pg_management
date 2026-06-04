/**
 * Universal Session Monitor
 * Handles session expiry detection and multi-tab synchronization for ALL users
 * Works regardless of ENABLE_HISTORICAL_DATA_ENTRY setting
 */

(function () {
    'use strict';

    // Configuration
    const CONFIG = {
        CHECK_INTERVAL: 30000, // 30 seconds
        HEARTBEAT_INTERVAL: 300000, // 5 minutes - send heartbeat to server
        SESSION_WARNING_TIME: 300000, // 5 minutes before expiry
        STORAGE_KEY: 'session_expired',
        WARNING_KEY: 'session_warning',
    };

    // Get session lifetime from server (in milliseconds)
    const SESSION_LIFETIME = (window.SESSION_LIFETIME_MINUTES || 120) * 60 * 1000;

    let lastActivity = Date.now();
    let sessionWarningShown = false;
    let heartbeatInterval = null;
    let checkInterval = null;

    /**
     * Track user activity to reset the activity timer
     */
    function trackActivity() {
        lastActivity = Date.now();
        sessionWarningShown = false; // Reset warning flag on activity
    }

    /**
     * Set up activity tracking
     */
    function initActivityTracking() {
        const events = ['click', 'keypress', 'mousemove', 'scroll', 'touchstart'];
        events.forEach((event) => {
            document.addEventListener(event, trackActivity, { passive: true });
        });
    }

    /**
     * Check if session should have expired based on last activity
     */
    function isSessionExpired() {
        return Date.now() - lastActivity > SESSION_LIFETIME;
    }

    /**
     * Check if session warning should be shown
     */
    function shouldShowWarning() {
        const timeUntilExpiry = SESSION_LIFETIME - (Date.now() - lastActivity);
        return timeUntilExpiry <= CONFIG.SESSION_WARNING_TIME && timeUntilExpiry > 0;
    }

    /**
     * Show session expiry warning
     */
    function showSessionWarning() {
        if (sessionWarningShown) return;

        sessionWarningShown = true;
        const timeLeft = Math.ceil((SESSION_LIFETIME - (Date.now() - lastActivity)) / 60000);

        // Use SweetAlert if available, otherwise use confirm
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Session Expiring',
                text: `Your session will expire in ${timeLeft} minutes. Click "Stay Logged In" to continue.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Stay Logged In',
                cancelButtonText: 'Logout Now',
                timer: 240000, // Auto-dismiss after 4 minutes
                timerProgressBar: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    // User chose to stay - refresh activity and send heartbeat
                    trackActivity();
                    sendHeartbeat();
                } else if (result.isDismissed) {
                    // User chose to logout or timer expired
                    handleSessionExpiry();
                }
            });
        } else {
            const stayLoggedIn = confirm(
                `Your session will expire in ${timeLeft} minutes. Click OK to stay logged in, or Cancel to logout now.`
            );
            if (stayLoggedIn) {
                trackActivity();
                sendHeartbeat();
            } else {
                handleSessionExpiry();
            }
        }
    }

    /**
     * Send heartbeat to server to keep session alive
     */
    function sendHeartbeat() {
        if (!navigator.onLine) return; // Skip if offline

        fetch('/api/session-check', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (response.status === 401 || response.status === 419) {
                    handleSessionExpiry();
                } else if (response.ok) {
                    trackActivity(); // Reset activity timer on successful heartbeat
                }
            })
            .catch((error) => {
                console.warn('Session heartbeat failed:', error);
                // Don't logout on network errors - user might be offline
            });
    }

    /**
     * Check session status with server
     */
    function checkSessionStatus() {
        // Skip check if tab is hidden (performance optimization)
        if (document.hidden) return;

        // Check client-side expiry first
        if (isSessionExpired()) {
            handleSessionExpiry();
            return;
        }

        // Show warning if needed
        if (shouldShowWarning() && !sessionWarningShown) {
            showSessionWarning();
            return;
        }

        // Don't check server if recently active
        if (Date.now() - lastActivity < CONFIG.HEARTBEAT_INTERVAL) {
            return;
        }

        sendHeartbeat();
    }

    /**
     * Handle session expiry
     */
    function handleSessionExpiry() {
        // Clear any existing intervals
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (checkInterval) clearInterval(checkInterval);

        // Broadcast to all tabs
        try {
            localStorage.setItem(CONFIG.STORAGE_KEY, Date.now().toString());
        } catch (e) {
            // LocalStorage might be disabled
            console.warn('Could not broadcast session expiry:', e);
        }

        // Show message and redirect
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Session Expired',
                text: 'Your session has expired. You will be redirected to the login page.',
                icon: 'info',
                timer: 3000,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                window.location.href = '/login?expired=1';
            });
        } else {
            alert('Your session has expired. You will be redirected to the login page.');
            window.location.href = '/login?expired=1';
        }
    }

    /**
     * Handle tab visibility changes
     */
    function handleVisibilityChange() {
        if (!document.hidden) {
            // Tab became visible - check session immediately
            checkSessionStatus();
        }
    }

    /**
     * Listen for session expiry from other tabs
     */
    function initCrossTabCommunication() {
        window.addEventListener('storage', function (e) {
            if (e.key === CONFIG.STORAGE_KEY) {
                // Another tab detected session expiry
                if (heartbeatInterval) clearInterval(heartbeatInterval);
                if (checkInterval) clearInterval(checkInterval);

                window.location.href = '/login?expired=1';
            }
        });
    }

    /**
     * Clear expired parameter from URL if user is authenticated and page loaded normally
     */
    function clearExpiredParameter() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('expired') && window.location.pathname === '/login') {
            // Only clear if we're on login page and user seems to be loading normally
            // (not due to actual session expiry)
            const isActualExpiry = localStorage.getItem(CONFIG.STORAGE_KEY);
            const expireTime = isActualExpiry ? parseInt(isActualExpiry) : 0;
            const timeSinceExpiry = Date.now() - expireTime;

            // If no recent expiry event (>5 minutes ago) or no expiry event, clear the parameter
            if (!isActualExpiry || timeSinceExpiry > 300000) {
                urlParams.delete('expired');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }
        }
    }

    /**
     * Initialize the session monitor
     */
    function init() {
        // Clear expired parameter if appropriate
        clearExpiredParameter();

        // Only run if we have a valid session lifetime
        if (!SESSION_LIFETIME || SESSION_LIFETIME <= 0) {
            console.warn('Invalid session lifetime, session monitor disabled');
            return;
        }

        console.info(`Session monitor initialized - lifetime: ${SESSION_LIFETIME / 60000} minutes`);

        // Set up activity tracking
        initActivityTracking();

        // Set up cross-tab communication
        initCrossTabCommunication();

        // Set up visibility change handler
        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Periodic session check
        checkInterval = setInterval(checkSessionStatus, CONFIG.CHECK_INTERVAL);

        // Periodic heartbeat (less frequent)
        heartbeatInterval = setInterval(sendHeartbeat, CONFIG.HEARTBEAT_INTERVAL);

        // Initial check
        setTimeout(checkSessionStatus, 1000);

        // Track initial activity
        trackActivity();
    }

    /**
     * Cleanup function (called when page unloads)
     */
    function cleanup() {
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (checkInterval) clearInterval(checkInterval);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);

    // Expose public methods for debugging
    window.SessionMonitor = {
        checkStatus: checkSessionStatus,
        sendHeartbeat: sendHeartbeat,
        getLastActivity: () => new Date(lastActivity).toISOString(),
        getTimeUntilExpiry: () => Math.max(0, SESSION_LIFETIME - (Date.now() - lastActivity)),
    };
})();
