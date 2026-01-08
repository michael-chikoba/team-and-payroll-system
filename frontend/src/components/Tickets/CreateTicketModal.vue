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
        <div class="fixed inset-0 bg-black bg-opacity-25 backdrop-blur-sm" />
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
              class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
            >
              <div class="flex items-center justify-between mb-6">
                <DialogTitle class="text-2xl font-bold text-gray-900">
                  Create New Ticket
                </DialogTitle>
                <button
                  @click="closeModal"
                  class="rounded-full p-2 hover:bg-gray-100 transition-colors"
                >
                  <XMarkIcon class="w-6 h-6 text-gray-500" />
                </button>
              </div>

              <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Title -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Title <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.title"
                    type="text"
                    required
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                    placeholder="What do you need help with?"
                    :class="{ 'border-red-300': errors.title }"
                  />
                  <p v-if="errors.title" class="mt-1 text-sm text-red-600">
                    {{ errors.title[0] }}
                  </p>
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Description <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <textarea
                      v-model="form.description"
                      rows="5"
                      required
                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors resize-none"
                      placeholder="Please describe your request in detail..."
                      :class="{ 'border-red-300': errors.description }"
                    />
                    <div class="absolute bottom-2 right-2 text-xs text-gray-500">
                      {{ form.description.length }}/2000
                    </div>
                  </div>
                  <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                    {{ errors.description[0] }}
                  </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Approver -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Approver <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <select
                        v-model="form.approver_id"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors appearance-none"
                        :class="{ 'border-red-300': errors.approver_id }"
                      >
                        <option value="" disabled>Select an approver</option>
                        <option
                          v-for="approver in approvers"
                          :key="approver.id"
                          :value="approver.id"
                        >
                          {{ approver.name }} • {{ approver.email }}
                        </option>
                      </select>
                      <ChevronDownIcon class="absolute right-3 top-3.5 w-5 h-5 text-gray-400 pointer-events-none" />
                    </div>
                    <p v-if="errors.approver_id" class="mt-1 text-sm text-red-600">
                      {{ errors.approver_id[0] }}
                    </p>
                  </div>

                  <!-- Priority -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Priority <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                      <button
                        v-for="priority in priorities"
                        :key="priority.value"
                        type="button"
                        @click="form.priority = priority.value"
                        :class="[
                          'flex flex-col items-center justify-center py-3 rounded-lg border transition-all',
                          'focus:outline-none focus:ring-2 focus:ring-offset-2',
                          form.priority === priority.value
                            ? [priority.borderClass, priority.bgClass, 'ring-2 ring-offset-2', priority.ringClass]
                            : 'border-gray-200 bg-gray-50 hover:bg-gray-100'
                        ]"
                      >
                        <component
                          :is="priority.icon"
                          class="w-5 h-5 mb-1"
                          :class="form.priority === priority.value ? priority.iconClass : 'text-gray-400'"
                        />
                        <span
                          class="text-xs font-medium"
                          :class="form.priority === priority.value ? priority.textClass : 'text-gray-600'"
                        >
                          {{ priority.label }}
                        </span>
                      </button>
                    </div>
                    <p v-if="errors.priority" class="mt-1 text-sm text-red-600">
                      {{ errors.priority[0] }}
                    </p>
                  </div>
                </div>

                <!-- Due Date -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Due Date
                  </label>
                  <div class="relative">
                    <input
                      v-model="form.due_date"
                      type="date"
                      :min="minDate"
                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                      :class="{ 'border-red-300': errors.due_date }"
                    />
                    <CalendarIcon class="absolute right-3 top-3.5 w-5 h-5 text-gray-400 pointer-events-none" />
                  </div>
                  <p class="mt-1 text-sm text-gray-500">
                    Optional: Set a deadline for this request
                  </p>
                  <p v-if="errors.due_date" class="mt-1 text-sm text-red-600">
                    {{ errors.due_date[0] }}
                  </p>
                </div>

                <!-- Attachments (Optional) -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Attachments
                  </label>
                  <div
                    @click="triggerFileInput"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition-colors"
                  >
                    <CloudArrowUpIcon class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                    <p class="text-sm text-gray-600 mb-1">
                      Drop files here or click to upload
                    </p>
                    <p class="text-xs text-gray-500">
                      Supports: PDF, JPG, PNG, DOC (Max 10MB)
                    </p>
                    <input
                      ref="fileInput"
                      type="file"
                      multiple
                      class="hidden"
                      @change="handleFileUpload"
                      accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    />
                  </div>
                  <div v-if="attachments.length > 0" class="mt-3 space-y-2">
                    <div
                      v-for="(file, index) in attachments"
                      :key="index"
                      class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2"
                    >
                      <div class="flex items-center space-x-3">
                        <PaperClipIcon class="w-4 h-4 text-gray-400" />
                        <span class="text-sm text-gray-700 truncate">
                          {{ file.name }}
                        </span>
                        <span class="text-xs text-gray-500">
                          ({{ formatFileSize(file.size) }})
                        </span>
                      </div>
                      <button
                        @click="removeAttachment(index)"
                        type="button"
                        class="text-gray-400 hover:text-red-500"
                      >
                        <XMarkIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                    :disabled="loading"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                  >
                    <template v-if="loading">
                      <ArrowPathIcon class="w-4 h-4 mr-2 animate-spin" />
                      Creating...
                    </template>
                    <template v-else>
                      <CheckIcon class="w-4 h-4 mr-2" />
                      Create Ticket
                    </template>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import axios from 'axios'
