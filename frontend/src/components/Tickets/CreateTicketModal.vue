<template>
  <TransitionRoot appear :show="show" as="template">
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
                    type="button"
                    @click="$emit('close')"
                    class="text-white/80 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Form Body -->
              <form @submit.prevent="handleSubmit" class="px-6 py-6 space-y-6">
                <!-- Ticket Type Selection -->
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-3">
                    Ticket Type <span class="text-red-500">*</span>
                  </label>
                  <div class="grid grid-cols-3 gap-3">
                    <button
                      type="button"
                      v-for="type in ticketTypes"
                      :key="type.slug"
                      @click="selectTicketType(type)"
                      :class="[
                        'flex flex-col items-center justify-center p-4 rounded-lg border-2 transition-all duration-200',
                        form.type === type.slug
                          ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                          : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                      ]"
                    >
                      <div :class="[
                        'p-3 rounded-full mb-2',
                        form.type === type.slug ? 'bg-indigo-100' : 'bg-slate-100'
                      ]">
                        <component
                          :is="getTypeIcon(type.slug)"
                          :class="[
                            'h-6 w-6',
                            form.type === type.slug ? 'text-indigo-600' : 'text-slate-500'
                          ]"
                        />
                      </div>
                      <span class="text-sm font-medium text-slate-900">{{ type.name }}</span>
                      <span class="text-xs text-slate-500 mt-1 text-center">{{ type.description }}</span>
                    </button>
                  </div>
                  <p v-if="errors.type" class="mt-2 text-sm text-red-600">{{ errors.type }}</p>
                </div>

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
                    :placeholder="titlePlaceholder"
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
                    :placeholder="descriptionPlaceholder"
                    class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                    :class="{ 'border-red-500': errors.description }"
                  ></textarea>
                  <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                </div>

                <!-- Category and Subcategory Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <!-- Category -->
                  <div>
                    <label for="category" class="block text-sm font-semibold text-slate-700 mb-2">
                      Category <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="category"
                      v-model="form.category"
                      required
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.category }"
                    >
                      <option value="">Select Category</option>
                      <option
                        v-for="category in selectedTypeCategories"
                        :key="category"
                        :value="category"
                      >
                        {{ category }}
                      </option>
                    </select>
                    <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
                  </div>

                  <!-- Subcategory -->
                  <div>
                    <label for="subcategory" class="block text-sm font-semibold text-slate-700 mb-2">
                      Subcategory
                    </label>
                    <select
                      id="subcategory"
                      v-model="form.subcategory"
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.subcategory }"
                    >
                      <option value="">Select Subcategory (Optional)</option>
                      <option
                        v-for="subcategory in selectedTypeSubcategories"
                        :key="subcategory"
                        :value="subcategory"
                      >
                        {{ subcategory }}
                      </option>
                    </select>
                    <p v-if="errors.subcategory" class="mt-1 text-sm text-red-600">{{ errors.subcategory }}</p>
                  </div>
                </div>

                <!-- Priority, Department, and Estimated Hours Row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
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

                  <!-- Department -->
                  <div>
                    <label for="department_id" class="block text-sm font-semibold text-slate-700 mb-2">
                      Department
                    </label>
                    <select
                      id="department_id"
                      v-model="form.department_id"
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.department_id }"
                    >
                      <option value="">Select Department</option>
                      <option
                        v-for="dept in departments"
                        :key="dept.id"
                        :value="dept.id"
                      >
                        {{ dept.name }}
                      </option>
                    </select>
                    <p v-if="errors.department_id" class="mt-1 text-sm text-red-600">{{ errors.department_id }}</p>
                  </div>

                  <!-- Estimated Hours -->
                  <div>
                    <label for="estimated_hours" class="block text-sm font-semibold text-slate-700 mb-2">
                      Estimated Hours
                    </label>
                    <input
                      id="estimated_hours"
                      v-model.number="form.estimated_hours"
                      type="number"
                      min="0"
                      max="1000"
                      step="0.5"
                      placeholder="e.g., 2.5"
                      class="block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      :class="{ 'border-red-500': errors.estimated_hours }"
                    />
                    <p v-if="errors.estimated_hours" class="mt-1 text-sm text-red-600">{{ errors.estimated_hours }}</p>
                  </div>
                </div>

                <!-- Due Date and Assignees Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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

                  <!-- Assignees Section -->
                  <div>
                    <label for="assigned_to" class="block text-sm font-semibold text-slate-700 mb-2">
                      Assign To
                    </label>
                    <Multiselect
                      v-model="form.assigned_to"
                      :options="availableUsers"
                      :multiple="true"
                      :searchable="true"
                      :close-on-select="false"
                      placeholder="Select team members..."
                      label="name"
                      track-by="id"
                      value-prop="id"
                      :object="false"
                      class="multiselect-custom"
                      :loading="loadingUsers"
                    />
                    <p v-if="errors.assigned_to" class="mt-1 text-sm text-red-600">{{ errors.assigned_to }}</p>
                    
                    <!-- Display selected assignees as chips -->
                    <div v-if="selectedAssignees.length > 0" class="mt-2 flex flex-wrap gap-2">
                      <div v-for="user in selectedAssignees" :key="user.id" class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                        {{ user.name }}
                        <button
                          type="button"
                          @click="removeAssignee(user.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          <XMarkIcon class="h-3 w-3" />
                        </button>
                      </div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                      Select team members who will work on this ticket
                    </p>
                  </div>
                </div>

                <!-- APPROVER SECTION (Based on the simpler example) -->
                <div v-if="showApproverField">
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

                <!-- Attachments -->
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Attachments
                  </label>
                  <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors">
                    <div class="space-y-1 text-center">
                      <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-slate-400" />
                      <div class="flex text-sm text-slate-600">
                        <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                          <span>Upload files</span>
                          <input
                            id="file-upload"
                            type="file"
                            multiple
                            @change="handleFileUpload"
                            class="sr-only"
                          />
                        </label>
                        <p class="pl-1">or drag and drop</p>
                      </div>
                      <p class="text-xs text-slate-500">
                        PNG, JPG, PDF up to 10MB
                      </p>
                    </div>
                  </div>
                  <!-- File list preview -->
                  <div v-if="form.attachments && form.attachments.length > 0" class="mt-3 space-y-2">
                    <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center justify-between p-2 bg-slate-50 rounded-lg">
                      <div class="flex items-center gap-2">
                        <DocumentIcon class="h-5 w-5 text-slate-400" />
                        <span class="text-sm text-slate-700 truncate">{{ file.name }}</span>
                      </div>
                      <button
                        type="button"
                        @click="removeAttachment(index)"
                        class="text-slate-400 hover:text-red-500"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- SLA Information -->
                <div v-if="selectedTicketType" class="rounded-lg bg-slate-50 p-4">
                  <div class="flex items-center gap-2">
                    <ClockIcon class="h-5 w-5 text-slate-500" />
                    <span class="text-sm font-medium text-slate-700">SLA Information</span>
                  </div>
                  <div class="mt-2 grid grid-cols-2 gap-4">
                    <div>
                      <span class="text-xs text-slate-500">Type SLA</span>
                      <p class="text-sm font-semibold text-slate-900">{{ selectedTicketType.sla_hours }} hours</p>
                    </div>
                    <div>
                      <span class="text-xs text-slate-500">Status</span>
                      <p class="text-sm font-semibold text-slate-900">
                        {{ selectedTicketType.requires_approval ? 'Requires Approval' : 'Auto-assigned' }}
                      </p>
                    </div>
                  </div>
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
                    :disabled="submitting || (loadingApprovers && internalApprovers.length === 0 && showApproverField)"
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
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import Multiselect from '@vueform/multiselect'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  TicketIcon,
  ExclamationTriangleIcon,
  DocumentArrowUpIcon,
  DocumentIcon,
  TrashIcon,
  ClockIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  assignableUsers: {
    type: Array,
    default: () => []
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
  type: 'issue',
  title: '',
  description: '',
  category: '',
  subcategory: '',
  department_id: '',
  approver_id: '',
  priority: 'medium',
  due_date: '',
  estimated_hours: null,
  assigned_to: [], // Store user IDs as array
  attachments: []
})

