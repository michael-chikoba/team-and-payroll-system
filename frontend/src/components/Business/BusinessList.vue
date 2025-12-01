<!-- BusinessList.vue - Updated with delete and manage admins buttons -->
<template>
  <div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Your Businesses</h2>
      <button
        @click="$emit('create-business')"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        + Register New Business
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="businesses.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg mb-4">No businesses registered yet.</p>
      <button
        @click="$emit('create-business')"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        Register Your First Business
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="business in businesses"
        :key="business.id"
        class="bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow"
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-semibold text-gray-800">{{ business.name }}</h3>
            <span
              :class="[
                'px-2 py-1 text-xs rounded-full',
                business.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]"
            >
              {{ business.status }}
            </span>
          </div>

          <div class="space-y-2 text-sm text-gray-600 mb-4">
            <p><strong>Legal Name:</strong> {{ business.legal_name }}</p>
            <p><strong>Type:</strong> {{ formatBusinessType(business.business_type) }}</p>
            <p><strong>Industry:</strong> {{ business.industry || 'N/A' }}</p>
            <p><strong>Email:</strong> {{ business.email }}</p>
            <p><strong>Currency:</strong> {{ business.currency_code }}</p>
            
            <!-- Display Admin Count -->
            <div class="pt-2 border-t border-gray-200">
              <p class="flex items-center justify-between">
                <strong>Administrators:</strong>
                <span class="text-blue-600">{{ business.admins?.length || 0 }}</span>
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-2">
            <button
              @click="switchBusiness(business)"
              class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
            >
              Switch to Business
            </button>
            
            <div class="grid grid-cols-2 gap-2">
              <button
                @click="$emit('edit-business', business)"
                class="px-3 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm"
              >
                Edit
              </button>
              
              <button
                @click="$emit('manage-admins', business)"
                class="px-3 py-2 border border-blue-300 text-blue-700 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                Admins
              </button>
            </div>
            
            <button
              @click="$emit('delete-business', business)"
              class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm"
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
import axios from 'axios';
export default {
  name: 'BusinessList',
  data() {
    return {
      businesses: [],
      loading: false
    }
  },
  async mounted() {
    await this.fetchBusinesses()
  },
  methods: {
    async fetchBusinesses() {
      this.loading = true
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load businesses'
        })
      } finally {
        this.loading = false
      }
    },
    async switchBusiness(business) {
      try {
        const response = await axios.post(`/api/admin/businesses/${business.id}/switch`)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: `Switched to ${business.name}`
        })
        
        // Reload the page or update global state
        window.location.reload()
      } catch (error) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to switch business'
        })
      }
    },
    formatBusinessType(type) {
      const types = {
        sole_proprietorship: 'Sole Proprietorship',
        partnership: 'Partnership',
        corporation: 'Corporation',
        llc: 'LLC'
      }
      return types[type] || type
    },
    formatPayPeriod(period) {
      const periods = {
        weekly: 'Weekly',
        'bi-weekly': 'Bi-Weekly',
        'semi-monthly': 'Semi-Monthly',
        monthly: 'Monthly'
      }
      return periods[period] || period
    }
  }
}
</script>