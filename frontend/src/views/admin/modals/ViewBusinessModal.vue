<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Center modal -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
              Business Details
            </h3>
            <button
              @click="$emit('close')"
              class="text-white hover:text-gray-200 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="bg-white px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
          <!-- Business Info -->
          <div class="mb-6">
            <div class="flex items-center mb-4">
              <div class="h-16 w-16 rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center">
                <span class="text-white font-bold text-2xl">{{ getInitials(business.name) }}</span>
              </div>
              <div class="ml-4">
                <h2 class="text-2xl font-bold text-gray-900">{{ business.name }}</h2>
                <p class="text-sm text-gray-500">{{ business.legal_name }}</p>
              </div>
            </div>

            <!-- Status Badge -->
            <div class="mb-4">
              <span
                :class="getStatusClass(business)"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ getStatusText(business) }}
              </span>
            </div>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Contact Information -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Contact Information</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Email</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.email }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Phone</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.phone || 'N/A' }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Website</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.website || 'N/A' }}</p>
              </div>
            </div>

            <!-- Business Details -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Business Details</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Registration Number</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.registration_number || 'N/A' }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Tax ID</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.tax_identification_number || 'N/A' }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Business Type</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatBusinessType(business.business_type) }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Industry</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.industry || 'N/A' }}</p>
              </div>
            </div>

            <!-- Address -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Address</h4>
              
              <div>
                <p class="text-sm text-gray-900">{{ business.address_line_1 }}</p>
                <p v-if="business.address_line_2" class="text-sm text-gray-900">{{ business.address_line_2 }}</p>
                <p class="text-sm text-gray-900">{{ business.city }}, {{ business.state }} {{ business.postal_code }}</p>
                <p class="text-sm text-gray-900">{{ business.country?.name || 'N/A' }}</p>
              </div>
            </div>

            <!-- Subscription Info -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Subscription</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Tier</label>
                <p class="mt-1">
                  <span :class="getTierBadgeClass(business.subscription_tier)">
                    {{ formatTier(business.subscription_tier) }}
                  </span>
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Type</label>
                <p class="mt-1 text-sm text-gray-900">
                  {{ business.is_trial ? 'Trial' : 'Paid' }}
                </p>
              </div>

              <div v-if="business.is_trial">
                <label class="block text-sm font-medium text-gray-500">Trial End Date</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(business.trial_end_date) }}</p>
              </div>

              <div v-else>
                <label class="block text-sm font-medium text-gray-500">Subscription End Date</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(business.subscription_end_date) }}</p>
              </div>
            </div>

            <!-- Employee Info -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Employees</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Current / Limit</label>
                <p class="mt-1 text-sm text-gray-900">
                  {{ business.current_employee_count }} / {{ business.employee_limit }}
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div
                    class="h-2 rounded-full transition-all"
                    :class="getUsageBarClass(business.employee_usage_percentage)"
                    :style="{ width: `${Math.min(business.employee_usage_percentage || 0, 100)}%` }"
                  ></div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Usage</label>
                <p class="mt-1 text-sm text-gray-900">{{ Math.round(business.employee_usage_percentage || 0) }}%</p>
              </div>
            </div>

            <!-- Settings -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 border-b pb-2">Settings</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Currency</label>
                <p class="mt-1 text-sm text-gray-900">{{ business.currency_code }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Pay Period</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatPayPeriod(business.pay_period) }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-500">Created</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(business.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Owner Information -->
          <div v-if="business.admins && business.admins.length > 0" class="mt-6 pt-6 border-t">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Owner & Admins</h4>
            <div class="space-y-2">
              <div
                v-for="admin in business.admins"
                :key="admin.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ admin.first_name }} {{ admin.last_name }}
                  </p>
                  <p class="text-sm text-gray-500">{{ admin.email }}</p>
                </div>
                <span class="text-xs px-2 py-1 bg-purple-100 text-purple-800 rounded-full">
                  {{ admin.pivot?.role || 'Admin' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Admin Notes -->
          <div v-if="business.admin_notes" class="mt-6 pt-6 border-t">
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Admin Notes</h4>
            <p class="text-sm text-gray-700 bg-yellow-50 p-4 rounded-lg">{{ business.admin_notes }}</p>
          </div>

          <!-- Suspension Info -->
          <div v-if="business.suspended_at" class="mt-6 pt-6 border-t">
            <h4 class="text-lg font-semibold text-red-900 mb-2">Suspension Information</h4>
            <div class="bg-red-50 p-4 rounded-lg space-y-2">
              <div>
                <label class="block text-sm font-medium text-red-700">Suspended At</label>
                <p class="text-sm text-red-900">{{ formatDate(business.suspended_at) }}</p>
              </div>
              <div v-if="business.suspension_reason">
                <label class="block text-sm font-medium text-red-700">Reason</label>
                <p class="text-sm text-red-900">{{ business.suspension_reason }}</p>
              </div>
              <div v-if="business.suspended_by_admin">
                <label class="block text-sm font-medium text-red-700">Suspended By</label>
                <p class="text-sm text-red-900">
                  {{ business.suspended_by_admin.first_name }} {{ business.suspended_by_admin.last_name }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-3">
          <button
            @click="$emit('edit', business)"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Edit Business
          </button>
          <button
            @click="$emit('close')"
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ViewBusinessModal',
  
  props: {
    business: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'edit'],
  
  methods: {
    getInitials(name) {
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },
    
    getStatusClass(business) {
      if (business.suspended_at) {
        return 'bg-red-100 text-red-800'
      }
      if (business.status === 'active') {
        return 'bg-green-100 text-green-800'
      }
      return 'bg-gray-100 text-gray-800'
    },
    
    getStatusText(business) {
      if (business.suspended_at) return 'Suspended'
      return business.status.charAt(0).toUpperCase() + business.status.slice(1)
    },
    
    getTierBadgeClass(tier) {
      const classes = {
        basic: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
        standard: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        premium: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
        enterprise: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'
      }
      return classes[tier] || classes.basic
    },
    
    formatTier(tier) {
      return tier ? tier.charAt(0).toUpperCase() + tier.slice(1) : 'Basic'
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
    },
    
    getUsageBarClass(percentage) {
      if (percentage >= 90) return 'bg-red-500'
      if (percentage >= 70) return 'bg-yellow-500'
      return 'bg-green-500'
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>