<template>
  <div class="task-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Team Task Management</h1>
            <p class="subtitle">View, assign, and track tasks across your team with ease</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="refreshTasks" class="btn-outline" :disabled="loadingEmployees">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            {{ loadingEmployees ? 'Syncing...' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Task Board Card ────────────────────────── -->
    <div class="board-card">
      <div class="board-header">
        <div class="board-title-wrap">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="header-icon"><path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Active Task Board
          </h2>
          <span class="board-subtitle">Manage and monitor your team's tasks in real-time</span>
        </div>
        <div class="board-meta" v-if="!loadingEmployees">
          <span class="team-count">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            {{ employees.length }} team member{{ employees.length !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>

      <!-- Loading state for employees -->
      <div v-if="loadingEmployees" class="board-loading">
        <div class="spinner"></div>
        <p>Loading team members...</p>
      </div>

      <!-- Task Board Component -->
      <div v-else class="board-content">
        <TaskBoard
          :key="refreshKey"
          :employees="employees"
          :loading="loadingEmployees"
          @task-updated="onTaskUpdated"
        />
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import TaskBoard from '../../components/common/TaskBoard.vue'

const emit = defineEmits(['task-created'])
const router = useRouter()

const employees = ref([])
const loadingEmployees = ref(false)
const refreshKey = ref(0)

onMounted(async () => {
  await fetchEmployees()
})

async function fetchEmployees() {
  loadingEmployees.value = true
  try {
    const response = await axios.get('/api/manager/employees')
    employees.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Failed to fetch employees:', err)
  } finally {
    loadingEmployees.value = false
  }
}

const refreshTasks = async () => {
  refreshKey.value++
  await fetchEmployees()
}

const onTaskUpdated = async () => {
  refreshKey.value++
  await fetchEmployees()
}

const getEmployeeUserId = (employee) => employee.user_id
const getEmployeeDisplayName = (employee) => `${employee.first_name} ${employee.last_name}`
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.task-page {
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
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
 
}

.user-greeting {
  display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;
}

.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #1d4ed8;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.btn-outline:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── Board Card ──────────────────────────────────── */
.board-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.board-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
}

.board-title-wrap { display: flex; flex-direction: column; gap: 0.2rem; }

.board-header h2 {
  font-size: 1.1rem; font-weight: 600; color: #334155;
  margin: 0; display: flex; align-items: center; gap: 0.5rem;
}

.header-icon { color: #6366f1; flex-shrink: 0; }

.board-subtitle { font-size: 0.78rem; color: #64748b; }

.board-meta { display: flex; align-items: center; gap: 0.75rem; }

.team-count {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.78rem; font-weight: 600; color: #475569;
  background: white; border: 1px solid #e2e8f0;
  padding: 0.25rem 0.75rem; border-radius: 9999px;
}

.board-loading {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 0.875rem; padding: 4rem;
  color: #64748b; font-size: 0.875rem;
}

.spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.board-content {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 1.5rem 1.75rem;
}

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .task-page { padding: 1rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .btn-outline { width: 100%; justify-content: center; }
  .board-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .board-content { padding: 1rem; }
}
</style>