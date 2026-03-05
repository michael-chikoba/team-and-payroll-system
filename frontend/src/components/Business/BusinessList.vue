<template>
  <div class="max-w-6xl mx-auto p-0">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 bg-white p-6 rounded-xl shadow-lg">
      <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-4 sm:mb-0">
        Registered Businesses
      </h2>
      
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-500"></div>
    </div>

    <div v-else-if="businesses.length === 0" class="text-center py-20 bg-white rounded-xl shadow-xl border border-dashed border-gray-300">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
      </svg>
      <h3 class="mt-2 text-xl font-medium text-gray-900">No businesses registered</h3>
      <p class="mt-1 text-base text-gray-500">Get started by registering your first organization.</p>
      <div class="mt-6">
        <button
          @click="$emit('create-business')"
          class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Register Your First Business
        </button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
      <div
        v-for="business in businesses"
        :key="business.id"
        class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-0.5 border border-gray-100"
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold text-gray-900 truncate" :title="business.name">{{ business.name }}</h3>
            <span
              :class="[
                'px-3 py-1 text-xs font-semibold uppercase tracking-wider rounded-full ml-4 whitespace-nowrap',
                business.status === 'active' ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-600/20'
              ]"
            >
              {{ business.status }}
            </span>
          </div>

          <dl class="space-y-3 text-sm text-gray-600 border-t border-gray-100 pt-4">
            <div class="flex items-center justify-between">
              <dt class="font-medium text-gray-500">Legal Name</dt>
              <dd class="text-gray-800 font-semibold">{{ business.legal_name }}</dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="font-medium text-gray-500">Type</dt>
              <dd>{{ formatBusinessType(business.business_type) }}</dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="font-medium text-gray-500">Industry</dt>
              <dd>{{ business.industry || 'N/A' }}</dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="font-medium text-gray-500">Currency</dt>
              <dd>{{ business.currency_code }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-dashed border-gray-200 pt-3 mt-3">
              <dt class="font-bold text-indigo-600 flex items-center">
                 <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m14-4a2 2 0 01-2 2H7a2 2 0 01-2-2m14-4H5m13 0a2 2 0 002-2V9a2 2 0 00-2-2h-3L8 3v10h4z"></path></svg>
                 Admins
              </dt>
              <dd class="text-indigo-600 font-bold text-lg">{{ business.admins?.length || 0 }}</dd>
            </div>
          </dl>

          <div class="mt-6 space-y-3">
            <button
              @click="switchBusiness(business)"
              :disabled="isCurrentBusiness(business.id)"
              :class="[
                'w-full inline-flex items-center justify-center px-4 py-2 rounded-xl shadow-md focus:outline-none focus:ring-4 focus:ring-opacity-50 transition duration-150 ease-in-out font-semibold',
                isCurrentBusiness(business.id)
                  ? 'bg-gray-400 text-gray-700 cursor-not-allowed'
                  : 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500'
              ]"
            >
              <svg 
                v-if="isCurrentBusiness(business.id)" 
                class="w-5 h-5 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              {{ isCurrentBusiness(business.id) ? 'Current Business' : `Switch to ${business.name}` }}
            </button>
            
            <div class="grid grid-cols-2 gap-3">
              <button
                @click="$emit('edit-business', business)"
                class="w-full inline-flex justify-center px-3 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm transition duration-150"
              >
                Edit
              </button>
              
              <button
                @click="$emit('manage-admins', business)"
                class="w-full inline-flex justify-center px-3 py-2 border border-indigo-200 text-indigo-700 rounded-xl hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm transition duration-150"
              >
                Admins
              </button>
            </div>
            
            <button
              @click="$emit('delete-business', business)"
              class="w-full inline-flex justify-center px-4 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-sm transition duration-150 font-medium"
            >
              Delete Business
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useBusinessStore } from '@/stores/business'
import { useAuthStore } from '@/stores/auth'
import { computed } from 'vue'

export default {
  name: 'BusinessList',
  emits: ['create-business', 'edit-business', 'delete-business', 'manage-admins'],
  setup() {
    const businessStore = useBusinessStore()
    const authStore = useAuthStore()
    
    return {
      businessStore,
      authStore,
      businesses: computed(() => businessStore.getAllBusinesses),
      currentBusinessId: computed(() => businessStore.getCurrentBusinessId),
      loading: computed(() => businessStore.loading)
    }
  },
  async mounted() {
    await this.fetchBusinesses()
  },
  methods: {
    async fetchBusinesses() {
      try {
        await this.businessStore.fetchBusinesses()
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load businesses'
        })
      }
    },
    
    isCurrentBusiness(businessId) {
      return this.businessStore.isCurrentBusiness(businessId)
    },
    
    async switchBusiness(business) {
      // Prevent switching if already the current business
      if (this.isCurrentBusiness(business.id)) {
        this.$notify({
          type: 'info',
          title: 'Already Active',
          text: `${business.name} is already your active business`
        })
        return
      }
      
      try {
        // Show loading state
        const switchingNotification = this.$notify({
          type: 'info',
          title: 'Switching Business',
          text: `Switching to ${business.name}...`,
          duration: -1 // Keep showing until we manually close it
        })
        
        // Switch business using store (NO PAGE RELOAD)
        await this.businessStore.switchBusiness(business.id)
        
        // Close the loading notification
        if (switchingNotification && switchingNotification.close) {
          switchingNotification.close()
        }
        
        // Show success notification
        this.$notify({
          type: 'success',
          title: 'Success',
          text: `Switched to ${business.name}`
        })
        
        // Emit event to parent components if needed
        this.$emit('business-switched', business)
        
        // Optional: Refresh certain data without full page reload
        // For example, refresh dashboard stats, employee list, etc.
        this.refreshAfterSwitch()
        
      } catch (error) {
        console.error('Failed to switch business:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to switch business'
        })
      }
    },
    
    /**
     * Refresh specific data after business switch without full page reload
     */
    refreshAfterSwitch() {
      // Dispatch custom event that other components can listen to
      window.dispatchEvent(new CustomEvent('business-context-changed'))
      
      // You can also use Vue's event bus or state management
      // to notify other components about the business switch
    },
    
    formatBusinessType(type) {
      const types = {
        sole_proprietorship: 'Sole Proprietorship',
        partnership: 'Partnership',
        corporation: 'Corporation',
        llc: 'LLC'
      }
      return types[type] || (type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' '))
    }
  }
}
</script>