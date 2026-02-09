<template>
  <div class="schedule-page min-h-screen bg-gray-50 pb-12">
    <!-- Header with Notifications -->
    <div class="bg-white shadow relative z-30 border-b border-gray-200">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900">Team Schedule & Operations</h1>
            <p class="mt-1 text-sm text-gray-600 font-medium">Manage weekly tasks, assignments, and deadlines</p>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- Notifications Bell -->
            <div class="relative" ref="notificationContainer">
              <button 
                type="button"
                @click="toggleNotifications"
                class="relative p-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full transition-colors bg-white hover:bg-gray-100 border border-gray-200">
                <span class="sr-only">View notifications</span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full shadow-sm">
                  {{ unreadCount }}
                </span>
              </button>
              
              <!-- Notifications Dropdown -->
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200 max-h-96 overflow-y-auto ring-1 ring-black ring-opacity-5">
                  <div class="p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">Notifications</h3>
                    <button type="button" v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-indigo-700 hover:text-indigo-900 font-bold">
                      Mark all read
                    </button>
                  </div>
                  <div v-if="!notifications || notifications.length === 0" class="p-8 text-center text-gray-500 font-medium">
                    No new notifications
                  </div>
                  <div v-else>
                    <div 
                      v-for="notif in notifications" 
                      :key="notif.id"
                      @click="handleNotificationClick(notif)"
                      :class="['p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors', !notif.is_read ? 'bg-blue-50' : 'bg-white']">
                      <div class="flex items-start">
                        <div :class="['p-2 rounded-full mr-3 flex-shrink-0', getNotificationColor(notif.notification_type)]">
                          <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                          </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-bold text-gray-900 truncate">{{ getNotificationMessage(notif) }}</p>
                          <p class="text-xs text-gray-600 mt-1 font-medium">{{ formatDateTime(notif.created_at) }}</p>
                        </div>
                        <div v-if="!notif.is_read" class="w-2 h-2 bg-blue-600 rounded-full mt-2 ml-2"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <!-- View Toggle -->
            <div class="flex rounded-lg border border-gray-300 overflow-hidden shadow-sm">
              <button 
                type="button"
                @click="currentView = 'calendar'"
                :class="['px-4 py-2 text-sm font-bold focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:z-10', currentView === 'calendar' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-50']">
                Calendar
              </button>
              <button 
                type="button"
                @click="currentView = 'list'"
                :class="['px-4 py-2 text-sm font-bold border-l border-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:z-10', currentView === 'list' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-50']">
                List View
              </button>
            </div>

            <!-- Create Button -->
            <button 
              type="button"
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition shadow-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              New Schedule
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error/Loading/Content Section -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 relative z-0">
      
      <!-- Error State -->
      <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700 font-bold">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Content -->
      <div v-else>
        <ScheduleCalendar 
          v-if="currentView === 'calendar'"
          :schedules="schedules"
          @schedule-click="openScheduleDetail"
          @date-click="openCreateModalWithDate"
        />

        <ScheduleListView 
          v-if="currentView === 'list'"
          :schedules="schedules"
          :employees="employees"
          @complete="completeSchedule"
          @edit="editSchedule"
          @delete="deleteSchedule"
          @toggle-meta="toggleBannerRegion"
        />
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <ScheduleModal
      v-if="showCreateModal"
      :schedule="editingSchedule"
      :employees="employees"
      :preset-date="presetDate"
      :saving="isSaving"
      @close="closeModal"
      @save="saveSchedule"
    />

    <!-- Schedule Detail Modal -->
    <ScheduleDetailModal
      v-if="showDetailModal"
      :schedule="selectedSchedule"
      @close="showDetailModal = false"
      @edit="editSchedule"
      @complete="completeSchedule"
      @delete="deleteSchedule"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

// Components
import ScheduleCalendar from '../../components/manager/ScheduleCalendar.vue';
import ScheduleListView from '../../components/manager/ScheduleListView.vue';
import ScheduleModal from '../../components/manager/ScheduleModal.vue';
import ScheduleDetailModal from '../../components/manager/ScheduleDetailModal.vue';

// --- State ---
const schedules = ref([]);
const employees = ref([]);
const notifications = ref([]);
const loading = ref(true);
const isSaving = ref(false);
const error = ref(null);
const currentView = ref('calendar');

// Modal States
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showNotifications = ref(false);
const editingSchedule = ref(null);
const selectedSchedule = ref(null);
const presetDate = ref(null);
const notificationContainer = ref(null);

const unreadCount = computed(() => {
  return (notifications.value || []).filter(n => !n.is_read).length;
});

