<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900">
            {{ leave.employee.user.name }}
          </h4>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="statusClass"
          >
            {{ leave.status }}
          </span>
        </div>
        
        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
          <div>
            <span class="text-sm font-medium text-gray-500">Type:</span>
            <span class="ml-2 text-sm text-gray-900 capitalize">{{ leave.type }}</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Duration:</span>
            <span class="ml-2 text-sm text-gray-900">{{ leave.total_days }} days</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Dates:</span>
            <span class="ml-2 text-sm text-gray-900">
              {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
            </span>
          </div>
        </div>

        <div class="mt-3">
          <span class="text-sm font-medium text-gray-500">Reason:</span>
          <p class="mt-1 text-sm text-gray-900">{{ leave.reason }}</p>
        </div>

        <div v-if="leave.manager_notes" class="mt-3">
          <span class="text-sm font-medium text-gray-500">Manager Notes:</span>
          <p class="mt-1 text-sm text-gray-900">{{ leave.manager_notes }}</p>
        </div>
      </div>
    </div>

    <div v-if="showActions && leave.status === 'pending'" class="mt-4 flex justify-end space-x-3">
      <button
        @click="approveLeave"
        class="btn-primary bg-green-600 hover:bg-green-700 focus:ring-green-500"
      >
        Approve
      </button>
      <button
        @click="rejectLeave"
        class="btn-secondary bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white"
      >
        Reject
      </button>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { dateUtils } from '@/utils/dateUtils'

export default {
  name: 'LeaveApprovalCard',
  props: {
    leave: {
      type: Object,
      required: true
    },
    showActions: {
      type: Boolean,
      default: true
    }
  },
  emits: ['approve', 'reject'],
  setup(props, { emit }) {
    const statusClass = computed(() => {
      const classes = {
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800'
      }
      return classes[props.leave.status] || 'bg-gray-100 text-gray-800'
    })

    function formatDate(dateString) {
      return dateUtils.formatDate(dateString)
    }

    function approveLeave() {
      emit('approve', props.leave)
    }

    function rejectLeave() {
      emit('reject', props.leave)
    }

    return {
      statusClass,
      formatDate,
      approveLeave,
      rejectLeave
    }
  }
}
</script>