// src/stores/business.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useBusinessStore = defineStore('business', {
  state: () => ({
    currentBusiness: null,
    businesses: [],
    loading: false,
    error: null
  }),

  getters: {
    getCurrentBusiness: (state) => state.currentBusiness,
    getAllBusinesses: (state) => state.businesses,
    getCurrentBusinessId: (state) => state.currentBusiness?.id || null,
    isCurrentBusiness: (state) => (businessId) => {
      return state.currentBusiness?.id === businessId
    },
    hasMultipleBusinesses: (state) => state.businesses.length > 1
  },

  actions: {
    /**
     * Fetch all businesses for the current user
     */
    async fetchBusinesses() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/admin/businesses')
        
        if (response.data.success) {
          this.businesses = response.data.data
          
          // Set current business if not already set
          if (!this.currentBusiness && this.businesses.length > 0) {
            // Try to get from user's current_business_id
            const authStore = useAuthStore()
            const currentBusinessId = authStore.user?.current_business_id
            
            if (currentBusinessId) {
              const currentBiz = this.businesses.find(b => b.id === currentBusinessId)
              if (currentBiz) {
                this.currentBusiness = currentBiz
              } else {
                // Fallback to first business
                this.currentBusiness = this.businesses[0]
              }
            } else {
              this.currentBusiness = this.businesses[0]
            }
          }
          
          return this.businesses
        }
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.error = error.response?.data?.message || 'Failed to load businesses'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Switch to a different business without page reload
     */
    async switchBusiness(businessId) {
      if (this.isCurrentBusiness(businessId)) {
        console.log('Already on this business')
        return this.currentBusiness
      }

      this.loading = true
      this.error = null

      try {
        // Call API to update user's current business
        const response = await axios.post(`/api/admin/businesses/${businessId}/switch`)
        
        if (response.data.success) {
          // Find the business in our local list
          const newBusiness = this.businesses.find(b => b.id === businessId)
          
          if (newBusiness) {
            // Update current business
            this.currentBusiness = newBusiness
            
            // Update auth store user's current_business_id
            const authStore = useAuthStore()
            if (authStore.user) {
              authStore.user.current_business_id = businessId
            }
            
            // Emit event for other components to react
            window.dispatchEvent(new CustomEvent('business-switched', { 
              detail: { business: newBusiness } 
            }))
            
            return newBusiness
          }
        }
      } catch (error) {
        console.error('Failed to switch business:', error)
        this.error = error.response?.data?.message || 'Failed to switch business'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Set current business from initial load
     */
    setCurrentBusiness(business) {
      this.currentBusiness = business
    },

    /**
     * Add a new business to the list
     */
    addBusiness(business) {
      this.businesses.push(business)
      
      // If it's the first business, make it current
      if (this.businesses.length === 1) {
        this.currentBusiness = business
      }
    },

    /**
     * Update an existing business
     */
    updateBusiness(businessId, updatedData) {
      const index = this.businesses.findIndex(b => b.id === businessId)
      
      if (index !== -1) {
        this.businesses[index] = { ...this.businesses[index], ...updatedData }
        
        // If it's the current business, update it too
        if (this.currentBusiness?.id === businessId) {
          this.currentBusiness = this.businesses[index]
        }
      }
    },

    /**
     * Remove a business from the list
     */
    removeBusiness(businessId) {
      const index = this.businesses.findIndex(b => b.id === businessId)
      
      if (index !== -1) {
        this.businesses.splice(index, 1)
        
        // If we deleted the current business, switch to another
        if (this.currentBusiness?.id === businessId) {
          this.currentBusiness = this.businesses.length > 0 ? this.businesses[0] : null
        }
      }
    },

    /**
     * Clear all business data (on logout)
     */
    clearBusinessData() {
      this.currentBusiness = null
      this.businesses = []
      this.error = null
    }
  }
})

// Import auth store (needed for getting current user)
import { useAuthStore } from './auth'