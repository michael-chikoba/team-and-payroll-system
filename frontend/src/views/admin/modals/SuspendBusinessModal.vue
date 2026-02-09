<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="handleClose"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-red-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <svg class="h-6 w-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="text-lg font-medium text-white">Suspend Business</h3>
              </div>
              <button type="button" @click="handleClose" class="text-white hover:text-gray-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="bg-white px-6 py-6">
            <div class="space-y-4">
              <!-- Warning Message -->
              <div class="rounded-md bg-yellow-50 p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Warning</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                      <p>You are about to suspend the business "{{ business.name }}". This action will:</p>
                      <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>Prevent all users from accessing the system</li>
                        <li>Stop all active operations and workflows</li>
                        <li>Maintain all data (can be reactivated later)</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Business Information -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ getInitials(business.name) }}</span>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">{{ business.name }}</p>
                    <p class="text-sm text-gray-500">{{ business.email }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ business.current_employee_count }} employees
                    </p>
                  </div>
                </div>
              </div>

              <!-- Suspension Reason -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Suspension Reason *
                </label>
                <textarea
                  v-model="form.reason"
                  required
                  rows="4"
                  placeholder="Please provide a detailed reason for suspending this business..."
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                  This reason will be recorded in the activity log and visible to superadmins.
                </p>
              </div>

              <!-- Confirmation -->
              <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <label class="flex items-start">
                  <input
                    v-model="form.confirmed"
                    type="checkbox"
                    required
                    class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500"
                  />
                  <span class="ml-2 text-sm text-red-900">
                    I understand that this action will immediately suspend the business and all associated users will lose access to the system.
                  </span>
                </label>
              </div>

              <!-- Error Display -->
              <div v-if="error" class="rounded-md bg-red-100 p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
            <button
              type="submit"
              :disabled="saving || !form.confirmed || !form.reason"
              class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Suspending...' : 'Suspend Business' }}
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
  name: 'SuspendBusinessModal',
  
  props: {
    business: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'suspended'],
  
  setup(props, { emit }) {
    const saving = ref(false)
    const error = ref(null)
    
    const form = reactive({
      reason: '',
      confirmed: false
    })
    
    const handleSubmit = async () => {
      if (!form.confirmed || !form.reason) {
        return
      }
      
      saving.value = true
      error.value = null
      
      try {
        const response = await axios.post(`/api/superadmin/businesses/${props.business.id}/suspend`, {
          reason: form.reason
        })
        
        if (response.data.success) {
          emit('suspended')
        } else {
          error.value = response.data.message || 'Failed to suspend business'
        }
      } catch (err) {
        console.error('Error suspending business:', err)
        error.value = err.response?.data?.message || 'An error occurred while suspending the business'
      } finally {
        saving.value = false
      }
    }
    
    const handleClose = () => {
      if (!saving.value) {
        emit('close')
      }
    }
    
    const getInitials = (name) => {
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }
    
    return {
      form,
      saving,
      error,
      handleSubmit,
      handleClose,
      getInitials
    }
  }
}
</script>