// --- Helper for Default Object (Prevents NULL crashes) ---
const createEmptySchedule = () => {
  try {
    const today = new Date();
    const dateStr = isNaN(today.getTime()) ? new Date().toISOString().split('T')[0] : today.toISOString().split('T')[0];
    
    return {
      title: '',
      description: '',
      type: 'banner_creation', // Frontend uses 'type'
      priority: 'moderate',
      due_date: dateStr,
      scheduled_date: dateStr, // Also set scheduled_date
      assigned_to: '',
      meta_data: {},
      status: 'pending'
    };
  } catch (err) {
    console.error('Error creating empty schedule:', err);
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    
    return {
      title: '',
      description: '',
      type: 'banner_creation',
      priority: 'moderate',
      due_date: `${year}-${month}-${day}`,
      scheduled_date: `${year}-${month}-${day}`,
      assigned_to: '',
      meta_data: {},
      status: 'pending'
    };
  }
};

// Helper to validate and fix date strings
const validateDate = (dateValue) => {
  if (!dateValue) return null;
  
  try {
    const date = new Date(dateValue);
    if (isNaN(date.getTime())) {
      console.warn('Invalid date detected:', dateValue);
      return null;
    }
    return date.toISOString().split('T')[0];
  } catch (err) {
    console.error('Date validation error:', err);
    return null;
  }
};

// --- Lifecycle ---
onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  await loadInitialData();
  const pollInterval = setInterval(fetchNotifications, 60000);
  
  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearInterval(pollInterval);
  });
});

const handleClickOutside = (event) => {
  if (showNotifications.value && notificationContainer.value && !notificationContainer.value.contains(event.target)) {
    showNotifications.value = false;
  }
};

const loadInitialData = async () => {
  loading.value = true;
  error.value = null;
  try {
    await Promise.allSettled([
      fetchEmployees(),
      fetchSchedules(),
      fetchNotifications()
    ]);
  } catch (err) {
    error.value = "Failed to load data. Please refresh.";
    console.error('Initial load error:', err);
  } finally {
    loading.value = false;
  }
};

// --- API Calls ---
const fetchEmployees = async () => {
  try {
    const response = await axios.get('/api/manager/employees');
    employees.value = response.data.data || response.data || [];
  } catch (err) { 
    console.error('Failed to fetch employees:', err);
    employees.value = [];
  }
};

const fetchSchedules = async () => {
  try {
    const response = await axios.get('/api/schedules');
    const rawSchedules = response.data.schedules || [];
    
    // Validate and sanitize schedule dates, and normalize field names
    schedules.value = rawSchedules.map(schedule => ({
      ...schedule,
      // Normalize field names (backend uses schedule_type, frontend uses type)
      type: schedule.schedule_type || schedule.type,
      schedule_type: schedule.schedule_type || schedule.type,
      // Validate dates
      due_date: validateDate(schedule.due_date) || validateDate(schedule.scheduled_date),
      scheduled_date: validateDate(schedule.scheduled_date) || validateDate(schedule.due_date),
      created_at: validateDate(schedule.created_at),
      updated_at: validateDate(schedule.updated_at)
    }));
    
    console.log('Schedules loaded:', schedules.value.length);
  } catch (err) {
    console.error('Failed to fetch schedules:', err);
    schedules.value = [];
  }
};

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/api/notifications');
    const rawNotifications = response.data.notifications || [];
    
    notifications.value = rawNotifications.map(notif => ({
      ...notif,
      created_at: notif.created_at || new Date().toISOString()
    }));
  } catch (err) {
    console.error('Failed to fetch notifications:', err);
    notifications.value = [];
  }
};

// --- Actions ---

const openCreateModal = () => {
  editingSchedule.value = createEmptySchedule();
  presetDate.value = null;
  showCreateModal.value = true;
  console.log('Opening create modal with:', editingSchedule.value);
};

const openCreateModalWithDate = (date) => {
  const newSchedule = createEmptySchedule();
  if (date) {
    try {
      const dateObj = new Date(date);
      if (!isNaN(dateObj.getTime())) {
        newSchedule.due_date = date;
        newSchedule.scheduled_date = date;
        presetDate.value = date;
      }
    } catch (err) {
      console.error('Invalid date provided:', date, err);
    }
  }
  editingSchedule.value = newSchedule;
  showCreateModal.value = true;
  console.log('Opening create modal with date:', date, editingSchedule.value);
};

