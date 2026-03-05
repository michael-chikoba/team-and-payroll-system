// resources/js/services/ActivityTracker.js

/**
 * ActivityTracker
 * 
 * Monitors REAL device activity:
 *   - Mouse movement / clicks
 *   - Keyboard presses
 *   - Touch events (mobile)
 *   - Page Visibility API (tab switch / screen lock)
 *   - Window focus/blur (alt-tab, minimize)
 * 
 * Sends heartbeats to the backend every 60s with accurate
 * screen_active and page_visible flags.
 * 
 * When idle threshold warning comes back from the server,
 * shows a countdown modal. User can click "I'm still working"
 * to reset, otherwise session auto-closes server-side.
 */
export class ActivityTracker {
    constructor(options = {}) {
        this.apiBase              = options.apiBase || '/api/attendance';
        this.authToken            = options.authToken || null;
        this.heartbeatInterval    = options.heartbeatInterval || 60_000;   // 60s
        this.idleResetTimeout     = options.idleResetTimeout  || 30_000;   // 30s of no input = inactive
        this.onWarning            = options.onWarning  || this._defaultWarning.bind(this);
        this.onClosed             = options.onClosed   || this._defaultClosed.bind(this);
        this.onActive             = options.onActive   || null;

        // Internal state
        this._lastInputAt         = Date.now();
        this._pageVisible         = !document.hidden;
        this._windowFocused       = document.hasFocus();
        this._heartbeatTimer      = null;
        this._idleCheckTimer      = null;
        this._warningModal        = null;
        this._isTracking          = false;

        // Bound references so we can removeEventListener cleanly
        this._onMouseMove         = this._recordInput.bind(this);
        this._onMouseClick        = this._recordInput.bind(this);
        this._onKeyDown           = this._recordInput.bind(this);
        this._onTouchStart        = this._recordInput.bind(this);
        this._onScroll            = this._recordInput.bind(this);
        this._onVisibilityChange  = this._handleVisibilityChange.bind(this);
        this._onWindowFocus       = this._handleWindowFocus.bind(this);
        this._onWindowBlur        = this._handleWindowBlur.bind(this);
        this._onBeforeUnload      = this._handleBeforeUnload.bind(this);
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Start tracking. Call this when the employee clocks into overtime.
     */
    start(authToken = null) {
        if (this._isTracking) return;

        if (authToken) this.authToken = authToken;

        this._isTracking   = true;
        this._lastInputAt  = Date.now();
        this._pageVisible  = !document.hidden;
        this._windowFocused= document.hasFocus();

        this._attachListeners();
        this._startHeartbeat();
        this._startIdleCheck();

        console.info('[ActivityTracker] Started');
    }

    /**
     * Stop tracking. Call this when the employee manually clocks out of overtime.
     */
    stop() {
        if (!this._isTracking) return;

        this._isTracking = false;
        this._detachListeners();
        this._stopHeartbeat();
        this._stopIdleCheck();
        this._hideWarningModal();

        console.info('[ActivityTracker] Stopped');
    }

    /**
     * Returns true if there has been real device input within idleResetTimeout ms.
     */
    isDeviceActive() {
        const timeSinceInput = Date.now() - this._lastInputAt;
        return timeSinceInput < this.idleResetTimeout;
    }

    // =========================================================================
    // DEVICE EVENT LISTENERS
    // =========================================================================

    _attachListeners() {
        // Mouse
        document.addEventListener('mousemove',  this._onMouseMove,  { passive: true });
        document.addEventListener('mousedown',  this._onMouseClick, { passive: true });
        document.addEventListener('mouseup',    this._onMouseClick, { passive: true });

        // Keyboard
        document.addEventListener('keydown',    this._onKeyDown,    { passive: true });
        document.addEventListener('keyup',      this._onKeyDown,    { passive: true });

        // Touch (mobile/tablet)
        document.addEventListener('touchstart', this._onTouchStart, { passive: true });
        document.addEventListener('touchmove',  this._onTouchStart, { passive: true });

        // Scroll
        document.addEventListener('scroll',     this._onScroll,     { passive: true });

        // Tab / screen visibility (Page Visibility API)
        // Fires when: tab is hidden, screen locks, browser minimized
        document.addEventListener('visibilitychange', this._onVisibilityChange);

        // Window focus (alt-tab, clicking away from browser)
        window.addEventListener('focus', this._onWindowFocus);
        window.addEventListener('blur',  this._onWindowBlur);

        // Tab close / navigate away — send a final heartbeat as inactive
        window.addEventListener('beforeunload', this._onBeforeUnload);
    }

    _detachListeners() {
        document.removeEventListener('mousemove',        this._onMouseMove);
        document.removeEventListener('mousedown',        this._onMouseClick);
        document.removeEventListener('mouseup',          this._onMouseClick);
        document.removeEventListener('keydown',          this._onKeyDown);
        document.removeEventListener('keyup',            this._onKeyDown);
        document.removeEventListener('touchstart',       this._onTouchStart);
        document.removeEventListener('touchmove',        this._onTouchStart);
        document.removeEventListener('scroll',           this._onScroll);
        document.removeEventListener('visibilitychange', this._onVisibilityChange);
        window.removeEventListener('focus',              this._onWindowFocus);
        window.removeEventListener('blur',               this._onWindowBlur);
        window.removeEventListener('beforeunload',       this._onBeforeUnload);
    }

    // =========================================================================
    // INPUT & VISIBILITY HANDLERS
    // =========================================================================

    _recordInput() {
        this._lastInputAt = Date.now();
    }

    _handleVisibilityChange() {
        this._pageVisible = !document.hidden;

        if (document.hidden) {
            // Tab hidden or screen locked — send immediate inactive heartbeat
            console.info('[ActivityTracker] Page hidden — sending inactive heartbeat');
            this._sendHeartbeat(false, false);
        } else {
            // Tab visible again — record as active input
            this._recordInput();
            console.info('[ActivityTracker] Page visible again');
        }
    }

    _handleWindowFocus() {
        this._windowFocused = true;
        this._recordInput();
        console.info('[ActivityTracker] Window focused');
    }

    _handleWindowBlur() {
        this._windowFocused = false;
        // Don't send heartbeat here immediately — wait for next scheduled one
        console.info('[ActivityTracker] Window blurred');
    }

    _handleBeforeUnload() {
        // Synchronous beacon so it fires even during page unload
        if (this.authToken) {
            const payload = JSON.stringify({
                screen_active: false,
                page_visible:  false,
            });
            navigator.sendBeacon(
                `${this.apiBase}/heartbeat`,
                new Blob([payload], { type: 'application/json' })
            );
        }
    }

    // =========================================================================
    // HEARTBEAT
    // =========================================================================

    _startHeartbeat() {
        // Send immediately on start
        this._sendHeartbeat();

        this._heartbeatTimer = setInterval(() => {
            this._sendHeartbeat();
        }, this.heartbeatInterval);
    }

    _stopHeartbeat() {
        if (this._heartbeatTimer) {
            clearInterval(this._heartbeatTimer);
            this._heartbeatTimer = null;
        }
    }

    async _sendHeartbeat(screenActive = null, pageVisible = null) {
        if (!this.authToken) {
            console.warn('[ActivityTracker] No auth token set — skipping heartbeat');
            return;
        }

        // If not explicitly overridden, derive from real device state
        const isDeviceActive = this.isDeviceActive();
        const finalScreenActive = screenActive !== null ? screenActive : (isDeviceActive && this._windowFocused);
        const finalPageVisible  = pageVisible  !== null ? pageVisible  : this._pageVisible;

        try {
            const response = await fetch(`${this.apiBase}/heartbeat`, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept':        'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    screen_active: finalScreenActive,
                    page_visible:  finalPageVisible,
                }),
            });

