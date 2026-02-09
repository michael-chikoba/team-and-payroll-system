<template>
  <div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
          <select v-model="filters.type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Types</option>
            <option value="banner_creation">Banner Creation</option>
            <option value="weekly_overview">Weekly Overview</option>
            <option value="test_sequence">Test Sequence</option>
            <option value="live_games">Live Games</option>
            <option value="multibets">Multibets</option>
            <option value="news_section">News Section</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
          <select v-model="filters.assignedTo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Team Members</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.full_name }}</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
            Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Schedule List by Type -->
    <div class="space-y-6">
      <!-- Banner Creation -->
      <section v-if="getFilteredSchedulesByType('banner_creation').length > 0">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-pink-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-gray-800">Banner Creation</h2>
          <span class="ml-3 px-2 py-1 text-xs font-semibold bg-pink-100 text-pink-800 rounded-full">
            {{ getFilteredSchedulesByType('banner_creation').length }}
          </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <BannerScheduleCard
            v-for="schedule in getFilteredSchedulesByType('banner_creation')"
            :key="schedule.id"
            :schedule="schedule"
            @toggle-region="(region) => $emit('toggle-meta', schedule, region)"
            @complete="$emit('complete', schedule.id)"
            @edit="$emit('edit', schedule)"
          />
        </div>
      </section>

      <!-- Other Schedule Types -->
      <ScheduleSection
        v-for="type in ['weekly_overview', 'test_sequence', 'live_games', 'multibets', 'news_section', 'other']"
        :key="type"
        :type="type"
        :schedules="getFilteredSchedulesByType(type)"
        @complete="$emit('complete', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>

    <!-- Empty State -->
    <div v-if="filteredSchedules.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No schedules found</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new schedule.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import BannerScheduleCard from './BannerScheduleCard.vue';
import ScheduleSection from './ScheduleSection.vue';

const props = defineProps({
  schedules: { type: Array, required: true },
  employees: { type: Array, required: true }
});

defineEmits(['complete', 'edit', 'delete', 'toggle-meta']);

const filters = ref({
  type: '',
  status: '',
  assignedTo: ''
});

const filteredSchedules = computed(() => {
  return props.schedules.filter(schedule => {
    // Backend returns 'type', but we normalize to both 'type' and 'schedule_type'
    const scheduleType = schedule.type || schedule.schedule_type;
    
    if (filters.value.type && scheduleType !== filters.value.type) return false;
    if (filters.value.status && schedule.status !== filters.value.status) return false;
    if (filters.value.assignedTo && schedule.assigned_to !== parseInt(filters.value.assignedTo)) return false;
    return true;
  });
});

const getFilteredSchedulesByType = (type) => {
  return filteredSchedules.value.filter(s => {
    const scheduleType = s.type || s.schedule_type;
    return scheduleType === type && s.status !== 'completed';
  });
};

const resetFilters = () => {
  filters.value = { type: '', status: '', assignedTo: '' };
};
</script>