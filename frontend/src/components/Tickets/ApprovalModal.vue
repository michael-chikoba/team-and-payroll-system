<template>
  <!-- Teleport the modal to body to avoid z-index conflicts -->
  <Teleport to="body">
    <TransitionRoot appear :show="show" as="template">
      <Dialog as="div" class="relative z-[9999]" @close="closeModal">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel
                class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all"
              >
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <CheckCircleIcon class="w-8 h-8 text-white" />
                      <DialogTitle class="text-xl font-bold text-white">
                        Review & Approve Ticket
                      </DialogTitle>
                    </div>
                    <button
                      @click="closeModal"
                      class="rounded-full p-1 hover:bg-blue-800 transition-colors"
                      :disabled="loading"
                    >
                      <XMarkIcon class="w-6 h-6 text-white" />
                    </button>
                  </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-6 space-y-6">
                  <!-- Admin Info Banner -->
                  <div v-if="showAdminBanner" class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-4">
                    <div class="flex items-start">
                      <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                      </div>
                      <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-indigo-900">
                          <span v-if="isAssignedApprover">You are the assigned approver for this ticket.</span>
                          <span v-else>As a business admin, you can approve this ticket on behalf of your team.</span>
                        </p>
                        <p class="mt-1 text-sm text-indigo-700">
                          Originally assigned to: <span class="font-semibold">{{ getApproverName }}</span>
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Ticket Summary -->
                  <div class="bg-gray-50 rounded-xl p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <p class="text-sm font-medium text-gray-500">Ticket ID</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ ticket?.id }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-500">Requester</p>
                        <p class="text-lg font-semibold text-gray-900">{{ getUserName(ticket?.user) }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-500">Priority</p>
                        <PriorityBadge :priority="ticket?.priority" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-500">Due Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ formatDate(ticket?.due_date) }}</p>
                      </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                      <p class="text-sm font-medium text-gray-500 mb-1">Title</p>
                      <p class="text-gray-900 font-medium">{{ ticket?.title }}</p>
                    </div>
                    
                    <div class="mt-3">
                      <p class="text-sm font-medium text-gray-500 mb-1">Description</p>
                      <p class="text-gray-700 text-sm">{{ ticket?.description }}</p>
                    </div>
                    
                    <div v-if="ticket?.comments" class="mt-3 pt-3 border-t border-gray-200">
                      <p class="text-sm font-medium text-gray-500 mb-1">Previous Comments</p>
                      <p class="text-gray-700 text-sm italic">{{ ticket?.comments }}</p>
                    </div>
                  </div>

                  <!-- Decision Options -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-4">
                      Select Decision <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <button
                        v-for="option in decisionOptions"
                        :key="option.value"
                        @click="selectDecision(option.value)"
                        :disabled="loading"
                        :class="[
                          'flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all',
                          'focus:outline-none focus:ring-2 focus:ring-offset-2',
                          'disabled:opacity-50 disabled:cursor-not-allowed',
                          form.status === option.value
                            ? option.selectedClasses
                            : option.unselectedClasses
                        ]"
                      >
                        <component
                          :is="option.icon"
                          class="w-10 h-10 mb-3"
                        />
                        <span class="text-lg font-semibold mb-1">{{ option.label }}</span>
                        <span class="text-sm text-center text-gray-600">{{ option.description }}</span>
                      </button>
                    </div>
                    <p v-if="errors.status" class="mt-2 text-sm text-red-600">
                      {{ errors.status }}
                    </p>
                  </div>

                  <!-- Comments -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Comments
                      <span class="text-sm font-normal text-gray-500">
                        (Optional - Provide feedback or reason for your decision)
                      </span>
                    </label>
                    <textarea
                      v-model="form.comments"
                      rows="4"
                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors resize-none"
                      placeholder="Add comments about your decision..."
                      :class="{ 'border-red-300': errors.comments }"
                      :disabled="loading"
                    ></textarea>
                    <div class="flex justify-between items-center mt-1">
                      <p v-if="errors.comments" class="text-sm text-red-600">
                        {{ errors.comments }}
                      </p>
                      <span class="text-sm text-gray-500">
                        {{ form.comments?.length || 0 }}/1000
                      </span>
                    </div>
                  </div>

                  <!-- Additional Options -->
                  <div v-if="form.status === 'approved'" class="space-y-4">
                    <div class="flex items-center">
                      <input
                        id="send-email"
                        v-model="form.send_email"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        :disabled="loading"
                      />
                      <label for="send-email" class="ml-2 block text-sm text-gray-900">
                        Send email notification to requester
                      </label>
                    </div>
                    
                    <div v-if="isDueDateSoon" class="flex items-center p-4 bg-yellow-50 rounded-lg">
                      <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 mr-3" />
                      <p class="text-sm text-yellow-800">
                        This ticket is due soon. Consider setting a follow-up date.
                      </p>
                    </div>
                  </div>

                  <!-- Validation Errors -->
                  <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                      <ExclamationCircleIcon class="w-5 h-5 text-red-600 mr-3" />
                      <p class="text-sm text-red-800">{{ errors.general }}</p>
                    </div>
                  </div>
                  
                  <!-- Success Message -->
                  <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                      <CheckCircleIcon class="w-5 h-5 text-green-600 mr-3" />
                      <p class="text-sm text-green-800">{{ successMessage }}</p>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-6 py-4">
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                      You are approving as: <span class="font-medium text-gray-700">{{ currentUser?.name || currentUser?.email }}</span>
                      <span v-if="!isAssignedApprover" class="ml-1 text-indigo-600 font-semibold">(Business Admin)</span>
                    </div>
                    <div class="flex items-center space-x-3">
                      <button
                        @click="closeModal"
                        :disabled="loading"
                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors disabled:opacity-50"
                      >
                        Cancel
                      </button>
                      <button
                        @click="submitApproval"
                        :disabled="loading || !form.status"
                        :class="[
                          'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all',
                          'disabled:opacity-50 disabled:cursor-not-allowed',
                          getButtonClass
                        ]"
                      >
                        <template v-if="loading">
                          <ArrowPathIcon class="w-4 h-4 mr-2 animate-spin" />
                          Processing...
                        </template>
                        <template v-else>
                          <CheckIcon class="w-4 h-4 mr-2" />
                          {{ getButtonText }}
                        </template>
                      </button>
                    </div>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import axios from 'axios'
