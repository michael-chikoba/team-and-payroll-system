<template>
  <div class="task-management">
    <!-- ── Main (header card lives inside here) ───────── -->
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="management-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
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
                <h1 class="greeting">Business Task Management</h1>
                <p class="subtitle">View, assign, and track tasks across your entire business</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ employees.length }} Employees</span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">
              <button @click="refreshTasks" class="btn-icon" title="Refresh Tasks" :disabled="loadingEmployees">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spinning': loadingEmployees }">
                  <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
              </button>

              <button class="btn-accent" @click="createNewTask">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create Task
              </button>
            </div>
          </div>

          <!-- View badge -->
          <div class="view-badge">
            <div class="view-content">
              <span class="view-primary">Board</span>
              <div class="view-details">
                <span class="view-label">Current View</span>
                <span class="view-total">Active Tasks</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Task Board Section -->
      <div class="content-card">
        <div class="card-header">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Active Task Board
          </h3>
          <span class="count-badge">{{ employees.length }} Employees</span>
        </div>
        
        <div class="task-board-container">
          <!-- Loading State -->
          <div v-if="loadingEmployees" class="state-banner loading">
            <div class="spinner"></div>
            <span>Loading employees and tasks...</span>
          </div>

          <!-- Task Board -->
          <div v-else class="task-board-wrapper">
            <TaskBoard
              :key="refreshKey"
              :employees="employees"
              :loading="loadingEmployees"
              @task-updated="onTaskUpdated"
              class="task-board"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import TaskBoard from '../../components/common/TaskBoard.vue';

const emit = defineEmits(['task-created']);
const router = useRouter();

const employees = ref([]);
const loadingEmployees = ref(false);
const refreshKey = ref(0);

onMounted(async () => {
  await fetchEmployees();
});

async function fetchEmployees() {
  loadingEmployees.value = true;
  try {
    const response = await axios.get('/api/tasks/employees/simple');
    employees.value = response.data.employees || response.data.data || [];
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    console.error('Failed to load business employees. Some features may be limited.');
  } finally {
    loadingEmployees.value = false;
  }
}

const refreshTasks = async () => {
  refreshKey.value++;
  await fetchEmployees();
};

const onTaskUpdated = async () => {
  refreshKey.value++;
  await fetchEmployees();
};

const createNewTask = () => {
  // Emit event or navigate to task creation page
  emit('task-created');
  // You can also use router to navigate to a task creation page
  // router.push({ name: 'create-task' });
};
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.task-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.user-greeting {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
  flex-wrap: wrap;
}

/* Avatar */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
  flex-wrap: wrap;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #7c3aed;
  display: inline-flex;
  align-items: center;
}

/* Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Buttons */
.btn-icon {
  width: 38px;
  height: 38px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-icon:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.btn-accent {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
  white-space: nowrap;
}

.btn-accent:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
}

/* View badge */
.view-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 110px;
  flex-shrink: 0;
}

.view-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: #8b5cf6;
  line-height: 1;
  text-transform: capitalize;
}

.view-details {
  display: flex;
  flex-direction: column;
}

.view-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.view-total {
  font-size: 0.7rem;
  color: #94a3b8;
  text-transform: capitalize;
}

/* ── Content Cards ────────────────────────────────────── */
.content-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  overflow: hidden;
  min-height: 600px;
  display: flex;
  flex-direction: column;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fcfcfc;
}

.card-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
}

.count-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

.task-board-container {
  flex: 1;
  padding: 1.5rem;
  min-height: 500px;
  position: relative;
}

.task-board-wrapper {
  height: 100%;
  min-height: 500px;
}

.task-board {
  height: 100%;
  min-height: 500px;
}

/* State Banners */
.state-banner {
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  height: 200px;
}

.state-banner.loading {
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #bae6fd;
  border-top-color: #0284c7;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Ensure the TaskBoard component fits properly */
:deep(.task-board) {
  height: 100%;
}

:deep(.task-board .board-columns) {
  height: calc(100% - 60px);
}

/* Responsive */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .management-main {
    padding: 1rem;
  }
  
  .header-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-controls {
    width: 100%;
    justify-content: space-between;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .view-badge {
    align-self: flex-start;
  }
  
  .task-board-container {
    padding: 1rem;
  }
  
  .card-header {
    padding: 0.75rem 1rem;
  }
}

@media (max-width: 480px) {
  .header-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-accent {
    width: 100%;
    justify-content: center;
  }
  
  .role-meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .view-badge {
    width: 100%;
  }
  
  .view-content {
    justify-content: center;
  }
  
  .task-board-container {
    padding: 0.75rem;
  }
}

/* Dark mode support if needed */
@media (prefers-color-scheme: white) {
  .task-management {
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
  }
  
  .management-header-card,
  .content-card {
    background: #1e293b;
    border-color: #334155;
  }
  
  .greeting,
  .card-header h3 {
    color: #f1f5f9;
  }
  
  .subtitle,
  .stat-label,
  .view-label {
    color: #94a3b8;
  }
  
  .card-header {
    background: #0f172a;
    border-color: #334155;
  }
  
  .view-badge {
    background: #0f172a;
    border-color: #334155;
  }
  
  .btn-icon {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
  }
  
  .btn-icon:hover:not(:disabled) {
    background: #1e293b;
    border-color: #475569;
    color: #f1f5f9;
  }
}
</style>