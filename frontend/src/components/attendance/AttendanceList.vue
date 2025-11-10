<template>
  <div class="overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Clock In
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Clock Out
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Hours
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="attendance in attendances" :key="attendance.id">
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
              {{ formatDate(attendance.date) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
              {{ attendance.clock_in || '-' }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
              {{ attendance.clock_out || '-' }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
              {{ attendance.total_hours || '0' }} hrs
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusClass(attendance.status)"
              >
                {{ attendance.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="attendances.length === 0" class="text-center py-8 text-gray-500">
      No attendance records found for this period.
    </div>
  </div>
</template>

<script>
import { format, parseISO } from 'date-fns'

export default {
  name: 'AttendanceList',
  props: {
    attendances: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    function formatDate(dateString) {
      return format(parseISO(dateString), 'MMM dd, yyyy')
    }

    function getStatusClass(status) {
      const classes = {
        present: 'bg-green-100 text-green-800',
        absent: 'bg-red-100 text-red-800',
        late: 'bg-yellow-100 text-yellow-800',
        half_day: 'bg-blue-100 text-blue-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    return {
      formatDate,
      getStatusClass
    }
  }
}
</script>