// src/composables/useBusinessContext.js
import { onMounted, onUnmounted } from 'vue'
import { useBusinessStore } from '@/stores/business'

/**
 * Composable for components that need to react to business context changes
 * Use this in any component that displays business-specific data
 */
export function useBusinessContext(callbacks = {}) {
  const businessStore = useBusinessStore()
  
  const handleBusinessSwitch = (event) => {
    const business = event.detail?.business || businessStore.getCurrentBusiness
    
    console.log('Business context changed:', business?.name)
    
    // Call the provided callback if it exists
    if (callbacks.onBusinessSwitch && typeof callbacks.onBusinessSwitch === 'function') {
      callbacks.onBusinessSwitch(business)
    }
    
    // Auto-refresh callback
    if (callbacks.onRefresh && typeof callbacks.onRefresh === 'function') {
      callbacks.onRefresh()
    }
  }
  
  onMounted(() => {
    window.addEventListener('business-switched', handleBusinessSwitch)
    window.addEventListener('business-context-changed', handleBusinessSwitch)
  })
  
  onUnmounted(() => {
    window.removeEventListener('business-switched', handleBusinessSwitch)
    window.removeEventListener('business-context-changed', handleBusinessSwitch)
  })
  
  return {
    currentBusiness: businessStore.getCurrentBusiness,
    currentBusinessId: businessStore.getCurrentBusinessId,
    refreshBusinessList: () => businessStore.fetchBusinesses()
  }
}

/**
 * Example usage in a component:
 * 
 * <script setup>
 * import { useBusinessContext } from '@/composables/useBusinessContext'
 * 
 * const { currentBusiness } = useBusinessContext({
 *   onBusinessSwitch: (business) => {
 *     console.log('Switched to:', business.name)
 *     fetchEmployees() // Refresh employee list
 *   },
 *   onRefresh: () => {
 *     refreshDashboardData()
 *   }
 * })
 * </script>
 */