import PriorityBadge from '@/components/PriorityBadge.vue'
import {
  XMarkIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
  ClockIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'

// Props
const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'approved'])

// Initialize toast
const toast = useToast()

// Reactive state
const loading = ref(false)
const errors = ref({})
const successMessage = ref('')
const currentUser = ref(JSON.parse(localStorage.getItem('user')))

const form = reactive({
  status: '',
  comments: '',
  send_email: true
})

const decisionOptions = [
  {
    value: 'approved',
    label: 'Approve',
    description: 'Accept and proceed with the request',
    icon: CheckCircleIcon,
    selectedClasses: 'border-green-500 bg-green-50 ring-2 ring-green-200',
    unselectedClasses: 'border-gray-200 hover:border-green-300 hover:bg-green-50'
  },
  {
    value: 'rejected',
    label: 'Reject',
    description: 'Decline the request',
    icon: XCircleIcon,
    selectedClasses: 'border-red-500 bg-red-50 ring-2 ring-red-200',
    unselectedClasses: 'border-gray-200 hover:border-red-300 hover:bg-red-50'
  },
  {
    value: 'in_progress',
    label: 'In Progress',
    description: 'Request needs more work',
    icon: ClockIcon,
    selectedClasses: 'border-blue-500 bg-blue-50 ring-2 ring-blue-200',
    unselectedClasses: 'border-gray-200 hover:border-blue-300 hover:bg-blue-50'
  }
]

// Computed properties
const isAssignedApprover = computed(() => {
  return props.ticket?.approver_id === currentUser.value?.id
})

const showAdminBanner = computed(() => {
  return currentUser.value?.role === 'admin'
})

const getApproverName = computed(() => {
  const approver = props.ticket?.approver
  if (!approver) return 'Unassigned'
  if (approver.name) return approver.name
  if (approver.first_name || approver.last_name) {
    return `${approver.first_name || ''} ${approver.last_name || ''}`.trim()
  }
  return approver.email || 'Unassigned'
})

const isDueDateSoon = computed(() => {
  if (!props.ticket?.due_date) return false
  const dueDate = new Date(props.ticket.due_date)
  const now = new Date()
  const daysDifference = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24))
  return daysDifference <= 3 && daysDifference >= 0
})

const getButtonText = computed(() => {
  switch (form.status) {
    case 'approved': return 'Approve Ticket'
    case 'rejected': return 'Reject Ticket'
    case 'in_progress': return 'Mark as In Progress'
    default: return 'Submit Decision'
  }
})

const getButtonClass = computed(() => {
  switch (form.status) {
    case 'approved': return 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:ring-green-500'
    case 'rejected': return 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:ring-red-500'
    case 'in_progress': return 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-blue-500'
    default: return 'bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 focus:ring-gray-500'
  }
})

// Methods
const closeModal = () => {
  if (!loading.value) {
    resetForm()
    emit('close')
  }
}

