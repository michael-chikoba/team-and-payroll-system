<!-- views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <!-- Welcome message -->
    <div class="welcome-section">
      <h1 class="text-2xl font-bold text-gray-800">
        Welcome back, {{ userName }}! 👋
      </h1>
      <p class="text-gray-600 mt-2">
        {{ welcomeMessage }}
      </p>
    </div>

    <!-- Role-specific dashboard content -->
    <div v-if="userRole === 'admin'" class="mt-8">
      <AdminDashboard />
    </div>
    
    <div v-else-if="userRole === 'manager'" class="mt-8">
      <ManagerDashboard />
    </div>
    
    <div v-else class="mt-8">
      <EmployeeDashboard />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AdminDashboard from '@/views/admin/Dashboard.vue'
import ManagerDashboard from '@/views/manager/Dashboard.vue'
import EmployeeDashboard from '@/views/employee/Dashboard.vue'

export default {
  name: 'Dashboard',
  components: {
    AdminDashboard,
    ManagerDashboard,
    EmployeeDashboard
  },
  setup() {
    const authStore = useAuthStore()
    
    const userRole = computed(() => authStore.userRole)
    const userName = computed(() => authStore.user?.name || authStore.user?.fullName || 'User')
    
    const welcomeMessage = computed(() => {
      const messages = {
        'admin': 'Manage your payroll system, employees, and configurations.',
        'manager': 'Oversee your team, schedules, and productivity metrics.',
        'employee': 'Check your attendance, leaves, and upcoming tasks.'
      }
      return messages[userRole.value] || 'Welcome to your dashboard.'
    })
    
    return {
      userRole,
      userName,
      welcomeMessage
    }
  }
}
</script>

<style scoped>
.dashboard {
  @apply p-6;
}

.welcome-section {
  @apply bg-white rounded-xl shadow-sm p-6 border border-gray-100;
}
</style>