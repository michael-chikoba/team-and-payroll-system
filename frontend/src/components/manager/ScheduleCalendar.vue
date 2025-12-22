<!-- ============================================== -->
<!-- ScheduleCalendar.vue - FIXED -->
<!-- ============================================== -->
<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <!-- Calendar Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ currentMonthYear }}</h2>
      <div class="flex space-x-2">
        <button @click="previousMonth" class="p-2 rounded hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <button @click="today" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded">
          Today
        </button>
        <button @click="nextMonth" class="p-2 rounded hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden">
      <!-- Day Headers -->
      <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" 
           class="bg-gray-50 p-2 text-center text-xs font-semibold text-gray-700">
        {{ day }}
      </div>

      <!-- Calendar Days -->
      <div 
        v-for="day in calendarDays" 
        :key="day.dateString"
        @click="$emit('date-click', day.dateString)"
        :class="[
          'bg-white p-2 min-h-[100px] cursor-pointer hover:bg-gray-50 transition-colors',
          !day.isCurrentMonth && 'bg-gray-50 text-gray-400',
          day.isToday && 'bg-blue-50'
        ]">
        <div class="flex flex-col h-full">
          <span :class="['text-sm font-medium mb-2', day.isToday && 'text-blue-600 font-bold']">
            {{ day.day }}
          </span>
          
          <!-- Schedule Items for this day -->
          <div class="space-y-1 overflow-y-auto flex-1">
            <div 
              v-for="schedule in day.schedules.slice(0, 3)" 
              :key="schedule.id"
              @click.stop="$emit('schedule-click', schedule)"
              :class="[
                'text-xs p-1 rounded cursor-pointer truncate',
                getScheduleColorClass(schedule)
              ]">
              <div class="flex items-center">
                <span class="w-2 h-2 rounded-full mr-1" :class="getStatusDot(schedule.status)"></span>
                <span class="font-medium truncate">{{ schedule.title }}</span>
              </div>
            </div>
            <div v-if="day.schedules.length > 3" class="text-xs text-gray-500 pl-1">
              +{{ day.schedules.length - 3 }} more
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 flex flex-wrap gap-4 text-sm">
      <div class="flex items-center">
        <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
        <span>Completed</span>
      </div>
      <div class="flex items-center">
        <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
        <span>In Progress</span>
      </div>
      <div class="flex items-center">
        <span class="w-3 h-3 rounded-full bg-gray-400 mr-2"></span>
        <span>Pending</span>
      </div>
      <div class="flex items-center">
        <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
        <span>Overdue</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  schedules: { type: Array, required: true }
});

defineEmits(['schedule-click', 'date-click']);

const currentDate = ref(new Date());

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

// Helper to safely parse and format dates
const safeDateParse = (dateValue) => {
  if (!dateValue) return null;
  try {
    const date = new Date(dateValue);
    if (isNaN(date.getTime())) return null;
    return date.toISOString().split('T')[0];
  } catch (err) {
    console.error('Date parse error:', err);
    return null;
  }
};

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - startDate.getDay());
  
  const days = [];
  const currentDateObj = new Date(startDate);
  
  for (let i = 0; i < 42; i++) {
    const dateString = currentDateObj.toISOString().split('T')[0];
    const isCurrentMonth = currentDateObj.getMonth() === month;
    const isToday = dateString === new Date().toISOString().split('T')[0];
    
    // FIXED: Check both due_date and scheduled_date for compatibility
    const daySchedules = props.schedules.filter(s => {
      // Parse the schedule date safely
      const scheduleDate = safeDateParse(s.due_date || s.scheduled_date);
      return scheduleDate === dateString;
    });
    
    days.push({
      day: currentDateObj.getDate(),
      dateString,
      isCurrentMonth,
      isToday,
      schedules: daySchedules
    });
    
    currentDateObj.setDate(currentDateObj.getDate() + 1);
  }
  
  return days;
});

const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
};

const today = () => {
  currentDate.value = new Date();
};

const getScheduleColorClass = (schedule) => {
  // FIXED: Use schedule_type from backend
  const scheduleType = schedule.schedule_type || schedule.type;
  
  const typeColors = {
    banner_creation: 'bg-pink-100 text-pink-800 hover:bg-pink-200',
    weekly_overview: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
    test_sequence: 'bg-purple-100 text-purple-800 hover:bg-purple-200',
    live_games: 'bg-red-100 text-red-800 hover:bg-red-200',
    multibets: 'bg-amber-100 text-amber-800 hover:bg-amber-200',
    news_section: 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200',
    other: 'bg-gray-100 text-gray-800 hover:bg-gray-200'
  };
  return typeColors[scheduleType] || 'bg-gray-100 text-gray-800';
};

const getStatusDot = (status) => {
  const colors = {
    completed: 'bg-green-500',
    in_progress: 'bg-yellow-500',
    pending: 'bg-gray-400',
    overdue: 'bg-red-500'
  };
  return colors[status] || 'bg-gray-400';
};
</script>