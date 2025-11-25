<template>
  <div class="manager-task-page">
    <!-- Background Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50"></div>
   
    <!-- Main Content -->
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 text-center sm:text-left">
          <div class="flex items-center justify-center sm:justify-start mb-4">
            <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg mr-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-blue-600 to-indigo-700 bg-clip-text text-transparent">
                Team Task Management
              </h1>
              <p class="text-gray-600 mt-2 text-lg">View, assign, and track tasks across your team with ease.</p>
            </div>
          </div>
        </div>
        <!-- Quick Actions -->
        <div class="mb-8 flex flex-wrap gap-4 justify-center sm:justify-start">
          <router-link
            to="/manager/tasks/new"
            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Task
          </router-link>
          <button
            @click="refreshTasks"
            :disabled="loadingEmployees"
            class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loadingEmployees" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ loadingEmployees ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
        <!-- Task Board Section -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Active Task Board
            </h2>
          </div>
          <div class="p-6">
            <TaskBoard
              :key="refreshKey"
              :employees="employees"
              :loading="loadingEmployees"
              @task-updated="onTaskUpdated"
              class="w-full"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import TaskBoard from '../../components/common/TaskBoard.vue';

const emit = defineEmits(['task-created']);
const router = useRouter();

const employees = ref([]);
const loadingEmployees = ref(false);
const refreshKey = ref(0);

onMounted(async () => {
  await fetchEmployees();
});

async function fetchEmployees() {
  loadingEmployees.value = true;
  try {
    const response = await axios.get('/api/manager/employees');
    employees.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    // Modern toast notification instead of alert
    // For now, console log; integrate with a toast lib if available
    console.error('Failed to load team members. Some features may be limited.');
  } finally {
    loadingEmployees.value = false;
  }
}

const refreshTasks = async () => {
  refreshKey.value++;
  await fetchEmployees();
};

const onTaskUpdated = async () => {
  refreshKey.value++;
  await fetchEmployees();
};

const getEmployeeUserId = (employee) => {
  return employee.user_id;
};

const getEmployeeDisplayName = (employee) => {
  return `${employee.first_name} ${employee.last_name}`;
};
</script>
<style scoped>
.manager-task-page {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}
.manager-task-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(147, 51, 234, 0.05) 100%);
  pointer-events: none;
  z-index: 0;
}
@media (max-width: 640px) {
  .max-w-7xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .text-4xl {
    font-size: 2rem;
  }
}
</style>