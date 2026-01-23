<template>
  <form @submit.prevent="submitForm" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
      <!-- Leave Type -->
      <div>
        <label for="type" class="block text-sm font-medium text-gray-700">
          Leave Type *
        </label>
        <select
          id="type"
          v-model="form.type"
          required
          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
        >
          <option value="">Select Leave Type</option>
          <option value="vacation">Vacation</option>
          <option value="sick">Sick Leave</option>
          <option value="personal">Personal Leave</option>
          <option value="maternity">Maternity Leave</option>
          <option value="paternity">Paternity Leave</option>
        </select>
        <p class="mt-1 text-sm text-gray-500">
          Available balance: {{ getBalanceByType(form.type) }} days
        </p>
      </div>

      <!-- Leave Balance Display -->
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Available Balances
        </label>
        <div class="mt-1 grid grid-cols-2 gap-2 text-sm">
          <div v-for="(balance, type) in leaveBalances" :key="type" class="flex justify-between">
            <span class="capitalize">{{ type }}:</span>
            <span class="font-medium">{{ balance.balance }} days</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
      <!-- Start Date -->
      <div>
        <label for="start_date" class="block text-sm font-medium text-gray-700">
          Start Date *
        </label>
        <input
          id="start_date"
          v-model="form.start_date"
          type="date"
          required
          :min="minDate"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        >
      </div>

      <!-- End Date -->
      <div>
        <label for="end_date" class="block text-sm font-medium text-gray-700">
          End Date *
        </label>
        <input
          id="end_date"
          v-model="form.end_date"
          type="date"
          required
          :min="form.start_date"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        >
      </div>
    </div>

    <!-- Total Days -->
    <div v-if="totalDays > 0" class="bg-blue-50 p-4 rounded-md">
      <p class="text-sm text-blue-700">
        Total days requested: <strong>{{ totalDays }}</strong> days
        <span v-if="insufficientBalance" class="text-red-600 ml-2">
          (Insufficient balance. Available: {{ getBalanceByType(form.type) }} days)
        </span>
      </p>
    </div>

    <!-- Reason -->
    <div>
      <label for="reason" class="block text-sm font-medium text-gray-700">
        Reason *
      </label>
      <textarea
        id="reason"
        v-model="form.reason"
        rows="4"
        required
        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        placeholder="Please provide a reason for your leave request..."
      ></textarea>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-end space-x-3">
      <button
        type="button"
        @click="$emit('cancel')"
        class="btn-secondary"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="isLoading || insufficientBalance"
        class="btn-primary"
      >
        <LoadingSpinner v-if="isLoading" class="h-4 w-4 mr-2" />
        {{ isLoading ? 'Submitting...' : 'Submit Leave Request' }}
      </button>
    </div>
  </form>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useLeaveStore } from '@/stores/leave'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

export default {
  name: 'LeaveForm',
  components: {
    LoadingSpinner
  },
  emits: ['submitted', 'cancel'],
  setup(props, { emit }) {
    const leaveStore = useLeaveStore()
    
    const isLoading = ref(false)
    const form = reactive({
      type: '',
      start_date: '',
      end_date: '',
      reason: ''
    })

    const minDate = computed(() => {
      return new Date().toISOString().split('T')[0]
    })

    const leaveBalances = computed(() => leaveStore.leaveBalances)

    const totalDays = computed(() => {
      if (!form.start_date || !form.end_date) return 0
      
      const start = new Date(form.start_date)
      const end = new Date(form.end_date)
      const diffTime = Math.abs(end - start)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
      
      return diffDays
    })

    const insufficientBalance = computed(() => {
      if (!form.type || totalDays.value === 0) return false
      const balance = leaveStore.getBalanceByType(form.type)
      return totalDays.value > balance
    })

    onMounted(async () => {
      await leaveStore.fetchLeaveBalances()
    })

    function getBalanceByType(type) {
      return leaveStore.getBalanceByType(type)
    }

    async function submitForm() {
      if (insufficientBalance.value) return
      
      isLoading.value = true
      
      try {
        await leaveStore.applyLeave({
          ...form,
          total_days: totalDays.value
        })
        
        emit('submitted')
      } catch (error) {
        console.error('Failed to submit leave request:', error)
      } finally {
        isLoading.value = false
      }
    }

    return {
      form,
      isLoading,
      minDate,
      leaveBalances,
      totalDays,
      insufficientBalance,
      getBalanceByType,
      submitForm
    }
  }
}
</script>