<template>
  <div class="employee-task-page min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">My Tasks 📋</h1>
        <p class="text-gray-600">View and manage your assigned tasks.</p>
      </div>
      
      <div v-if="loadingTasks" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-t-4 border-blue-500 border-opacity-25 border-t-blue-500"></div>
        <p class="ml-4 text-lg text-gray-600">Loading tasks...</p>
      </div>
      
      <TaskBoard
        v-else
        :tasks="myTasks"
        :employee-view="true"
        @task-updated="fetchMyTasks"
        class="mb-8"
      />
      
      <div v-if="!loadingTasks && myTasks.length === 0" class="text-center py-12 bg-white rounded-lg shadow-lg">
        <p class="text-xl text-gray-500">🎉 You have no tasks assigned at the moment. Enjoy the break!</p>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth'; // Importing the store
import axios from 'axios';
import TaskBoard from '../../components/common/TaskBoard.vue';

// Initialize the Auth Store
const authStore = useAuthStore(); // Directly available in <script setup>

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
      // Use authStore for robust session handling if needed
      // authStore.clearAuth(); 
      // router.push('/login'); 
    } else if (err.response?.status === 403) {
      errorMessage = 'You do not have permission to view tasks.';
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message;
    }
    
    alert(`Error: ${errorMessage}`);
    myTasks.value = []; // Clear tasks on error
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
  /* Retain original font styling for consistency */
  font-family: 'Inter', sans-serif;
}

@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>