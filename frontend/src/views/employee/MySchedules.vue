<template>
  <div class="schedules-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <!-- Removed the blue accent line -->
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">My Schedules</h1>
            <p class="subtitle">View and manage your assigned schedules</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <!-- Notifications Bell -->
          <div class="notif-wrap" ref="notificationContainer">
            <button
              type="button"
              @click="toggleNotifications"
              class="btn-icon"
              :class="{ active: showNotifications }"
              title="Notifications"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount }}</span>
            </button>

            <transition name="dropdown">
              <div v-if="showNotifications" class="notif-dropdown">
                <div class="notif-header">
                  <span class="notif-title">Notifications</span>
                  <button type="button" v-if="unreadCount > 0" @click="markAllRead" class="btn-text-sm">Mark all read</button>
                </div>
                <div v-if="!notifications || notifications.length === 0" class="notif-empty">No new notifications</div>
                <div v-else>
                  <div
                    v-for="notif in notifications"
                    :key="notif.id"
                    @click="handleNotificationClick(notif)"
                    :class="['notif-item', !notif.is_read ? 'unread' : '']"
                  >
                    <div :class="['notif-type-dot', getNotificationColor(notif.notification_type)]"></div>
                    <div class="notif-body">
                      <p class="notif-msg">{{ getNotificationMessage(notif) }}</p>
                      <p class="notif-time">{{ formatDateTime(notif.created_at) }}</p>
                    </div>
                    <div v-if="!notif.is_read" class="unread-dot"></div>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <button @click="fetchSchedules" :disabled="loading" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="{ 'spin': loading }"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Metrics ──────────────────────────────── -->
      <div class="metrics-section" v-if="schedules.length > 0 && !loading">
        <h2>Summary</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V3"/></svg>
            </div>
            <div class="metric-value">{{ stats.total }}</div>
            <div class="metric-label">Total Assigned</div>
          </div>
          <div class="metric-card" style="--accent:#3b82f6;">
            <div class="metric-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="metric-value">{{ stats.inProgress }}</div>
            <div class="metric-label">In Progress</div>
          </div>
          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="metric-value">{{ stats.completed }}</div>
            <div class="metric-label">Completed</div>
          </div>
          <div class="metric-card" style="--accent:#ef4444;">
            <div class="metric-icon-wrap" style="background:rgba(239,68,68,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="metric-value">{{ stats.overdue }}</div>
            <div class="metric-label">Overdue</div>
          </div>
        </div>
      </div>

      <!-- ── Filters + List ───────────────────────── -->
      <div class="table-section">

        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>Task Type</label>
              <select v-model="filters.type" class="filter-select">
                <option value="">All Types</option>
                <option value="banner_creation">Banner Creation</option>
                <option value="weekly_overview">Weekly Overview</option>
                <option value="test_sequence">Test Sequence</option>
                <option value="live_games">Live Games</option>
                <option value="multibets">Multibets</option>
                <option value="news_section">News Section</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Status</label>
              <select v-model="filters.status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>
          </div>
          <div class="controls-right">
            <span class="records-count" v-if="!loading">{{ filteredSchedules.length }} task{{ filteredSchedules.length !== 1 ? 's' : '' }}</span>
            <button v-if="filters.type || filters.status" @click="resetFilters" class="btn-clear">Clear Filters</button>

            <!-- View Toggle -->
            <div class="view-toggle">
              <button
                type="button"
                :class="['toggle-btn', viewMode === 'grid' ? 'active' : '']"
                @click="viewMode = 'grid'"
                title="Grid View"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Grid
              </button>
              <button
                type="button"
                :class="['toggle-btn', viewMode === 'table' ? 'active' : '']"
                @click="viewMode = 'table'"
                title="Table View"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                Table
              </button>
            </div>
          </div>
        </div>

        <!-- Error Banner -->
        <div v-if="error" class="error-banner">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
          <span>{{ error }}</span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading schedules...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredSchedules.length === 0" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          </div>
          <h3>No Schedules Found</h3>
          <p>{{ (filters.type || filters.status) ? 'Try adjusting your filters.' : 'You have no schedules assigned at the moment.' }}</p>
          <div v-if="filters.type || filters.status" class="empty-actions">
            <button @click="resetFilters" class="btn-secondary">Clear Filters</button>
          </div>
        </div>

        <!-- ── GRID VIEW ── -->
        <div v-else-if="viewMode === 'grid'" class="schedules-grid">
          <div
            v-for="schedule in filteredSchedules"
            :key="schedule.id"
            class="schedule-card"
            @click="showScheduleDetails(schedule)"
          >
            <div class="card-body">
              <!-- Card Header -->
              <div class="card-header-row">
                <div class="card-title-block">
                  <h3 class="card-title">{{ schedule.title }}</h3>
                  <span class="card-type-label">{{ getTypeName(schedule.schedule_type) }}</span>
                </div>
                <span :class="['priority-badge', 'priority-badge-' + schedule.priority]">
                  {{ schedule.priority?.toUpperCase() }}
                </span>
              </div>

              <!-- Description -->
              <p v-if="schedule.description" class="card-description">{{ schedule.description }}</p>

              <!-- Status Row -->
              <div class="status-row">
                <span :class="['status-badge', getStatusClass(schedule.status)]">
                  <span class="dot"></span>{{ formatStatus(schedule.status) }}
                </span>
                <span v-if="schedule.has_report" class="status-badge info">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                  Report Submitted
                </span>
              </div>

              <!-- Dates -->
              <div class="dates-row">
                <div class="date-item">
                  <span class="date-label">Start</span>
                  <span class="date-value">{{ formatDate(schedule.scheduled_date) }}</span>
                </div>
                <div class="date-item">
                  <span class="date-label">Due</span>
                  <span :class="['date-value', isOverdue(schedule) ? 'overdue' : '']">{{ formatDate(schedule.due_date) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="card-actions" @click.stop>
                <button
                  v-if="schedule.status === 'pending'"
                  @click="updateStatus(schedule.id, 'in_progress')"
                  class="action-pill action-blue"
                >Start</button>
                <button
                  v-if="schedule.status === 'in_progress'"
                  @click="updateStatus(schedule.id, 'completed')"
                  class="action-pill action-green"
                >Complete</button>
                <button
                  v-if="schedule.status === 'completed' && !schedule.has_report"
                  @click="openReportModal(schedule)"
                  class="action-pill action-purple"
                >Submit Report</button>
                <button
                  @click="showScheduleDetails(schedule)"
                  class="action-pill action-neutral"
                >Details</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── TABLE VIEW ── -->
        <div v-else class="table-wrapper">
          <table class="schedules-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Start</th>
                <th>Due</th>
                <th>Report</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="schedule in filteredSchedules"
                :key="schedule.id"
                class="table-row"
                @click="showScheduleDetails(schedule)"
              >
                <td class="col-title">
                  <span class="table-title">{{ schedule.title }}</span>
                </td>
                <td class="col-type">
                  <span class="table-type">{{ getTypeName(schedule.schedule_type) }}</span>
                </td>
                <td class="col-priority">
                  <span :class="['priority-badge', 'priority-badge-' + schedule.priority]">
                    {{ schedule.priority?.toUpperCase() }}
                  </span>
                </td>
                <td class="col-status">
                  <span :class="['status-badge', getStatusClass(schedule.status)]">
                    <span class="dot"></span>{{ formatStatus(schedule.status) }}
                  </span>
                </td>
                <td class="col-date">{{ formatDate(schedule.scheduled_date) }}</td>
                <td class="col-date">
                  <span :class="isOverdue(schedule) ? 'text-danger' : ''">{{ formatDate(schedule.due_date) }}</span>
                </td>
                <td class="col-report">
                  <span v-if="schedule.has_report" class="status-badge info" style="font-size:0.68rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    Yes
                  </span>
                  <span v-else class="status-badge neutral" style="font-size:0.68rem;">—</span>
                </td>
                <td class="col-actions" @click.stop>
                  <div class="table-actions">
                    <button
                      v-if="schedule.status === 'pending'"
                      @click="updateStatus(schedule.id, 'in_progress')"
                      class="action-pill action-blue"
                    >Start</button>
                    <button
                      v-if="schedule.status === 'in_progress'"
                      @click="updateStatus(schedule.id, 'completed')"
                      class="action-pill action-green"
                    >Complete</button>
                    <button
                      v-if="schedule.status === 'completed' && !schedule.has_report"
                      @click="openReportModal(schedule)"
                      class="action-pill action-purple"
                    >Report</button>
                    <button
                      @click="showScheduleDetails(schedule)"
                      class="action-pill action-neutral"
                    >Details</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- ── Details Modal ──────────────────────────── -->
    <transition name="fade">
      <div
        v-if="selectedSchedule"
        class="modal-overlay"
        @click.self="selectedSchedule = null"
      >
        <transition name="modal-slide">
          <div v-if="selectedSchedule" class="modal-panel" @click.stop>
            <div class="modal-header">
              <h2>Schedule Details</h2>
              <button type="button" @click="selectedSchedule = null" class="btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>

            <div class="modal-body">
              <h3 class="modal-title">{{ selectedSchedule.title }}</h3>
              <div class="modal-badges">
                <span :class="['priority-badge', 'priority-badge-' + selectedSchedule.priority]">{{ selectedSchedule.priority?.toUpperCase() }}</span>
                <span :class="['status-badge', getStatusClass(selectedSchedule.status)]">
                  <span class="dot"></span>{{ formatStatus(selectedSchedule.status) }}
                </span>
                <span class="status-badge info">{{ getTypeName(selectedSchedule.schedule_type) }}</span>
                <span v-if="selectedSchedule.has_report" class="status-badge neutral">Report Submitted</span>
              </div>

              <div v-if="selectedSchedule.description" class="detail-block">
                <label>Description</label>
                <p class="detail-text">{{ selectedSchedule.description }}</p>
              </div>

              <div class="detail-grid">
                <div class="detail-block">
                  <label>Start Date</label>
                  <p class="detail-text">{{ formatDate(selectedSchedule.scheduled_date) }}</p>
                </div>
                <div class="detail-block">
                  <label>Due Date</label>
                  <p :class="['detail-text', isOverdue(selectedSchedule) ? 'text-danger' : '']">{{ formatDate(selectedSchedule.due_date) }}</p>
                </div>
              </div>

              <div v-if="selectedSchedule.notes" class="detail-block">
                <label>Notes</label>
                <p class="detail-text detail-notes">{{ selectedSchedule.notes }}</p>
              </div>

              <div v-if="selectedSchedule.meta_data && Object.keys(selectedSchedule.meta_data).length > 0" class="detail-block">
                <label>Additional Information</label>
                <pre class="detail-code">{{ JSON.stringify(selectedSchedule.meta_data, null, 2) }}</pre>
              </div>
            </div>

            <div class="modal-footer">
              <button @click="selectedSchedule = null" class="btn-secondary">Close</button>
              <button
                v-if="selectedSchedule.status === 'pending'"
                @click="updateStatus(selectedSchedule.id, 'in_progress'); selectedSchedule = null"
                class="btn-blue"
              >Start Task</button>
              <button
                v-if="selectedSchedule.status === 'in_progress'"
                @click="updateStatus(selectedSchedule.id, 'completed'); selectedSchedule = null"
                class="btn-green"
              >Mark Complete</button>
              <button
                v-if="selectedSchedule.status === 'completed' && !selectedSchedule.has_report"
                @click="openReportModal(selectedSchedule); selectedSchedule = null"
                class="btn-purple"
              >Submit Report</button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

    <!-- Submit Report Modal -->
    <SubmitReportModal
      :show="showReportModal"
      :schedule="selectedScheduleForReport"
      @close="showReportModal = false"
      @submitted="handleReportSubmitted"
    />

    <audio ref="notificationSound" preload="auto">
      <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTcIGWi77eeeTRAMUKbj8LdjHAU7k9nyyXkqBSl+zPLaizsKGGS57OahUBELTKXh77BYGwcugs/y24k3CBlou+3nnk0QDFCm4/C3YxwFO5PZ8sl5KgUpfszy2os7ChhluezmAA" type="audio/wav">
    </audio>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import SubmitReportModal from '@/components/reports/Reportmodel.vue';

const schedules = ref([]);
const notifications = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedSchedule = ref(null);
const showNotifications = ref(false);
const notificationContainer = ref(null);
const notificationSound = ref(null);
const lastNotificationCount = ref(0);
const showReportModal = ref(false);
const selectedScheduleForReport = ref(null);
const viewMode = ref('grid'); // 'grid' | 'table'

const filters = ref({ type: '', status: '' });

const unreadCount = computed(() => (notifications.value || []).filter(n => !n.is_read).length);

const stats = computed(() => ({
  total: schedules.value.length,
  inProgress: schedules.value.filter(s => s.status === 'in_progress').length,
  completed: schedules.value.filter(s => s.status === 'completed').length,
  overdue: schedules.value.filter(s => s.status !== 'completed' && s.due_date && new Date(s.due_date) < new Date()).length
}));

const filteredSchedules = computed(() =>
  schedules.value.filter(s => {
    if (filters.value.type && s.schedule_type !== filters.value.type) return false;
    if (filters.value.status && s.status !== filters.value.status) return false;
    return true;
  })
);

const fetchSchedules = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api/employee/schedules');
    schedules.value = response.data.schedules || response.data.data || response.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load schedules. Please try again.';
    schedules.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/api/notifications');
    const raw = (response.data.notifications || []).map(n => ({ ...n, created_at: n.created_at || new Date().toISOString() }));
    const newUnread = raw.filter(n => !n.is_read).length;
    if (newUnread > lastNotificationCount.value && lastNotificationCount.value > 0) {
      try { notificationSound.value?.play().catch(() => {}); } catch {}
    }
    lastNotificationCount.value = newUnread;
    notifications.value = raw;
  } catch {}
};

