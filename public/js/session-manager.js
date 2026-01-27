/**
 * Universal Session Manager for QR Feeding System
 * Standalone class - no auto-initialization
 */
class UniversalSessionManager {
    constructor(options = {}) {
        // Don't run if no CSRF token
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!tokenMeta) {
            console.warn('Session Manager: CSRF token not found');
            return;
        }

        // Don't run on authentication pages
        if (this.isAuthPage()) {
            console.log('Session Manager: Skipping on auth page');
            return;
        }

        this.csrfToken = tokenMeta.getAttribute('content');
        this.lastActivity = Date.now();
        this.sessionTimeout = null;
        this.isExpired = false;
        this.isActive = true;
        this.modalVisible = false;

        // Configuration with defaults
        this.config = {
            userType: options.userType || 'user',
            keepAliveUrl: options.keepAliveUrl || '/session/keep-alive',
            loginUrl: options.loginUrl || '/login',
            logoutUrl: options.logoutUrl || '/logout',
            warningTime: options.warningTime || 5 * 60 * 1000, // 5 minutes
            checkInterval: options.checkInterval || 60 * 1000, // 1 minute
            keepAliveInterval: options.keepAliveInterval || 10 * 60 * 1000, // 10 minutes
            initialDelay: options.initialDelay || 30000, // 30 seconds
            showWarningModal: options.showWarningModal !== false,
            ...options
        };

        // Store original fetch for AJAX interception
        this.originalFetch = window.fetch;

