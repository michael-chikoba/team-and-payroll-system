<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="closeModal">
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
            <DialogPanel
              class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
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
                  <UserPlusIcon class="h-6 w-6 text-indigo-600" />
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                    Reassign Ticket
                  </DialogTitle>
                  
                  <div class="mt-4 space-y-4">
                    <!-- Ticket Info -->
                    <div class="bg-slate-50 p-3 rounded-lg">
                      <p class="text-sm font-medium text-slate-600 mb-1">Ticket Details</p>
                      <p class="text-sm text-slate-900 font-medium">{{ ticket.title }}</p>
                      <div class="flex items-center gap-2 mt-1">
                        <PriorityBadge :priority="ticket.priority" />
                        <StatusBadge :status="ticket.status" />
                      </div>
                    </div>
                    
                    <!-- Current Assignee -->
                    <div>
                      <p class="text-sm font-medium text-slate-600 mb-2">Currently Assigned To</p>
                      <div class="flex items-center p-2 bg-blue-50 rounded-lg">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                          <UserIcon class="h-4 w-4 text-blue-600" />
                        </div>
                        <div>
                          <p class="text-sm font-medium text-blue-900">
                            {{ getApproverName(ticket) }}
                          </p>
                          <p class="text-xs text-blue-600">{{ ticket.approver?.email || '' }}</p>
                        </div>
                      </div>
                    </div>
                    
                    <!-- New Assignee Selection -->
                    <div>
                      <label for="newApprover" class="block text-sm font-medium text-gray-700 mb-2">
                        Select New Approver
                      </label>
                      <div v-if="approvers.length === 0" class="text-center py-4">
                        <p class="text-sm text-slate-500">No other approvers available</p>
                      </div>
                      <div v-else class="space-y-2 max-h-60 overflow-y-auto pr-2">
                        <div
                          v-for="approver in approvers"
                          :key="approver.id"
                          @click="selectApprover(approver)"
                          :class="[
                            'flex items-center p-3 rounded-lg border cursor-pointer transition-all',
                            selectedApprover?.id === approver.id
                              ? 'border-indigo-300 bg-indigo-50 ring-2 ring-indigo-200'
                              : 'border-slate-200 hover:bg-slate-50'
                          ]"
                        >
                          <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center mr-3">
                            <UserIcon class="h-5 w-5 text-slate-600" />
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">
                              {{ approver.name }}
                            </p>
                            <p class="text-xs text-slate-500 truncate">
                              {{ approver.email }}
                            </p>
                            <p v-if="approver.position" class="text-xs text-slate-400 mt-0.5">
                              {{ approver.position }}
                            </p>
                          </div>
                          <div v-if="selectedApprover?.id === approver.id" class="text-indigo-600">
                            <CheckIcon class="h-5 w-5" />
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Reason for Reassignment -->
                    <div>
                      <label for="reassignmentReason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Reassignment
                      </label>
                      <textarea
                        id="reassignmentReason"
                        v-model="reason"
                        rows="2"
                        placeholder="Explain why you're reassigning this ticket..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      />
                    </div>
                    
                    <!-- Error Message -->
                    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-3">
                      <p class="text-sm text-red-700">{{ error }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 sm:mt-5 sm:flex sm:flex-row-reverse gap-3">
                <button
                  type="button"
                  @click="reassignTicket"
                  :disabled="isReassigning || !selectedApprover"
                  :class="[
                    'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto',
                    isReassigning || !selectedApprover
                      ? 'bg-indigo-400 cursor-not-allowed'
                      : 'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                  ]"
                >
                  <ArrowPathIcon v-if="isReassigning" class="animate-spin h-4 w-4 mr-2" />
                  {{ isReassigning ? 'Reassigning...' : 'Reassign Ticket' }}
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
import { ref } from 'vue'
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
  UserPlusIcon,
  UserIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'
import PriorityBadge from '@/components/PriorityBadge.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  ticket: Object,
  approvers: Array
})

const emit = defineEmits(['close', 'reassigned'])

const selectedApprover = ref(null)
const reason = ref('')
const isReassigning = ref(false)
const error = ref(null)

const getApproverName = (ticket) => {
  const approver = ticket?.approver
  if (!approver) return 'Not Assigned'
  if (approver.name) return approver.name
  if (approver.first_name || approver.last_name) {
    return `${approver.first_name || ''} ${approver.last_name || ''}`.trim() || 'Unknown'
  }
  return approver.email || 'Unknown'
}

const selectApprover = (approver) => {
  selectedApprover.value = approver
}

const reassignTicket = async () => {
  if (!props.ticket || !selectedApprover.value) return
  
  isReassigning.value = true
  error.value = null
  
  try {
    const response = await axios.post(`/api/tickets/${props.ticket.id}/reassign`, {
      new_approver_id: selectedApprover.value.id,
      reason: reason.value || null
    })
    
    if (response.data.success) {
      emit('reassigned', {
        ticket: response.data.ticket,
        old_approver: props.ticket.approver,
        new_approver: selectedApprover.value
      })
      closeModal()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to reassign ticket'
    console.error('Error reassigning ticket:', err)
  } finally {
    isReassigning.value = false
  }
}

const closeModal = () => {
  selectedApprover.value = null
  reason.value = ''
  error.value = null
  emit('close')
}
</script>