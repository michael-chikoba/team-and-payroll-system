<template>
  <div class="shifts-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">My Shifts</h1>
            <p class="subtitle">View and manage your assigned shifts</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <button @click="loadTodayShift(); loadUpcomingShifts()" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Toast Messages ─────────────────────────── -->
      <transition name="toast">
        <div v-if="successMessage" class="toast-banner success">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span>{{ successMessage }}</span>
          <button @click="successMessage = ''" class="toast-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
      </transition>
      <transition name="toast">
        <div v-if="errorMessage" class="toast-banner error">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          <span>{{ errorMessage }}</span>
          <button @click="errorMessage = ''" class="toast-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
      </transition>

      <!-- ── Today's Shift ───────────────────────────── -->
      <div v-if="todayShift" class="today-card">
        <div class="today-card-accent"></div>
        <div class="today-inner">
          <div class="today-left">
            <p class="today-eyebrow">Today's Shift</p>
            <h2 class="today-title">{{ capitalize(todayShift.shift_type) }} Shift</h2>

            <!-- Times -->
            <div class="today-times">
              <div class="time-block">
                <span class="time-label">Start Time</span>
                <span class="time-value">{{ formatTime(todayShift.start_time) }}</span>
              </div>
              <div class="time-divider">→</div>
              <div class="time-block">
                <span class="time-label">End Time</span>
                <span class="time-value">{{ formatTime(todayShift.end_time) }}</span>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="todayShift.notes" class="today-notes">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              {{ todayShift.notes }}
            </div>

            <!-- Status & Action Badges -->
            <div class="today-actions-row">
              <span v-if="todayShift.status === 'pending'" class="status-badge warning"><span class="dot"></span>Pending Acceptance</span>
              <span v-else-if="todayShift.status === 'accepted'" class="status-badge success"><span class="dot"></span>Accepted</span>

              <span v-if="attendanceStatus?.status === 'present'" class="status-badge info">
                <span class="dot"></span>Checked In · {{ attendanceStatus.attendance?.clock_in }}
              </span>
              <span v-else-if="attendanceStatus?.status === 'completed'" class="status-badge neutral">
                <span class="dot"></span>Shift Completed
              </span>

              <button
                v-if="todayShift.status === 'accepted' && canCheckIn"
                @click="handleCheckIn"
                :disabled="checkingIn"
                class="btn-checkin"
              >
                <svg v-if="checkingIn" class="spin-icon" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path></svg>
                {{ checkingIn ? 'Checking In…' : 'Check In Now' }}
              </button>

              <button
                v-if="todayShift.status === 'accepted' && canCheckOut"
                @click="handleCheckOut"
                :disabled="checkingOut"
                class="btn-checkout"
              >
                <svg v-if="checkingOut" class="spin-icon" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path></svg>
                {{ checkingOut ? 'Checking Out…' : 'Check Out' }}
              </button>
            </div>

            <!-- Attendance Detail -->
            <div v-if="attendanceStatus?.attendance" class="attendance-grid">
              <div class="att-item">
                <span class="att-label">Clock In</span>
                <span class="att-value">{{ attendanceStatus.attendance.clock_in || '—' }}</span>
              </div>
              <div class="att-item">
                <span class="att-label">Clock Out</span>
                <span class="att-value">{{ attendanceStatus.attendance.clock_out || 'Working…' }}</span>
              </div>
              <div class="att-item">
                <span class="att-label">Total Hours</span>
                <span class="att-value highlight">{{ formatHours(attendanceStatus.attendance.total_hours) }}</span>
              </div>
            </div>
          </div>

          <!-- Accept / Reject (pending only) -->
          <div v-if="todayShift.status === 'pending'" class="today-decision">
            <button @click="acceptShift(todayShift.id)" class="btn-accept">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Accept
            </button>
            <button @click="showRejectModal(todayShift)" class="btn-reject">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              Reject
            </button>
          </div>
        </div>
      </div>

      <!-- No Today Shift -->
      <div v-else class="table-section">
        <div class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <h3>No Shift Today</h3>
          <p>You have no shift assigned for today.</p>
        </div>
      </div>

      <!-- ── Upcoming Shifts ─────────────────────────── -->
      <div class="table-section">
        <div class="section-header">
          <h2>Upcoming Shifts</h2>
          <span class="records-count" v-if="!loading">{{ upcomingShifts.length }} shift{{ upcomingShifts.length !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading shifts…</p>
        </div>

        <div v-else-if="upcomingShifts.length === 0" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          </div>
          <h3>No Upcoming Shifts</h3>
          <p>You have no upcoming shifts scheduled.</p>
        </div>

        <div v-else class="assignments-list">
          <div
            v-for="shift in upcomingShifts"
            :key="shift.id"
            class="assignment-card"
          >
            <!-- Shift type bar -->
            <div class="shift-type-bar" :class="'type-' + shift.shift_type"></div>

            <div class="card-left">
              <!-- Shift Info Grid -->
              <div class="shift-info-grid">
                <div class="shift-info-item">
                  <span class="info-label">Date</span>
                  <span class="info-value">{{ formatDate(shift.shift_date) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Type</span>
                  <span class="info-value">
                    <span class="type-dot" :class="'type-' + shift.shift_type"></span>
                    {{ capitalize(shift.shift_type) }}
                  </span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Time</span>
                  <span class="info-value">{{ formatTime(shift.start_time) }} – {{ formatTime(shift.end_time) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Duration</span>
                  <span class="info-value">{{ calcDuration(shift.start_time, shift.end_time) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Department</span>
                  <span class="info-value">{{ shift.department?.name || '—' }}</span>
                </div>
                <div class="shift-info-item" v-if="shift.notes">
                  <span class="info-label">Notes</span>
                  <span class="info-value note-value">{{ shift.notes }}</span>
                </div>
              </div>
            </div>

            <div class="card-right">
              <span :class="['status-badge', getStatusClass(shift.status)]">
                <span class="dot"></span>{{ capitalize(shift.status) }}
              </span>
              <div v-if="shift.status === 'pending'" class="card-actions">
                <button @click="acceptShift(shift.id)" class="action-btn accept" title="Accept Shift">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
                <button @click="showRejectModal(shift)" class="action-btn danger" title="Reject Shift">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Past Shifts (Collapsible) ──────────────── -->
      <div class="table-section">
        <button @click="showPastShifts = !showPastShifts" class="collapsible-header">
          <h2>Past Shifts</h2>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="['chevron', { open: showPastShifts }]">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <transition name="collapse">
          <div v-if="showPastShifts" class="empty-state" style="padding: 2rem;">
            <p style="color:#94a3b8; font-size:0.875rem;">Past shifts feature coming soon.</p>
          </div>
        </transition>
      </div>

    </div>

    <!-- ── Reject Modal ────────────────────────────── -->
    <transition name="fade">
      <div v-if="showRejectDialogue" class="modal-overlay" @click.self="closeRejectModal">
        <transition name="modal-slide">
          <div v-if="showRejectDialogue" class="modal-panel" @click.stop>
            <div class="modal-header">
              <h2>Reject Shift</h2>
              <button type="button" @click="closeRejectModal" class="btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
            <div class="modal-body">
              <p class="modal-desc">Please provide a reason for rejecting this shift:</p>
              <textarea
                v-model="rejectionReason"
                rows="4"
                class="modal-textarea"
                placeholder="Enter your reason…"
              ></textarea>
            </div>
            <div class="modal-footer">
              <button @click="closeRejectModal" class="btn-secondary">Cancel</button>
              <button @click="submitRejection" :disabled="!rejectionReason" class="btn-danger">
                Reject Shift
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { format } from 'date-fns';

const todayShift = ref(null);
const upcomingShifts = ref([]);
const attendanceStatus = ref(null);
const loading = ref(false);
const checkingIn = ref(false);
const checkingOut = ref(false);
const showPastShifts = ref(false);
const showRejectDialogue = ref(false);
const rejectionReason = ref('');
const selectedShiftForRejection = ref(null);
const successMessage = ref('');
const errorMessage = ref('');

const canCheckIn = computed(() => !attendanceStatus.value || attendanceStatus.value.status === 'absent');
const canCheckOut = computed(() => attendanceStatus.value?.status === 'present' && attendanceStatus.value.attendance?.clock_in);

const getToken = () => localStorage.getItem('token');

const showSuccess = (msg) => { successMessage.value = msg; setTimeout(() => successMessage.value = '', 5000); };
const showError   = (msg) => { errorMessage.value   = msg; };

const loadTodayShift = async () => {
  try {
    const res = await fetch('/api/shift-assignments/today', { headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` } });
    const data = await res.json();
    todayShift.value = data.assignment;
    if (todayShift.value) await loadAttendanceStatus();
  } catch { showError("Failed to load today's shift"); }
};

const loadAttendanceStatus = async () => {
  try {
    const res = await fetch('/api/employee/attendance/today-status', { headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` } });
    attendanceStatus.value = await res.json();
  } catch {}
};

const loadUpcomingShifts = async () => {
  loading.value = true;
  try {
    const res = await fetch('/api/shift-assignments/my-shifts', { headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` } });
    const data = await res.json();
    const today = new Date().toDateString();
    upcomingShifts.value = (data.assignments || []).filter(s => new Date(s.shift_date).toDateString() !== today);
  } catch { showError('Failed to load upcoming shifts'); }
  finally { loading.value = false; }
};

const handleCheckIn = async () => {
  checkingIn.value = true; errorMessage.value = '';
  try {
    const res = await fetch('/api/employee/attendance/clock-in', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` } });
    const data = await res.json();
    if (res.ok && data.success) { showSuccess(data.message || 'Successfully checked in!'); await loadAttendanceStatus(); }
    else showError(data.message || 'Failed to check in');
  } catch { showError('An error occurred while checking in'); }
  finally { checkingIn.value = false; }
};

const handleCheckOut = async () => {
  checkingOut.value = true; errorMessage.value = '';
  try {
    const res = await fetch('/api/employee/attendance/clock-out', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` } });
    const data = await res.json();
    if (res.ok && data.success) { showSuccess(data.message || 'Successfully checked out!'); await loadAttendanceStatus(); }
    else showError(data.message || 'Failed to check out');
  } catch { showError('An error occurred while checking out'); }
  finally { checkingOut.value = false; }
};

const acceptShift = async (id) => {
  try {
    const res = await fetch(`/api/shift-assignments/${id}/accept`, { method: 'POST', headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` } });
    if (res.ok) { showSuccess('Shift accepted successfully!'); loadTodayShift(); loadUpcomingShifts(); }
  } catch { showError('Failed to accept shift'); }
};

const showRejectModal  = (shift) => { selectedShiftForRejection.value = shift; rejectionReason.value = ''; showRejectDialogue.value = true; };
const closeRejectModal = () => { showRejectDialogue.value = false; selectedShiftForRejection.value = null; rejectionReason.value = ''; };

const submitRejection = async () => {
  if (!rejectionReason.value || !selectedShiftForRejection.value) return;
  try {
    const res = await fetch(`/api/shift-assignments/${selectedShiftForRejection.value.id}/reject`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ reason: rejectionReason.value })
    });
    if (res.ok) { showSuccess('Shift rejected successfully'); closeRejectModal(); loadTodayShift(); loadUpcomingShifts(); }
  } catch { showError('Failed to reject shift'); }
};

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

const formatDate = (d) => {
  try { return format(new Date(d), 'EEE, MMM dd, yyyy'); } catch { return d; }
};

const formatTime = (t) => {
  if (!t) return '';
  try {
    const [h, m] = t.split(':');
    const hour = parseInt(h);
    return `${hour % 12 || 12}:${m.padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`;
  } catch { return t; }
};

const calcDuration = (start, end) => {
  if (!start || !end) return '';
  try {
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    let diff = (eh * 60 + em) - (sh * 60 + sm);
    if (diff < 0) diff += 1440;
    return `${Math.floor(diff / 60)}h ${diff % 60}m`;
  } catch { return ''; }
};

const formatHours = (h) => h ? `${Number(h).toFixed(2)} hrs` : '0.00 hrs';

const getStatusClass = (s) => ({ pending: 'warning', accepted: 'success', rejected: 'danger', completed: 'info' }[s] || 'neutral');

onMounted(() => {
  loadTodayShift();
  loadUpcomingShifts();
  setInterval(() => { if (todayShift.value) loadAttendanceStatus(); }, 30000);
});
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.shifts-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; position: relative; overflow: hidden; flex-shrink: 0;
}
.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
 
}
.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }
.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.125rem 0.6rem;
  border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1d4ed8; display: inline-block;
}
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center; }

