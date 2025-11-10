<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Leave Balances</h3>
      
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="(balance, type) in balances"
          :key="type"
          class="border border-gray-200 rounded-lg p-4 text-center"
        >
          <div class="text-sm font-medium text-gray-500 capitalize mb-2">
            {{ type }} Leave
          </div>
          <div class="text-2xl font-bold text-blue-600">
            {{ balance.balance }}
          </div>
          <div class="text-xs text-gray-400 mt-1">
            days available
          </div>
        </div>
      </div>

      <div class="mt-4 text-sm text-gray-500">
        <p>Leave balances reset annually on January 1st.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted } from 'vue'
import { useLeaveStore } from '@/stores/leave'

export default {
  name: 'LeaveBalance',
  setup() {
    const leaveStore = useLeaveStore()

    const balances = computed(() => leaveStore.leaveBalances)

    onMounted(async () => {
      await leaveStore.fetchLeaveBalances()
    })

    return {
      balances
    }
  }
}
</script>