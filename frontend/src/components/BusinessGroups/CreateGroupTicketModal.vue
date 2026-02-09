<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Center modal -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
        <div class="absolute top-0 right-0 pt-4 pr-4">
          <button
            @click="$emit('close')"
            class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none"
          >
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="sm:flex sm:items-start">
          <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              Create Cross-Business Ticket
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Create a ticket that can be viewed and managed across multiple businesses in your group.
            </p>

            <form @submit.prevent="createTicket" class="mt-6 space-y-4">
              <!-- Business Group Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Business Group <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.business_group_id"
                  @change="loadGroupBusinesses"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                >
                  <option value="">Select a business group</option>
                  <option v-for="group in businessGroups" :key="group.id" :value="group.id">
                    {{ group.name }}
                  </option>
                </select>
              </div>

              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Title <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.title"
                  type="text"
                  required
                  placeholder="Enter ticket title"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Description <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  required
                  placeholder="Describe the issue or request..."
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                ></textarea>
              </div>

              <!-- Priority and Status Row -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                  <select
                    v-model="form.priority"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select
                    v-model="form.status"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                  >
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                  </select>
                </div>
              </div>

              <!-- Assign to Business -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Assign to Business (Optional)
                </label>
                <select
                  v-model="form.assigned_business_id"
                  :disabled="!form.business_group_id || loadingBusinesses"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-100"
                >
                  <option value="">Not assigned</option>
                  <option v-for="business in groupBusinesses" :key="business.id" :value="business.id">
                    {{ business.name }}
                  </option>
                </select>
                <p v-if="loadingBusinesses" class="mt-1 text-sm text-gray-500">Loading businesses...</p>
              </div>

              <!-- Error Message -->
              <div v-if="error" class="rounded-md bg-red-50 p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm text-red-800">{{ error }}</p>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button
                  type="submit"
                  :disabled="submitting"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                >
                  <span v-if="!submitting">Create Ticket</span>
                  <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                  </span>
                </button>
                <button
                  type="button"
                  @click="$emit('close')"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'
import axios from 'axios'

export default {
  name: 'CreateGroupTicketModal',
  
  props: {
    businessGroups: {
      type: Array,
      required: true
    }
  },
  
  emits: ['close', 'created'],
  
  setup(props, { emit }) {
    const submitting = ref(false)
    const error = ref('')
    const loadingBusinesses = ref(false)
    const groupBusinesses = ref([])
    
    const form = ref({
      business_group_id: '',
      title: '',
      description: '',
      priority: 'medium',
      status: 'open',
      assigned_business_id: '',
    })

    const loadGroupBusinesses = async () => {
      if (!form.value.business_group_id) {
        groupBusinesses.value = []
        return
      }

      loadingBusinesses.value = true
      try {
        const response = await axios.get(`/api/business-groups/${form.value.business_group_id}/businesses`)
        if (response.data.success) {
          groupBusinesses.value = response.data.data
        }
      } catch (err) {
        console.error('Error loading businesses:', err)
      } finally {
        loadingBusinesses.value = false
      }
    }

    const createTicket = async () => {
      error.value = ''
      submitting.value = true

      try {
        const response = await axios.post('/api/group-tickets', form.value)
        
        if (response.data.success) {
          emit('created', response.data.data)
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create ticket'
      } finally {
        submitting.value = false
      }
    }

    return {
      form,
      submitting,
      error,
      loadingBusinesses,
      groupBusinesses,
      loadGroupBusinesses,
      createTicket,
    }
  }
}
</script>