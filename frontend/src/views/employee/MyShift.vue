<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">My Shifts</h1>
      <p class="text-gray-600 mt-1">View and manage your assigned shifts</p>
    </div>

    <!-- Today's Shift Card -->
    <div v-if="todayShift" class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-indigo-100 text-sm mb-2">Today's Shift</p>
          <h2 class="text-2xl font-bold mb-4">{{ todayShift.shift_type }} Shift</h2>
          
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <p class="text-indigo-100 text-sm">Start Time</p>
              <p class="text-xl font-semibold">{{ todayShift.start_time }}</p>
            </div>
            <div>
              <p class="text-indigo-100 text-sm">End Time</p>
              <p class="text-xl font-semibold">{{ todayShift.end_time }}</p>
            </div>
          </div>

          <div v-if="todayShift.notes" class="bg-white/10 backdrop-blur rounded-lg p-3 mb-4">
            <p class="text-sm">{{ todayShift.notes }}</p>
          </div>

          <div class="flex items-center gap-3">
            <span v-if="todayShift.status === 'pending'" class="inline-flex px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-medium">
              Pending Acceptance
            </span>
            <span v-else-if="todayShift.status === 'accepted'" class="inline-flex px-3 py-1 bg-green-400 text-green-900 rounded-full text-sm font-medium">
              ✓ Accepted
            </span>
            
            <button
              v-if="todayShift.status === 'accepted' && !todayShift.shift"
              @click="checkInNow"
              class="px-4 py-2 bg-white text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors font-medium"
            >
              Check In Now
            </button>
            
            <span v-if="todayShift.shift" class="inline-flex px-3 py-1 bg-blue-400 text-blue-900 rounded-full text-sm font-medium">
              ✓ Checked In
            </span>
          </div>
        </div>

        <div v-if="todayShift.status === 'pending'" class="flex gap-2">
          <button
            @click="acceptShift(todayShift.id)"
            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
          >
            Accept
          </button>
          <button
            @click="showRejectModal(todayShift)"
            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
          >
            Reject
          </button>
        </div>
      </div>
    </div>

    <!-- No Today Shift -->
    <div v-else class="bg-white rounded-lg shadow-sm p-8 mb-6 text-center">
      <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-gray-600 text-lg">No shift assigned for today</p>
    </div>

    <!-- Upcoming Shifts -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Upcoming Shifts</h2>

      <div v-if="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else-if="upcomingShifts.length === 0" class="text-center py-8">
        <p class="text-gray-500">No upcoming shifts</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="shift in upcomingShifts"
          :key="shift.id"
          class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full" :class="getShiftTypeClass(shift.shift_type)">
                  {{ shift.shift_type }}
                </span>
                <span class="text-sm text-gray-500">{{ formatDate(shift.shift_date) }}</span>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(shift.status)">
                  {{ shift.status }}
                </span>
              </div>

              <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-3">
                <div>
                  <p class="text-xs text-gray-500">Start Time</p>
                  <p class="font-medium">{{ shift.start_time }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">End Time</p>
                  <p class="font-medium">{{ shift.end_time }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500">Department</p>
                  <p class="font-medium">{{ shift.department?.name || 'N/A' }}</p>
                </div>
              </div>

              <div v-if="shift.notes" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                {{ shift.notes }}
              </div>
            </div>

            <div v-if="shift.status === 'pending'" class="flex gap-2 ml-4">
              <button
                @click="acceptShift(shift.id)"
                class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm font-medium"
              >
                Accept
              </button>
              <button
                @click="showRejectModal(shift)"
                class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Past Shifts (Collapsed) -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <button
        @click="showPastShifts = !showPastShifts"
        class="w-full flex items-center justify-between text-xl font-bold text-gray-900 mb-4"
      >
        <span>Past Shifts</span>
        <svg
          class="w-6 h-6 transition-transform"
          :class="{ 'rotate-180': showPastShifts }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div v-if="showPastShifts" class="space-y-4">
        <!-- Add past shifts list here similar to upcoming -->
        <p class="text-gray-500 text-center py-4">Past shifts will be shown here</p>
      </div>
    </div>

    <!-- Reject Modal -->
    <div
      v-if="showRejectDialogue"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
      @click.self="closeRejectModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Reject Shift</h3>
        
        <p class="text-gray-600 mb-4">Please provide a reason for rejecting this shift:</p>
        
        <textarea
          v-model="rejectionReason"
          rows="4"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Enter your reason..."
        ></textarea>

        <div class="flex items-center justify-end gap-3 mt-6">
          <button
            @click="closeRejectModal"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitRejection"
            :disabled="!rejectionReason"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Reject Shift
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { format } from 'date-fns';

const todayShift = ref(null);
const upcomingShifts = ref([]);
const loading = ref(false);
const showPastShifts = ref(false);
const showRejectDialogue = ref(false);
const rejectionReason = ref('');
const selectedShiftForRejection = ref(null);

const loadTodayShift = async () => {
  try {
    const response = await fetch('/api/shift-assignments/today', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });

    const data = await response.json();
    todayShift.value = data.assignment;
  } catch (error) {
    console.error('Failed to load today shift:', error);
  }
};

const loadUpcomingShifts = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/shift-assignments/my-shifts', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });

    const data = await response.json();
    // Filter out today's shift from upcoming
    upcomingShifts.value = (data.assignments || []).filter(shift => {
      const shiftDate = new Date(shift.shift_date);
      const today = new Date();
      return shiftDate.toDateString() !== today.toDateString();
    });
  } catch (error) {
    console.error('Failed to load upcoming shifts:', error);
  } finally {
    loading.value = false;
  }
};

const acceptShift = async (id) => {
  try {
    const response = await fetch(`/api/shift-assignments/${id}/accept`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });

    if (response.ok) {
      loadTodayShift();
      loadUpcomingShifts();
    }
  } catch (error) {
    console.error('Failed to accept shift:', error);
  }
};

const showRejectModal = (shift) => {
  selectedShiftForRejection.value = shift;
  rejectionReason.value = '';
  showRejectDialogue.value = true;
};

const closeRejectModal = () => {
  showRejectDialogue.value = false;
  selectedShiftForRejection.value = null;
  rejectionReason.value = '';
};

const submitRejection = async () => {
  if (!rejectionReason.value || !selectedShiftForRejection.value) return;

  try {
    const response = await fetch(`/api/shift-assignments/${selectedShiftForRejection.value.id}/reject`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify({ reason: rejectionReason.value })
    });

    if (response.ok) {
      closeRejectModal();
      loadTodayShift();
      loadUpcomingShifts();
    }
  } catch (error) {
    console.error('Failed to reject shift:', error);
  }
};

const checkInNow = () => {
  // Navigate to check-in page or trigger check-in API
  window.location.href = '/shifts/check-in';
};

const formatDate = (date) => {
  return format(new Date(date), 'EEEE, MMM dd, yyyy');
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    completed: 'bg-blue-100 text-blue-800'
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
  loadTodayShift();
  loadUpcomingShifts();
});
</script>