/* ── Buttons ─────────────────────────────────────── */
.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }

.btn-icon {
  width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: white; color: #475569; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-icon:hover { background: #eff6ff; border-color: #a5b4fc; color: #4f46e5; }

.btn-secondary {
  padding: 0.45rem 1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #e2e8f0; }

.btn-danger {
  padding: 0.45rem 1rem; background: #ef4444; color: white;
  border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: background 0.15s; font-family: inherit;
}
.btn-danger:hover { background: #dc2626; }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-accept {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.45rem 0.9rem; background: #10b981; color: white;
  border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: background 0.15s; font-family: inherit;
}
.btn-accept:hover { background: #059669; }

.btn-reject {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.45rem 0.9rem; background: #ef4444; color: white;
  border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: background 0.15s; font-family: inherit;
}
.btn-reject:hover { background: #dc2626; }

.btn-checkin {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 1rem; background: white; color: #4f46e5;
  border: 2px solid rgba(255,255,255,0.6); border-radius: 8px;
  font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-checkin:hover:not(:disabled) { background: #eff6ff; }
.btn-checkin:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-checkout {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 1rem; background: #ef4444; color: white;
  border: 2px solid rgba(255,255,255,0.3); border-radius: 8px;
  font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-checkout:hover:not(:disabled) { background: #dc2626; }
.btn-checkout:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── Toast Banners ───────────────────────────────── */
.toast-banner {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem; font-weight: 600;
}
.toast-banner.success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.toast-banner.error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.toast-banner span { flex: 1; }
.toast-close { background: none; border: none; color: inherit; cursor: pointer; opacity: 0.7; line-height: 1; padding: 0; }
.toast-close:hover { opacity: 1; }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Today's Shift Card ──────────────────────────── */
.today-card {
  background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #4f46e5 100%);
  border-radius: 16px; position: relative; overflow: hidden;
  box-shadow: 0 8px 24px -8px rgba(79,70,229,0.4), 0 4px 8px -4px rgba(0,0,0,0.1);
}
.today-card-accent {
  position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 60%);
}
.today-inner {
  position: relative; padding: 1.75rem; display: flex; justify-content: space-between;
  align-items: flex-start; gap: 1.5rem;
}
.today-left { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1rem; }
.today-eyebrow { margin: 0; font-size: 0.75rem; font-weight: 600; color: rgba(199,210,254,0.9); text-transform: uppercase; letter-spacing: 0.08em; }
.today-title { margin: 0; font-size: 1.625rem; font-weight: 800; color: white; line-height: 1.2; }

/* Times */
.today-times { display: flex; align-items: center; gap: 1rem; }
.time-block { display: flex; flex-direction: column; gap: 0.15rem; }
.time-label { font-size: 0.7rem; color: rgba(199,210,254,0.8); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
.time-value { font-size: 1.25rem; font-weight: 700; color: white; }
.time-divider { color: rgba(199,210,254,0.5); font-size: 1.25rem; font-weight: 300; margin-top: 0.85rem; }

/* Notes */
.today-notes {
  display: flex; align-items: flex-start; gap: 0.5rem;
  background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);
  padding: 0.75rem 1rem; border-radius: 10px;
  font-size: 0.82rem; color: rgba(224,231,255,0.9); line-height: 1.5;
}

/* Actions row */
.today-actions-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }

/* Attendance grid */
.attendance-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
  background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);
  padding: 0.875rem 1rem; border-radius: 10px;
}
.att-item { display: flex; flex-direction: column; gap: 0.2rem; }
.att-label { font-size: 0.68rem; color: rgba(199,210,254,0.8); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.att-value { font-size: 0.875rem; font-weight: 600; color: white; }
.att-value.highlight { font-size: 1rem; font-weight: 800; }

/* Decision buttons */
.today-decision { display: flex; flex-direction: column; gap: 0.5rem; flex-shrink: 0; }

/* ── Section Cards ───────────────────────────────── */
.table-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}
.section-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;
}
h2 { font-size: 1.1rem; font-weight: 600; margin: 0; color: #334155; }
.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px;
}

/* Collapsible header */
.collapsible-header {
  display: flex; align-items: center; justify-content: space-between; width: 100%;
  background: none; border: none; cursor: pointer; font-family: inherit; padding: 0;
  margin-bottom: 0;
}
.chevron { transition: transform 0.2s ease; flex-shrink: 0; color: #94a3b8; }
.chevron.open { transform: rotate(180deg); }

/* ── States ──────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }
.spin-icon { animation: spin 0.8s linear infinite; }

.empty-state {
  text-align: center; padding: 3.5rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }

/* ── Assignment Cards ────────────────────────────── */
.assignments-list { display: flex; flex-direction: column; gap: 0.875rem; }

.assignment-card {
  display: flex; justify-content: space-between; align-items: flex-start; gap: 1.25rem;
  padding: 1.125rem 1.25rem 1.125rem 0; border: 1px solid #e2e8f0; border-radius: 12px;
  background: #fdfdfe; transition: border-color 0.15s, box-shadow 0.15s; position: relative; overflow: hidden;
}
.assignment-card:hover { border-color: #c7d2fe; box-shadow: 0 4px 12px -4px rgba(0,0,0,0.07); }

.card-left { display: flex; gap: 1.125rem; flex: 1; min-width: 0; padding-left: 1.25rem; }

.shift-type-bar {
  position: absolute; left: 0; top: 0; bottom: 0; width: 4px; border-radius: 12px 0 0 12px;
}
.type-morning { background: #f97316; }
.type-day     { background: #3b82f6; }
.type-evening { background: #8b5cf6; }
.type-night   { background: #475569; }

.shift-info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem 1.5rem; flex: 1; align-content: start;
}
.shift-info-item { display: flex; flex-direction: column; gap: 0.1rem; }
.info-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.info-value { font-size: 0.82rem; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 0.35rem; }
.note-value { font-weight: 400; color: #64748b; font-size: 0.8rem; }

.type-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

.card-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem; flex-shrink: 0; }
.card-actions { display: flex; gap: 0.35rem; }

.action-btn {
  width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e2e8f0;
  background: white; color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn.accept:hover { background: #d1fae5; color: #059669; border-color: #6ee7b7; }
.action-btn.danger:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* Status Badges */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px; padding: 0.25rem 0.65rem;
  border-radius: 9999px; font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.info    { background: #dbeafe; color: #1e40af; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.45); z-index: 50;
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-panel {
  background: white; border-radius: 16px; max-width: 440px; width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
  display: flex; flex-direction: column;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
}
.modal-header h2 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.875rem; }
.modal-desc { margin: 0; font-size: 0.875rem; color: #64748b; }
.modal-textarea {
  width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.875rem; font-family: inherit; color: #334155; resize: vertical;
  transition: border-color 0.15s, box-shadow 0.15s; background: #f8fafc; box-sizing: border-box;
}
.modal-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.5rem;
  padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; border-radius: 0 0 16px 16px;
}

/* ── Transitions ─────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-slide-enter-active, .modal-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-slide-enter-from, .modal-slide-leave-to { opacity: 0; transform: scale(0.96) translateY(8px); }
.collapse-enter-active, .collapse-leave-active { transition: all 0.25s ease; overflow: hidden; }
.collapse-enter-from, .collapse-leave-to { opacity: 0; max-height: 0; }
.collapse-enter-to, .collapse-leave-from { max-height: 300px; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .shifts-page { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .today-inner { flex-direction: column; }
  .today-decision { flex-direction: row; }
  .shift-info-grid { grid-template-columns: repeat(2, 1fr); }
  .attendance-grid { grid-template-columns: repeat(2, 1fr); }
  .assignment-card { flex-direction: column; padding: 1rem 1rem 1rem 1.25rem; }
  .card-right { flex-direction: row; align-items: center; justify-content: space-between; width: 100%; }
}
</style>