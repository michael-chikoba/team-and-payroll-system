/**
 * Device Detection Utility
 * Detects device type, orientation, and provides reactive state
 */

export const DeviceDetection = {
  data() {
    return {
      isMobile: false,
      isTablet: false,
      isDesktop: false,
      isTouch: false,
      orientation: 'landscape',
      screenWidth: 0,
      screenHeight: 0,
      breakpoint: 'xs'
    }
  },

  methods: {
    detectDevice() {
      this.screenWidth = window.innerWidth
      this.screenHeight = window.innerHeight
      
      // Determine breakpoint
      if (this.screenWidth < 576) this.breakpoint = 'xs'
      else if (this.screenWidth < 768) this.breakpoint = 'sm'
      else if (this.screenWidth < 992) this.breakpoint = 'md'
      else if (this.screenWidth < 1200) this.breakpoint = 'lg'
      else if (this.screenWidth < 1400) this.breakpoint = 'xl'
      else this.breakpoint = 'xxl'
      
      // Determine device type based on screen width and touch capability
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0
      this.isTouch = isTouchDevice
      
      if (this.screenWidth < 768) {
        this.isMobile = true
        this.isTablet = false
        this.isDesktop = false
      } else if (this.screenWidth >= 768 && this.screenWidth < 992) {
        this.isMobile = false
        this.isTablet = true
        this.isDesktop = false
      } else {
        this.isMobile = false
        this.isTablet = false
        this.isDesktop = true
      }
      
      // Update body classes for CSS targeting
      this.updateBodyClasses()
    },
    
    detectOrientation() {
      this.orientation = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'
      this.updateBodyClasses()
    },
    
    updateBodyClasses() {
      const body = document.body
      
      // Remove existing classes
      body.classList.remove(
        'is-mobile', 'is-tablet', 'is-desktop',
        'touch-device', 'no-touch',
        'portrait', 'landscape',
        'breakpoint-xs', 'breakpoint-sm', 'breakpoint-md',
        'breakpoint-lg', 'breakpoint-xl', 'breakpoint-xxl'
      )
      
      // Add current classes
      if (this.isMobile) body.classList.add('is-mobile')
      if (this.isTablet) body.classList.add('is-tablet')
      if (this.isDesktop) body.classList.add('is-desktop')
      
      body.classList.add(this.isTouch ? 'touch-device' : 'no-touch')
      body.classList.add(this.orientation)
      body.classList.add(`breakpoint-${this.breakpoint}`)
    }
  },

  mounted() {
    this.detectDevice()
    this.detectOrientation()
    
    window.addEventListener('resize', () => {
      this.detectDevice()
      this.detectOrientation()
    })
    
    window.addEventListener('orientationchange', () => {
      this.detectOrientation()
    })
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.detectDevice)
    window.removeEventListener('orientationchange', this.detectOrientation)
  }
}

/**
 * Vue Composition API composable for device detection
 */
import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useDeviceDetection() {
  const isMobile = ref(false)
  const isTablet = ref(false)
  const isDesktop = ref(false)
  const isTouch = ref(false)
  const orientation = ref('landscape')
  const screenWidth = ref(0)
  const screenHeight = ref(0)
  const breakpoint = ref('xs')

  const detectDevice = () => {
    screenWidth.value = window.innerWidth
    screenHeight.value = window.innerHeight
    
    // Determine breakpoint
    if (screenWidth.value < 576) breakpoint.value = 'xs'
    else if (screenWidth.value < 768) breakpoint.value = 'sm'
    else if (screenWidth.value < 992) breakpoint.value = 'md'
    else if (screenWidth.value < 1200) breakpoint.value = 'lg'
    else if (screenWidth.value < 1400) breakpoint.value = 'xl'
    else breakpoint.value = 'xxl'
    
    // Determine device type
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0
    isTouch.value = isTouchDevice
    
    if (screenWidth.value < 768) {
      isMobile.value = true
      isTablet.value = false
      isDesktop.value = false
    } else if (screenWidth.value >= 768 && screenWidth.value < 992) {
      isMobile.value = false
      isTablet.value = true
      isDesktop.value = false
    } else {
      isMobile.value = false
      isTablet.value = false
      isDesktop.value = true
    }
    
    // Update body classes
    const body = document.body
    body.classList.remove('is-mobile', 'is-tablet', 'is-desktop', 'touch-device', 'no-touch')
    if (isMobile.value) body.classList.add('is-mobile')
    if (isTablet.value) body.classList.add('is-tablet')
    if (isDesktop.value) body.classList.add('is-desktop')
    body.classList.add(isTouchDevice ? 'touch-device' : 'no-touch')
  }

  const detectOrientation = () => {
    orientation.value = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'
    document.body.classList.remove('portrait', 'landscape')
    document.body.classList.add(orientation.value)
  }

  onMounted(() => {
    detectDevice()
    detectOrientation()
    
    window.addEventListener('resize', detectDevice)
    window.addEventListener('orientationchange', detectOrientation)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', detectDevice)
    window.removeEventListener('orientationchange', detectOrientation)
  })

  return {
    isMobile,
    isTablet,
    isDesktop,
    isTouch,
    orientation,
    screenWidth,
    screenHeight,
    breakpoint
  }
}