const toggleNotifications = () => { showNotifications.value = !showNotifications.value; };

const handleNotificationClick = async (notif) => {
  if (!notif.is_read) await markAsRead(notif.id);
  if (notif.schedule_id) { const s = schedules.value.find(s => s.id === notif.schedule_id); if (s) showScheduleDetails(s); }
  showNotifications.value = false;
};

const markAsRead = async (id) => {
  try { const i = notifications.value.findIndex(n => n.id === id); if (i !== -1) notifications.value[i].is_read = true; await axios.put(`/api/notifications/${id}/read`); } catch {}
};

const markAllRead = async () => {
  try { await axios.put('/api/notifications/read-all'); notifications.value.forEach(n => n.is_read = true); } catch {}
};

const handleClickOutside = (e) => {
  if (showNotifications.value && notificationContainer.value && !notificationContainer.value.contains(e.target)) showNotifications.value = false;
};

const updateStatus = async (scheduleId, newStatus) => {
  try {
    await axios.put(`/api/schedules/${scheduleId}`, { status: newStatus });
    const s = schedules.value.find(s => s.id === scheduleId);
    if (s) { s.status = newStatus; if (newStatus === 'completed') s.completed_at = new Date().toISOString(); }
  } catch { alert('Failed to update status. Please try again.'); }
};

