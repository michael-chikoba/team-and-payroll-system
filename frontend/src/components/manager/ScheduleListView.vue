<template>
  <div class="schedule-list-view">

    <!-- ─── Filters Card ─────────────────────────────────────────────────── -->
    <div class="filter-card">
      <div class="filter-header">
        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        <span class="filter-title">Filters</span>
        <span v-if="activeFilterCount > 0" class="filter-active-badge">{{ activeFilterCount }} active</span>
      </div>

      <div class="filters-grid">
        <div class="filter-group">
          <label class="filter-label">Task Type</label>
          <div class="select-wrap">
            <select v-model="filters.type" :class="['filter-select', filters.type ? 'active' : '']">
              <option value="">All Types</option>
              <option value="banner_creation">Banner Creation</option>
              <option value="weekly_overview">Weekly Overview</option>
              <option value="test_sequence">Test Sequence</option>
              <option value="live_games">Live Games</option>
              <option value="multibets">Multibets</option>
              <option value="news_section">News Section</option>
              <option value="other">Other</option>
            </select>
            <svg class="select-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>

        <div class="filter-group">
          <label class="filter-label">Status</label>
          <div class="select-wrap">
            <select v-model="filters.status" :class="['filter-select', filters.status ? 'active' : '']">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="overdue">Overdue</option>
            </select>
            <svg class="select-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>

        <div class="filter-group">
          <label class="filter-label">Assigned To</label>
          <div class="select-wrap">
            <select v-model="filters.assignedTo" :class="['filter-select', filters.assignedTo ? 'active' : '']">
              <option value="">All Team Members</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.full_name }}</option>
            </select>
            <svg class="select-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </div>

        <div class="filter-group filter-group-reset">
          <button @click="resetFilters" :disabled="activeFilterCount === 0" :class="['btn-clear-filters', activeFilterCount === 0 ? 'disabled' : '']">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Reset Filters
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Table Card ────────────────────────────────────────────────────── -->
    <div class="table-section">

      <!-- Controls Bar -->
      <div class="controls-bar">
        <div class="search-wrap">
          <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input v-model="searchQuery" placeholder="Search by title or description..." class="search-input" />
        </div>
        <div class="controls-right">
          <span class="records-count">{{ filteredSchedules.length }} schedule{{ filteredSchedules.length !== 1 ? 's' : '' }}</span>
          <div class="per-page-wrap">
            <label class="per-page-label">Rows:</label>
            <select v-model="perPage" @change="currentPage = 1" class="per-page-select">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredSchedules.length === 0" class="empty-state">
        <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p>No schedules found matching your search.</p>
        <button @click="resetAll" class="btn-secondary">Clear All Filters</button>
      </div>

      <!-- Table -->
      <div v-else class="table-container">
        <div class="list-header">
          <div class="col-title sortable" @click="sortBy('title')">Title <span v-if="sortField === 'title'" class="sort-icon">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span></div>
          <div class="col-type">Type</div>
          <div class="col-status">Status</div>
          <div class="col-date sortable" @click="sortBy('due_date')">Due Date <span v-if="sortField === 'due_date'" class="sort-icon">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span></div>
          <div class="col-assigned">Assigned To</div>
          <div class="col-actions">Actions</div>
        </div>

        <div v-for="schedule in paginatedSchedules" :key="schedule.id" class="list-row">

          <div class="col-title">
            <div class="title-info">
              <span class="title-name">{{ schedule.title }}</span>
              <span v-if="schedule.description" class="title-desc">{{ schedule.description }}</span>
            </div>
          </div>

          <div class="col-type">
            <span class="type-badge" :class="getTypeBadgeClass(schedule)">{{ getTypeLabel(schedule) }}</span>
          </div>

          <div class="col-status">
            <span class="status-badge" :class="getStatusBadgeClass(schedule.status)">{{ getStatusLabel(schedule.status) }}</span>
          </div>

          <div class="col-date">
            <span :class="['due-date', schedule.status === 'overdue' ? 'overdue' : '']">{{ formatDueDate(schedule.due_date || schedule.scheduled_date) }}</span>
          </div>

          <div class="col-assigned">
            <span class="assignee-name">{{ getAssigneeName(schedule.assigned_to) }}</span>
          </div>

          <div class="col-actions" @click.stop>
            <div class="action-group">
              <button v-if="schedule.status !== 'completed'" @click="$emit('complete', schedule.id)" class="action-btn action-complete" title="Mark Complete">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </button>
              <button @click="$emit('edit', schedule)" class="action-btn action-edit" title="Edit">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button @click="$emit('delete', schedule.id)" class="action-btn action-delete" title="Delete">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination-bar">
        <span class="pagination-info">Showing <strong>{{ pageStart }}&ndash;{{ pageEnd }}</strong> of <strong>{{ filteredSchedules.length }}</strong></span>
        <div class="pagination-controls">
          <button @click="currentPage--" :disabled="currentPage === 1" class="page-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Prev
          </button>
          <template v-for="page in pageNumbers" :key="page">
            <span v-if="page === '...'" class="page-ellipsis">&#8230;</span>
            <button v-else @click="currentPage = page" :class="['page-btn', currentPage === page ? 'active' : '']">{{ page }}</button>
          </template>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="page-btn">
            Next
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Status Legend ─────────────────────────────────────────────────── -->
    <div class="legend-card">
      <p class="legend-title">Status Legend</p>
      <div class="legend-items">
        <div v-for="item in statusLegend" :key="item.status" class="legend-item" :class="item.containerClass">
          <span class="legend-dot" :class="item.dotClass"></span>
          <span class="legend-label" :class="item.textClass">{{ item.label }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  schedules: { type: Array, required: true },
  employees: { type: Array, required: true }
});

