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

    <!-- ─── Create/Edit Modal — Teleported to <body> ──────────────────────── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
          @mousedown.self="closeModal"
        >
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" aria-hidden="true"></div>

          <!-- Modal Panel -->
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
          >
            <div
              v-if="showCreateModal"
              class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl ring-1 ring-black/10"
              role="dialog"
              aria-modal="true"
            >
              <ScheduleModal
                :schedule="editingSchedule"
                :employees="employees"
                :preset-date="presetDate"
                :saving="isSaving"
                @close="closeModal"
                @save="saveSchedule"
              />
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- ─── Schedule Detail Modal — Teleported to <body> ─────────────────── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showDetailModal"
          class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
          @mousedown.self="showDetailModal = false"
        >
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" aria-hidden="true"></div>

          <!-- Modal Panel -->
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
          >
            <div
              v-if="showDetailModal"
              class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl ring-1 ring-black/10"
              role="dialog"
              aria-modal="true"
            >
              <ScheduleDetailModal
                :schedule="selectedSchedule"
                @close="showDetailModal = false"
                @edit="editSchedule"
                @complete="completeSchedule"
                @delete="deleteSchedule"
              />
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

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

// --- Helper for Default Object ---
const createEmptySchedule = () => {
  try {
    const today = new Date();
    const dateStr = today.toISOString().split('T')[0];
    return {
      title: '',
      description: '',
      type: 'banner_creation',
      priority: 'moderate',
      due_date: dateStr,
      scheduled_date: dateStr,
      assigned_to: null,
      meta_data: {},
      notes: '',
      status: 'pending'
    };
  } catch (err) {
    console.error('Error creating empty schedule:', err);
    const now = new Date();
    const fallbackDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    return {
      title: '',
      description: '',
      type: 'banner_creation',
      priority: 'moderate',
      due_date: fallbackDate,
      scheduled_date: fallbackDate,
      assigned_to: null,
      meta_data: {},
      notes: '',
      status: 'pending'
    };
  }
};

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

// --- Keyboard handler (Escape closes modal) ---
const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    if (showCreateModal.value) closeModal();
    if (showDetailModal.value) showDetailModal.value = false;
  }
};

// --- Lifecycle ---
onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydown);
  await loadInitialData();
  const pollInterval = setInterval(fetchNotifications, 60000);

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
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
    let response;
    try {
      response = await axios.get('/api/manager/employees');
    } catch (err) {
      if (err.response?.status === 403 || err.response?.status === 404) {
        response = await axios.get('/api/admin/employees');
      } else {
        throw err;
      }
    }
    employees.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    employees.value = [];
  }
};

const fetchSchedules = async () => {
  try {
    const response = await axios.get('/api/schedules');
    let rawSchedules = [];
    if (response.data.schedules) rawSchedules = response.data.schedules;
    else if (response.data.data) rawSchedules = response.data.data;
    else if (Array.isArray(response.data)) rawSchedules = response.data;

    schedules.value = rawSchedules.map(schedule => ({
      ...schedule,
      type: schedule.type || schedule.schedule_type || 'other',
      schedule_type: schedule.type || schedule.schedule_type || 'other',
      due_date: validateDate(schedule.due_date) || validateDate(schedule.scheduled_date) || new Date().toISOString().split('T')[0],
      scheduled_date: validateDate(schedule.scheduled_date) || validateDate(schedule.due_date) || new Date().toISOString().split('T')[0],
      created_at: validateDate(schedule.created_at),
      updated_at: validateDate(schedule.updated_at),
      meta_data: schedule.meta_data || {},
      assigned_to: schedule.assigned_to || null
    }));
  } catch (err) {
    console.error('Failed to fetch schedules:', err);
    error.value = 'Failed to load schedules: ' + (err.response?.data?.message || err.message);
    schedules.value = [];
  }
};

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/api/notifications');
    const rawNotifications = response.data.notifications || response.data.data || [];
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
};

const saveSchedule = async (scheduleData) => {
  isSaving.value = true;
  error.value = null;
  try {
    if (!scheduleData.title || !scheduleData.due_date) {
      alert('Please fill in all required fields (Title and Due Date)');
      return;
    }
    const validatedDate = validateDate(scheduleData.due_date);
    if (!validatedDate) {
      alert('Invalid due date. Please select a valid date.');
      return;
    }
    const dataToSend = {
      title: scheduleData.title,
      description: scheduleData.description || '',
      type: scheduleData.type || scheduleData.schedule_type || 'other',
      priority: scheduleData.priority || 'moderate',
      due_date: validatedDate,
      scheduled_date: scheduleData.scheduled_date || validatedDate,
      assigned_to: scheduleData.assigned_to || null,
      meta_data: scheduleData.meta_data || {},
      notes: scheduleData.notes || '',
      status: scheduleData.status || 'pending'
    };

    let response;
    if (scheduleData.id) {
      response = await axios.put(`/api/schedules/${scheduleData.id}`, dataToSend);
    } else {
      response = await axios.post('/api/schedules', dataToSend);
    }

    await fetchSchedules();
    closeModal();
  } catch (err) {
    console.error('Failed to save schedule:', err);
    if (err.response?.data?.errors) {
      const errorMessages = Object.values(err.response.data.errors).flat().join('\n');
      alert('Validation failed:\n' + errorMessages);
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message;
      alert('Error: ' + err.response.data.message);
    } else {
      error.value = 'Failed to save schedule. Please try again.';
      alert('Failed to save. Please check your inputs and try again.');
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
    if (selectedSchedule.value?.id === scheduleId) showDetailModal.value = false;
  } catch (err) {
    console.error('Failed to complete schedule:', err);
    alert('Error completing schedule: ' + (err.response?.data?.message || err.message));
  }
};

const editSchedule = (schedule) => {
  const clonedSchedule = JSON.parse(JSON.stringify(schedule));
  clonedSchedule.type = clonedSchedule.type || clonedSchedule.schedule_type || 'other';
  clonedSchedule.schedule_type = clonedSchedule.type;
  editingSchedule.value = clonedSchedule;
  showCreateModal.value = true;
  showDetailModal.value = false;
};

const deleteSchedule = async (scheduleId) => {
  if (!confirm('Are you sure you want to delete this schedule? This action cannot be undone.')) return;
  try {
    await axios.delete(`/api/schedules/${scheduleId}`);
    await fetchSchedules();
    showDetailModal.value = false;
  } catch (err) {
    console.error('Failed to delete schedule:', err);
    alert('Error deleting schedule: ' + (err.response?.data?.message || err.message));
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
    alert('Failed to update banner region');
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
  if (!notif.is_read) await markAsRead(notif.id);
  if (notif.schedule_id) {
    const sched = schedules.value.find(s => s.id === notif.schedule_id);
    if (sched) openScheduleDetail(sched);
  }
  showNotifications.value = false;
};

const markAsRead = async (id) => {
  try {
    const idx = notifications.value.findIndex(n => n.id === id);
    if (idx !== -1) notifications.value[idx].is_read = true;
    await axios.post(`/api/notifications/${id}/read`);
  } catch (err) {
    console.error('Failed to mark as read:', err);
  }
};

const markAllRead = async () => {
  try {
    await axios.post('/api/notifications/read-all');
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
  if (!dateString || dateString === '') return 'Just now';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Recently';
    const now = new Date();
    const diffMinutes = Math.floor((now - date) / 60000);
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
    return date.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch (err) {
    console.error('Date formatting error:', err);
    return 'Recently';
  }
};
</script>