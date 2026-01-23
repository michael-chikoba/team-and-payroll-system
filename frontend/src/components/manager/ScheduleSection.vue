<template>
  <section v-if="schedules.length > 0">
    <div class="flex items-center mb-4">
      <div :class="['p-2 rounded-lg mr-3', iconBgClass]">
        <svg class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800">{{ typeTitle }}</h2>
    </div>
    
    <div class="space-y-3">
      <div
        v-for="schedule in schedules"
        :key="schedule.id"
        class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">{{ schedule.title }}</h3>
              <span :class="priorityClasses[schedule.priority]" class="px-2 py-1 text-xs font-medium rounded">
                {{ schedule.priority }}
              </span>
              <span :class="statusClasses[schedule.status]" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ formatStatus(schedule.status) }}
              </span>
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-3">
              <div>
                <span class="font-medium">Assigned:</span> Employee #{{ schedule.assigned_to }}
              </div>
              <div>
                <span class="font-medium">Start:</span> {{ formatDate(schedule.scheduled_date) }}
              </div>
              <div>
                <span class="font-medium">Due:</span> 
                <span :class="isOverdue(schedule) ? 'text-red-600 font-medium' : ''">
                  {{ formatDate(schedule.due_date) }}
                </span>
              </div>
            </div>

            <div v-if="schedule.notes" class="text-sm text-gray-600 bg-gray-50 p-2 rounded mb-3">
              {{ schedule.notes }}
            </div>
          </div>

          <div class="flex gap-2 ml-4">
            <button
              v-if="schedule.status !== 'completed'"
              @click="$emit('complete', schedule.id)"
              class="px-3 py-2 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700"
            >
              Complete
            </button>
            <button
              @click="$emit('edit', schedule)"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
            >
              Edit
            </button>
            <button
              @click="$emit('delete', schedule.id)"
              class="px-3 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: { type: String, required: true },
  schedules: { type: Array, required: true }
});

defineEmits(['complete', 'edit', 'delete']);

const typeConfig = {
  weekly_overview: { title: 'Weekly Overview Documents', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  test_sequence: { title: 'Weekly Test Sequences', iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
  live_games: { title: 'Live Games (Lite)', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  multibets: { title: 'Multibets (USSD VIP)', iconBg: 'bg-orange-100', iconColor: 'text-orange-600' },
  news_section: { title: 'News Section (USSD VIP)', iconBg: 'bg-indigo-100', iconColor: 'text-indigo-600' }
};

const typeTitle = computed(() => typeConfig[props.type]?.title || props.type);
const iconBgClass = computed(() => typeConfig[props.type]?.iconBg || 'bg-gray-100');
const iconColorClass = computed(() => typeConfig[props.type]?.iconColor || 'text-gray-600');

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

const isOverdue = (schedule) => {
  if (schedule.status === 'completed') return false;
  return new Date(schedule.due_date) < new Date();
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatStatus = (status) => {
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};
</script>