            const data = await response.json();

            console.debug('[ActivityTracker] Heartbeat response:', {
                status:        response.status,
                idle_status:   data.idle_status,
                idle_minutes:  data.idle_minutes,
                screen_active: finalScreenActive,
                page_visible:  finalPageVisible,
            });

            this._handleServerResponse(data, response.status);

        } catch (error) {
            // Network error — don't crash, just log
            console.error('[ActivityTracker] Heartbeat failed:', error.message);
        }
    }

    // =========================================================================
    // IDLE CHECK (client-side pre-warning)
    // =========================================================================

    /**
     * Every 10 seconds, check if device has been idle.
     * If idle and no server warning yet, send an inactive heartbeat immediately
     * so the server knows sooner than the next 60s scheduled heartbeat.
     */
    _startIdleCheck() {
        this._idleCheckTimer = setInterval(() => {
            if (!this._isTracking) return;

            const deviceActive = this.isDeviceActive();

            if (!deviceActive) {
                console.debug('[ActivityTracker] Device idle detected — sending inactive heartbeat');
                this._sendHeartbeat(false, this._pageVisible);
            }
        }, 10_000); // check every 10 seconds
    }

    _stopIdleCheck() {
        if (this._idleCheckTimer) {
            clearInterval(this._idleCheckTimer);
            this._idleCheckTimer = null;
        }
    }

    // =========================================================================
    // SERVER RESPONSE HANDLING
    // =========================================================================

    _handleServerResponse(data, httpStatus) {
        if (!data.has_active_session) {
            // Session was closed server-side
            this.stop();
            this.onClosed(data);
            return;
        }

        switch (data.idle_status) {
            case 'active':
                this._hideWarningModal();
                if (this.onActive) this.onActive(data);
                break;

            case 'warning':
                // Server knows about idle but hasn't sent formal warning yet
                // Show a soft nudge
                this.onWarning(data, false);
                break;

            case 'closing':
                // Formal warning sent — countdown is live
                // HTTP 202 from our controller
                this.onWarning(data, true);
                break;
        }

        // If session was closed server-side (clock_out set), stop tracking
        if (httpStatus === 200 && data.idle_status === 'active') {
            this._hideWarningModal();
        }
    }

    // =========================================================================
    // WARNING MODAL (default implementation — override via options.onWarning)
    // =========================================================================

    _defaultWarning(data, isFinal) {
        if (this._warningModal) {
            // Update existing modal countdown
            const countdownEl = document.getElementById('activity-tracker-countdown');
            if (countdownEl && data.seconds_until_close) {
                countdownEl.textContent = Math.round(data.seconds_until_close);
            }
            return;
        }

        // Build modal
        const modal = document.createElement('div');
        modal.id = 'activity-tracker-modal';
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6); z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            font-family: sans-serif;
        `;

        const box = document.createElement('div');
        box.style.cssText = `
            background: #fff; border-radius: 12px; padding: 32px 40px;
            max-width: 420px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        `;

        const icon = isFinal ? '⚠️' : '💤';
        const countdown = data.seconds_until_close
            ? `<p style="font-size:14px;color:#666;">
                 Auto-closing in <strong><span id="activity-tracker-countdown">
                 ${Math.round(data.seconds_until_close)}</span> seconds</strong>
               </p>`
            : '';

        box.innerHTML = `
            <div style="font-size:48px;margin-bottom:16px">${icon}</div>
            <h2 style="margin:0 0 12px;color:#1a1a1a;font-size:20px">
                Are you still working?
            </h2>
            <p style="color:#555;margin:0 0 8px;line-height:1.5">
                ${data.warning_message || 'Your overtime session may be closed due to inactivity.'}
            </p>
            ${countdown}
            <button id="activity-tracker-still-working" style="
                margin-top:20px; padding:12px 28px; background:#2563eb;
                color:#fff; border:none; border-radius:8px; font-size:16px;
                cursor:pointer; width:100%;
            ">
                I'm still working
            </button>
        `;

        modal.appendChild(box);
        document.body.appendChild(modal);
        this._warningModal = modal;

        document.getElementById('activity-tracker-still-working')
            .addEventListener('click', () => this._userConfirmedActive());
    }

    _defaultClosed(data) {
        this._hideWarningModal();

        const notice = document.createElement('div');
        notice.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 99999;
            background: #dc2626; color: #fff; padding: 16px 24px;
            border-radius: 8px; font-family: sans-serif; font-size: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 320px;
        `;
        notice.textContent = 'Your overtime session was automatically closed due to inactivity.';
        document.body.appendChild(notice);

        setTimeout(() => notice.remove(), 8000);
    }

    _hideWarningModal() {
        if (this._warningModal) {
            this._warningModal.remove();
            this._warningModal = null;
        }
    }

    async _userConfirmedActive() {
        this._hideWarningModal();
        this._recordInput();

        try {
            const response = await fetch(`${this.apiBase}/refresh-activity`, {
                method:  'POST',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept':        'application/json',
                    'Content-Type':  'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();
            console.info('[ActivityTracker] Activity refreshed:', data.message);

        } catch (error) {
            console.error('[ActivityTracker] Failed to refresh activity:', error.message);
        }
    }
}