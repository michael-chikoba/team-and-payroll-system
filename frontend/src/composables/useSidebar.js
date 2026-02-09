// composables/useSidebar.js
import { ref, computed } from 'vue'

// Shared state across all layouts
const isSidebarOpen = ref(false)
const isMobile = ref(false)

export function useSidebar() {
  // Check if device is mobile
  const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768
    // Auto-close sidebar on mobile when navigating
    if (isMobile.value && isSidebarOpen.value) {
      isSidebarOpen.value = false
    }
  }

  // Toggle sidebar
  const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
    
    // Prevent body scroll when sidebar is open on mobile
    if (isMobile.value) {
      if (isSidebarOpen.value) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = 'auto'
      }
    }
  }

  // Close sidebar
  const closeSidebar = () => {
    isSidebarOpen.value = false
    if (isMobile.value) {
      document.body.style.overflow = 'auto'
    }
  }

  // Open sidebar
  const openSidebar = () => {
    isSidebarOpen.value = true
    if (isMobile.value) {
      document.body.style.overflow = 'hidden'
    }
  }

  // Computed property for sidebar classes
  const sidebarClasses = computed(() => ({
    'sidebar-open': isSidebarOpen.value,
    'sidebar-closed': !isSidebarOpen.value && isMobile.value
  }))

  return {
    isSidebarOpen,
    isMobile,
    toggleSidebar,
    closeSidebar,
    openSidebar,
    checkIfMobile,
    sidebarClasses
  }
}