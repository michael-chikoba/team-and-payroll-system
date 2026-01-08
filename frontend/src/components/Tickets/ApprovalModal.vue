<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" class="relative z-50" @close="closeModal">
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
                  >
                    <XMarkIcon class="w-6 h-6 text-white" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="px-6 py-6 space-y-6">
                <!-- Ticket Summary -->
                <div class="bg-gray-50 rounded-xl p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <p class="text-sm font-medium text-gray-500">Ticket ID</p>
                      <p class="text-lg font-semibold text-gray-900">#{{ ticket?.id }}</p>
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-500">Requester</p>
                      <p class="text-lg font-semibold text-gray-900">{{ ticket?.user?.name }}</p>
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
                    <p class="text-sm font-medium text-gray-500 mb-2">Title</p>
                    <p class="text-gray-900">{{ ticket?.title }}</p>
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
                      :class="[
                        'flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all',
                        'focus:outline-none focus:ring-2 focus:ring-offset-2',
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
                    {{ errors.status[0] }}
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
                  ></textarea>
                  <div class="flex justify-between items-center mt-1">
                    <p v-if="errors.comments" class="text-sm text-red-600">
                      {{ errors.comments[0] }}
                    </p>
                    <span class="text-sm text-gray-500">
                      {{ form.comments.length }}/1000
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
                    <p class="text-sm text-red-800">{{ errors.general[0] }}</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="text-sm text-gray-500">
                    You are approving as: <span class="font-medium text-gray-700">{{ currentUser?.name }}</span>
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
                      class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
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

const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'approved'])

// Reactive state
const loading = ref(false)
const errors = ref({})
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
}

const formatDate = (dateString) => {
  if (!dateString) return 'No due date'
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const selectDecision = (status) => {
  form.status = status
  errors.value = {}
}

const submitApproval = async () => {
  if (!form.status) {
    errors.value = { status: ['Please select a decision'] }
    return
  }

  loading.value = true
  errors.value = {}

  try {
    const payload = {
      status: form.status,
      comments: form.comments,
      send_email: form.send_email
    }

    const response = await axios.patch(`/api/tickets/${props.ticket.id}/status`, payload)
    
    // Show success message
    showSuccessMessage()
    
    // Emit event and close modal
    emit('approved', response.data)
    resetForm()
    
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else if (error.response?.status === 403) {
      errors.value = { 
        general: ['You are not authorized to approve this ticket.'] 
      }
    } else {
      errors.value = { 
        general: [error.response?.data?.message || 'Failed to submit decision. Please try again.'] 
      }
    }
  } finally {
    loading.value = false
  }
}

const showSuccessMessage = () => {
  const messages = {
    approved: 'Ticket approved successfully! An email has been sent to the requester.',
    rejected: 'Ticket rejected. The requester has been notified.',
    in_progress: 'Ticket marked as In Progress. The requester has been notified.'
  }
  
  // You could use a toast notification library here
  alert(messages[form.status] || 'Decision submitted successfully!')
}

// Watchers
watch(() => props.show, (newVal) => {
  if (!newVal) {
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
</style>