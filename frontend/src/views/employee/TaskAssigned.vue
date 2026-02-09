<template>
  <div class="employee-task-page">
    <!-- Background Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50"></div>
    
    <!-- Main Content -->
    <div class="relative z-10 min-h-screen py-6 px-4 sm:px-6 lg:px-8 flex flex-col">
      <div class="max-w-6xl mx-auto w-full flex-1 flex flex-col">
        <!-- Compact Header Card -->
        <div class="mb-6">
          <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-5">
            <div class="flex items-center">
              <div class="p-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 via-indigo-600 to-purple-700 bg-clip-text text-transparent">
                      My Tasks
                    </h1>
                    <p class="text-gray-600 mt-1 text-sm">View and manage your assigned tasks</p>
                  </div>
                  <button 
                    @click="fetchMyTasks" 
                    :disabled="loadingTasks"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:from-indigo-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                  >
                    <svg v-if="loadingTasks" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ loadingTasks ? 'Refreshing...' : 'Refresh' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loadingTasks" class="loading-container flex-1 flex items-center justify-center">
          <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="spinner mb-4"></div>
            <p class="text-lg text-gray-600">Loading your tasks...</p>
          </div>
        </div>

        <!-- Task Board Section -->
        <div v-else-if="myTasks.length > 0" class="task-board-section flex-1 flex flex-col">
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden flex-1 flex flex-col">
            <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  Active Tasks
                  <span class="ml-2 bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ myTasks.length }}
                  </span>
                </h2>
              </div>
            </div>
            <div class="p-5 flex-1 min-h-0">
              <TaskBoard 
                :tasks="myTasks"
                :employee-view="true"
                @task-updated="fetchMyTasks"
                class="w-full h-full"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state flex-1 flex items-center justify-center">
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
  height: 100vh;
  display: flex;
  flex-direction: column;
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

/* Ensure the main content area takes full height */
.min-h-screen {
  min-height: 100vh;
}

/* Ensure TaskBoard container has proper height constraints */
.min-h-0 {
  min-height: 0;
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
  
  .text-2xl {
    font-size: 1.5rem;
  }
  
  .employee-task-page {
    height: auto;
    min-height: 100vh;
  }
  
  .empty-state .p-12 {
    padding: 3rem 1.5rem;
  }

  .task-board-section .p-5 {
    padding: 1rem;
  }
  
  /* Stack header content on mobile */
  .flex.items-center.justify-between {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .flex.items-center.justify-between button {
    align-self: flex-end;
  }
}
</style>