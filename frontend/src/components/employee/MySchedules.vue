<template>
  <div class="employee-schedules-page">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50"></div>
    
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="p-3 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-indigo-600 to-purple-700 bg-clip-text text-transparent">
                  My Schedules
                </h1>
                <p class="text-gray-600 mt-2 text-lg">View and manage your assigned schedules</p>
              </div>
            </div>
            
            <button 
              @click="fetchSchedules" 
              :disabled="loading"
              class="inline-flex items-center px-4 py-2 bg-white border-2 border-indigo-200 text-indigo-700 font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:bg-indigo-50 disabled:opacity-50"
            >
              <svg :class="{'animate-spin': loading}" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Refresh
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Assigned</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
              </div>
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">In Progress</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.inProgress }}</p>
              </div>
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
              </div>
              <div class="p-3 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Overdue</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.overdue }}</p>
              </div>
              <div class="p-3 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
              <select v-model="filters.type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="banner_creation">Banner Creation</option>
                <option value="weekly_overview">Weekly Overview</option>
                <option value="test_sequence">Test Sequence</option>
                <option value="live_games">Live Games</option>
                <option value="multibets">Multibets</option>
                <option value="news_section">News Section</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="filters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>
            
            <div class="flex items-end">
              <button @click="resetFilters" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                Reset Filters
              </button>
            </div>
          </div>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center p-12 bg-white rounded-lg shadow-sm">
          <div class="spinner mb-4"></div>
          <p class="text-lg text-gray-600">Loading schedules...</p>
        </div>

        <div v-else-if="filteredSchedules.length > 0" class="space-y-6">
          <div v-for="type in activeScheduleTypes" :key="type">
            <EmployeeScheduleSection
              :type="type"
              :schedules="getSchedulesByType(type)"
              @status-change="handleStatusChange"
              @view-details="showScheduleDetails"
            />
          </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-xl border border-gray-200 p-12 text-center">
          <div class="text-6xl mb-6">📋</div>
          <h3 class="text-2xl font-semibold text-gray-800 mb-2">No Schedules Found</h3>
          <p class="text-gray-600 text-lg">
            {{ filters.type || filters.status ? 'Try adjusting your filters' : 'You have no schedules assigned at the moment' }}
          </p>
        </div>
      </div>
    </div>

    <ScheduleDetailModal
      v-if="selectedSchedule"
      :schedule="selectedSchedule"
      :employee-view="true"
      @close="selectedSchedule = null"
      @status-change="handleStatusChange"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';
import EmployeeScheduleSection from '@/components/employee/EmployeeScheduleSection.vue';
import ScheduleDetailModal from '@/components/employee/ScheduleDetailModal.vue';

const authStore = useAuthStore();
const router = useRouter();

const schedules = ref([]);
const loading = ref(false);
const selectedSchedule = ref(null);

const filters = ref({
  type: '',
  status: ''
});

const fetchSchedules = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/schedules/my-schedules');
    schedules.value = response.data.schedules || response.data.data || response.data || [];
    console.log('Fetched schedules:', schedules.value.length);
  } catch (error) {
    console.error('Failed to fetch schedules:', error);
    
    if (error.response?.status === 401) {
      authStore.clearAuth();
      router.push('/login');
    } else {
      alert('Failed to load schedules. Please try again.');
    }
    schedules.value = [];
  } finally {
    loading.value = false;
  }
};

const stats = computed(() => {
  const total = schedules.value.length;
  const inProgress = schedules.value.filter(s => s.status === 'in_progress').length;
  const completed = schedules.value.filter(s => s.status === 'completed').length;
  const overdue = schedules.value.filter(s => {
    if (s.status === 'completed') return false;
    return new Date(s.due_date) < new Date();
  }).length;
  
  return { total, inProgress, completed, overdue };
});

const filteredSchedules = computed(() => {
  return schedules.value.filter(schedule => {
    if (filters.value.type && schedule.schedule_type !== filters.value.type) return false;
    if (filters.value.status && schedule.status !== filters.value.status) return false;
    return true;
  });
});

const activeScheduleTypes = computed(() => {
  const types = [...new Set(filteredSchedules.value.map(s => s.schedule_type))];
  return types.sort();
});

const getSchedulesByType = (type) => {
  return filteredSchedules.value.filter(s => s.schedule_type === type);
};

const resetFilters = () => {
  filters.value = { type: '', status: '' };
};

const handleStatusChange = async (scheduleId, newStatus) => {
  try {
    await axios.put(`/api/schedules/${scheduleId}/status`, { status: newStatus });
    
    const schedule = schedules.value.find(s => s.id === scheduleId);
    if (schedule) {
      schedule.status = newStatus;
    }
    
    if (selectedSchedule.value?.id === scheduleId) {
      selectedSchedule.value.status = newStatus;
    }
    
    console.log('Status updated successfully');
  } catch (error) {
    console.error('Failed to update status:', error);
    alert('Failed to update status. Please try again.');
  }
};

const showScheduleDetails = (schedule) => {
  selectedSchedule.value = schedule;
};

onMounted(() => {
  fetchSchedules();
});
</script>

<style scoped>
.employee-schedules-page {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}

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
</style>