const errors = ref({})
const submitting = ref(false)
const loadingTicketTypes = ref(false)
const loadingDepartments = ref(false)
const loadingUsers = ref(false)
const loadingApprovers = ref(false)
const ticketTypes = ref([])
const departments = ref([])
const users = ref([])
const internalApprovers = ref([])

// Computed Properties
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const selectedTicketType = computed(() => {
  return ticketTypes.value.find(type => type.slug === form.value.type)
})

const showApproverField = computed(() => {
  return selectedTicketType.value?.requires_approval || false
})

const selectedTypeCategories = computed(() => {
  return selectedTicketType.value?.categories || []
})

const selectedTypeSubcategories = computed(() => {
  return selectedTicketType.value?.subcategories || []
})

const availableUsers = computed(() => {
  return props.assignableUsers.length > 0 ? props.assignableUsers : users.value
})

const selectedAssignees = computed(() => {
  if (!form.value.assigned_to || form.value.assigned_to.length === 0) return []
  return availableUsers.value.filter(user => form.value.assigned_to.includes(user.id))
})

const titlePlaceholder = computed(() => {
  switch(form.value.type) {
    case 'issue':
      return 'Brief description of the issue or problem'
    case 'request':
      return 'What are you requesting?'
    case 'change_request':
      return 'Describe the change you want to make'
    default:
      return 'Brief description'
  }
})

