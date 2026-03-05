import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSuspendedBusinessStore = defineStore('suspendedBusiness', () => {
  const isVisible             = ref(false)
  const suspendedBusinessName = ref('')
  const previousBusinessId    = ref(null)

  /**
   * Call this right after detecting the admin is suspended in the
   * business they just switched into.
   *
   * @param {string} businessName    - Display name of the restricted business
   * @param {string|number} prevId   - The business ID to switch back to
   */
  function show(businessName, prevId) {
    suspendedBusinessName.value = businessName || 'this business'
    previousBusinessId.value    = prevId
    isVisible.value             = true
  }

  function hide() {
    isVisible.value             = false
    suspendedBusinessName.value = ''
    previousBusinessId.value    = null
  }

  return { isVisible, suspendedBusinessName, previousBusinessId, show, hide }
})