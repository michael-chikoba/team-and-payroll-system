<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Business Details
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                ID: {{ business.id }}
              </p>
            </div>
            <button
              @click="close"
              class="text-gray-400 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-6">
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Basic Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Business Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.name }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Legal Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.legal_name || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.phone || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ formatBusinessType(business.business_type) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Industry</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ business.industry || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        getStatusClass(business.status)
                      ]">
                        {{ formatStatus(business.status) }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Verification</dt>
                    <dd class="mt-1">
                      <span v-if="business.is_verified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Verified
                      </span>
                      <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Pending Verification
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Registration Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Registration Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Registration Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.registration_number || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Tax Identification Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.tax_identification_number || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Country</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.country?.name || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Currency</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.currency_code || 'N/A' }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Contact Information & Additional Details -->
            <div class="space-y-6">
              <!-- Contact Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Contact Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Full Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ business.full_address || 'Not provided' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Address Line 1</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.address_line_1 || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Address Line 2</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.address_line_2 || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">City</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.city || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">State</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.state || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Postal Code</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.postal_code || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Website</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      <a v-if="business.website" :href="business.website" target="_blank" class="text-blue-600 hover:text-blue-900">
                        {{ business.website }}
                      </a>
                      <span v-else class="text-gray-500">Not provided</span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Fax</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.fax || 'Not provided' }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Financial Settings -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Financial Settings</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Fiscal Year Start</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(business.fiscal_year_start) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Pay Period</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ business.pay_period || 'N/A' }}</dd>
                  </div>
                </dl>
              </div>

              <!-- System Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">System Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(business.created_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(business.updated_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Admin Count</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ getAdminCount(business) }} administrators</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          <!-- Admins Section -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <h4 class="text-md font-medium text-gray-900 mb-4">Administrators ({{ getAdminCount(business) }})</h4>
            
            <div v-if="getAdminCount(business) === 0" class="text-center py-4 bg-gray-50 rounded-lg">
              <p class="text-gray-500">No administrators found</p>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="admin in business.admins"
                :key="admin.id"
                class="flex items-center p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-3">
                  <div class="text-sm font-medium text-gray-900">
                    {{ admin.first_name }} {{ admin.last_name }}
                  </div>
                  <div class="text-sm text-gray-500">{{ admin.email }}</div>
                  <div class="flex items-center mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                      {{ admin.role || 'admin' }}
                    </span>
                    <span v-if="admin.pivot?.is_primary" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                      Primary
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
            <button
              @click="close"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Close
            </button>
            <button
              @click="edit"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Edit Business
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BusinessDetailModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    business: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'edit'],
  
  methods: {
    close() {
      this.$emit('close');
    },
    
    edit() {
      this.$emit('edit', this.business);
    },
    
    getStatusClass(status) {
      switch (status) {
        case 'active':
          return 'bg-green-100 text-green-800';
        case 'suspended':
          return 'bg-red-100 text-red-800';
        case 'pending':
          return 'bg-yellow-100 text-yellow-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    },
    
    formatStatus(status) {
      if (!status) return 'Unknown';
      return status.charAt(0).toUpperCase() + status.slice(1);
    },
    
    formatBusinessType(type) {
      if (!type) return 'N/A';
      return type
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    },
    
    getAdminCount(business) {
      if (!business.admins || !Array.isArray(business.admins)) return 0;
      return business.admins.length;
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    
    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
}
</script>