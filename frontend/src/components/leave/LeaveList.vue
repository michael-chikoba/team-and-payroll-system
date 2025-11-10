<template>
  <DataTable
    :columns="columns"
    :data="leaves"
    :pagination="pagination"
    @page-change="onPageChange"
  >
    <template #column-type="{ item }">
      <span class="capitalize">{{ item.type }}</span>
    </template>

    <template #column-start_date="{ item }">
      {{ formatDate(item.start_date) }}
    </template>

    <template #column-end_date="{ item }">
      {{ formatDate(item.end_date) }}
    </template>

    <template #column-status="{ item }">
      <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="getStatusClass(item.status)"
      >
        {{ item.status }}
      </span>
    </template>

    <template #column-manager.name="{ item }">
      {{ item.manager?.name || 'N/A' }}
    </template>

    <template #actions="{ item }">
      <button
        v-if="item.status === 'pending' && isManager"
        @click="approveLeave(item)"
        class="text-green-600 hover:text-green-900 mr-3"
      >
        Approve
      </button>
      <button
        v-if="item.status === 'pending' && isManager"
        @click="rejectLeave(item)"
        class="text-red-600 hover:text-red-900"
      >
        Reject
      </button>
      <button
        v-if="item.status === 'pending' && !isManager"
        @click="editLeave(item)"
        class="text-blue-600 hover:text-blue-900 mr-3"
      >
        Edit
      </button>
      <button
        v-if="item.status === 'pending' && !isManager"
        @click="cancelLeave(item)"
        class="text-red-600 hover:text-red-900"
      >
        Cancel
      </button>
    </template>

    <template #empty>
      <div class="text-center py-12">
        <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No leave requests</h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by submitting a new leave request.
        </p>
        <div class="mt-6">
          <router-link
            to="/leaves/apply"
            class="btn-primary"
          >
            Apply for Leave
          </router-link>
        </div>
      </div>
    </template>
  </DataTable>
</template>

<script>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useLeaveStore } from '@/stores/leave'
import DataTable from '@/components/common/DataTable.vue'
import { CalendarIcon } from '@heroicons/vue/outline'
import { format, parseISO } from 'date-fns'

export default {
  name: 'LeaveList',
  components: {
    DataTable,
    CalendarIcon
  },
  props: {
    leaves: {
      type: Array,
      default: () => []
    },
    pagination: {
      type: Object,
      default: null
    }
  },
  emits: ['page-change', 'approve', 'reject', 'edit', 'cancel'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const leaveStore = useLeaveStore()

    const isManager = computed(() => authStore.isManager)

    const columns = computed(() => {
      const baseColumns = [
        { key: 'type', label: 'Type' },
        { key: 'start_date', label: 'Start Date' },
        { key: 'end_date', label: 'End Date' },
        { key: 'total_days', label: 'Days' },
        { key: 'status', label: 'Status' },
        { key: 'reason', label: 'Reason' }
      ]

      if (isManager.value) {
        baseColumns.splice(5, 0, { key: 'employee.user.name', label: 'Employee' })
      } else {
        baseColumns.splice(5, 0, { key: 'manager.name', label: 'Manager' })
      }

      return baseColumns
    })

    function formatDate(dateString) {
      return format(parseISO(dateString), 'MMM dd, yyyy')
    }

    function getStatusClass(status) {
      const classes = {
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    function onPageChange(url) {
      emit('page-change', url)
    }

    function approveLeave(leave) {
      emit('approve', leave)
    }

    function rejectLeave(leave) {
      emit('reject', leave)
    }

    function editLeave(leave) {
      emit('edit', leave)
    }

    function cancelLeave(leave) {
      emit('cancel', leave)
    }

    return {
      columns,
      isManager,
      formatDate,
      getStatusClass,
      onPageChange,
      approveLeave,
      rejectLeave,
      editLeave,
      cancelLeave
    }
  }
}
</script>