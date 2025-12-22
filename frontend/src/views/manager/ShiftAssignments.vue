<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Shift Assignments</h1>
      <p class="text-gray-600 mt-1">Manage and assign shifts to employees</p>
    </div>
    <!-- Actions Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex items-center justify-between gap-4">
      <!-- Filters -->
      <div class="flex items-center gap-3 flex-1">
        <select v-model="filters.status" @change="loadAssignments" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
        </select>
        <input
          v-model="filters.from_date"
          type="date"
          @change="loadAssignments"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          placeholder="From Date"
        />
        <input
          v-model="filters.to_date"
          type="date"
          @change="loadAssignments"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          placeholder="To Date"
        />
      </div>
      <!-- Actions -->
      <div class="flex items-center gap-3">
        <button
          @click="openAssignModal"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Assign Shift
        </button>
        <button
          @click="loadAssignments"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          title="Refresh"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </button>
      </div>
    </div>
    <!-- Assignments List -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      <p class="text-gray-600 mt-4">Loading assignments...</p>
    </div>
    <div v-else-if="assignments.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
      <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-gray-600 text-lg">No shift assignments found</p>
      <button
        @click="openAssignModal"
        class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        Assign First Shift
      </button>
    </div>
    <div v-else class="grid grid-cols-1 gap-4">
      <div
        v-for="assignment in assignments"
        :key="assignment.id"
        class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Employee Info -->
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                <span class="text-indigo-600 font-semibold">
                  {{ getEmployeeInitial(assignment) }}
                </span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">
                  {{ getEmployeeName(assignment) }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ assignment.employee?.department?.name || 'No Department' }}
                </p>
              </div>
            </div>
            <!-- Shift Details -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
              <div>
                <p class="text-xs text-gray-500 mb-1">Date Range</p>
                <!-- Updated to use formatDateRange -->
                <p class="font-medium">
                  {{ formatDateRange(assignment.start_date || assignment.shift_date, assignment.end_date) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Shift Type</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full" :class="getShiftTypeClass(assignment.shift_type)">
                  {{ capitalize(assignment.shift_type) }}
                </span>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Time</p>
                <p class="font-medium">{{ formatTime(assignment.start_time) }} - {{ formatTime(assignment.end_time) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Status</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(assignment.status)">
                  {{ capitalize(assignment.status) }}
                </span>
              </div>
            </div>
            <!-- Notes -->
            <div v-if="assignment.notes" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
              <p class="font-medium text-gray-700 mb-1">Notes:</p>
              {{ assignment.notes }}
            </div>
            <!-- Rejection Reason -->
            <div v-if="assignment.status === 'rejected' && assignment.rejection_reason" class="mt-3 text-sm text-red-600 bg-red-50 p-3 rounded-lg">
              <p class="font-medium mb-1">Rejection Reason:</p>
              {{ assignment.rejection_reason }}
            </div>
          </div>
          <!-- Actions -->
          <div class="flex flex-col gap-2 ml-4">
            <button
              v-if="assignment.status !== 'completed'"
              @click="editAssignment(assignment)"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
              title="Edit"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button
              v-if="['pending', 'accepted'].includes(assignment.status)"
              @click="cancelAssignment(assignment.id)"
              class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
              title="Cancel"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <button
              v-if="assignment.status !== 'completed'"
              @click="deleteAssignment(assignment.id)"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="Delete"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Assign Shift Modal -->
    <AssignShiftModal
      v-if="showAssignModal"
      :show="showAssignModal"
      :assignment="selectedAssignment"
      :auth-store="authStore"
      @close="closeAssignModal"
      @saved="handleAssignmentSaved"
    />
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { format } from 'date-fns';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import AssignShiftModal from './AssignShiftModal.vue';
const authStore = useAuthStore();
const assignments = ref([]);
const loading = ref(false);
const showAssignModal = ref(false);
const selectedAssignment = ref(null);
const filters = ref({
  status: '',
  from_date: '',
  to_date: ''
});
const loadAssignments = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.from_date) params.from_date = filters.value.from_date;
    if (filters.value.to_date) params.to_date = filters.value.to_date;
    const response = await axios.get('/api/shift-assignments', {
      params,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    assignments.value = response.data.assignments || response.data.data || [];
  } catch (error) {
    console.error('Failed to load assignments:', error);
  } finally {
    loading.value = false;
  }
};
const openAssignModal = () => {
  selectedAssignment.value = null;
  showAssignModal.value = true;
};
const editAssignment = (assignment) => {
  selectedAssignment.value = assignment;
  showAssignModal.value = true;
};
const closeAssignModal = () => {
  showAssignModal.value = false;
  selectedAssignment.value = null;
};
const handleAssignmentSaved = () => {
  loadAssignments();
  closeAssignModal();
};
const cancelAssignment = async (id) => {
  if (!confirm('Are you sure you want to cancel this assignment?')) return;
  try {
    await axios.post(`/api/shift-assignments/${id}/cancel`, {}, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    loadAssignments();
  } catch (error) {
    console.error('Failed to cancel assignment:', error);
    alert('Failed to cancel assignment.');
  }
};
const deleteAssignment = async (id) => {
  if (!confirm('Are you sure you want to delete this assignment?')) return;
  try {
    await axios.delete(`/api/shift-assignments/${id}`, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    loadAssignments();
  } catch (error) {
    console.error('Failed to delete assignment:', error);
    alert('Failed to delete assignment.');
  }
};
// --- Helpers ---
// Handle Date Ranges
const formatDateRange = (startDate, endDate) => {
  if (!startDate) return 'N/A';
  const start = new Date(startDate);
  // Fallback to start date if end date is missing (single day shift)
  const end = endDate ? new Date(endDate) : start;
 
  // If dates are the same, just return one
  if (start.toDateString() === end.toDateString()) {
    return format(start, 'MMM dd, yyyy');
  }
 
  // If years are different
  if (start.getFullYear() !== end.getFullYear()) {
    return `${format(start, 'MMM dd, yyyy')} - ${format(end, 'MMM dd, yyyy')}`;
  }
 
  // Same year, different days
  return `${format(start, 'MMM dd')} - ${format(end, 'MMM dd, yyyy')}`;
};
const formatTime = (time) => {
  if (!time) return '';
  return time.substring(0, 5); // Returns HH:MM from HH:MM:SS
};
const capitalize = (str) => {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
};
const getEmployeeName = (assignment) => {
  return assignment.employee?.user?.name ||
         (assignment.employee?.first_name ? `${assignment.employee.first_name} ${assignment.employee.last_name}` : 'Unknown Employee');
};
const getEmployeeInitial = (assignment) => {
  const name = getEmployeeName(assignment);
  return name.charAt(0).toUpperCase();
};
const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-gray-100 text-gray-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
const getShiftTypeClass = (type) => {
  const classes = {
    day: 'bg-blue-100 text-blue-800',
    night: 'bg-purple-100 text-purple-800',
    evening: 'bg-orange-100 text-orange-800',
    morning: 'bg-green-100 text-green-800'
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};
onMounted(() => {
  loadAssignments();
});
</script>