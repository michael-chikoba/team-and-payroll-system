<template>
  <!-- Only renders when an overtime session is being tracked and idle is detected -->
  <Teleport to="body">
    <Transition name="idle-fade">
      <div v-if="isTracking && isIdle" class="idle-overlay" role="alertdialog" aria-modal="true">
        <div class="idle-box">

          <!-- Icon -->
          <div class="idle-icon">{{ isClosing ? '⚠️' : '💤' }}</div>

          <!-- Title -->
          <h2 class="idle-title">
            {{ isClosing ? 'Session closing soon' : 'Are you still working?' }}
          </h2>

          <!-- Message -->
          <p class="idle-message">{{ warningMessage }}</p>

          <!-- Countdown (only shown in closing state) -->
          <div v-if="isClosing && secondsUntilClose !== null" class="idle-countdown">
            <svg class="countdown-ring" viewBox="0 0 44 44">
              <circle cx="22" cy="22" r="18" class="ring-bg" />
              <circle
                cx="22" cy="22" r="18"
                class="ring-progress"
                :stroke-dasharray="ringCircumference"
                :stroke-dashoffset="ringOffset"
              />
            </svg>
            <span class="countdown-number">{{ Math.round(secondsUntilClose) }}</span>
          </div>

          <!-- Idle minutes info -->
          <p class="idle-subtext">
            Idle for <strong>{{ idleMinutes }} minute{{ idleMinutes !== 1 ? 's' : '' }}</strong>
          </p>

          <!-- Action button -->
          <button class="idle-btn-primary" @click="handleStillWorking">
            ✋ I'm still working
          </button>

        </div>
      </div>
    </Transition>

    <!-- Session closed toast -->
    <Transition name="toast-slide">
      <div v-if="showClosedToast" class="idle-closed-toast">
        🔴 Your overtime session was automatically closed due to inactivity.
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useActivityTracker } from '@/composables/useActivityTracker'

const {
    isTracking,
    isIdle,
    isClosing,
    isWarning,
    idleMinutes,
    secondsUntilClose,
    warningMessage,
    sessionClosed,
    confirmStillWorking,
} = useActivityTracker()

const showClosedToast = ref(false)

// Ring countdown calculation (SVG circle)
const ringCircumference = computed(() => 2 * Math.PI * 18)  // r=18
const ringOffset = computed(() => {
    if (!secondsUntilClose.value) return 0
    const totalSeconds = 5 * 60  // matches backend auto_close_after_warning_minutes
    const progress = secondsUntilClose.value / totalSeconds
    return ringCircumference.value * (1 - progress)
})

// Watch for session being closed server-side
watch(sessionClosed, (closed) => {
    if (closed) {
        showClosedToast.value = true
        setTimeout(() => { showClosedToast.value = false }, 8000)
    }
})

async function handleStillWorking() {
    await confirmStillWorking()
}
</script>

<style scoped>
/* ── Overlay ─────────────────────────────────────────────────────────────── */
.idle-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(3px);
}

/* ── Modal box ───────────────────────────────────────────────────────────── */
.idle-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 40px 48px;
    max-width: 440px;
    width: 90%;
    text-align: center;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.25);
}

.idle-icon {
    font-size: 52px;
    margin-bottom: 16px;
    line-height: 1;
}

.idle-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.idle-message {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0 0 16px;
}

.idle-subtext {
    font-size: 13px;
    color: #9ca3af;
    margin: 0 0 20px;
}

/* ── Countdown ring ──────────────────────────────────────────────────────── */
.idle-countdown {
    position: relative;
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
}

.countdown-ring {
    width: 72px;
    height: 72px;
    transform: rotate(-90deg);
}

.ring-bg {
    fill: none;
    stroke: #e5e7eb;
    stroke-width: 4;
}

.ring-progress {
    fill: none;
    stroke: #ef4444;
    stroke-width: 4;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s linear;
}

.countdown-number {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: #ef4444;
}

/* ── Button ──────────────────────────────────────────────────────────────── */
.idle-btn-primary {
    width: 100%;
    padding: 13px 24px;
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
}

.idle-btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.idle-btn-primary:active {
    transform: translateY(0);
}

/* ── Closed toast ────────────────────────────────────────────────────────── */
.idle-closed-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    background: #dc2626;
    color: #ffffff;
    padding: 14px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    max-width: 340px;
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);
    line-height: 1.5;
}

/* ── Transitions ─────────────────────────────────────────────────────────── */
.idle-fade-enter-active,
.idle-fade-leave-active {
    transition: opacity 0.25s ease;
}
.idle-fade-enter-from,
.idle-fade-leave-to {
    opacity: 0;
}

.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
    transform: translateX(120%);
    opacity: 0;
}
</style>