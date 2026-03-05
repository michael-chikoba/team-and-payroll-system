// src/composables/useActivityTracker.js

import { ref, computed, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

// ─── Singleton tracker instance ───────────────────────────────────────────────
// We keep one instance at module level so it survives component re-mounts
// (e.g. route changes). Only one tracker should ever be running at a time.
let _trackerInstance = null

// ─── Reactive state (shared across all components that call this composable) ──
const isTracking       = ref(false)
const idleStatus       = ref('active')   // 'active' | 'warning' | 'closing'
const idleMinutes      = ref(0)
const secondsUntilClose= ref(null)
const warningMessage   = ref(null)
const sessionClosed    = ref(false)

// ─── Config ───────────────────────────────────────────────────────────────────
const API_BASE              = import.meta.env.VITE_API_URL || 'http://localhost:8000'
const HEARTBEAT_INTERVAL_MS = 60_000   // 60s — matches backend cron cadence
const DEVICE_IDLE_TIMEOUT_MS= 30_000   // 30s no input = device considered idle
const IDLE_CHECK_INTERVAL_MS= 10_000   // poll device state every 10s

export function useActivityTracker() {
    const authStore = useAuthStore()

    // =========================================================================
    // PUBLIC — start / stop
    // =========================================================================

    function startTracking() {
        if (isTracking.value) return

        _trackerInstance = new ActivityTrackerCore({
            apiBase:           `${API_BASE}/api/attendance`,
            getToken:          () => authStore.token,
            heartbeatInterval: HEARTBEAT_INTERVAL_MS,
            idleResetTimeout:  DEVICE_IDLE_TIMEOUT_MS,
            idleCheckInterval: IDLE_CHECK_INTERVAL_MS,

            onStatusUpdate(data) {
                idleStatus.value       = data.idle_status       ?? 'active'
                idleMinutes.value      = data.idle_minutes       ?? 0
                secondsUntilClose.value= data.seconds_until_close ?? null
                warningMessage.value   = data.warning_message    ?? null
            },

            onSessionClosed(data) {
                isTracking.value    = false
                sessionClosed.value = true
                idleStatus.value    = 'active'
                warningMessage.value= null
            },
        })

        _trackerInstance.start()
        isTracking.value    = true
        sessionClosed.value = false

        console.info('[useActivityTracker] Tracking started')
    }

    function stopTracking() {
        if (!isTracking.value || !_trackerInstance) return

        _trackerInstance.stop()
        _trackerInstance = null
        isTracking.value = false
        idleStatus.value = 'active'
        warningMessage.value = null

        console.info('[useActivityTracker] Tracking stopped')
    }

    async function confirmStillWorking() {
        if (!_trackerInstance) return
        await _trackerInstance.refreshActivity()
        idleStatus.value    = 'active'
        warningMessage.value= null
        secondsUntilClose.value = null
    }

    // Computed helpers for templates
    const isIdle    = computed(() => idleStatus.value !== 'active')
    const isClosing = computed(() => idleStatus.value === 'closing')
    const isWarning = computed(() => idleStatus.value === 'warning')

    // Clean up if the component that called this is destroyed
    // (only relevant if used in a component that can unmount while tracking)
    onUnmounted(() => {
        // Do NOT stop tracking on unmount — we want it to survive route changes.
        // Only stop explicitly via stopTracking().
    })

    return {
        // State
        isTracking,
        idleStatus,
        idleMinutes,
        secondsUntilClose,
        warningMessage,
        sessionClosed,

        // Computed
        isIdle,
        isClosing,
        isWarning,

        // Actions
        startTracking,
        stopTracking,
        confirmStillWorking,
    }
}

// =============================================================================
// CORE TRACKER CLASS (framework-agnostic, used by the composable)
// =============================================================================

class ActivityTrackerCore {
    constructor(options) {
        this.apiBase           = options.apiBase
        this.getToken          = options.getToken
        this.heartbeatInterval = options.heartbeatInterval
        this.idleResetTimeout  = options.idleResetTimeout
        this.idleCheckInterval = options.idleCheckInterval
        this.onStatusUpdate    = options.onStatusUpdate    || (() => {})
        this.onSessionClosed   = options.onSessionClosed   || (() => {})

        // Internal state
        this._lastInputAt      = Date.now()
        this._pageVisible      = !document.hidden
        this._windowFocused    = document.hasFocus()
        this._heartbeatTimer   = null
        this._idleCheckTimer   = null
        this._running          = false

        // Bound event handler references (needed for clean removeEventListener)
        this._onMouseMove         = this._recordInput.bind(this)
        this._onMouseDown         = this._recordInput.bind(this)
        this._onMouseUp           = this._recordInput.bind(this)
        this._onKeyDown           = this._recordInput.bind(this)
        this._onKeyUp             = this._recordInput.bind(this)
        this._onTouchStart        = this._recordInput.bind(this)
        this._onTouchMove         = this._recordInput.bind(this)
        this._onScroll            = this._recordInput.bind(this)
        this._onWheel             = this._recordInput.bind(this)
        this._onVisibilityChange  = this._handleVisibilityChange.bind(this)
        this._onWindowFocus       = this._handleWindowFocus.bind(this)
        this._onWindowBlur        = this._handleWindowBlur.bind(this)
        this._onBeforeUnload      = this._handleBeforeUnload.bind(this)
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    start() {
        if (this._running) return
        this._running     = true
        this._lastInputAt = Date.now()

        this._attachListeners()
        this._startHeartbeat()
        this._startIdleCheck()
    }

    stop() {
        if (!this._running) return
        this._running = false

        this._detachListeners()
        this._stopHeartbeat()
        this._stopIdleCheck()

        // Send a final "I'm offline" heartbeat
        this._sendHeartbeat(false, false)
    }

    async refreshActivity() {
        const token = this.getToken()
        if (!token) return

        try {
            const res = await fetch(`${this.apiBase}/refresh-activity`, {
                method:  'POST',
                headers: this._headers(),
            })
            const data = await res.json()
            console.info('[ActivityTracker] Activity refreshed:', data.message)
            this._lastInputAt = Date.now()
        } catch (err) {
            console.error('[ActivityTracker] Refresh failed:', err.message)
        }
    }

    // ── Device event listeners ────────────────────────────────────────────────

    _attachListeners() {
        // ── Mouse ──────────────────────────────────────────────────────
        document.addEventListener('mousemove',  this._onMouseMove,  { passive: true })
        document.addEventListener('mousedown',  this._onMouseDown,  { passive: true })
        document.addEventListener('mouseup',    this._onMouseUp,    { passive: true })

        // ── Keyboard ───────────────────────────────────────────────────
        document.addEventListener('keydown',    this._onKeyDown,    { passive: true })
        document.addEventListener('keyup',      this._onKeyUp,      { passive: true })

        // ── Touch (mobile/tablet) ──────────────────────────────────────
        document.addEventListener('touchstart', this._onTouchStart, { passive: true })
        document.addEventListener('touchmove',  this._onTouchMove,  { passive: true })

        // ── Scroll / Wheel ─────────────────────────────────────────────
        document.addEventListener('scroll',     this._onScroll,     { passive: true })
        document.addEventListener('wheel',      this._onWheel,      { passive: true })

        // ── Page Visibility API ────────────────────────────────────────
        // Fires on: tab switch, screen lock, browser minimize, phone sleep
        document.addEventListener('visibilitychange', this._onVisibilityChange)

        // ── Window focus / blur ────────────────────────────────────────
        // Fires on: alt-tab, clicking outside browser window
        window.addEventListener('focus',        this._onWindowFocus)
        window.addEventListener('blur',         this._onWindowBlur)

        // ── Tab/window close ───────────────────────────────────────────
        window.addEventListener('beforeunload', this._onBeforeUnload)
    }

    _detachListeners() {
        document.removeEventListener('mousemove',        this._onMouseMove)
        document.removeEventListener('mousedown',        this._onMouseDown)
        document.removeEventListener('mouseup',          this._onMouseUp)
        document.removeEventListener('keydown',          this._onKeyDown)
        document.removeEventListener('keyup',            this._onKeyUp)
        document.removeEventListener('touchstart',       this._onTouchStart)
        document.removeEventListener('touchmove',        this._onTouchMove)
        document.removeEventListener('scroll',           this._onScroll)
        document.removeEventListener('wheel',            this._onWheel)
        document.removeEventListener('visibilitychange', this._onVisibilityChange)
        window.removeEventListener('focus',              this._onWindowFocus)
        window.removeEventListener('blur',               this._onWindowBlur)
        window.removeEventListener('beforeunload',       this._onBeforeUnload)
    }

    // ── Input recording ───────────────────────────────────────────────────────

    _recordInput() {
        this._lastInputAt = Date.now()
    }

    _isDeviceActive() {
        return (Date.now() - this._lastInputAt) < this.idleResetTimeout
    }

    // ── Visibility / focus handlers ───────────────────────────────────────────

    _handleVisibilityChange() {
        this._pageVisible = !document.hidden

        if (document.hidden) {
            // Screen locked, tab hidden, browser minimized
            // Send immediate inactive heartbeat — don't wait for the 60s timer
            console.info('[ActivityTracker] Page hidden → inactive heartbeat')
            this._sendHeartbeat(false, false)
        } else {
            this._recordInput()
            console.info('[ActivityTracker] Page visible again')
        }
    }

    _handleWindowFocus() {
        this._windowFocused = true
        this._recordInput()
    }

    _handleWindowBlur() {
        this._windowFocused = false
        // Don't send heartbeat immediately — the idle check will catch it within 10s
    }

    _handleBeforeUnload() {
        // navigator.sendBeacon fires even during unload — fetch() does not
        const token = this.getToken()
        if (!token) return

        const payload = JSON.stringify({ screen_active: false, page_visible: false })
        navigator.sendBeacon(
            `${this.apiBase}/heartbeat`,
            new Blob([payload], { type: 'application/json' })
        )
    }

    // ── Heartbeat ─────────────────────────────────────────────────────────────

    _startHeartbeat() {
        // Fire immediately so the server knows we're active right now
        this._sendHeartbeat()

        this._heartbeatTimer = setInterval(
            () => this._sendHeartbeat(),
            this.heartbeatInterval
        )
    }

    _stopHeartbeat() {
        if (this._heartbeatTimer) {
            clearInterval(this._heartbeatTimer)
            this._heartbeatTimer = null
        }
    }

    async _sendHeartbeat(screenActiveOverride = null, pageVisibleOverride = null) {
        const token = this.getToken()
        if (!token) return

        // Derive real values from device state unless explicitly overridden
        const screenActive = screenActiveOverride !== null
            ? screenActiveOverride
            : (this._isDeviceActive() && this._windowFocused)

        const pageVisible = pageVisibleOverride !== null
            ? pageVisibleOverride
            : this._pageVisible

        console.debug('[ActivityTracker] Heartbeat →', {
            screen_active: screenActive,
            page_visible:  pageVisible,
            device_active: this._isDeviceActive(),
            window_focused:this._windowFocused,
            idle_since_s:  Math.round((Date.now() - this._lastInputAt) / 1000),
        })

        try {
            const res = await fetch(`${this.apiBase}/heartbeat`, {
                method:  'POST',
                headers: this._headers(),
                body:    JSON.stringify({ screen_active: screenActive, page_visible: pageVisible }),
            })

            const data = await res.json()

            // Session closed server-side
            if (!data.has_active_session && data.success === false) {
                this.onSessionClosed(data)
                return
            }

            // Session was auto-closed by idle detection
            if (data.has_active_session === false) {
                this.onSessionClosed(data)
                return
            }

            this.onStatusUpdate(data)

        } catch (err) {
            console.error('[ActivityTracker] Heartbeat error:', err.message)
        }
    }

    // ── Idle check (client-side fast detection) ───────────────────────────────

    _startIdleCheck() {
        this._idleCheckTimer = setInterval(() => {
            if (!this._running) return

            if (!this._isDeviceActive()) {
                // Device is idle — send an inactive heartbeat immediately
                // so the server knows within 10s rather than waiting up to 60s
                console.debug('[ActivityTracker] Idle check: device inactive → sending heartbeat')
                this._sendHeartbeat(false, this._pageVisible)
            }
        }, this.idleCheckInterval)
    }

    _stopIdleCheck() {
        if (this._idleCheckTimer) {
            clearInterval(this._idleCheckTimer)
            this._idleCheckTimer = null
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    _headers() {
        return {
            'Content-Type':     'application/json',
            'Accept':           'application/json',
            'Authorization':    `Bearer ${this.getToken()}`,
            'X-Requested-With': 'XMLHttpRequest',
        }
    }
}