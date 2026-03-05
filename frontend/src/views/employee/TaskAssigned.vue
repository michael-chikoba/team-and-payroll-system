<template>
  <div class="task-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">My Tasks</h1>
            <p class="subtitle">View and manage your assigned tasks</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="fetchMyTasks" class="btn-outline" :disabled="loadingTasks">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            {{ loadingTasks ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Loading State ──────────────────────────── -->
    <div v-if="loadingTasks" class="board-card">
      <div class="board-loading">
        <div class="spinner"></div>
        <p>Loading your tasks...</p>
      </div>
    </div>

    <!-- ── Task Board ─────────────────────────────── -->
    <div v-else-if="myTasks.length > 0" class="board-card">
      <div class="board-header">
        <div class="board-title-wrap">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="header-icon"><path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Active Tasks
          </h2>
          <span class="board-subtitle">Your currently assigned work items</span>
        </div>
        <span class="task-count">{{ myTasks.length }} task{{ myTasks.length !== 1 ? 's' : '' }}</span>
      </div>

      <div class="board-content">
        <TaskBoard
          :tasks="myTasks"
          :employee-view="true"
          @task-updated="fetchMyTasks"
        />
      </div>
    </div>

    <!-- ── Empty State ────────────────────────────── -->
    <div v-else class="board-card">
      <div class="empty-state">
        <div class="empty-icon-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
          </svg>
        </div>
        <h3>No Tasks Assigned</h3>
        <p>You have no tasks at the moment. Check back later or contact your manager.</p>
        <button @click="fetchMyTasks" class="btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
          Check Again
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'
import TaskBoard from '../../components/common/TaskBoard.vue'

const authStore = useAuthStore()
const router = useRouter()

const myTasks = ref([])
const loadingTasks = ref(false)

async function fetchMyTasks() {
  loadingTasks.value = true
  try {
    const response = await axios.get('/api/tasks')
    const tasksData = response.data.tasks || response.data.data || response.data
    myTasks.value = Array.isArray(tasksData) ? tasksData : []
  } catch (err) {
    console.error('Failed to fetch tasks:', err)
    if (err.response?.status === 401) {
      authStore.clearAuth()
      router.push('/login')
    }
    myTasks.value = []
  } finally {
    loadingTasks.value = false
  }
}

onMounted(async () => {
  await fetchMyTasks()
})
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
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #166534;
  display: inline-block;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.btn-outline:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  font-family: inherit; margin-top: 0.25rem;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

/* ── Board Card ──────────────────────────────────── */
.board-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

/* ── Board Header ────────────────────────────────── */
.board-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.125rem 1.75rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
  gap: 1rem;
}

.board-title-wrap { display: flex; flex-direction: column; gap: 0.2rem; }

.board-header h2 {
  font-size: 1.1rem; font-weight: 600; color: #334155;
  margin: 0; display: flex; align-items: center; gap: 0.5rem;
}

.header-icon { color: #6366f1; flex-shrink: 0; }

.board-subtitle { font-size: 0.78rem; color: #64748b; }

.task-count {
  display: inline-flex; align-items: center;
  font-size: 0.78rem; font-weight: 700; color: #4f46e5;
  background: #eff6ff; border: 1px solid #c7d2fe;
  padding: 0.25rem 0.75rem; border-radius: 9999px;
  flex-shrink: 0;
}

/* ── Board Content ───────────────────────────────── */
.board-content {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 1.5rem 1.75rem;
}

/* ── Loading ─────────────────────────────────────── */
.board-loading {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 0.875rem; padding: 5rem 2rem;
  color: #64748b; font-size: 0.875rem;
}

.spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Empty State ─────────────────────────────────── */
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 0.875rem;
  padding: 5rem 2rem; text-align: center;
}

.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center;
  color: #94a3b8; margin-bottom: 0.25rem;
}

.empty-state h3 {
  margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;
}

.empty-state p {
  margin: 0; font-size: 0.875rem; color: #64748b;
  max-width: 320px; line-height: 1.6;
}

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .task-page { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .btn-outline { width: 100%; justify-content: center; }
  .board-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; padding: 1rem 1.25rem; }
  .board-content { padding: 1rem 1.25rem; }
  .empty-state { padding: 3rem 1.5rem; }
}
</style>