defineEmits(['complete', 'edit', 'delete', 'toggle-meta']);

// ─── Search + Filters ─────────────────────────────────────────────────────────
const searchQuery = ref('');
const filters = ref({ type: '', status: '', assignedTo: '' });
const sortField = ref('title');
const sortDirection = ref('asc');

const activeFilterCount = computed(() =>
  Object.values(filters.value).filter(v => v !== '').length
);

const filteredSchedules = computed(() => {
  return props.schedules.filter(schedule => {
    const scheduleType = schedule.type || schedule.schedule_type;
    if (filters.value.type && scheduleType !== filters.value.type) return false;
    if (filters.value.status && schedule.status !== filters.value.status) return false;
    if (filters.value.assignedTo && schedule.assigned_to !== parseInt(filters.value.assignedTo)) return false;
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const title = (schedule.title || '').toLowerCase();
      const desc = (schedule.description || '').toLowerCase();
      if (!title.includes(q) && !desc.includes(q)) return false;
    }
    return true;
  }).sort((a, b) => {
    let aVal = sortField.value === 'due_date'
      ? (a.due_date || a.scheduled_date || '')
      : (a[sortField.value] || '');
    let bVal = sortField.value === 'due_date'
      ? (b.due_date || b.scheduled_date || '')
      : (b[sortField.value] || '');
    if (typeof aVal === 'string') aVal = aVal.toLowerCase();
    if (typeof bVal === 'string') bVal = bVal.toLowerCase();
    if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const resetFilters = () => { filters.value = { type: '', status: '', assignedTo: '' }; };
const resetAll = () => { resetFilters(); searchQuery.value = ''; };

const sortBy = (field) => {
  if (sortField.value === field) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  else { sortField.value = field; sortDirection.value = 'asc'; }
};

// ─── Pagination ───────────────────────────────────────────────────────────────
const currentPage = ref(1);
const perPage = ref(10);

watch([filters, searchQuery], () => { currentPage.value = 1; }, { deep: true });

const totalPages = computed(() => Math.ceil(filteredSchedules.value.length / perPage.value) || 1);
const pageStart = computed(() => filteredSchedules.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1);
const pageEnd = computed(() => Math.min(currentPage.value * perPage.value, filteredSchedules.value.length));

const paginatedSchedules = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredSchedules.value.slice(start, start + perPage.value);
});

const pageNumbers = computed(() => {
  const total = totalPages.value, current = currentPage.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
  if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
  return [1, '...', current - 1, current, current + 1, '...', total];
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getAssigneeName = (assignedTo) => {
  if (!assignedTo) return '—';
  const emp = props.employees.find(e => e.id === assignedTo);
  return emp ? emp.full_name : '—';
};

const formatDueDate = (dateValue) => {
  if (!dateValue) return '—';
  try {
    const d = new Date(dateValue);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  } catch { return '—'; }
};

const getTypeLabel = (schedule) => {
  const type = schedule.schedule_type || schedule.type;
  const labels = {
    banner_creation: 'Banner Creation', weekly_overview: 'Weekly Overview',
    test_sequence: 'Test Sequence', live_games: 'Live Games',
    multibets: 'Multibets', news_section: 'News Section', other: 'Other'
  };
  return labels[type] || type || 'Other';
};

const getTypeBadgeClass = (schedule) => {
  const type = schedule.schedule_type || schedule.type;
  const map = {
    banner_creation: 'type-pink', weekly_overview: 'type-blue',
    test_sequence: 'type-purple', live_games: 'type-red',
    multibets: 'type-amber', news_section: 'type-emerald', other: 'type-gray'
  };
  return map[type] || 'type-gray';
};

const getStatusBadgeClass = (status) => ({ completed: 'status-completed', in_progress: 'status-in-progress', pending: 'status-pending', overdue: 'status-overdue' }[status] || 'status-pending');
const getStatusLabel = (status) => ({ completed: 'Done', in_progress: 'In Progress', pending: 'Pending', overdue: 'Overdue' }[status] || status || 'Unknown');

const statusLegend = [
  { status: 'completed',   label: 'Completed',   dotClass: 'dot-green',  textClass: 'text-green',  containerClass: 'legend-green'  },
  { status: 'in_progress', label: 'In Progress', dotClass: 'dot-yellow', textClass: 'text-yellow', containerClass: 'legend-yellow' },
  { status: 'pending',     label: 'Pending',     dotClass: 'dot-slate',  textClass: 'text-slate',  containerClass: 'legend-slate'  },
  { status: 'overdue',     label: 'Overdue',     dotClass: 'dot-red',    textClass: 'text-red',    containerClass: 'legend-red'    }
];
</script>

<style scoped>
.schedule-list-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Filter Card ─────────────────────────────────────────────────────────────── */
.filter-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
}
.filter-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; }
.filter-icon { width: 16px; height: 16px; color: #6366f1; flex-shrink: 0; }
.filter-title { font-size: 0.8rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.05em; }
.filter-active-badge { background: #eff0fe; border: 1px solid #c7d2fe; color: #4f46e5; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.6rem; border-radius: 9999px; }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end; }
.filter-group { display: flex; flex-direction: column; gap: 0.35rem; }
.filter-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.select-wrap { position: relative; }
.filter-select { width: 100%; padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #334155; font-size: 0.875rem; font-weight: 500; cursor: pointer; appearance: none; transition: all 0.2s; font-family: inherit; }
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.filter-select.active { border-color: #6366f1; background: #eff0fe; color: #3730a3; font-weight: 600; }
.select-chevron { pointer-events: none; position: absolute; right: 0.65rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #94a3b8; }
.filter-group-reset { justify-content: flex-end; }
.btn-clear-filters { display: flex; align-items: center; justify-content: center; gap: 0.4rem; width: 100%; padding: 0.45rem 0.875rem; background: white; border: 1px solid #d1d5db; border-radius: 8px; color: #475569; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; }
.btn-clear-filters:hover:not(.disabled) { background: #f8fafc; border-color: #6366f1; color: #6366f1; }
.btn-clear-filters.disabled { opacity: 0.45; cursor: not-allowed; }

/* ── Table Section ───────────────────────────────────────────────────────────── */
.table-section {
  background: white;
  border-radius:none;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  overflow: hidden;
}

/* Controls Bar */
.controls-bar { display: flex; align-items: center; gap: 0.875rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
.search-wrap { display: flex; align-items: center; gap: 0.625rem; flex: 1; min-width: 200px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.5rem 0.875rem; transition: all 0.2s; }
.search-wrap:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: white; }
.search-icon { color: #94a3b8; flex-shrink: 0; }
.search-input { flex: 1; border: none; background: transparent; outline: none; font-size: 0.875rem; color: #1e293b; font-family: inherit; }
.search-input::placeholder { color: #94a3b8; }
.controls-right { display: flex; align-items: center; gap: 1rem; margin-left: auto; }
.records-count { font-size: 0.75rem; font-weight: 700; color: #6366f1; background: #eff0fe; padding: 0.2rem 0.75rem; border-radius: 9999px; border: 1px solid #c7d2fe; white-space: nowrap; }
.per-page-wrap { display: flex; align-items: center; gap: 0.4rem; }
.per-page-label { font-size: 0.72rem; font-weight: 600; color: #94a3b8; }
.per-page-select { font-size: 0.78rem; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.2rem 0.5rem; color: #334155; font-family: inherit; cursor: pointer; }
.per-page-select:focus { outline: none; border-color: #6366f1; }

/* Table — matches TeamReports list structure */
.table-container {
  border-top: none;
  border-bottom: none;
  border-left: none;
  border-right: none;
}

.list-header,
.list-row {
  display: grid;
  grid-template-columns: 2.5fr 1.2fr 1.1fr 1.1fr 1.2fr 1fr;
  padding: 0.75rem 1rem;
  align-items: center;
  gap: 1rem;
}

.list-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.list-header .sortable { cursor: pointer; user-select: none; transition: color 0.15s; }
.list-header .sortable:hover { color: #334155; }
.sort-icon { margin-left: 0.25rem; color: #6366f1; }

.list-row {
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem;
  background: white;
  transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

/* Title column — mirrors TeamReports report-name-wrap */
.col-title { display: flex; align-items: center; min-width: 0; }
.title-info { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }
.title-name { font-weight: 600; color: #1e293b; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.title-desc { font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Type badges — coloured, matches TeamReports style */
.type-badge {
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 600;
  border: 1px solid transparent;
  white-space: nowrap;
}
.type-pink    { background: #fdf2f8; color: #9d174d; border-color: #fbcfe8; }
.type-blue    { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.type-purple  { background: #f5f3ff; color: #5b21b6; border-color: #ddd6fe; }
.type-red     { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
.type-amber   { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.type-emerald { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.type-gray    { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* Status badges — matches TeamReports pill style */
.status-badge {
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}
.status-completed   { background: #d1fae5; color: #065f46; }
.status-in-progress { background: #fef3c7; color: #92400e; }
.status-pending     { background: #f1f5f9; color: #475569; }
.status-overdue     { background: #fee2e2; color: #991b1b; }

.due-date { font-size: 0.82rem; color: #475569; font-weight: 500; }
.due-date.overdue { color: #dc2626; font-weight: 700; }
.assignee-name { font-size: 0.82rem; color: #334155; font-weight: 500; }

/* Action buttons — matches TeamReports action-btn style */
.action-group { display: flex; align-items: center; gap: 0.4rem; }
.action-btn {
  padding: 0.3rem 0.65rem;
  border: none;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 28px;
}
.action-complete { background: #f1f5f9; color: #475569; }
.action-complete:hover { background: #d1fae5; color: #065f46; }
.action-edit { background: #f1f5f9; color: #475569; }
.action-edit:hover { background: #eff6ff; color: #1d4ed8; }
.action-delete { background: #f1f5f9; color: #475569; }
.action-delete:hover { background: #fee2e2; color: #991b1b; }

/* Empty state */
.empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; font-weight: 500; }
.btn-secondary { padding: 0.45rem 1rem; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; }
.btn-secondary:hover { background: #e2e8f0; }

/* Pagination */
.pagination-bar { display: flex; justify-content: space-between; align-items: center; padding: 0.875rem 1.25rem; border-top: 1px solid #f1f5f9; background: #f8fafc; }
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; align-items: center; gap: 0.3rem; }
.page-btn { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff0fe; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-color: transparent; box-shadow: 0 2px 8px rgba(99,102,241,0.3); min-width: 32px; justify-content: center; padding: 0.35rem 0.5rem; }
.page-ellipsis { padding: 0.35rem 0.25rem; font-size: 0.78rem; color: #94a3b8; user-select: none; }

/* ── Legend ──────────────────────────────────────────────────────────────────── */
.legend-card { background: white; border-radius: 16px; padding: 1.125rem 1.5rem; box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
.legend-title { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em; margin: 0 0 0.875rem 0; }
.legend-items { display: flex; flex-wrap: wrap; gap: 0.625rem; }
.legend-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.875rem; border-radius: 9999px; border: 1px solid transparent; }
.legend-green  { background: #d1fae5; border-color: #6ee7b7; }
.legend-yellow { background: #fef3c7; border-color: #fcd34d; }
.legend-slate  { background: #f1f5f9; border-color: #cbd5e1; }
.legend-red    { background: #fee2e2; border-color: #fca5a5; }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-green  { background: #10b981; }
.dot-yellow { background: #f59e0b; }
.dot-slate  { background: #94a3b8; }
.dot-red    { background: #ef4444; }
.legend-label { font-size: 0.75rem; font-weight: 700; }
.text-green  { color: #065f46; }
.text-yellow { color: #92400e; }
.text-slate  { color: #475569; }
.text-red    { color: #991b1b; }

/* ── Responsive ──────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .list-header { display: none; }
  .list-row {
    grid-template-columns: 1fr auto;
    grid-template-areas: "title status" "type date" "assign actions";
    gap: 0.4rem;
    padding: 0.875rem 1rem;
  }
  .col-title    { grid-area: title; }
  .col-status   { grid-area: status; justify-self: end; }
  .col-type     { grid-area: type; }
  .col-date     { grid-area: date; justify-self: end; }
  .col-assigned { grid-area: assign; }
  .col-actions  { grid-area: actions; justify-self: end; }
}
@media (max-width: 640px) {
  .filters-grid { grid-template-columns: 1fr; }
  .controls-bar { flex-direction: column; align-items: stretch; }
  .controls-right { justify-content: space-between; }
  .pagination-bar { flex-direction: column; gap: 0.75rem; }
}
</style>