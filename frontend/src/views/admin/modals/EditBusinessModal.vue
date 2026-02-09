<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="handleClose"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-white">Edit Business</h3>
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
              <!-- Basic Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Basic Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Business Name *</label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Legal Name *</label>
                    <input
                      v-model="form.legal_name"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Email *</label>
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input
                      v-model="form.phone"
                      type="tel"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Subscription Settings -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Subscription Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status *</label>
                    <select
                      v-model="form.status"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
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
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Subscription End</label>
                    <input
                      v-model="form.subscription_end_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Trial End</label>
                    <input
                      v-model="form.trial_end_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Admin Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Admin Notes</label>
                <textarea
                  v-model="form.admin_notes"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                  placeholder="Internal notes..."
                ></textarea>
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
              {{ saving ? 'Saving...' : 'Update Business' }}
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
  name: 'EditBusinessModal',
  props: {
    business: { type: Object, required: true }
  },
  emits: ['close', 'updated'],
  setup(props, { emit }) {
    const saving = ref(false)
    const error = ref(null)
    
    const form = reactive({
      name: props.business.name,
      legal_name: props.business.legal_name,
      email: props.business.email,
      phone: props.business.phone,
      status: props.business.status,
      subscription_tier: props.business.subscription_tier,
      employee_limit: props.business.employee_limit,
      subscription_end_date: props.business.subscription_end_date ? props.business.subscription_end_date.split('T')[0] : null,
      trial_end_date: props.business.trial_end_date ? props.business.trial_end_date.split('T')[0] : null,
      admin_notes: props.business.admin_notes || '',
    })
    
    const handleSubmit = async () => {
      saving.value = true
      error.value = null
      try {
        const response = await axios.put(`/api/superadmin/businesses/${props.business.id}`, form)
        if (response.data.success) {
          emit('updated')
        } else {
          error.value = response.data.message || 'Failed to update'
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