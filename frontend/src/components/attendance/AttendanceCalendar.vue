<template>
  <div class="bg-white">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium">{{ currentMonthYear }}</h3>
      <div class="flex space-x-2">
        <button @click="previousMonth" class="p-1 rounded hover:bg-gray-100">
          <ChevronLeftIcon class="h-5 w-5" />
        </button>
        <button @click="nextMonth" class="p-1 rounded hover:bg-gray-100">
          <ChevronRightIcon class="h-5 w-5" />
        </button>
      </div>
    </div>
    
    <div class="grid grid-cols-7 gap-1 mb-2">
      <div v-for="day in days" :key="day" class="text-center text-sm font-medium text-gray-500 py-2">
        {{ day }}
      </div>
    </div>
    
    <div class="grid grid-cols-7 gap-1">
      <div
        v-for="day in calendarDays"
        :key="day.date"
        class="min-h-12 p-1 border rounded text-center"
        :class="getDayClasses(day)"
      >
        <div class="text-sm font-medium mb-1">{{ day.date.getDate() }}</div>
        <div v-if="day.attendance" class="text-xs">
          <span :class="getStatusColor(day.attendance.status)">
            {{ day.attendance.status === 'present' ? '✓' : day.attendance.status === 'absent' ? '✗' : 'L' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/outline'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday } from 'date-fns'

export default {
  name: 'AttendanceCalendar',
  components: {
    ChevronLeftIcon,
    ChevronRightIcon
  },
  props: {
    attendances: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const currentDate = ref(new Date())
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

    const currentMonthYear = computed(() => {
      return format(currentDate.value, 'MMMM yyyy')
    })

    const calendarDays = computed(() => {
      const start = startOfMonth(currentDate.value)
      const end = endOfMonth(currentDate.value)
      const daysInMonth = eachDayOfInterval({ start, end })

      return daysInMonth.map(date => {
        const attendance = props.attendances.find(att => {
          const attDate = new Date(att.date)
          return attDate.toDateString() === date.toDateString()
        })

        return {
          date,
          isCurrentMonth: isSameMonth(date, currentDate.value),
          isToday: isToday(date),
          attendance
        }
      })
    })

    function previousMonth() {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
    }

    function nextMonth() {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
    }

    function getDayClasses(day) {
      const classes = []
      
      if (!day.isCurrentMonth) {
        classes.push('bg-gray-50 text-gray-400')
      } else if (day.isToday) {
        classes.push('bg-blue-50 border-blue-200')
      } else {
        classes.push('bg-white')
      }

      return classes
    }

    function getStatusColor(status) {
      const colors = {
        present: 'text-green-600',
        absent: 'text-red-600',
        late: 'text-yellow-600',
        half_day: 'text-blue-600'
      }
      return colors[status] || 'text-gray-600'
    }

    return {
      currentDate,
      days,
      currentMonthYear,
      calendarDays,
      previousMonth,
      nextMonth,
      getDayClasses,
      getStatusColor
    }
  }
}
</script>