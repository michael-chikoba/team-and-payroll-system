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
                    
                    <!-- New Status Selection -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select New Status
                      </label>
                      <div class="grid grid-cols-2 gap-2">
                        <button
                          v-for="option in statusOptions"
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
                  </div>
                </div>
              </div>

              <div class="mt-6 sm:mt-5 sm:flex sm:flex-row-reverse gap-3">
                <button
                  type="button"
                  @click="updateStatus"
                  :disabled="isUpdating || !selectedStatus"
                  :class="[
                    'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto',
                    isUpdating || !selectedStatus
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
  PlayIcon
} from '@heroicons/vue/24/outline'
import StatusBadge from '@/components/StatusBadge.vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'status-updated'])

const selectedStatus = ref(props.ticket?.status || 'pending')
const comments = ref('')
const isUpdating = ref(false)
const error = ref(null)

const statusOptions = computed(() => [
  {
    value: 'pending',
    label: 'Pending',
    icon: ClockIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-amber-300 bg-amber-50 text-amber-700 ring-2 ring-amber-200'
  },
  {
    value: 'approved',
    label: 'Approved',
    icon: CheckCircleIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-emerald-300 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-200'
  },
  {
    value: 'rejected',
    label: 'Rejected',
    icon: XCircleIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-red-300 bg-red-50 text-red-700 ring-2 ring-red-200'
  },
  {
    value: 'in_progress',
    label: 'In Progress',
    icon: PlayIcon,
    defaultClass: 'border-slate-200 text-slate-600 hover:bg-slate-50',
    selectedClass: 'border-indigo-300 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-200'
  }
])

const updateStatus = async () => {
  if (!props.ticket || !selectedStatus.value) return
  
  isUpdating.value = true
  error.value = null
  
  try {
    // Use update-status endpoint
    const response = await axios.patch(`/api/tickets/${props.ticket.id}/update-status`, {
      status: selectedStatus.value,
      comments: comments.value || null
    })
    
    // Your controller returns 'message' not 'success'
    if (response.data.message) {
      emit('status-updated', {
        ticket: response.data.ticket,
        old_status: props.ticket.status,
        new_status: selectedStatus.value
      })
      closeModal()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update status'
    console.error('Error updating status:', err)
  } finally {
    isUpdating.value = false
  }
}
const closeModal = () => {
  selectedStatus.value = props.ticket?.status || 'pending'
  comments.value = ''
  error.value = null
  emit('close')
}
</script>