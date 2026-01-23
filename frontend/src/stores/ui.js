import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUIStore = defineStore('ui', () => {
  const sidebarOpen = ref(false)
  const currentTheme = ref('light')
  const loading = ref(false)
  const pageTitle = ref('Payroll System')

  const isSidebarOpen = computed(() => sidebarOpen.value)

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebarOpen(open) {
    sidebarOpen.value = open
  }

  function toggleTheme() {
    currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light'
    document.documentElement.setAttribute('data-theme', currentTheme.value)
  }

  function setTheme(theme) {
    currentTheme.value = theme
    document.documentElement.setAttribute('data-theme', theme)
  }

  function setLoading(loadingState) {
    loading.value = loadingState
  }

  function setPageTitle(title) {
    pageTitle.value = title
    document.title = `${title} - Payroll System`
  }

  // Initialize theme
  const savedTheme = localStorage.getItem('theme') || 'light'
  setTheme(savedTheme)

  return {
    sidebarOpen,
    currentTheme,
    loading,
    pageTitle,
    isSidebarOpen,
    toggleSidebar,
    setSidebarOpen,
    toggleTheme,
    setTheme,
    setLoading,
    setPageTitle
  }
})