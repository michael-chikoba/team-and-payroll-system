<template>
  <!-- Modal Overlay -->
  <transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="show"
      class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <!-- Modal Content -->
      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div 
          v-if="show"
          class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <div class="flex-1 min-w-0">
              <h2 class="text-2xl font-bold text-gray-900 truncate">{{ schedule?.title || 'Schedule Details' }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ formatDate(schedule?.scheduled_date) }}</p>
            </div>
            <div class="flex items-center gap-3 ml-4">
              <span 
                v-if="schedule?.priority"
                :class="priorityClasses[schedule.priority]" 
                class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap"
              >
                {{ schedule.priority.toUpperCase() }}
              </span>
              <button
                type="button"
                @click="closeModal"
                class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-full p-1 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="px-6 py-5 space-y-5">
            
            <!-- Status -->
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-2">Status</label>
              <span 
                v-if="schedule?.status"
                :class="statusClasses[schedule.status]" 
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
              >
                <span 
                  :class="statusDotClasses[schedule.status]"
                  class="w-2 h-2 rounded-full mr-2"
                ></span>
                {{ formatStatus(schedule.status) }}
              </span>
            </div>

            <!-- Assigned To -->
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-2">Assigned To</label>
              <div class="flex items-center gap-2 text-sm text-gray-900">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="font-medium">{{ getEmployeeName(schedule?.assigned_to) }}</span>
              </div>
            </div>

            <!-- Type -->
            <div v-if="schedule?.type">
              <label class="text-sm font-medium text-gray-700 block mb-2">Task Type</label>
              <span class="inline-flex items-center px-3 py-1 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg">
                {{ formatTaskType(schedule.type) }}
              </span>
            </div>

            <!-- Due Date -->
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-2">Due Date</label>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span 
                  :class="isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900'"
                  class="text-sm"
                >
                  {{ formatDate(schedule?.due_date) }}
                </span>
                <span v-if="isOverdue" class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded">
                  OVERDUE
                </span>
              </div>
            </div>

            <!-- Description -->
            <div v-if="schedule?.description">
              <label class="text-sm font-medium text-gray-700 block mb-2">Description</label>
              <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-200">
                {{ schedule.description }}
              </div>
            </div>

            <!-- Region Toggles (if meta data exists) -->
            <div v-if="schedule?.meta_data && hasRegions">
              <label class="text-sm font-medium text-gray-700 block mb-2">Target Regions</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="region in availableRegions"
                  :key="region"
                  type="button"
                  @click="$emit('toggle-region', region)"
                  :class="[
                    'px-4 py-2 text-sm font-medium rounded-lg border-2 transition-all duration-200',
                    isRegionActive(region)
                      ? 'bg-green-500 border-green-600 text-white shadow-sm'
                      : 'bg-white border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50'
                  ]"
                >
                  <span class="flex items-center gap-2">
                    <svg 
                      v-if="isRegionActive(region)"
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
            <div v-if="schedule?.notes">
              <label class="text-sm font-medium text-gray-700 block mb-2">Additional Notes</label>
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <p class="text-sm text-gray-700">{{ schedule.notes }}</p>
              </div>
            </div>

          </div>

          <!-- Footer Actions -->
          <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
            >
              Close
            </button>
            <button
              v-if="schedule?.status !== 'completed'"
              type="button"
              @click="$emit('edit')"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
            >
              Edit Schedule
            </button>
            <button
              v-if="schedule?.status !== 'completed'"
              type="button"
              @click="handleComplete"
              class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
            >
              Mark as Complete
            </button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: { 
    type: Boolean, 
    default: false 
  },
  schedule: { 
    type: Object, 
    default: null 
  }
});

const emit = defineEmits(['close', 'toggle-region', 'complete', 'edit']);

const priorityClasses = {
  low: 'bg-gray-100 text-gray-800',
  moderate: 'bg-blue-100 text-blue-800',
  high: 'bg-orange-100 text-orange-800',
  urgent: 'bg-red-100 text-red-800'
};

const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-800',
  in_progress: 'bg-blue-100 text-blue-800',
  completed: 'bg-green-100 text-green-800',
  overdue: 'bg-red-100 text-red-800'
};

const statusDotClasses = {
  pending: 'bg-yellow-500',
  in_progress: 'bg-blue-500',
  completed: 'bg-green-500',
  overdue: 'bg-red-500'
};

const availableRegions = ['zambia', 'malawi', 'zimbabwe'];

const hasRegions = computed(() => {
  return props.schedule?.meta_data && 
         (props.schedule.meta_data.regions || 
          props.schedule.meta_data.zambia !== undefined ||
          props.schedule.meta_data.malawi !== undefined ||
          props.schedule.meta_data.zimbabwe !== undefined);
});

const isRegionActive = (region) => {
  if (!props.schedule?.meta_data) return false;
  
  // Check both nested and flat structure
  if (props.schedule.meta_data.regions) {
    return props.schedule.meta_data.regions[region] === true;
  }
  return props.schedule.meta_data[region] === true;
};

const isOverdue = computed(() => {
  if (!props.schedule || props.schedule.status === 'completed') return false;
  if (!props.schedule.due_date) return false;
  return new Date(props.schedule.due_date) < new Date();
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (err) {
    return 'N/A';
  }
};

const formatStatus = (status) => {
  if (!status) return 'Unknown';
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const formatTaskType = (type) => {
  if (!type) return 'General';
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const getEmployeeName = (employeeId) => {
  if (!employeeId) return 'Unassigned';
  return `Employee #${employeeId}`;
};

const closeModal = () => {
  emit('close');
};

const handleComplete = () => {
  emit('complete');
  closeModal();
};
</script>