const descriptionPlaceholder = computed(() => {
  switch(form.value.type) {
    case 'issue':
      return 'Describe the issue in detail, including steps to reproduce, error messages, and impact...'
    case 'request':
      return 'Provide details about your request, why it\'s needed, and any specific requirements...'
    case 'change_request':
      return 'Describe the change, business justification, impact analysis, and implementation plan...'
    default:
      return 'Provide detailed information'
  }
})

// Methods
const getTypeIcon = (type) => {
  switch(type) {
    case 'issue':
      return ExclamationCircleIcon
    case 'request':
      return DocumentTextIcon
    case 'change_request':
      return AdjustmentsHorizontalIcon
    default:
      return TicketIcon
  }
}

const selectTicketType = (type) => {
  form.value.type = type.slug
  form.value.category = ''
  form.value.subcategory = ''
  form.value.approver_id = ''
  
  if (type.slug) {
    fetchCategories(type.slug)
  }
}

const removeAssignee = (userId) => {
  form.value.assigned_to = form.value.assigned_to.filter(id => id !== userId)
}

// Enhanced Approvers Fetching (from second example)
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

const fetchUsers = async () => {
  if (props.assignableUsers.length > 0) {
    users.value = props.assignableUsers
    return
  }
  
  loadingUsers.value = true
  try {
    const response = await axios.get('/api/users/assignable')
    users.value = response.data || []
  } catch (error) {
    console.error('Error fetching users:', error)
    users.value = []
  } finally {
    loadingUsers.value = false
  }
}

const fetchTicketTypes = async () => {
  loadingTicketTypes.value = true
  try {
    const response = await axios.get('/api/tickets/types')
    ticketTypes.value = response.data || []
    
    if (ticketTypes.value.length > 0 && !form.value.type) {
      form.value.type = ticketTypes.value[0].slug
      fetchCategories(ticketTypes.value[0].slug)
    }
  } catch (error) {
    console.error('Error fetching ticket types:', error)
    ticketTypes.value = []
  } finally {
    loadingTicketTypes.value = false
  }
}