const resetForm = () => {
  form.status = ''
  form.comments = ''
  form.send_email = true
  errors.value = {}
  successMessage.value = ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'No due date'
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      weekday: 'short',
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch (error) {
    return 'Invalid Date'
  }
}

const getUserName = (user) => {
  if (!user) return 'Unknown'
  if (user.name) return user.name
  if (user.first_name || user.last_name) {
    return `${user.first_name || ''} ${user.last_name || ''}`.trim()
  }
  return user.email || 'Unknown'
}

const selectDecision = (status) => {
  form.status = status
  errors.value = {}
  successMessage.value = ''
}

const submitApproval = async () => {
  if (!form.status) {
    errors.value = { status: 'Please select a decision' }
    return
  }

  loading.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    const payload = {
      status: form.status,
      comments: form.comments,
      send_email: form.send_email
    }

    console.log('📤 Submitting approval for ticket:', props.ticket?.id)
    console.log('📤 Payload:', payload)
    console.log('📤 Current user:', currentUser.value)
    console.log('📤 Is assigned approver:', isAssignedApprover.value)
    console.log('📤 API Endpoint:', `/api/tickets/${props.ticket?.id}/update-status`)

    const response = await axios.post(`/api/tickets/${props.ticket.id}/update-status`, payload)
    
    console.log('✅ Approval response:', response.data)
    
    // Show success message
    const messages = {
      approved: isAssignedApprover.value 
        ? 'Ticket approved successfully!' 
        : 'Ticket approved successfully (as business admin)!',
      rejected: 'Ticket rejected successfully.',
      in_progress: 'Ticket marked as In Progress.'
    }
    
    successMessage.value = messages[form.status] || 'Decision submitted successfully!'
    
    // Show toast notification
    toast.success(successMessage.value, {
      timeout: 3000,
      position: 'top-right'
    })
    
    // Wait a moment for user to see success message
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Emit event and close modal
    emit('approved', response.data.ticket)
    resetForm()
    
    // Dispatch event to refresh ticket count
    window.dispatchEvent(new CustomEvent('ticket-updated'))
    
    // Close modal after successful submission
    setTimeout(() => {
      closeModal()
    }, 500)
    
  } catch (error) {
    console.error('❌ Error approving ticket:', error)
    console.error('❌ Error response:', error.response)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      
      if (typeof errors.value === 'object' && !Array.isArray(errors.value)) {
        const errorMessages = []
        for (const key in errors.value) {
          if (Array.isArray(errors.value[key])) {
            errorMessages.push(...errors.value[key])
          } else {
            errorMessages.push(errors.value[key])
          }
        }
        errors.value = { general: errorMessages.join(', ') }
      }
    } else if (error.response?.status === 403) {
      errors.value = { 
        general: 'You are not authorized to approve this ticket. Please ensure you are an admin in the same business.' 
      }
      toast.error('Unauthorized to approve this ticket', {
        timeout: 3000,
        position: 'top-right'
      })
    } else if (error.response?.status === 404) {
      errors.value = { 
        general: 'Ticket not found or route does not exist.' 
      }
      toast.error('Ticket not found', {
        timeout: 3000,
        position: 'top-right'
      })
    } else if (error.code === 'ERR_NETWORK') {
      errors.value = { 
        general: 'Network error. Please check your connection.' 
      }
      toast.error('Network error. Please check your connection.', {
        timeout: 3000,
        position: 'top-right'
      })
    } else {
      const errorMsg = error.response?.data?.message || 
                      error.response?.data?.error || 
                      'Failed to submit decision. Please try again.'
      errors.value = { 
        general: errorMsg
      }
      toast.error(errorMsg, {
        timeout: 3000,
        position: 'top-right'
      })
    }
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal) {
    console.log('🎫 Approval modal opened for ticket:', props.ticket)
    console.log('👤 Current user:', currentUser.value)
    console.log('✅ Is assigned approver:', isAssignedApprover.value)
    console.log('👮 Is admin:', currentUser.value?.role === 'admin')
    
    resetForm()
  } else {
    resetForm()
  }
})

// Validation for comments length
watch(() => form.comments, (newComments) => {
  if (newComments.length > 1000) {
    form.comments = newComments.substring(0, 1000)
  }
})
</script>

<style scoped>
/* Animation for selected option */
@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
  }
}

.border-green-500.ring-2 {
  animation: pulse 2s infinite;
}

/* Smooth transitions */
button {
  transition: all 0.2s ease-in-out;
}

textarea {
  min-height: 120px;
}

/* Loading state styles */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Success message animation */
.bg-green-50 {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Hover effects for decision buttons */
button[class*="hover:border-"]:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Focus styles for accessibility */
button:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

textarea:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>
