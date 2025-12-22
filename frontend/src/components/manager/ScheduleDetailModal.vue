<!-- ============================================== -->
<!-- ScheduleDetailModal.vue -->
<!-- ============================================== -->
<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <!-- Header -->
          <div class="flex justify-between items-start mb-6">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ schedule.title }}</h2>
              <div class="flex items-center space-x-2">
                <span :class="['px-3 py-1 text-xs font-semibold rounded-full', getStatusClass(schedule.status)]">
                  {{ schedule.status.replace('_', ' ').toUpperCase() }}
                </span>
                <span :class="['px-3 py-1 text-xs font-semibold rounded-full', getPriorityClass(schedule.priority)]">
                  {{ schedule.priority.toUpperCase() }}
                </span>
                <span :class="['px-3 py-1 text-xs font-semibold rounded-full', getTypeClass(schedule.schedule_type)]">
                  {{ getTypeName(schedule.schedule_type) }}
                </span>
              </div>
            </div>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <!-- Assigned To -->
            <div>
              <label class="block text-sm font-medium text-gray-500 mb-2">Assigned To</label>
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-bold mr-3">
                  {{ getInitials(schedule.assigned_to?.full_name) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ schedule.assigned_to?.full_name }}</p>
                  <p class="text-sm text-gray-500">{{ schedule.assigned_to?.email }}</p>
                </div>
              </div>
            </div>

            <!-- Created By -->
            <div>
              <label class="block text-sm font-medium text-gray-500 mb-2">Created By</label>
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-gray-500 flex items-center justify-center text-white text-sm font-bold mr-3">
                  {{ getInitials(schedule.created_by?.full_name) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ schedule.created_by?.full_name }}</p>
                  <p class="text-sm text-gray-500">{{ formatDateTime(schedule.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Start Date -->
            <div>
              <label class="block text-sm font-medium text-gray-500 mb-2">Start Date</label>
              <div class="flex items-center text-gray-900">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="font-medium">{{ formatDateTime(schedule.scheduled_date) }}</span>
              </div>
            </div>

            <!-- Due Date -->
            <div>
              <label class="block text-sm font-medium text-gray-500 mb-2">Due Date</label>
              <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span :class="['font-medium', schedule.is_overdue ? 'text-red-600' : 'text-gray-900']">
                  {{ formatDateTime(schedule.due_date) }}
                  <span v-if="schedule.days_until_due >= 0" class="text-sm ml-1">({{ schedule.days_until_due }} days)</span>
                  <span v-else class="text-sm ml-1 text-red-600">({{ Math.abs(schedule.days_until_due) }} days overdue)</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="schedule.notes" class="mb-6">
            <label class="block text-sm font-medium text-gray-500 mb-2">Notes</label>
            <div class="bg-gray-50 rounded-lg p-4 text-gray-700">
              {{ schedule.notes }}
            </div>
          </div>

          <!-- Banner Regions (if banner type) -->
          <div v-if="schedule.schedule_type === 'banner_creation' && schedule.meta_data" class="mb-6">
            <label class="block text-sm font-medium text-gray-500 mb-3">Banner Regions</label>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="region in ['zambia', 'namibia', 'congo', 'vip']" :key="region" 
                   :class="['p-3 rounded-lg border-2', schedule.meta_data[region] ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-gray-50']">
                <div class="flex items-center justify-between">
                  <span :class="['font-medium', region === 'vip' ? 'text-amber-600' : 'text-gray-700']">
                    {{ region.charAt(0).toUpperCase() + region.slice(1) }}
                    {{ region === 'vip' ? 'Group' : '' }}
                  </span>
                  <span v-if="schedule.meta_data[region]" class="text-green-600 font-bold">✓</span>
                  <span v-else class="text-gray-400">○</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Completion Info -->
          <div v-if="schedule.status === 'completed'" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-green-800 font-medium">Completed on {{ formatDateTime(schedule.completed_at) }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex space-x-3">
            <button 
              @click="$emit('edit', schedule)"
              class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium transition-colors">
              Edit Schedule
            </button>
            <button 
              v-if="schedule.status !== 'completed'"
              @click="$emit('complete', schedule.id)"
              class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium transition-colors">
              Mark as Complete
            </button>
            <button 
              @click="$emit('delete', schedule.id)"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium transition-colors">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  schedule: { type: Object, required: true }
});

defineEmits(['close', 'edit', 'complete', 'delete']);

const getInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('en-GB', { 
    month: 'long', 
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    overdue: 'bg-red-100 text-red-700'
  };
  return classes[status] || classes.pending;
};

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-gray-100 text-gray-700',
    moderate: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700'
  };
  return classes[priority] || classes.moderate;
};

const getTypeClass = (type) => {
  const classes = {
    banner_creation: 'bg-pink-100 text-pink-700',
    weekly_overview: 'bg-blue-100 text-blue-700',
    test_sequence: 'bg-purple-100 text-purple-700',
    live_games: 'bg-red-100 text-red-700',
    multibets: 'bg-amber-100 text-amber-700',
    news_section: 'bg-emerald-100 text-emerald-700'
  };
  return classes[type] || 'bg-gray-100 text-gray-700';
};

const getTypeName = (type) => {
  const names = {
    banner_creation: 'Banner Creation',
    weekly_overview: 'Weekly Overview',
    test_sequence: 'Test Sequence',
    live_games: 'Live Games',
    multibets: 'Multibets',
    news_section: 'News Section'
  };
  return names[type] || type;
};
</script>