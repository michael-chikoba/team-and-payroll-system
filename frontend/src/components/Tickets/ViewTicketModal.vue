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
              class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all"
            >
              <!-- Header -->
              <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-gray-100">
                      <TicketIcon class="w-6 h-6 text-gray-600" />
                    </div>
                    <div>
                      <DialogTitle class="text-xl font-bold text-gray-900 line-clamp-1">
                        {{ ticket?.title }}
                      </DialogTitle>
                      <div class="flex items-center space-x-2 mt-1">
                        <StatusBadge :status="ticket?.status" />
                        <PriorityBadge :priority="ticket?.priority" />
                        <span class="text-sm text-gray-500">
                          #{{ ticket?.id }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <button
                    @click="closeModal"
                    class="rounded-full p-2 hover:bg-gray-100 transition-colors"
                  >
                    <XMarkIcon class="w-6 h-6 text-gray-500" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="px-6 py-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                  <ArrowPathIcon class="w-8 h-8 text-blue-600 animate-spin" />
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-12">
                  <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-4" />
                  <p class="text-lg font-medium text-gray-900 mb-2">Unable to load ticket</p>
                  <p class="text-gray-600">{{ error }}</p>
                </div>

                <!-- Ticket Details -->
                <div v-else-if="ticket" class="space-y-6">
                  <!-- Basic Info Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                      <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Created By
                        </label>
                        <div class="flex items-center mt-2">
                          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <UserIcon class="w-4 h-4 text-blue-600" />
                          </div>
                          <div>
                            <p class="font-medium text-gray-900">{{ ticket.user?.name }}</p>
                            <p class="text-sm text-gray-500">{{ formatDate(ticket.created_at) }}</p>
                          </div>
                        </div>
                      </div>

                      <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Approver
                        </label>
                        <div class="flex items-center mt-2">
                          <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <CheckCircleIcon class="w-4 h-4 text-green-600" />
                          </div>
                          <div>
                            <p class="font-medium text-gray-900">{{ ticket.approver?.name || 'Not assigned' }}</p>
                            <p class="text-sm text-gray-500">{{ ticket.approver?.email }}</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="space-y-4">
                      <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Due Date
                        </label>
                        <div class="flex items-center mt-2">
                          <CalendarDaysIcon class="w-5 h-5 text-gray-400 mr-2" />
                          <span :class="[
                            'font-medium',
                            isOverdue ? 'text-red-600' : 'text-gray-900'
                          ]">
                            {{ formatDate(ticket.due_date) || 'No due date' }}
                          </span>
                          <span v-if="isOverdue" class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                            Overdue
                          </span>
                        </div>
                      </div>

                      <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Last Updated
                        </label>
                        <div class="flex items-center mt-2">
                          <ClockIcon class="w-5 h-5 text-gray-400 mr-2" />
                          <span class="font-medium text-gray-900">
                            {{ formatDate(ticket.updated_at) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                      Description
                    </label>
                    <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                      <p class="text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
                    </div>
                  </div>

                  <!-- Comments -->
                  <div v-if="ticket.comments">
                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                      Approver Comments
                    </label>
                    <div class="mt-2 p-4 bg-blue-50 rounded-lg border border-blue-100">
                      <div class="flex items-start space-x-3">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <p class="text-gray-700 whitespace-pre-wrap">{{ ticket.comments }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Attachments -->
                  <div v-if="ticket.attachments?.length">
                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                      Attachments ({{ ticket.attachments.length }})
                    </label>
                    <div class="mt-2 space-y-2">
                      <div
                        v-for="attachment in ticket.attachments"
                        :key="attachment.id"
                        class="flex items-center justify-between bg-gray-50 hover:bg-gray-100 rounded-lg px-4 py-3 transition-colors"
                      >
                        <div class="flex items-center space-x-3">
                          <PaperClipIcon class="w-5 h-5 text-gray-400" />
                          <div>
                            <p class="font-medium text-gray-900">{{ attachment.name }}</p>
                            <p class="text-sm text-gray-500">
                              {{ formatFileSize(attachment.size) }} • {{ formatDate(attachment.created_at) }}
                            </p>
                          </div>
                        </div>
                        <button
                          @click="downloadAttachment(attachment)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ArrowDownTrayIcon class="w-5 h-5" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Status History -->
                  <div>
                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                      Status History
                    </label>
                    <div class="mt-2 space-y-4">
                      <div v-if="ticket.approved_at" class="flex items-start space-x-3">
                        <div class="w-2 h-2 mt-2 rounded-full bg-green-500"></div>
                        <div class="flex-1">
                          <p class="font-medium text-gray-900">Approved</p>
                          <p class="text-sm text-gray-500">
                            By {{ ticket.approver?.name }} on {{ formatDateTime(ticket.approved_at) }}
                          </p>
                        </div>
                      </div>
                      <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 mt-2 rounded-full bg-blue-500"></div>
                        <div class="flex-1">
                          <p class="font-medium text-gray-900">Created</p>
                          <p class="text-sm text-gray-500">
                            By {{ ticket.user?.name }} on {{ formatDateTime(ticket.created_at) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="text-sm text-gray-500">
                    <span v-if="ticket">Last updated {{ timeAgo(ticket.updated_at) }}</span>
                  </div>
                  <div class="flex items-center space-x-3">
                    <button
                      v-if="canApprove"
                      @click="openApproval"
                      class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all"
                    >
                      <CheckCircleIcon class="w-4 h-4 mr-2 inline" />
                      Review & Approve
                    </button>
                    <button
                      @click="closeModal"
                      class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                    >
                      Close
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
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import axios from 'axios'
// FIXED: Lowercase 'components' to match Linux file system
import StatusBadge from '@/components/StatusBadge.vue'
import PriorityBadge from '@/components/PriorityBadge.vue'
import {
  XMarkIcon,
  TicketIcon,
  UserIcon,
  CheckCircleIcon,
  CalendarDaysIcon,
  ClockIcon,
  PaperClipIcon,
  ArrowDownTrayIcon,
  ChatBubbleLeftRightIcon,
  ArrowPathIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'status-updated'])

// Reactive state
const loading = ref(false)
const error = ref('')
const ticketDetails = ref(null)

// Computed properties
const isOverdue = computed(() => {
  if (!ticketDetails.value?.due_date) return false
  const dueDate = new Date(ticketDetails.value.due_date)
  const now = new Date()
  return dueDate < now && ticketDetails.value.status !== 'approved'
})

const canApprove = computed(() => {
  if (!ticketDetails.value) return false
  const user = JSON.parse(localStorage.getItem('user'))
  return user?.id === ticketDetails.value.approver_id && ticketDetails.value.status === 'pending'
})

// Methods
const closeModal = () => {
  ticketDetails.value = null
  error.value = ''
  emit('close')
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const timeAgo = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  
  if (seconds < 60) return 'just now'
  
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
  
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`
  
  const days = Math.floor(hours / 24)
  return `${days} day${days > 1 ? 's' : ''} ago`
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const downloadAttachment = async (attachment) => {
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}/attachments/${attachment.id}`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', attachment.name)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Download failed:', err)
    alert('Failed to download attachment')
  }
}

const openApproval = () => {
  emit('status-updated', ticketDetails.value)
  closeModal()
}

const fetchTicketDetails = async () => {
  if (!props.ticket?.id) return
  
  loading.value = true
  error.value = ''
  
  try {
    const response = await axios.get(`/api/tickets/${props.ticket.id}`)
    ticketDetails.value = response.data
  } catch (err) {
    console.error('Failed to fetch ticket details:', err)
    error.value = err.response?.data?.message || 'Failed to load ticket details'
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.ticket?.id) {
    fetchTicketDetails()
  }
})

watch(() => props.ticket, (newTicket) => {
  if (newTicket?.id) {
    fetchTicketDetails()
  }
})
</script>

<style scoped>
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>