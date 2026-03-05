/**
 * Responsive Mixin for Vue 2 Options API
 * Provides device detection to any component
 */

export const responsiveMixin = {
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
      
      // Determine device type
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
    },
    
    detectOrientation() {
      this.orientation = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'
    }
  },

  mounted() {
    this.detectDevice()
    this.detectOrientation()
    
    window.addEventListener('resize', this.detectDevice)
    window.addEventListener('orientationchange', this.detectOrientation)
  },

  beforeDestroy() {
    window.removeEventListener('resize', this.detectDevice)
    window.removeEventListener('orientationchange', this.detectOrientation)
  }
}