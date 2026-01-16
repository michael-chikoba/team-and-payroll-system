<template>
  <div class="admin-task-page">
    <!-- Background Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50"></div>
   
    <!-- Main Content -->
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 text-center sm:text-left">
          <div class="flex items-center justify-center sm:justify-start mb-4">
            <div class="p-3 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg mr-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-purple-600 to-indigo-700 bg-clip-text text-transparent">
                Business Task Management
              </h1>
              <p class="text-gray-600 mt-2 text-lg">View, assign, and track tasks across your entire business.</p>
            </div>
          </div>
        </div>

     

        <!-- Task Board Section -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-purple-50">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    const response = await axios.get('/api/tasks/employees/simple');
    employees.value = response.data.employees || response.data.data || [];
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    console.error('Failed to load business employees. Some features may be limited.');
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
</script>

<style scoped>
.admin-task-page {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}

.admin-task-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
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