const saveSchedule = async (scheduleData) => {
  isSaving.value = true;
  error.value = null;
  
  try {
    // Validate required fields
    if (!scheduleData.title || !scheduleData.due_date) {
      alert('Please fill in all required fields');
      return;
    }

    // Validate the due_date
    const validatedDate = validateDate(scheduleData.due_date);
    if (!validatedDate) {
      alert('Invalid due date. Please select a valid date.');
      return;
    }

    // Prepare data for backend - map frontend fields to backend fields
    const dataToSend = {
      title: scheduleData.title,
      description: scheduleData.description || '',
      type: scheduleData.type || scheduleData.schedule_type, // Backend expects 'type'
      priority: scheduleData.priority,
      due_date: validatedDate,
      scheduled_date: scheduleData.scheduled_date || validatedDate,
      assigned_to: scheduleData.assigned_to,
      meta_data: scheduleData.meta_data || {},
      notes: scheduleData.notes || '',
      status: scheduleData.status || 'pending'
    };

    console.log('Saving schedule:', dataToSend);

    // Check if ID exists to determine update vs create
    if (scheduleData.id) {
      await axios.put(`/api/schedules/${scheduleData.id}`, dataToSend);
    } else {
      await axios.post('/api/schedules', dataToSend);
    }
    
    await fetchSchedules();
    closeModal();
  } catch (err) {
    console.error('Failed to save schedule:', err);
    if (err.response?.data?.errors) {
      console.error('Validation errors:', err.response.data.errors);
      const errorMessages = Object.values(err.response.data.errors).flat().join('\n');
      alert('Validation failed:\n' + errorMessages);
    } else {
      error.value = 'Failed to save schedule. Please try again.';
      alert('Failed to save. Please check inputs.');
    }
  } finally {
    isSaving.value = false;
  }
};

const completeSchedule = async (scheduleId) => {
  if (!confirm('Mark this task as complete?')) return;
  try {
    await axios.post(`/api/schedules/${scheduleId}/complete`);
    await fetchSchedules();
    if(selectedSchedule.value?.id === scheduleId) showDetailModal.value = false;
  } catch (err) {
    console.error('Failed to complete schedule:', err);
    alert('Error completing schedule');
  }
};

const editSchedule = (schedule) => {
  // Deep clone and normalize fields
  const clonedSchedule = JSON.parse(JSON.stringify(schedule));
  // Ensure both type and schedule_type are set
  clonedSchedule.type = clonedSchedule.schedule_type || clonedSchedule.type;
  clonedSchedule.schedule_type = clonedSchedule.schedule_type || clonedSchedule.type;
  
  editingSchedule.value = clonedSchedule;
  showCreateModal.value = true;
  showDetailModal.value = false;
};

const deleteSchedule = async (scheduleId) => {
  if (!confirm('Delete this schedule?')) return;
  try {
    await axios.delete(`/api/schedules/${scheduleId}`);
    await fetchSchedules();
    showDetailModal.value = false;
  } catch (err) {
    console.error('Failed to delete schedule:', err);
    alert('Error deleting schedule');
  }
};

const toggleBannerRegion = async (schedule, region) => {
  const newMeta = { ...(schedule.meta_data || {}) };
  newMeta[region] = !newMeta[region];
  
  const oldMeta = { ...schedule.meta_data };
  schedule.meta_data = newMeta;
  
  try {
    await axios.put(`/api/schedules/${schedule.id}/meta`, { meta_data: newMeta });
  } catch (err) {
    console.error('Failed to toggle banner region:', err);
    schedule.meta_data = oldMeta;
  }
};

const openScheduleDetail = (schedule) => {
  selectedSchedule.value = schedule;
  showDetailModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  editingSchedule.value = null;
  presetDate.value = null;
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
};

const handleNotificationClick = async (notif) => {
  if(!notif.is_read) await markAsRead(notif.id);
  if(notif.schedule_id) {
     const sched = schedules.value.find(s => s.id === notif.schedule_id);
     if(sched) openScheduleDetail(sched);
  }
  showNotifications.value = false;
};

const markAsRead = async (id) => {
  try {
    const idx = notifications.value.findIndex(n => n.id === id);
    if(idx !== -1) notifications.value[idx].is_read = true;
    await axios.put(`/api/notifications/${id}/read`);
  } catch (err) { 
    console.error('Failed to mark as read:', err);
  }
};

const markAllRead = async () => {
  try {
    await axios.put('/api/notifications/read-all');
    notifications.value.forEach(n => n.is_read = true);
  } catch (err) { 
    console.error('Failed to mark all as read:', err);
  }
};

// --- Helpers ---

const getNotificationColor = (type) => {
  const colors = { 
    assigned: 'bg-blue-500', 
    reminder: 'bg-yellow-500', 
    overdue: 'bg-red-500', 
    completed: 'bg-green-500' 
  };
  return colors[type] || 'bg-gray-500';
};

const getNotificationMessage = (notif) => {
  if (!notif) return 'New notification';
  
  const title = (notif.schedule && notif.schedule.title) ? notif.schedule.title : 'Unknown Task';
  
  const messages = {
    assigned: `You've been assigned: ${title}`,
    reminder: `Reminder: ${title} is due soon`,
    overdue: `Overdue: ${title}`,
    completed: `Completed: ${title}`
  };
  
  return messages[notif.notification_type] || notif.message || 'New notification';
};

const formatDateTime = (dateString) => {
  if (!dateString || dateString === '') {
    return 'Just now';
  }
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return 'Recently';
    }
    
    const now = new Date();
    const diffMinutes = Math.floor((now - date) / 60000);
    
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
    
    const options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-GB', options);
  } catch (err) {
    console.error('Date formatting error:', err);
    return 'Recently';
  }
};
</script>