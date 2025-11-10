<template>
  <div class="manager-task-page min-h-screen bg-gray-50 py-8">
    <!-- Task Board Section -->
    <div class="container mx-auto px-4">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Team Task Management</h1>
        <p class="text-gray-600">View and manage all assigned tasks across your team.</p>
      </div>

      <TaskBoard @task-updated="fetchEmployees" class="mb-8" />
      
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import TaskBoard from '../../components/common/TaskBoard.vue';


const emit = defineEmits(['task-created']);
const employees = ref([]);
const loadingEmployees = ref(false);

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
    alert('Failed to load team members. Some features may be limited.');
  } finally {
    loadingEmployees.value = false;
  }
}

const getEmployeeUserId = (employee) => {
  return employee.user_id;
};

const getEmployeeDisplayName = (employee) => {
  return `${employee.first_name} ${employee.last_name}`;
};
</script>

<style scoped>
.manager-task-page {
  font-family: 'Inter', sans-serif;
}

@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>