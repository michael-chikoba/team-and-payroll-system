<template>
  <Transition name="modal-fade">
    <div v-if="isVisible" class="suspended-overlay" @click.self="handleBackdropClick">
      <div class="suspended-modal" role="alertdialog" aria-modal="true" aria-labelledby="suspended-title">

        <!-- Icon -->
        <div class="modal-icon-ring">
          <div class="modal-icon-inner">
            <NoSymbolIcon style="width:2rem;height:2rem;color:#ef4444;" />
          </div>
        </div>

        <!-- Content -->
        <div class="modal-body">
          <h2 id="suspended-title" class="modal-title">Access Restricted</h2>
          <p class="modal-message">
            You don't have access to
            <strong>{{ suspendedBusinessName }}</strong>.
          </p>
          <p class="modal-hint">
            Contact your business administrator for assistance.
          </p>
        </div>

        <!-- Actions -->
        <div class="modal-actions">
          <button
            class="btn-go-back"
            :disabled="isSwitching"
            @click="goBackToPreviousBusiness"
          >
            <span v-if="isSwitching" class="btn-spinner" />
            <ArrowLeftIcon v-else style="width:1rem;height:1rem;" />
            {{ isSwitching ? 'Switching…' : 'Go Back to My Business' }}
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script>
import { ref, computed } from 'vue'
import { NoSymbolIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useSuspendedBusinessStore } from '@/stores/suspendedBusiness'
import { useBusinessSwitcher } from '@/composables/useBusinessSwitcher'

export default {
  name: 'SuspendedBusinessModal',
  components: { NoSymbolIcon, ArrowLeftIcon },

  setup() {
    const store          = useSuspendedBusinessStore()
    const { switchBusiness } = useBusinessSwitcher()
    const isSwitching    = ref(false)

    const isVisible             = computed(() => store.isVisible)
    const suspendedBusinessName = computed(() => store.suspendedBusinessName)
    const previousBusinessId    = computed(() => store.previousBusinessId)

    // Backdrop click does nothing — admin MUST click the button
    const handleBackdropClick = () => {}

    const goBackToPreviousBusiness = async () => {
      if (!previousBusinessId.value || isSwitching.value) return
      isSwitching.value = true
      try {
        await switchBusiness(previousBusinessId.value)
        store.hide()
      } catch (e) {
        console.error('Failed to switch back:', e)
      } finally {
        isSwitching.value = false
      }
    }

    return {
      isVisible,
      suspendedBusinessName,
      isSwitching,
      handleBackdropClick,
      goBackToPreviousBusiness,
    }
  },
}
</script>

<style scoped>
.suspended-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.suspended-modal {
  background: #fff;
  border-radius: 20px;
  width: min(440px, 94vw);
  padding: 2.5rem 2rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 32px 64px -12px rgba(0,0,0,0.3), 0 0 0 1px rgba(0,0,0,0.04);
  text-align: center;
}

/* Icon */
.modal-icon-ring {
  width: 5rem;
  height: 5rem;
  border-radius: 50%;
  background: #fff1f2;
  border: 2px solid #fecdd3;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.modal-icon-inner {
  width: 3.5rem;
  height: 3.5rem;
  background: #fee2e2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Text */
.modal-body   { display: flex; flex-direction: column; gap: 0.5rem; }
.modal-title  { margin: 0; font-size: 1.35rem; font-weight: 800; color: #111827; }
.modal-message {
  margin: 0;
  font-size: 0.95rem;
  color: #374151;
  line-height: 1.6;
}
.modal-message strong { color: #111827; font-weight: 700; }
.modal-hint {
  margin: 0;
  font-size: 0.82rem;
  color: #9ca3af;
  line-height: 1.5;
}

/* Button */
.modal-actions { width: 100%; margin-top: 0.5rem; }
.btn-go-back {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #1e293b;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
}
.btn-go-back:hover:not(:disabled) {
  background: #0f172a;
  transform: translateY(-1px);
}
.btn-go-back:disabled { opacity: 0.55; cursor: not-allowed; }

.btn-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Transition */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-active .suspended-modal,
.modal-fade-leave-active .suspended-modal { transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1); }
.modal-fade-enter-from { opacity: 0; }
.modal-fade-enter-from .suspended-modal { transform: scale(0.92) translateY(12px); }
.modal-fade-leave-to   { opacity: 0; }
</style>