const showScheduleDetails = (schedule) => { selectedSchedule.value = schedule; };
const openReportModal = (schedule) => { selectedScheduleForReport.value = schedule; showReportModal.value = true; };
const handleReportSubmitted = async (report) => {
  const s = schedules.value.find(s => s.id === report.schedule_id);
  if (s) s.has_report = true;
  await fetchSchedules();
};

const resetFilters = () => { filters.value = { type: '', status: '' }; };
const isOverdue = (s) => s.status !== 'completed' && s.due_date && new Date(s.due_date) < new Date();

const formatDate = (d) => {
  if (!d) return 'N/A';
  try { const date = new Date(d); return isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  catch { return 'N/A'; }
};

const formatStatus = (s) => s ? s.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : 'Unknown';
const getTypeName = (t) => ({ banner_creation: 'Banner Creation', weekly_overview: 'Weekly Overview', test_sequence: 'Test Sequence', live_games: 'Live Games', multibets: 'Multibets', news_section: 'News Section' }[t] || t);

const getNotificationColor = (type) => ({ assigned: 'notif-blue', reminder: 'notif-yellow', overdue: 'notif-red', completed: 'notif-green' }[type] || 'notif-gray');

const getNotificationMessage = (notif) => {
  if (!notif) return 'New notification';
  const title = notif.schedule?.title || 'Unknown Task';
  return ({ assigned: `Assigned: ${title}`, reminder: `Reminder: ${title} is due soon`, overdue: `Overdue: ${title}`, completed: `Completed: ${title}` }[notif.notification_type]) || notif.message || 'New notification';
};

const formatDateTime = (d) => {
  if (!d) return 'Just now';
  try {
    const date = new Date(d);
    if (isNaN(date.getTime())) return 'Recently';
    const diff = Math.floor((new Date() - date) / 60000);
    if (diff < 1) return 'Just now';
    if (diff < 60) return `${diff}m ago`;
    if (diff < 1440) return `${Math.floor(diff / 60)}h ago`;
    return date.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch { return 'Recently'; }
};

const getStatusClass = (s) => ({ pending: 'warning', in_progress: 'info', completed: 'success', overdue: 'danger' }[s] || 'neutral');

onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  await fetchSchedules();
  await fetchNotifications();
  lastNotificationCount.value = unreadCount.value;
  if ('Notification' in window && Notification.permission === 'default') await Notification.requestPermission();
  const poll = setInterval(fetchNotifications, 30000);
  onUnmounted(() => { document.removeEventListener('click', handleClickOutside); clearInterval(poll); });
});
</script>

