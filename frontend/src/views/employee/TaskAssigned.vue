<template>
  <div class="employee-task-page">
    <!-- Background Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50"></div>
    
    <!-- Main Content -->
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto"> <!-- Increased max-width for more space -->
        <!-- Header Section -->
        <div class="mb-8 text-center">
          <div class="flex items-center justify-center mb-4">
            <div class="p-3 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg mr-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-indigo-600 to-purple-700 bg-clip-text text-transparent">
                My Tasks
              </h1>
              <p class="text-gray-600 mt-2 text-lg">View and manage your assigned tasks efficiently.</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8 flex justify-center">
          <button 
            @click="fetchMyTasks" 
            :disabled="loadingTasks"
            class="inline-flex items-center px-6 py-3 bg-white border-2 border-indigo-200 text-indigo-700 font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loadingTasks" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ loadingTasks ? 'Refreshing...' : 'Refresh Tasks' }}
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loadingTasks" class="loading-container">
          <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="spinner mb-4"></div>
            <p class="text-lg text-gray-600">Loading your tasks...</p>
          </div>
        </div>

        <!-- Task Board Section -->
        <div v-else-if="myTasks.length > 0" class="task-board-section">
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
              <h2 class="text-xl font-semibold text-gray-800 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Your Active Tasks
              </h2>
            </div>
            <div class="p-8 min-h-[600px]"> <!-- Increased padding and added min-height for more visible space -->
              <TaskBoard 
                :tasks="myTasks"
                :employee-view="true"
                @task-updated="fetchMyTasks"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-12 text-center">
            <div class="empty-icon mb-6">🎉</div>
            <h3 class="text-2xl font-semibold text-gray-800 mb-2">No Tasks Assigned</h3>
            <p class="text-gray-600 text-lg">You have no tasks at the moment. Enjoy the break!</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';
import TaskBoard from '../../components/common/TaskBoard.vue';

// Initialize stores and router
const authStore = useAuthStore();
const router = useRouter();

const myTasks = ref([]);
const loadingTasks = ref(false);

/**
 * Fetches the current employee's tasks from the API.
 */
async function fetchMyTasks() {
  loadingTasks.value = true;
  try {
    // API endpoint for employee tasks
    const response = await axios.get('/api/tasks');
   
    // Normalize response data structure
    const tasksData = response.data.tasks || response.data.data || response.data;
    myTasks.value = Array.isArray(tasksData) ? tasksData : [];
   
    console.log('Fetched tasks:', myTasks.value.length, 'tasks');
  } catch (err) {
    console.error('Failed to fetch tasks:', err);
   
    let errorMessage = 'Failed to load your tasks. Please try again.';
   
    if (err.response?.status === 401) {
      errorMessage = 'Session expired. Please login again.';
      authStore.clearAuth();
      router.push('/login');
    } else if (err.response?.status === 403) {
      errorMessage = 'You do not have permission to view tasks.';
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message;
    }
   
    // Modern toast notification instead of alert
    // For now, console log; integrate with a toast lib if available
    console.error(`Error: ${errorMessage}`);
    myTasks.value = [];
  } finally {
    loadingTasks.value = false;
  }
}

// Fetch tasks when the component is mounted
onMounted(async () => {
  await fetchMyTasks();
});
</script>

<style scoped>
.employee-task-page {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}

.employee-task-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(236, 72, 153, 0.05) 100%);
  pointer-events: none;
  z-index: 0;
}

/* Loading Spinner */
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
}

/* Responsive Design */
@media (max-width: 640px) {
  .max-w-6xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .text-4xl {
    font-size: 2rem;
  }
  
  .empty-state {
    padding: 3rem 2rem;
  }

  .task-board-section .p-8 {
    padding: 2rem 1.5rem; /* Reduced padding on mobile for better fit */
    min-height: 500px; /* Adjusted min-height for mobile */
  }
}
</style>