        this.initialize();
    }

    /**
     * Check if current page is an authentication page
     */
    isAuthPage() {
        const path = window.location.pathname.toLowerCase();
        const authPages = [
            '/login',
            '/register',
            '/password',
            '/two-factor',
            '/forgot-password',
            '/reset-password'
        ];
        return authPages.some(authPath => path.startsWith(authPath));
    }

    /**
     * Initialize the session manager
     */
    initialize() {
        console.log(`✅ Session Manager initialized for ${this.config.userType}`);

        // Inject CSS if not already present
        this.injectStyles();

        this.trackActivity();
        this.startKeepAlive();
        this.startSessionChecker();
        this.interceptAjax();
    }

    /**
     * Inject necessary CSS styles
     */
    injectStyles() {
        // Only inject if not already present
        if (document.getElementById('session-manager-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'session-manager-styles';
        style.textContent = `
            @keyframes session-fade-in {
                from { opacity: 0; transform: translateY(-10px) scale(0.95); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            .session-animate-fade-in {
                animation: session-fade-in 0.3s ease-out forwards;
            }
            .session-toast {
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Track user activity
     */
    trackActivity() {
        const events = ['click', 'keypress', 'mousemove', 'scroll', 'touchstart', 'mousedown'];

        const activityHandler = () => {
            this.lastActivity = Date.now();

            // Clear any existing timeout warning
            if (this.sessionTimeout) {
                clearTimeout(this.sessionTimeout);
                this.sessionTimeout = null;
            }

            // Hide warning modal if shown
            this.hideWarningModal();
        };

        events.forEach(event => {
            document.addEventListener(event, activityHandler, { passive: true });
        });

        // Store handler for cleanup (if needed)
        this.activityHandler = activityHandler;
    }

    /**
     * Start keep-alive requests
     */
    startKeepAlive() {
        // Wait before first keep-alive
        setTimeout(() => {
            this.sendKeepAlive();
            this.keepAliveIntervalId = setInterval(() => this.sendKeepAlive(), this.config.keepAliveInterval);
        }, this.config.initialDelay);
    }

    /**
     * Start session checking
     */
    startSessionChecker() {
        this.sessionCheckIntervalId = setInterval(() => {
            this.checkSession();
        }, this.config.checkInterval);
    }

    /**
     * Check session status
     */
    async checkSession() {
        if (this.isExpired) return;

        try {
            const response = await fetch(this.config.keepAliveUrl, {
                method: 'HEAD',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (response.status === 401 || response.status === 419) {
                this.showSessionExpired();
            } else if (response.status === 200) {
                // Session is still valid
                const timeSinceActivity = Date.now() - this.lastActivity;
                if (timeSinceActivity > this.config.warningTime && this.config.showWarningModal) {
                    this.showSessionWarning(timeSinceActivity);
                }
            }
        } catch (error) {
            console.warn('Session check failed:', error);
        }
    }

    /**
     * Send keep-alive request
     */
    async sendKeepAlive() {
        if (this.isExpired) return;

        try {
            const response = await fetch(this.config.keepAliveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ keep_alive: true })
            });

            if (response.status === 401 || response.status === 419) {
                let data = null;
                try {
                    data = await response.json();
                } catch (_) {}

                if (data && data.session_expired === true) {
                    this.showSessionExpired();
                }
                return;
            }

            const result = await response.json();
            if (result && result.success) {
                console.log('✅ Session keep-alive successful');
            }
            return result;
        } catch (error) {
            console.warn('Session keep-alive failed:', error);
        }
    }

    /**
     * Intercept AJAX requests to check for session expiration
     */
   /**
 * Intercept AJAX requests to check for session expiration
 */
/**
 * Intercept AJAX requests to check for session expiration
 */
interceptAjax() {
    const self = this;
    const originalFetch = window.fetch;

    window.fetch = async (...args) => {
        let [url, options = {}] = args;

        try {
            // Ensure options has proper headers for same-origin requests
            if (options.method && options.method.toUpperCase() !== 'GET' &&
                url.startsWith('/') && !url.includes('/session/keep-alive')) {

                // Preserve existing headers
                options.headers = options.headers || {};

                // Ensure CSRF token is set for Laravel requests
                if (!options.headers['X-CSRF-TOKEN']) {
                    options.headers['X-CSRF-TOKEN'] = self.csrfToken;
                }

                // Ensure X-Requested-With header for AJAX identification
                if (!options.headers['X-Requested-With']) {
                    options.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
            }

            // Call original fetch with modified options
            const response = await originalFetch(url, options);

            if (response.status === 401 || response.status === 419) {
                try {
                    const data = await response.clone().json();
                    if (data && data.session_expired === true) {
                        self.showSessionExpired();
                        return Promise.reject(new Error('Session expired'));
                    }
                } catch (_) {}
            }

            return response;
        } catch (error) {
            if (error.message !== 'Session expired') {
                console.warn('Fetch error:', error);
                throw error;
            }
            return Promise.reject(error);
        }
    };
}

    /**
     * Show session warning modal
     */
    showSessionWarning(timeSinceActivity) {
        // Don't show multiple warnings
        if (this.sessionTimeout || document.getElementById('sessionWarningModal') || this.modalVisible) {
            return;
        }

        const timeLeft = this.config.warningTime * 2 - timeSinceActivity;
        const minutes = Math.ceil(timeLeft / 60000);

        if (minutes <= 0) return;

        // Create warning modal
        const modal = document.createElement('div');
        modal.id = 'sessionWarningModal';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl session-animate-fade-in">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Session About to Expire</h3>
                            <p class="text-sm text-gray-600">Your session will expire in ${minutes} minute${minutes !== 1 ? 's' : ''}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div class="bg-yellow-500 h-2 rounded-full transition-all duration-500" id="sessionProgressBar"></div>
                        </div>
                        <div class="text-xs text-gray-500 flex justify-between">
                            <span>Active</span>
                            <span>Expiring</span>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-3">
                        <button id="extendSessionBtn"
                                class="w-full bg-secondary-blue text-white py-2.5 rounded-lg font-medium hover:bg-blue-600 transition-colors flex items-center justify-center">
                            <i class="fas fa-sync-alt mr-2"></i>Stay Logged In
                        </button>
                        <button id="logoutNowBtn"
                                class="w-full bg-gray-100 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout Now
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modalVisible = true;

        // Animate progress bar
        const progressBar = document.getElementById('sessionProgressBar');
        const percentage = Math.min(100, (timeSinceActivity - this.config.warningTime) / (this.config.warningTime) * 100);
        setTimeout(() => {
            progressBar.style.width = `${percentage}%`;
        }, 100);

        // Add event listeners
        document.getElementById('extendSessionBtn').addEventListener('click', () => {
            this.extendSession();
            this.hideWarningModal();
        });

        document.getElementById('logoutNowBtn').addEventListener('click', () => {
            this.logout();
        });

        // Auto-hide after warning time
        this.sessionTimeout = setTimeout(() => {
            this.hideWarningModal();
        }, Math.min(timeLeft, this.config.warningTime));
    }

    /**
     * Hide warning modal
     */
    hideWarningModal() {
        const modal = document.getElementById('sessionWarningModal');
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.95)';
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.remove();
                }
                this.modalVisible = false;
            }, 300);
        }
        if (this.sessionTimeout) {
            clearTimeout(this.sessionTimeout);
            this.sessionTimeout = null;
        }
    }

    /**
     * Extend session
     */
    async extendSession() {
        try {
            const result = await this.sendKeepAlive();
            if (result && result.success) {
                this.lastActivity = Date.now();
                this.showToast('Session extended!', 'success');
                return true;
            }
        } catch (error) {
            this.showToast('Failed to extend session', 'error');
        }
        return false;
    }

    /**
     * Show session expired modal
     */
    showSessionExpired() {
        if (this.isExpired) return;
        this.isExpired = true;

        // Remove any existing warning modal
        this.hideWarningModal();

        // Clean up intervals
        this.cleanup();

        // Restore original fetch
        if (this.originalFetch) {
            window.fetch = this.originalFetch;
        }

        // Create expired session modal
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg p-6 max-w-md w-full text-center shadow-xl session-animate-fade-in">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Session Expired</h3>
                    <p class="text-gray-600 mb-6">Your session has expired due to inactivity.</p>
                    <button id="loginAgainBtn"
                            class="w-full bg-primary-red text-white py-3 rounded-lg font-medium hover:bg-red-600 transition-colors mb-4">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login Again
                    </button>
                    <p class="text-sm text-gray-500">Redirecting in <span id="sessionCountdown" class="font-semibold">5</span> seconds...</p>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Start countdown
        let countdown = 5;
        const countdownElement = document.getElementById('sessionCountdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = this.config.loginUrl;
            }
        }, 1000);

        // Login button
        document.getElementById('loginAgainBtn').addEventListener('click', () => {
            clearInterval(countdownInterval);
            window.location.href = this.config.loginUrl;
        });
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Remove existing toast
        const existingToast = document.getElementById('sessionToast');
        if (existingToast) {
            existingToast.remove();
        }

        const colors = {
            'success': 'bg-green-500 text-white',
            'error': 'bg-red-500 text-white',
            'info': 'bg-blue-500 text-white',
            'warning': 'bg-yellow-500 text-white'
        };

        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle',
            'warning': 'fa-exclamation-triangle'
        };

        const toast = document.createElement('div');
        toast.id = 'sessionToast';
        toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${colors[type]} session-toast transform translate-x-full opacity-0`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icons[type]} mr-3"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }, 10);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, 3000);
    }

    /**
     * Logout user
     */
    logout() {
        this.isExpired = true;
        this.cleanup();

        // Use the logout form that already exists in the template
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.submit();
        } else {
            // Fallback: create a form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.config.logoutUrl;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = this.csrfToken;

            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    /**
     * Clean up intervals and event listeners
     */
    cleanup() {
        if (this.keepAliveIntervalId) {
            clearInterval(this.keepAliveIntervalId);
        }
        if (this.sessionCheckIntervalId) {
            clearInterval(this.sessionCheckIntervalId);
        }
        if (this.sessionTimeout) {
            clearTimeout(this.sessionTimeout);
        }

        // Remove activity handler if it was stored
        if (this.activityHandler) {
            const events = ['click', 'keypress', 'mousemove', 'scroll', 'touchstart', 'mousedown'];
            events.forEach(event => {
                document.removeEventListener(event, this.activityHandler);
            });
        }
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UniversalSessionManager;
}