<style scoped>
/* =========================================
   BASE - Clean White Background
   ========================================= */
.schedules-page {
  min-height: 100vh;
  background: #ffffff;  /* Pure white background */
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* =========================================
   HEADER CARD - No accent line
   ========================================= */
.dashboard-header-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
  border: 1px solid #f1f5f9;
  flex-shrink: 0;
}
.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.2); flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }
.role-badge {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #334155; display: inline-block;
}
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center; }

/* =========================================
   BUTTONS
   ========================================= */
.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: #ffffff; border: 1px solid #e2e8f0;
  color: #334155; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }
.btn-outline:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-icon {
  position: relative; width: 36px; height: 36px; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #ffffff; color: #475569;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-icon:hover, .btn-icon.active { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }

.btn-secondary {
  padding: 0.45rem 1rem; background: #ffffff; color: #334155;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-blue   { padding: 0.45rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.15s; }
.btn-blue:hover { background: #2563eb; }
.btn-green  { padding: 0.45rem 1rem; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.15s; }
.btn-green:hover { background: #059669; }
.btn-purple { padding: 0.45rem 1rem; background: #8b5cf6; color: white; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.15s; }
.btn-purple:hover { background: #7c3aed; }

.btn-text-sm { background: none; border: none; color: #6366f1; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-text-sm:hover { color: #4f46e5; text-decoration: underline; }

.btn-clear { padding: 0.25rem 0.75rem; background: none; border: none; color: #6366f1; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-clear:hover { color: #4f46e5; text-decoration: underline; }

/* =========================================
   VIEW TOGGLE
   ========================================= */
.view-toggle {
  display: flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; flex-shrink: 0;
  background: #ffffff;
}
.toggle-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; border: none; background: transparent;
  color: #64748b; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.toggle-btn:first-child { border-right: 1px solid #e2e8f0; }
.toggle-btn.active { background: #f1f5f9; color: #0f172a; }
.toggle-btn:not(.active):hover { background: #f8fafc; color: #334155; }

/* =========================================
   NOTIFICATIONS
   ========================================= */
.notif-wrap { position: relative; }
.notif-badge {
  position: absolute; top: -4px; right: -4px;
  min-width: 18px; height: 18px; border-radius: 9999px;
  background: #dc2626; color: white;
  font-size: 0.65rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px; border: 2px solid white;
}
.notif-dropdown {
  position: absolute; right: 0; top: calc(100% + 8px);
  width: 320px; background: #ffffff; border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9; max-height: 380px; overflow-y: auto; z-index: 100;
}
.notif-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.875rem 1rem; border-bottom: 1px solid #f1f5f9;
  position: sticky; top: 0; background: #ffffff; z-index: 10;
}
.notif-title { font-size: 0.875rem; font-weight: 700; color: #0f172a; }
.notif-empty { padding: 2rem 1rem; text-align: center; color: #94a3b8; font-size: 0.82rem; }
.notif-item {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.875rem 1rem; border-bottom: 1px solid #f8fafc;
  cursor: pointer; transition: background 0.15s;
}
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #f0f9ff; }
.notif-type-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.notif-blue   { background: #3b82f6; }
.notif-yellow { background: #f59e0b; }
.notif-red    { background: #ef4444; }
.notif-green  { background: #10b981; }
.notif-gray   { background: #94a3b8; }
.notif-body { flex: 1; min-width: 0; }
.notif-msg  { margin: 0; font-size: 0.82rem; font-weight: 600; color: #0f172a; }
.notif-time { margin: 0.2rem 0 0; font-size: 0.72rem; color: #94a3b8; }
.unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #3b82f6; flex-shrink: 0; margin-top: 4px; }

/* Dropdown transition */
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: scale(0.97) translateY(-4px); }

/* =========================================
   DASHBOARD CONTENT
   ========================================= */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* =========================================
   METRICS SECTION
   ========================================= */
.metrics-section, .table-section {
  background: #ffffff; border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
  border: 1px solid #f1f5f9; padding: 1.5rem;
}
h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
.metric-card {
  padding: 1.25rem; background: #ffffff; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #f1f5f9; position: relative; overflow: hidden;
  transition: all 0.2s ease;
}
.metric-card:hover { 
  border-color: var(--accent); 
  box-shadow: 0 8px 12px -4px rgba(0, 0, 0, 0.04);
}
.metric-icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* =========================================
   CONTROLS
   ========================================= */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #ffffff; color: #1e293b;
  font-size: 0.82rem; font-weight: 500; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
  transition: all 0.2s; font-family: inherit;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.controls-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.records-count {
  font-size: 0.78rem; font-weight: 700; color: #475569;
  background: #f8fafc; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}

/* =========================================
   ERROR BANNER
   ========================================= */
.error-banner {
  display: flex; align-items: center; gap: 0.625rem;
  background: #fee2e2; border: 1px solid #fecaca; border-radius: 10px;
  padding: 0.875rem 1rem; color: #991b1b; font-size: 0.875rem; font-weight: 600;
  margin-bottom: 1rem;
}

/* =========================================
   STATES
   ========================================= */
.spinner {
  width: 40px; height: 40px; border: 3px solid #f1f5f9; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #ffffff;
  border: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; max-width: 320px; }
.empty-actions { display: flex; gap: 0.75rem; margin-top: 0.25rem; }

/* =========================================
   GRID VIEW
   ========================================= */
.schedules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }

.schedule-card {
  border: 1px solid #f1f5f9; border-radius: 12px; background: #ffffff;
  position: relative; overflow: hidden; cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.01);
}
.schedule-card:hover { 
  border-color: #e2e8f0; 
  box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.04);
  transform: translateY(-1px); 
}

.card-body { flex: 1; padding: 1.125rem; display: flex; flex-direction: column; gap: 0.75rem; }

.card-header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; }
.card-title-block { flex: 1; min-width: 0; }
.card-title { margin: 0; font-size: 0.9rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-type-label { font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin-top: 0.15rem; display: block; }

/* Priority badges */
.priority-badge { padding: 0.15rem 0.55rem; border-radius: 9999px; font-size: 0.68rem; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.priority-badge-low      { background: #f8fafc; color: #475569; }
.priority-badge-moderate { background: #e0f2fe; color: #0369a1; }
.priority-badge-high     { background: #fff3cd; color: #856404; }
.priority-badge-urgent   { background: #fee2e2; color: #b91c1c; }

.card-description {
  margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.5;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  line-clamp: 2;
}

/* Status Row */
.status-row { display: flex; flex-wrap: wrap; gap: 0.4rem; }

/* Status Badges */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.2rem 0.6rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.status-badge.success { background: #dcfce7; color: #166534; }
.status-badge.warning { background: #fef9c3; color: #854d0e; }
.status-badge.danger  { background: #fee2e2; color: #b91c1c; }
.status-badge.info    { background: #dbeafe; color: #1e40af; }
.status-badge.neutral { background: #f1f5f9; color: #475569; }

/* Dates */
.dates-row { display: flex; gap: 1.25rem; }
.date-item { display: flex; flex-direction: column; gap: 0.1rem; }
.date-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.date-value { font-size: 0.8rem; font-weight: 600; color: #334155; }
.date-value.overdue { color: #dc2626; }
.text-danger { color: #dc2626; font-weight: 600; }

/* Card Actions */
.card-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: auto; }
.action-pill {
  padding: 0.3rem 0.75rem; border-radius: 6px; border: none;
  font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.action-blue    { background: #3b82f6; color: white; }
.action-blue:hover   { background: #2563eb; }
.action-green   { background: #10b981; color: white; }
.action-green:hover  { background: #059669; }
.action-purple  { background: #8b5cf6; color: white; }
.action-purple:hover { background: #7c3aed; }
.action-neutral { background: #f1f5f9; color: #334155; }
.action-neutral:hover { background: #e2e8f0; }

/* =========================================
   TABLE VIEW - Clean White Background
   ========================================= */
.table-wrapper { 
  overflow-x: auto; 
  border-radius: 12px; 
  border: 1px solid #f1f5f9;
  background: #ffffff;
}
.schedules-table { 
  width: 100%; 
  border-collapse: collapse; 
  font-size: 0.82rem; 
  background: #ffffff;
}
.schedules-table thead tr {
  background: #f8fafc; 
  border-bottom: 1px solid #f1f5f9;
}
.schedules-table th {
  padding: 0.75rem 1rem; text-align: left;
  font-size: 0.7rem; font-weight: 700; color: #475569;
  text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.schedules-table tbody tr {
  border-bottom: 1px solid #f8fafc; 
  transition: background 0.12s; 
  cursor: pointer;
}
.schedules-table tbody tr:last-child { border-bottom: none; }
.schedules-table tbody tr:hover { background: #fafbfc; }
.schedules-table td { 
  padding: 0.875rem 1rem; 
  vertical-align: middle; 
  color: #1e293b;
}
.col-title .table-title { font-weight: 600; color: #0f172a; }
.col-type .table-type { color: #64748b; font-size: 0.78rem; }
.col-date { color: #475569; white-space: nowrap; }
.col-actions .table-actions { display: flex; gap: 0.35rem; flex-wrap: nowrap; }

/* =========================================
   MODAL
   ========================================= */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.3); z-index: 50;
  display: flex; align-items: center; justify-content: center; padding: 1rem;
  backdrop-filter: blur(4px);
}
.modal-panel {
  background: #ffffff; border-radius: 16px; max-width: 560px; width: 100%;
  max-height: 90vh; overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
  display: flex; flex-direction: column;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
  position: sticky; top: 0; background: #ffffff; z-index: 10;
}
.modal-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; flex: 1; }
.modal-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.modal-badges { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
.detail-block { display: flex; flex-direction: column; gap: 0.3rem; }
.detail-block label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.detail-text { margin: 0; font-size: 0.875rem; color: #1e293b; background: #f8fafc; padding: 0.75rem 1rem; border-radius: 8px; line-height: 1.6; }
.detail-notes { border-left: 3px solid #f59e0b; background: #fffbeb; }
.detail-code { margin: 0; font-size: 0.78rem; color: #334155; background: #f8fafc; padding: 0.875rem 1rem; border-radius: 8px; overflow-x: auto; font-family: monospace; line-height: 1.6; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.5rem;
  padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9;
  position: sticky; bottom: 0; background: #f8fafc; border-radius: 0 0 16px 16px;
}

/* =========================================
   TRANSITIONS
   ========================================= */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-slide-enter-active, .modal-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-slide-enter-from, .modal-slide-leave-to { opacity: 0; transform: scale(0.96) translateY(8px); }

/* =========================================
   RESPONSIVE
   ========================================= */
@media (max-width: 768px) {
  .schedules-page { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; flex-wrap: wrap; }
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .controls-bar { flex-direction: column; align-items: flex-start; }
  .schedules-grid { grid-template-columns: 1fr; }
  .notif-dropdown { right: auto; left: 0; width: 290px; }
  .detail-grid { grid-template-columns: 1fr; }
  .controls-right { flex-wrap: wrap; }
}
@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr; }
  .schedules-page { padding: 0.75rem; }
  .card-actions { flex-wrap: wrap; }
  .action-pill { flex: 1; text-align: center; }
}
</style>