<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
              <!-- Header -->
              <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-5">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                      <TicketIcon class="h-6 w-6 text-white" />
                    </div>
                    <DialogTitle class="text-xl font-bold text-white">
                      Create New Ticket
                    </DialogTitle>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="text-white/80 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Form Body -->
              <form @submit.prevent="handleSubmit" class="px-6 py-6 space-y-6">
                <!-- Title -->
                <div>
                  <label for="title" class="block text-sm font-semibold text-slate-700 mb-2">
                    Ticket Title <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    required
                    placeholder="Brief description of the issue"
                    class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                    :class="{ 'border-red-500': errors.title }"
                  />
                  <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Description -->
                <div>
                  <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">
                    Description <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="description"
                    v-model="form.description"
                    required
                    rows="4"
                    placeholder="Provide detailed information about the ticket"
                    class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                    :class="{ 'border-red-500': errors.description }"
                  ></textarea>
                  <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                </div>

                <!-- Priority and Due Date Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <!-- Priority -->
                  <div>
                    <label for="priority" class="block text-sm font-semibold text-slate-700 mb-2">
                      Priority <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="priority"
                      v-model="form.priority"
                      required
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.priority }"
                    >
                      <option value="">Select Priority</option>
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="critical">Critical</option>
                    </select>
                    <p v-if="errors.priority" class="mt-1 text-sm text-red-600">{{ errors.priority }}</p>
                  </div>

                  <!-- Due Date -->
                  <div>
                    <label for="due_date" class="block text-sm font-semibold text-slate-700 mb-2">
                      Due Date <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="due_date"
                      v-model="form.due_date"
                      type="date"
                      required
                      :min="minDate"
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.due_date }"
                    />
                    <p v-if="errors.due_date" class="mt-1 text-sm text-red-500">{{ errors.due_date }}</p>
                  </div>
                </div>

                <!-- Approver -->
                <div>
                  <label for="approver_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    Assign to Approver <span class="text-red-500">*</span>
                  </label>
                 
                  <!-- Loading State -->
                  <div v-if="loadingApprovers && internalApprovers.length === 0" class="flex items-center gap-2 px-4 py-3 border border-slate-300 rounded-lg bg-slate-50">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600"></div>
                    <span class="text-sm text-slate-500">Loading approvers...</span>
                  </div>
                 
                  <!-- No Approvers -->
                  <div v-else-if="!loadingApprovers && internalApprovers.length === 0" class="px-4 py-3 border border-amber-300 rounded-lg bg-amber-50">
                    <p class="text-sm text-amber-800">No approvers available. Please contact your administrator.</p>
                  </div>
                 
                  <!-- Approvers List -->
                  <select
                    v-else
                    id="approver_id"
                    v-model="form.approver_id"
                    required
                    class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                    :class="{ 'border-red-500': errors.approver_id }"
                  >
                    <option value="">Select Approver</option>
                    <option
                      v-for="approver in internalApprovers"
                      :key="approver.id"
                      :value="approver.id"
                    >
                      {{ approver.name }} ({{ approver.email }})
                    </option>
                  </select>
                  <p v-if="errors.approver_id" class="mt-1 text-sm text-red-500">{{ errors.approver_id }}</p>
                </div>

                <!-- General Error Message -->
                <div v-if="errors.general" class="rounded-lg bg-red-50 p-4 border border-red-200">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-red-800">{{ errors.general }}</p>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                    :disabled="submitting"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    :disabled="submitting || (loadingApprovers && internalApprovers.length === 0)"
                  >
                    <span v-if="!submitting">Create Ticket</span>
                    <span v-else class="flex items-center gap-2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                      Creating...
                    </span>
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon, TicketIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  approvers: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'created'])

// Reactive State
const form = ref({
  title: '',
  description: '',
  priority: '',
  due_date: '',
  approver_id: ''
})

const errors = ref({})
const submitting = ref(false)
const loadingApprovers = ref(false)
const internalApprovers = ref([])

// Computed
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

// Methods
const fetchApprovers = async () => {
  loadingApprovers.value = true
  try {
    console.log('📞 [Modal] Fetching approvers from API...')
    const response = await axios.get('/api/tickets/approvers')
    console.log('📦 [Modal] Raw API response:', response.data)
   
    let rawList = []
   
    if (Array.isArray(response.data)) {
      rawList = response.data
    } else if (response.data && Array.isArray(response.data.approvers)) {
      rawList = response.data.approvers
    } else if (response.data && Array.isArray(response.data.data)) {
      rawList = response.data.data
    }
   
    console.log('🔍 [Modal] Extracted raw list:', rawList)
   
    const processedList = rawList
      .filter(item => item && typeof item === 'object')
      .map(approver => {
        const name = approver.name ||
                     `${approver.first_name || ''} ${approver.last_name || ''}`.trim() ||
                     approver.email ||
                     'Unknown User'
       
        return {
          id: approver.id,
          name: name,
          email: approver.email || '',
          first_name: approver.first_name || '',
          last_name: approver.last_name || '',
          business_id: approver.business_id,
          position: approver.position || 'Admin'
        }
      })
      .filter(approver => approver.id)
   
    console.log('✅ [Modal] Processed approvers:', processedList)
    console.log('📊 [Modal] Total approvers:', processedList.length)
   
    internalApprovers.value = processedList
   
  } catch (error) {
    console.error('❌ [Modal] Error fetching approvers:', error)
    internalApprovers.value = []
  } finally {
    loadingApprovers.value = false
  }
}

const resetForm = () => {
  form.value = {
    title: '',
    description: '',
    priority: '',
    due_date: '',
    approver_id: ''
  }
  errors.value = {}
}

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
    isValid = false
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
    isValid = false
  }

  if (!form.value.priority) {
    errors.value.priority = 'Priority is required'
    isValid = false
  }

  if (!form.value.due_date) {
    errors.value.due_date = 'Due date is required'
    isValid = false
  }

  if (!form.value.approver_id) {
    errors.value.approver_id = 'Approver is required'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const response = await axios.post('/api/tickets', form.value)
   
    if (response.data) {
      resetForm()
      emit('created', response.data)
    }
  } catch (error) {
    console.error('Error creating ticket:', error)
   
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else if (error.response?.data?.message) {
      errors.value.general = error.response.data.message
    } else {
      errors.value.general = 'Failed to create ticket. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

// Watch for modal opening to fetch approvers
watch(() => props.show, (newVal) => {
  if (newVal) {
    console.log('🚪 [Modal] Modal opened')
    console.log('📦 [Modal] Props approvers:', props.approvers)
   
    // Use approvers from props if available
    if (props.approvers && props.approvers.length > 0) {
      console.log('✅ [Modal] Using approvers from parent:', props.approvers.length)
      internalApprovers.value = props.approvers
      loadingApprovers.value = false
    } else {
      console.log('🔄 [Modal] No approvers passed, fetching...')
      fetchApprovers()
    }
    
    // Reset form when modal opens
    resetForm()
  } else {
    resetForm()
    internalApprovers.value = []
    loadingApprovers.value = false
  }
}, { immediate: true })

// Watch for changes in approvers prop
watch(() => props.approvers, (newApprovers) => {
  console.log('🔄 [Modal] Approvers prop updated:', newApprovers?.length)
  if (newApprovers && newApprovers.length > 0 && props.show) {
    console.log('✅ [Modal] Updating internal approvers from prop')
    internalApprovers.value = newApprovers
  }
})

// Initialize
onMounted(() => {
  console.log('🎫 [Modal] Component mounted')
})
</script>