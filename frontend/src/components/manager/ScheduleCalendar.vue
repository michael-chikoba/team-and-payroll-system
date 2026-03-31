<!-- ============================================== -->
<!-- ScheduleCalendar.vue - IMPROVED STATUS VISIBILITY -->
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
           class="bg-gray-50 p-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wide">
        {{ day }}
      </div>

      <!-- Calendar Days -->
      <div
        v-for="day in calendarDays"
        :key="day.dateString"
        @click="$emit('date-click', day.dateString)"
        :class="[
          'bg-white p-2 min-h-[120px] cursor-pointer hover:bg-gray-50 transition-colors',
          !day.isCurrentMonth && 'bg-gray-50 text-gray-400',
          day.isToday && 'ring-2 ring-inset ring-blue-500'
        ]">
        <div class="flex flex-col h-full">
          <!-- Day Number -->
          <div class="flex items-center justify-between mb-1">
            <span :class="[
              'text-sm font-semibold w-6 h-6 flex items-center justify-center rounded-full',
              day.isToday ? 'bg-blue-600 text-white' : 'text-gray-700'
            ]">
              {{ day.day }}
            </span>
            <!-- Overdue count badge if any -->
            <span
              v-if="day.schedules.filter(s => s.status === 'overdue').length > 0"
              class="text-[10px] font-bold bg-red-600 text-white rounded-full px-1.5 py-0.5 leading-none">
              {{ day.schedules.filter(s => s.status === 'overdue').length }} overdue
            </span>
          </div>

          <!-- Schedule Items for this day -->
          <div class="space-y-1 overflow-y-auto flex-1">
            <div
              v-for="schedule in day.schedules.slice(0, 3)"
              :key="schedule.id"
              @click.stop="$emit('schedule-click', schedule)"
              :class="[
                'text-xs rounded-md cursor-pointer border transition-all duration-150',
                getScheduleColorClass(schedule)
              ]">
              <!-- Top row: type label + status badge -->
              <div class="flex items-center justify-between px-1.5 pt-1 pb-0.5 gap-1">
                <span class="font-semibold truncate flex-1">{{ schedule.title }}</span>
                <!-- STATUS BADGE — clearly visible -->
                <span :class="['shrink-0 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full border', getStatusBadgeClass(schedule.status)]">
                  {{ getStatusLabel(schedule.status) }}
                </span>
              </div>
              <!-- Bottom row: status indicator bar -->
              <div :class="['h-1 rounded-b-md w-full', getStatusBarClass(schedule.status)]"></div>
            </div>

            <div v-if="day.schedules.length > 3" class="text-xs text-blue-600 font-medium pl-1 pt-0.5">
              +{{ day.schedules.length - 3 }} more
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 pt-4 border-t border-gray-100">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Status Legend</p>
      <div class="flex flex-wrap gap-3">
        <div v-for="item in statusLegend" :key="item.status"
             class="flex items-center gap-2 px-3 py-1.5 rounded-full border"
             :class="item.containerClass">
          <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="item.dotClass"></span>
          <span class="text-xs font-semibold" :class="item.textClass">{{ item.label }}</span>
        </div>
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
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - startDate.getDay());

  const days = [];
  const currentDateObj = new Date(startDate);

  for (let i = 0; i < 42; i++) {
    const dateString = currentDateObj.toISOString().split('T')[0];
    const isCurrentMonth = currentDateObj.getMonth() === month;
    const isToday = dateString === new Date().toISOString().split('T')[0];

    const daySchedules = props.schedules.filter(s => {
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
  const scheduleType = schedule.schedule_type || schedule.type;
  const typeColors = {
    banner_creation:  'bg-pink-50   border-pink-200  text-pink-900  hover:bg-pink-100',
    weekly_overview:  'bg-blue-50   border-blue-200  text-blue-900  hover:bg-blue-100',
    test_sequence:    'bg-purple-50 border-purple-200 text-purple-900 hover:bg-purple-100',
    live_games:       'bg-red-50    border-red-200   text-red-900   hover:bg-red-100',
    multibets:        'bg-amber-50  border-amber-200  text-amber-900  hover:bg-amber-100',
    news_section:     'bg-emerald-50 border-emerald-200 text-emerald-900 hover:bg-emerald-100',
    other:            'bg-gray-50   border-gray-200  text-gray-900  hover:bg-gray-100'
  };
  return typeColors[scheduleType] || 'bg-gray-50 border-gray-200 text-gray-900 hover:bg-gray-100';
};

// Clearly visible, high-contrast status badge classes
const getStatusBadgeClass = (status) => {
  const classes = {
    completed:   'bg-green-100  text-green-800  border-green-400',
    in_progress: 'bg-yellow-100 text-yellow-800 border-yellow-400',
    pending:     'bg-slate-100  text-slate-700  border-slate-400',
    overdue:     'bg-red-100    text-red-800    border-red-500'
  };
  return classes[status] || 'bg-slate-100 text-slate-700 border-slate-400';
};

// Solid bottom bar accent per status
const getStatusBarClass = (status) => {
  const classes = {
    completed:   'bg-green-500',
    in_progress: 'bg-yellow-400',
    pending:     'bg-slate-300',
    overdue:     'bg-red-600'
  };
  return classes[status] || 'bg-slate-300';
};

const getStatusLabel = (status) => {
  const labels = {
    completed:   'Done',
    in_progress: 'In Progress',
    pending:     'Pending',
    overdue:     'Overdue'
  };
  return labels[status] || status || 'Unknown';
};

const statusLegend = [
  {
    status: 'completed',
    label: 'Completed',
    dotClass: 'bg-green-500',
    textClass: 'text-green-800',
    containerClass: 'bg-green-50 border-green-300'
  },
  {
    status: 'in_progress',
    label: 'In Progress',
    dotClass: 'bg-yellow-400',
    textClass: 'text-yellow-800',
    containerClass: 'bg-yellow-50 border-yellow-300'
  },
  {
    status: 'pending',
    label: 'Pending',
    dotClass: 'bg-slate-400',
    textClass: 'text-slate-700',
    containerClass: 'bg-slate-50 border-slate-300'
  },
  {
    status: 'overdue',
    label: 'Overdue',
    dotClass: 'bg-red-600',
    textClass: 'text-red-800',
    containerClass: 'bg-red-50 border-red-300'
  }
];
</script>