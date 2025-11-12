<template>
  <div class="employee-task-page">
    <div class="page-header">
      <h1 class="page-title">My Tasks 📋</h1>
      <p class="page-description">View and manage your assigned tasks.</p>
    </div>
    
    <div v-if="loadingTasks" class="loading-container">
      <div class="spinner"></div>
      <p class="loading-text">Loading tasks...</p>
    </div>
    
    <TaskBoard
      v-else-if="myTasks.length > 0"
      :tasks="myTasks"
      :employee-view="true"
      @task-updated="fetchMyTasks"
    />
    
    <div v-if="!loadingTasks && myTasks.length === 0" class="empty-state">
      <div class="empty-icon">🎉</div>
      <p class="empty-text">You have no tasks assigned at the moment. Enjoy the break!</p>
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
    
    alert(`Error: ${errorMessage}`);
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
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.page-title {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 600;
  color: #333333;
}

.page-description {
  margin: 0;
  color: #7a7a7a;
  font-size: 1rem;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #4A90E2;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1.1rem;
  color: #7a7a7a;
  margin: 0;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-text {
  font-size: 1.2rem;
  color: #7a7a7a;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
  }
  
  .loading-container {
    padding: 3rem 1rem;
  }
  
  .empty-state {
    padding: 3rem 1.5rem;
  }
}
</style>