import {
  XMarkIcon,
  ChevronDownIcon,
  CalendarIcon,
  CloudArrowUpIcon,
  PaperClipIcon,
  ArrowPathIcon,
  CheckIcon,
  FlagIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  approvers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'created'])

// Reactive state
const loading = ref(false)
const attachments = ref([])
const fileInput = ref(null)
const errors = ref({})

const form = reactive({
  title: '',
  description: '',
  approver_id: '',
  priority: 'medium',
  due_date: ''
})

const priorities = [
  {
    value: 'low',
    label: 'Low',
    icon: InformationCircleIcon,
    iconClass: 'text-green-600',
    bgClass: 'bg-green-50',
    borderClass: 'border-green-200',
    ringClass: 'ring-green-200',
    textClass: 'text-green-700'
  },
  {
    value: 'medium',
    label: 'Medium',
    icon: FlagIcon,
    iconClass: 'text-blue-600',
    bgClass: 'bg-blue-50',
    borderClass: 'border-blue-200',
    ringClass: 'ring-blue-200',
    textClass: 'text-blue-700'
  },
  {
    value: 'high',
    label: 'High',
    icon: ExclamationTriangleIcon,
    iconClass: 'text-yellow-600',
    bgClass: 'bg-yellow-50',
    borderClass: 'border-yellow-200',
    ringClass: 'ring-yellow-200',
    textClass: 'text-yellow-700'
  },
  {
    value: 'critical',
    label: 'Critical',
    icon: ExclamationCircleIcon,
    iconClass: 'text-red-600',
    bgClass: 'bg-red-50',
    borderClass: 'border-red-200',
    ringClass: 'ring-red-200',
    textClass: 'text-red-700'
  }
]

// Computed properties
const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

// Methods
const closeModal = () => {
  if (!loading.value) {
    resetForm()
    emit('close')
  }
}

const resetForm = () => {
  Object.keys(form).forEach(key => {
    form[key] = ''
  })
  form.priority = 'medium'
  attachments.value = []
  errors.value = {}
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  const maxSize = 10 * 1024 * 1024 // 10MB
  const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']

  files.forEach(file => {
    if (file.size > maxSize) {
      alert(`File ${file.name} exceeds 10MB limit`)
      return
    }
    
    if (!allowedTypes.includes(file.type)) {
      alert(`File type not supported: ${file.name}`)
      return
    }

    attachments.value.push(file)
  })

  // Reset file input
  event.target.value = ''
}

const removeAttachment = (index) => {
  attachments.value.splice(index, 1)
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    
    // Append form data
    Object.keys(form).forEach(key => {
      if (form[key]) {
        formData.append(key, form[key])
      }
    })

    // Append attachments
    attachments.value.forEach(file => {
      formData.append('attachments[]', file)
    })

    const response = await axios.post('/api/tickets', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    emit('created', response.data)
    resetForm()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { 
        general: [error.response?.data?.message || 'Failed to create ticket. Please try again.'] 
      }
    }
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Set default due date to 7 days from now
  const defaultDueDate = new Date()
  defaultDueDate.setDate(defaultDueDate.getDate() + 7)
  form.due_date = defaultDueDate.toISOString().split('T')[0]
})
</script>

<style scoped>
/* Custom scrollbar for select */
select::-webkit-scrollbar {
  width: 8px;
}

select::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

select::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

select::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>