<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="handleClose"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-white">Create New Business</h3>
              <button type="button" @click="handleClose" class="text-white hover:text-gray-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="bg-white px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
            <div class="space-y-6">
              <!-- Info Alert -->
              <div class="rounded-md bg-blue-50 p-4">
                <p class="text-sm text-blue-700">
                  Creating a business through SuperAdmin will set up the business and create/link an owner account.
                </p>
              </div>

              <!-- Business Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Business Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Business Name *</label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      placeholder="e.g., Acme Corporation"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Legal Name *</label>
                    <input
                      v-model="form.legal_name"
                      type="text"
                      required
                      placeholder="e.g., Acme Corporation Inc."
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Email *</label>
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      placeholder="business@example.com"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input
                      v-model="form.phone"
                      type="tel"
                      placeholder="+1234567890"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Owner Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Owner Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">First Name *</label>
                    <input
                      v-model="form.owner_first_name"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                    <input
                      v-model="form.owner_last_name"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Owner Email *</label>
                    <input
                      v-model="form.owner_email"
                      type="email"
                      required
                      placeholder="owner@example.com"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                      If this email exists, the business will be linked to that user. Otherwise, a new account will be created.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Subscription Settings -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Subscription Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Tier *</label>
                    <select
                      v-model="form.subscription_tier"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    >
                      <option value="basic">Basic</option>
                      <option value="standard">Standard</option>
                      <option value="premium">Premium</option>
                      <option value="enterprise">Enterprise</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Employee Limit *</label>
                    <input
                      v-model.number="form.employee_limit"
                      type="number"
                      min="1"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="flex items-center mt-6">
                      <input
                        v-model="form.is_trial"
                        type="checkbox"
                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                      />
                      <span class="ml-2 text-sm text-gray-700">Start as Trial</span>
                    </label>
                  </div>
                  <div v-if="form.is_trial">
                    <label class="block text-sm font-medium text-gray-700">Trial Days</label>
                    <input
                      v-model.number="form.trial_days"
                      type="number"
                      min="1"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div v-else class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Subscription Months</label>
                    <input
                      v-model.number="form.subscription_months"
                      type="number"
                      min="1"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Address Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Address</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Address Line 1 *</label>
                    <input
                      v-model="form.address_line_1"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">City *</label>
                    <input
                      v-model="form.city"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">State/Province *</label>
                    <input
                      v-model="form.state"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Postal Code *</label>
                    <input
                      v-model="form.postal_code"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Country ID *</label>
                    <input
                      v-model.number="form.country_id"
                      type="number"
                      required
                      placeholder="e.g., 1"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Additional Settings -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Additional Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Currency Code *</label>
                    <input
                      v-model="form.currency_code"
                      type="text"
                      required
                      maxlength="3"
                      placeholder="USD"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Pay Period *</label>
                    <select
                      v-model="form.pay_period"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    >
                      <option value="weekly">Weekly</option>
                      <option value="bi-weekly">Bi-Weekly</option>
                      <option value="semi-monthly">Semi-Monthly</option>
                      <option value="monthly">Monthly</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Business Type *</label>
                    <select
                      v-model="form.business_type"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    >
                      <option value="sole_proprietorship">Sole Proprietorship</option>
                      <option value="partnership">Partnership</option>
                      <option value="corporation">Corporation</option>
                      <option value="llc">LLC</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Error Display -->
              <div v-if="error" class="rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-sm font-medium text-white hover:bg-purple-700 focus:outline-none disabled:opacity-50"
            >
              {{ saving ? 'Creating...' : 'Create Business' }}
            </button>
            <button
              type="button"
              @click="handleClose"
              :disabled="saving"
              class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none disabled:opacity-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import axios from 'axios'

export default {
  name: 'CreateBusinessModal',
  emits: ['close', 'created'],
  setup(_, { emit }) {
    const saving = ref(false)
    const error = ref(null)
    
    const form = reactive({
      name: '',
      legal_name: '',
      email: '',
      phone: '',
      owner_first_name: '',
      owner_last_name: '',
      owner_email: '',
      subscription_tier: 'basic',
      employee_limit: 50,
      is_trial: true,
      trial_days: 30,
      subscription_months: 12,
      address_line_1: '',
      city: '',
      state: '',
      postal_code: '',
      country_id: 1,
      currency_code: 'USD',
      pay_period: 'monthly',
      business_type: 'corporation',
    })
    
    const handleSubmit = async () => {
      saving.value = true
      error.value = null
      try {
        const response = await axios.post('/api/superadmin/businesses', form)
        if (response.data.success) {
          emit('created')
        } else {
          error.value = response.data.message || 'Failed to create'
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred'
      } finally {
        saving.value = false
      }
    }
    
    const handleClose = () => {
      if (!saving.value) emit('close')
    }
    
    return { form, saving, error, handleSubmit, handleClose }
  }
}
</script>