const fetchCategories = async (typeSlug) => {
  try {
    const response = await axios.get(`/api/tickets/types/${typeSlug}/categories`)
    if (response.data) {
      const typeIndex = ticketTypes.value.findIndex(t => t.slug === typeSlug)
      if (typeIndex !== -1) {
        ticketTypes.value[typeIndex].categories = response.data.categories || []
        ticketTypes.value[typeIndex].subcategories = response.data.subcategories || []
      }
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

const fetchDepartments = async () => {
  loadingDepartments.value = true
  try {
    const response = await axios.get('/api/tickets/departments')
    departments.value = response.data || []
  } catch (error) {
    console.error('Error fetching departments:', error)
    departments.value = []
  } finally {
    loadingDepartments.value = false
  }
}

const resetForm = () => {
  form.value = {
    type: 'issue',
    title: '',
    description: '',
    category: '',
    subcategory: '',
    department_id: '',
    approver_id: '',
    priority: 'medium',
    due_date: '',
    estimated_hours: null,
    assigned_to: [], // Reset to empty array
    attachments: []
  }
  errors.value = {}
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  const maxSize = 10 * 1024 * 1024 // 10MB
  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']
  
  files.forEach(file => {
    if (file.size > maxSize) {
      errors.value.attachments = `File "${file.name}" exceeds 10MB limit`
      return
    }
    
    if (!allowedTypes.includes(file.type)) {
      errors.value.attachments = `File "${file.name}" has unsupported format`
      return
    }
    
    form.value.attachments.push(file)
  })
  
  event.target.value = ''
}

const removeAttachment = (index) => {
  form.value.attachments.splice(index, 1)
}

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.type) {
    errors.value.type = 'Ticket type is required'
    isValid = false
  }

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
    isValid = false
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
    isValid = false
  }

  if (!form.value.category) {
    errors.value.category = 'Category is required'
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

  // Validate due date is not in the past
  if (form.value.due_date) {
    const today = new Date().toISOString().split('T')[0]
    if (form.value.due_date < today) {
      errors.value.due_date = 'Due date cannot be in the past'
      isValid = false
    }
  }

  // Only require approver for request and change_request types
  if (showApproverField.value && !form.value.approver_id) {
    errors.value.approver_id = 'Approver is required for this ticket type'
    isValid = false
  }

  // Ensure assigned_to is always an array
  if (!Array.isArray(form.value.assigned_to)) {
    form.value.assigned_to = []
  }

  return isValid
}

const prepareFormData = () => {
  // Ensure assigned_to is always an array
  let assignedToArray = form.value.assigned_to;
  
  if (!assignedToArray) {
    assignedToArray = [];
  } else if (!Array.isArray(assignedToArray)) {
    assignedToArray = [assignedToArray];
  }
  
  const data = {
    type: form.value.type,
    title: form.value.title,
    description: form.value.description,
    category: form.value.category,
    subcategory: form.value.subcategory || null,
    department_id: form.value.department_id || null,
    approver_id: form.value.approver_id || null, // Send null for issue type
    priority: form.value.priority,
    due_date: form.value.due_date,
    estimated_hours: form.value.estimated_hours || null,
    assigned_to: assignedToArray
  }
  
  console.log('Submitting ticket data:', data)
  return data
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const data = prepareFormData()
    
    if (form.value.attachments.length > 0) {
      const formData = new FormData()
      
      // Append regular fields
      Object.keys(data).forEach(key => {
        const value = data[key]
        if (key === 'assigned_to' && Array.isArray(value)) {
          // Handle assigned_to array
          if (value.length > 0) {
            value.forEach(item => {
              formData.append('assigned_to[]', item)
            })
          } else {
            // Send empty array as empty string
            formData.append('assigned_to', '')
          }
        } else {
          formData.append(key, value || '')
        }
      })
      
      // Append attachments
      form.value.attachments.forEach(file => {
        formData.append('attachments[]', file)
      })
      
      const response = await axios.post('/api/tickets', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      if (response.data) {
        resetForm()
        emit('created', response.data)
      }
    } else {
      // No attachments, send as JSON
      const response = await axios.post('/api/tickets', data)
      
      if (response.data) {
        resetForm()
        emit('created', response.data)
      }
    }
  } catch (error) {
    console.error('Error creating ticket:', error.response || error)
    
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

// Watchers
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
    
    fetchTicketTypes()
    fetchDepartments()
    fetchUsers()
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

onMounted(() => {
  import('@vueform/multiselect/themes/default.css')
  console.log('🎫 [Modal] Component mounted')
  
  if (props.assignableUsers.length === 0) {
    fetchUsers()
  }
})
</script>

<style scoped>
:deep(.multiselect-custom) {
  --ms-radius: 0.5rem;
  --ms-border-width: 1px;
  --ms-border-color: #cbd5e1;
  --ms-font-size: 0.875rem;
  --ms-py: 0.75rem;
  --ms-px: 1rem;
}

:deep(.multiselect-custom .multiselect-tags) {
  border-radius: 0.5rem;
  min-height: 48px;
}

:deep(.multiselect-custom .multiselect-tag) {
  background: #e0e7ff;
  color: #4f46e5;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

:deep(.multiselect-custom .multiselect-tag-remove) {
  color: #4f46e5;
}

:deep(.multiselect-custom .multiselect-spinner) {
  border-color: #4f46e5 transparent transparent;
}
</style>