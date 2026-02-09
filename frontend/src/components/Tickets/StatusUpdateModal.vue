<template>
  <TransitionRoot as="template" :show="show">
    <!-- Updated z-index to z-[2000] to overlay AdminLayout (z-1000) -->
    <Dialog as="div" class="relative z-[2000]" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" />
      </TransitionChild>

      <!-- Updated container z-index to z-[2000] -->
      <div class="fixed inset-0 z-[2000] overflow-y-auto">
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
            <DialogPanel
              class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6"
            >
              <div class="absolute right-0 top-0 pr-4 pt-4">
                <button
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  @click="closeModal"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-6 w-6" />
                </button>
              </div>

              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                  <CheckCircleIcon class="h-6 w-6 text-indigo-600" />
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                    Update Ticket Status
                  </DialogTitle>
                  
                  <div class="mt-4 space-y-4">
                    <!-- Current Status -->
                    <div class="bg-slate-50 p-3 rounded-lg">
                      <p class="text-sm font-medium text-slate-600">Current Status</p>
                      <div class="mt-1">
                        <StatusBadge :status="ticket.status" size="lg" />
                      </div>
                    </div>
                    
                    <!-- ✅ FIXED: Only show valid status transitions -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select New Status
                      </label>
                      
                      <!-- Show available options or message if none -->
                      <div v-if="availableStatusOptions.length > 0" class="grid grid-cols-2 gap-2">
                        <button
                          v-for="option in availableStatusOptions"
                          :key="option.value"
                          @click="selectedStatus = option.value"
                          :class="[
                            'flex flex-col items-center justify-center p-3 rounded-lg border transition-all',
                            selectedStatus === option.value
                              ? option.selectedClass
                              : option.defaultClass
                          ]"
                        >
                          <component :is="option.icon" class="w-5 h-5 mb-1" />
                          <span class="text-sm font-medium">{{ option.label }}</span>
                        </button>
                      </div>
                      
                      <!-- Show message if no transitions available -->
                      <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm text-amber-800">
                          No status transitions available from current status.
                        </p>
                      </div>
                      
                      <!-- ✅ Show helpful guidance -->
                      <p class="mt-2 text-xs text-gray-500">
                        {{ statusGuidance }}
                      </p>
                    </div>
                    
                    <!-- Comments -->
                    <div>
                      <label for="comments" class="block text-sm font-medium text-gray-700 mb-2">
                        Comments
                      </label>
                      <textarea
                        id="comments"
                        v-model="comments"
                        rows="3"
                        placeholder="Add any comments about this status update..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      />
                    </div>
                    
                    <!-- ✅ Show error messages -->
                    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                      <p class="text-sm text-red-800 font-medium">{{ error }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 sm:mt-5 sm:flex sm:flex-row-reverse gap-3">
                <button
                  type="button"
                  @click="updateStatus"
                  :disabled="isUpdating || !selectedStatus || !canSubmit"
                  :class="[
                    'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto',
                    isUpdating || !selectedStatus || !canSubmit
                      ? 'bg-indigo-400 cursor-not-allowed'
                      : 'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                  ]"
                >
                  <ArrowPathIcon v-if="isUpdating" class="animate-spin h-4 w-4 mr-2" />
                  {{ isUpdating ? 'Updating...' : 'Update Status' }}
                </button>
                <button
                  type="button"
                  @click="closeModal"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                  Cancel
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
  PlayIcon,
  CheckBadgeIcon,
  ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline'
import StatusBadge from '@/components/StatusBadge.vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'status-updated'])

const selectedStatus = ref('')
const comments = ref('')
const isUpdating = ref(false)
const error = ref(null)

// ✅ FIXED: Define status transitions (must match backend)
const statusTransitions = {
  'pending': ['approved', 'rejected', 'in_progress'],
  'in_progress': ['resolved', 'closed', 'reopened'],
  'resolved': ['closed', 'reopened'],
  'closed': ['reopened'],
  'reopened': ['in_progress', 'resolved', 'closed'],
  'approved': ['in_progress', 'rejected'],
  'rejected': ['reopened'],
}

// ✅ All possible status options with their styling
const allStatusOptions = {
  'pending': {
    value: 'pending',
    label: 'Pending',
    icon: ClockIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-amber-300 bg-amber-50 text-amber-700 ring-2 ring-amber-200'
  },
  'approved': {
    value: 'approved',
    label: 'Approved',
    icon: CheckBadgeIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-emerald-300 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-200'
  },
  'rejected': {
    value: 'rejected',
    label: 'Rejected',
    icon: XCircleIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-red-300 bg-red-50 text-red-700 ring-2 ring-red-200'
  },
  'in_progress': {
    value: 'in_progress',
    label: 'In Progress',
    icon: PlayIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-indigo-300 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-200'
  },
  'resolved': {
    value: 'resolved',
    label: 'Resolved',
    icon: CheckCircleIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-green-300 bg-green-50 text-green-700 ring-2 ring-green-200'
  },
  'closed': {
    value: 'closed',
    label: 'Closed',
    icon: CheckCircleIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-gray-300 bg-gray-50 text-gray-700 ring-2 ring-gray-200'
  },
  'reopened': {
    value: 'reopened',
    label: 'Reopened',
    icon: ArrowUturnLeftIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-purple-300 bg-purple-50 text-purple-700 ring-2 ring-purple-200'
  }
}

// ✅ FIXED: Only show valid status options based on current status
const availableStatusOptions = computed(() => {
  if (!props.ticket?.status) return []
  
  const currentStatus = props.ticket.status
  const availableStatuses = statusTransitions[currentStatus] || []
  
  return availableStatuses
    .map(status => allStatusOptions[status])
    .filter(option => option !== undefined)
})

// ✅ Show helpful guidance text
const statusGuidance = computed(() => {
  if (!props.ticket?.status) return ''
  
  const available = statusTransitions[props.ticket.status] || []
  if (available.length === 0) {
    return 'No transitions available from current status.'
  }
  
  return `Available transitions: ${available.map(s => allStatusOptions[s]?.label || s).join(', ')}`
})

// ✅ Check if can submit
const canSubmit = computed(() => {
  return selectedStatus.value && 
         selectedStatus.value !== props.ticket?.status &&
         availableStatusOptions.value.some(opt => opt.value === selectedStatus.value)
})

const updateStatus = async () => {
  if (!props.ticket || !selectedStatus.value || !canSubmit.value) return
  
  // ✅ Client-side validation
  if (selectedStatus.value === props.ticket.status) {
    error.value = 'Ticket is already in this status'
    return
  }
  
  const availableStatuses = statusTransitions[props.ticket.status] || []
  if (!availableStatuses.includes(selectedStatus.value)) {
    error.value = `Cannot transition from '${props.ticket.status}' to '${selectedStatus.value}'`
    return
  }
  
  isUpdating.value = true
  error.value = null
  
  try {
    console.log('Updating ticket status:', {
      ticketId: props.ticket.id,
      currentStatus: props.ticket.status,
      newStatus: selectedStatus.value,
      comments: comments.value
    })
    
    // Use update-status endpoint
    const response = await axios.post(`/api/tickets/${props.ticket.id}/update-status`, {
      status: selectedStatus.value,
      comments: comments.value || null,
      send_email: true
    })
    
    console.log('Status update response:', response.data)
    
    // Your controller returns 'message' not 'success'
    if (response.data.message || response.data.success) {
      emit('status-updated', {
        ticket: response.data.ticket,
        old_status: props.ticket.status,
        new_status: selectedStatus.value
      })
      closeModal()
    }
  } catch (err) {
    console.error('Error updating status:', err)
    
    // ✅ Better error handling
    if (err.response?.status === 422) {
      const errors = err.response.data.errors
      if (errors?.status) {
        error.value = errors.status[0]
      } else {
        error.value = err.response.data.message || 'Validation failed'
      }
      
      // ✅ Log debug info if available
      if (err.response.data.debug) {
        console.log('Backend debug info:', err.response.data.debug)
      }
    } else if (err.response?.status === 403) {
      error.value = 'You do not have permission to update this ticket'
    } else {
      error.value = err.response?.data?.message || 'Failed to update status'
    }
  } finally {
    isUpdating.value = false
  }
}

const closeModal = () => {
  selectedStatus.value = ''
  comments.value = ''
  error.value = null
  emit('close')
}
</script>