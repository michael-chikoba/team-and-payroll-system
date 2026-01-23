<template>
  <!-- Modal Overlay -->
  <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <!-- Modal Content -->
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
        <h2 class="text-2xl font-bold text-gray-900">
          {{ schedule?.id ? 'Edit Schedule' : 'Create New Schedule' }}
        </h2>
        <button
          type="button"
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-full p-1 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="handleSubmit" class="px-6 py-5 space-y-5">
        
        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Title <span class="text-red-500">*</span>
          </label>
          <input
            id="title"
            v-model="formData.title"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Enter schedule title"
          />
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <textarea
            id="description"
            v-model="formData.description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Enter schedule description"
          ></textarea>
        </div>

        <!-- Type and Priority Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Type -->
          <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
              Task Type <span class="text-red-500">*</span>
            </label>
            <select
              id="type"
              v-model="formData.type"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="banner_creation">Banner Creation</option>
              <option value="weekly_overview">Weekly Overview</option>
              <option value="test_sequence">Test Sequence</option>
              <option value="live_games">Live Games</option>
              <option value="multibets">Multibets</option>
              <option value="news_section">News Section</option>
            </select>
          </div>

          <!-- Priority -->
          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
              Priority <span class="text-red-500">*</span>
            </label>
            <select
              id="priority"
              v-model="formData.priority"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="low">Low</option>
              <option value="moderate">Moderate</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>

        <!-- Dates Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Start Date -->
          <div>
            <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
              Start Date
            </label>
            <input
              id="scheduled_date"
              v-model="formData.scheduled_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Due Date -->
          <div>
            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
              Due Date <span class="text-red-500">*</span>
            </label>
            <input
              id="due_date"
              v-model="formData.due_date"
              type="date"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Assigned To -->
        <div>
          <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
            Assign To <span class="text-red-500">*</span>
          </label>
          <select
            id="assigned_to"
            v-model="formData.assigned_to"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select team member</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.full_name || emp.name || `Employee #${emp.id}` }}
            </option>
          </select>
        </div>

        <!-- Banner Regions (Only for banner_creation type) -->
        <div v-if="formData.type === 'banner_creation'">
          <label class="block text-sm font-medium text-gray-700 mb-3">
            Target Regions
          </label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <button
              v-for="region in availableRegions"
              :key="region"
              type="button"
              @click="toggleRegion(region)"
              :class="[
                'px-4 py-3 text-sm font-medium rounded-lg border-2 transition-all duration-200',
                formData.meta_data[region]
                  ? 'bg-green-500 border-green-600 text-white shadow-sm'
                  : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50'
              ]"
            >
              <span class="flex items-center justify-center gap-2">
                <svg 
                  v-if="formData.meta_data[region]"
                  class="w-4 h-4" 
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                {{ region.charAt(0).toUpperCase() + region.slice(1) }}
              </span>
            </button>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
            Additional Notes
          </label>
          <textarea
            id="notes"
            v-model="formData.notes"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Add any additional information or instructions"
          ></textarea>
        </div>

        <!-- Footer Actions -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 -mx-6 px-6 py-4 flex items-center justify-end gap-3 mt-6">
          <button
            type="button"
            @click="$emit('close')"
            :disabled="saving"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
          >
            <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ saving ? 'Saving...' : (schedule?.id ? 'Update Schedule' : 'Create Schedule') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  schedule: {
    type: Object,
    default: null
  },
  employees: {
    type: Array,
    default: () => []
  },
  presetDate: {
    type: String,
    default: null
  },
  saving: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'save']);

const availableRegions = ['zambia', 'malawi', 'zimbabwe', 'namibia', 'congo', 'vip'];

// Initialize form data
const formData = ref({
  title: '',
  description: '',
  type: 'banner_creation',
  priority: 'moderate',
  scheduled_date: new Date().toISOString().split('T')[0],
  due_date: new Date().toISOString().split('T')[0],
  assigned_to: '',
  notes: '',
  meta_data: {},
  status: 'pending'
});

// Initialize form with schedule data or defaults
const initializeForm = () => {
  if (props.schedule) {
    // Editing existing schedule
    formData.value = {
      ...props.schedule,
      meta_data: props.schedule.meta_data || {},
      scheduled_date: props.schedule.scheduled_date || new Date().toISOString().split('T')[0],
      due_date: props.schedule.due_date || new Date().toISOString().split('T')[0],
      assigned_to: props.schedule.assigned_to || ''
    };
  } else {
    // New schedule
    const today = new Date().toISOString().split('T')[0];
    formData.value = {
      title: '',
      description: '',
      type: 'banner_creation',
      priority: 'moderate',
      scheduled_date: props.presetDate || today,
      due_date: props.presetDate || today,
      assigned_to: '',
      notes: '',
      meta_data: {},
      status: 'pending'
    };
  }
};

// Toggle region selection
const toggleRegion = (region) => {
  if (!formData.value.meta_data) {
    formData.value.meta_data = {};
  }
  formData.value.meta_data[region] = !formData.value.meta_data[region];
};

// Handle form submission
const handleSubmit = () => {
  // Validate required fields
  if (!formData.value.title || !formData.value.due_date || !formData.value.assigned_to) {
    alert('Please fill in all required fields');
    return;
  }

  // Emit save event with form data
  emit('save', { ...formData.value });
};

// Watch for schedule changes
watch(() => props.schedule, () => {
  initializeForm();
}, { immediate: true });

// Initialize on mount
onMounted(() => {
  initializeForm();
  console.log('Modal initialized with:', formData.value);
});
</script>