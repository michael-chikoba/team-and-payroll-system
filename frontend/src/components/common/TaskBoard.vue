<template>
  <div class="task-board">
    <!-- Header with filters and actions -->
    <div class="board-header">
      <div class="header-left">
        <h1>Task Board</h1>
        <div class="filter-controls">
          <div class="date-range-filter">
            <label>Date Range:</label>
            <input
              type="date"
              v-model="filter.startDate"
              class="date-input"
              :max="filter.endDate"
            >
            <span>to</span>
            <input
              type="date"
              v-model="filter.endDate"
              class="date-input"
              :min="filter.startDate"
            >
            <button
              @click="applyDateFilter"
              class="btn-filter"
              :disabled="!filter.startDate || !filter.endDate"
            >
              Apply
            </button>
            <button
              @click="clearDateFilter"
              class="btn-clear"
            >
              Clear
            </button>
          </div>
        
          <div class="additional-filters">
            <select v-model="filter.priority" class="filter-select">
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="moderate">Moderate</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          
            <select v-model="filter.status" class="filter-select">
              <option value="">All Statuses</option>
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="under_review">Under Review</option>
              <option value="completed">Completed</option>
            </select>
          
            <select v-model="filter.assignedTo" class="filter-select">
              <option value="">All Assignees</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ employee.full_name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    
      <div class="header-right">
        <div class="report-actions">
          <button
            @click="showReportModal = true"
            class="btn-report"
            :disabled="filteredTasks.length === 0"
          >
            📊 Generate Report
          </button>
          <button @click="handleCreateTask" class="btn-primary">
            + Create Task
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Summary -->
    <div v-if="!loading && !error" class="stats-summary">
      <div class="stat-card">
        <span class="stat-label">Total Tasks</span>
        <span class="stat-value">{{ filteredTasks.length }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Completed</span>
        <span class="stat-value">{{ completedTasksCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">In Progress</span>
        <span class="stat-value">{{ inProgressTasksCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Overdue</span>
        <span class="stat-value">{{ overdueTasksCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">This Week</span>
        <span class="stat-value">{{ thisWeekTasksCount }}</span>
      </div>
    </div>

    <!-- Loading and Error States -->
    <div v-if="loading" class="loading">Loading tasks...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <!-- Task Columns -->
    <div v-else class="board-columns">
      <TaskColumn
        title="To Do"
        status="todo"
        :tasks="getTasksByStatus('todo')"
        :user-role="userRole"
        @update-status="handleStatusUpdate"
        @edit-task="handleEditTask"
        @delete-task="handleDeleteTask"
        @view-task="handleViewTask"
      />
    
      <TaskColumn
        title="In Progress"
        status="in_progress"
        :tasks="getTasksByStatus('in_progress')"
        :user-role="userRole"
        @update-status="handleStatusUpdate"
        @edit-task="handleEditTask"
        @delete-task="handleDeleteTask"
        @view-task="handleViewTask"
      />
    
      <TaskColumn
        title="Under Review"
        status="under_review"
        :tasks="getTasksByStatus('under_review')"
        :user-role="userRole"
        @update-status="handleStatusUpdate"
        @edit-task="handleEditTask"
        @delete-task="handleDeleteTask"
        @view-task="handleViewTask"
      />
    
      <TaskColumn
        title="Completed"
        status="completed"
        :tasks="getTasksByStatus('completed')"
        :user-role="userRole"
        @update-status="handleStatusUpdate"
        @edit-task="handleEditTask"
        @delete-task="handleDeleteTask"
        @view-task="handleViewTask"
      />
    </div>

    <!-- Create/Edit Task Modal -->
    <Teleport to="body">
      <TaskModal
        v-if="showCreateModal || showEditModal"
        :task="editingTask"
        :employees="employees"
        :loading-employees="loadingEmployees"
        :user-role="userRole"
        @close="closeModals"
        @save="handleSaveTask"
      />
    </Teleport>

    <!-- View Task Modal -->
    <Teleport to="body">
      <TaskDetailModal
        v-if="showDetailModal"
        :task="selectedTask"
        :user-role="userRole"
        @close="showDetailModal = false"
        @add-comment="handleAddComment"
        @delete-comment="handleDeleteComment"
      />
    </Teleport>

    <!-- Report Generation Modal -->
    <Teleport to="body">
      <div v-if="showReportModal" class="modal-overlay" @click.self="showReportModal = false">
        <div class="modal-content report-modal">
          <div class="modal-header">
            <h2>Generate Task Report</h2>
            <button @click="showReportModal = false" class="close-btn">&times;</button>
          </div>
        
          <div class="modal-body">
            <div class="report-options">
              <div class="form-group">
                <label for="reportType">Report Type</label>
                <select id="reportType" v-model="reportOptions.type" class="form-control">
                  <option value="weekly">Weekly Report</option>
                  <option value="custom">Custom Date Range</option>
                  <option value="all">All Tasks</option>
                </select>
              </div>
            
              <div v-if="reportOptions.type === 'custom'" class="form-group">
                <label for="reportStartDate">Start Date</label>
                <input
                  id="reportStartDate"
                  type="date"
                  v-model="reportOptions.startDate"
                  class="form-control"
                >
              </div>
            
              <div v-if="reportOptions.type === 'custom'" class="form-group">
                <label for="reportEndDate">End Date</label>
                <input
                  id="reportEndDate"
                  type="date"
                  v-model="reportOptions.endDate"
                  class="form-control"
                  :min="reportOptions.startDate"
                >
              </div>
            
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="reportOptions.includeDetails" class="checkbox">
                  Include task details
                </label>
              </div>
            
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="reportOptions.includeComments" class="checkbox">
                  Include comments
                </label>
              </div>
            
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="reportOptions.includeSubtasks" class="checkbox">
                  Include subtasks
                </label>
              </div>
            
              <div class="form-group">
                <label for="reportFormat">Format</label>
                <select id="reportFormat" v-model="reportOptions.format" class="form-control">
                  <option value="pdf">PDF</option>
                  <option value="excel">Excel</option>
                </select>
              </div>
            
              <div class="selected-count">
                <strong>Selected Tasks:</strong> {{ selectedTasksForReport.length }}
                <span v-if="selectedTasksForReport.length === 0" class="warning-text">
                  (No tasks match your criteria)
                </span>
              </div>
            </div>
          
            <div v-if="filteredTasks.length > 0" class="task-selection">
              <h3>Select Tasks for Report</h3>
              <div class="select-all-controls">
                <label>
                  <input
                    type="checkbox"
                    :checked="allTasksSelected"
                    @change="toggleSelectAll"
                    class="checkbox"
                  >
                  Select All ({{ filteredTasks.length }} tasks)
                </label>
              </div>
            
              <div class="tasks-list">
                <div
                  v-for="task in filteredTasks"
                  :key="task.id"
                  class="task-select-item"
                  :class="{ 'selected': selectedReportTasks.includes(task.id) }"
                >
                  <label class="task-select-label">
                    <input
                      type="checkbox"
                      :value="task.id"
                      v-model="selectedReportTasks"
                      class="task-checkbox"
                    >
                    <div class="task-info">
                      <span class="task-title">{{ task.title }}</span>
                      <div class="task-meta">
                        <span class="task-priority" :class="`priority-${task.priority}`">
                          {{ task.priority }}
                        </span>
                        <span class="task-assignee">{{ task.assigned_to.name }}</span>
                        <span class="task-date">
                          {{ task.created_at ? formatDate(task.created_at) : 'No date' }}
                        </span>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        
          <div class="modal-footer">
            <button
              @click="showReportModal = false"
              class="btn-secondary"
              :disabled="generatingReport"
            >
              Cancel
            </button>
            <button
              @click="generateReport"
              class="btn-primary"
              :disabled="generatingReport || selectedReportTasks.length === 0"
            >
              <span v-if="generatingReport">Generating...</span>
              <span v-else>Generate {{ reportOptions.format.toUpperCase() }} Report</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useTasks } from '../../composables/useTasks';
import TaskColumn from './TaskColumn.vue';
import TaskModal from './TaskModal.vue';
import TaskDetailModal from './TaskDetailModal.vue';
import axios from 'axios';

const {
  tasks,
  employees,
  loading,
  error,
  fetchTasks,
  createTask,
  updateTask,
  updateTaskStatus,
  deleteTask,
  fetchEmployees,
  addComment,
  deleteComment
} = useTasks();

const userRole = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);
const showReportModal = ref(false);
const editingTask = ref(null);
const selectedTask = ref(null);
const loadingEmployees = ref(false);
const generatingReport = ref(false);
const selectedReportTasks = ref([]);

const filter = ref({
  startDate: '',
  endDate: '',
  priority: '',
  assignedTo: '',
  status: ''
});

const reportOptions = ref({
  type: 'weekly',
  startDate: '',
  endDate: '',
  includeDetails: true,
  includeComments: false,
  includeSubtasks: false,
  format: 'pdf'
});

onMounted(async () => {
  const data = await fetchTasks();
  userRole.value = data.user_role;
  
  const endDate = new Date();
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - 30);
  filter.value.endDate = formatDateForInput(endDate);
  filter.value.startDate = formatDateForInput(startDate);
  
  const weekAgo = new Date();
  weekAgo.setDate(weekAgo.getDate() - 7);
  reportOptions.value.startDate = formatDateForInput(weekAgo);
  reportOptions.value.endDate = formatDateForInput(new Date());
});

const filteredTasks = computed(() => {
  if (!tasks.value) return [];
  
  return tasks.value.filter(task => {
    if (filter.value.startDate && filter.value.endDate) {
      const taskDate = task.created_at ? new Date(task.created_at) : new Date();
      const startDate = new Date(filter.value.startDate);
      const endDate = new Date(filter.value.endDate);
      endDate.setHours(23, 59, 59, 999);
      if (taskDate < startDate || taskDate > endDate) return false;
    }
    if (filter.value.priority && task.priority !== filter.value.priority) return false;
    if (filter.value.assignedTo && task.assigned_to.id !== filter.value.assignedTo) return false;
    if (filter.value.status && task.status !== filter.value.status) return false;
    return true;
  });
});

const selectedTasksForReport = computed(() =>
  filteredTasks.value.filter(task => selectedReportTasks.value.includes(task.id))
);

const allTasksSelected = computed(() =>
  filteredTasks.value.length > 0 &&
  selectedReportTasks.value.length === filteredTasks.value.length
);

const completedTasksCount = computed(() =>
  filteredTasks.value.filter(task => task.status === 'completed').length
);

const inProgressTasksCount = computed(() =>
  filteredTasks.value.filter(task => task.status === 'in_progress').length
);

const overdueTasksCount = computed(() =>
  filteredTasks.value.filter(task => {
    if (!task.deadline || task.status === 'completed') return false;
    return new Date(task.deadline) < new Date();
  }).length
);

const thisWeekTasksCount = computed(() => {
  const today = new Date();
  const weekAgo = new Date();
  weekAgo.setDate(weekAgo.getDate() - 7);
  return filteredTasks.value.filter(task => {
    if (!task.created_at) return false;
    const taskDate = new Date(task.created_at);
    return taskDate >= weekAgo && taskDate <= today;
  }).length;
});

watch(filteredTasks, (newTasks) => {
  selectedReportTasks.value = newTasks.map(task => task.id);
}, { deep: true });

const getTasksByStatus = (status) =>
  filteredTasks.value.filter(task => task.status === status);

const applyDateFilter = () => {
  if (!filter.value.startDate || !filter.value.endDate) {
    alert('Please select both start and end dates');
    return;
  }
};

const clearDateFilter = () => {
  filter.value.startDate = '';
  filter.value.endDate = '';
  filter.value.priority = '';
  filter.value.assignedTo = '';
  filter.value.status = '';
};

const toggleSelectAll = () => {
  if (allTasksSelected.value) {
    selectedReportTasks.value = [];
  } else {
    selectedReportTasks.value = filteredTasks.value.map(task => task.id);
  }
};

const generateReport = async () => {
  if (selectedReportTasks.value.length === 0) {
    alert('Please select at least one task for the report');
    return;
  }
  try {
    generatingReport.value = true;
    const reportData = {
      task_ids: selectedReportTasks.value,
      report_type: reportOptions.value.type,
      start_date: reportOptions.value.startDate,
      end_date: reportOptions.value.endDate,
      include_details: reportOptions.value.includeDetails,
      include_comments: reportOptions.value.includeComments,
      include_subtasks: reportOptions.value.includeSubtasks,
      format: reportOptions.value.format
    };
    const response = await axios.post('/api/tasks/reports/generate', reportData, {
      responseType: 'blob',
      headers: {
        'Content-Type': 'application/json',
        'Accept': reportOptions.value.format === 'pdf'
          ? 'application/pdf'
          : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    const fileName = `task-report-${new Date().toISOString().split('T')[0]}.${reportOptions.value.format}`;
    link.href = url;
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showReportModal.value = false;
    alert('Report generated successfully!');
  } catch (err) {
    console.error('Failed to generate report:', err);
    let errorMessage = 'Failed to generate report. ';
    if (err.response) {
      if (err.response.status === 404) errorMessage += 'Report endpoint not found.';
      else if (err.response.status === 500) errorMessage += 'Server error occurred.';
      else errorMessage += `Error: ${err.response.data?.message || err.response.statusText}`;
    } else if (err.request) {
      errorMessage += 'No response from server.';
    } else {
      errorMessage += err.message;
    }
    alert(errorMessage);
  } finally {
    generatingReport.value = false;
  }
};

const formatDateForInput = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatDate = (dateString) => {
  if (!dateString) return 'No date';
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric'
  });
};

const handleCreateTask = async () => {
  loadingEmployees.value = true;
  try {
    await fetchEmployees();
    showCreateModal.value = true;
  } catch (err) {
    alert('Failed to load team members. Please try again.');
  } finally {
    loadingEmployees.value = false;
  }
};

const handleStatusUpdate = async (taskId, newStatus) => {
  try {
    await updateTaskStatus(taskId, newStatus);
  } catch (err) {
    alert('Failed to update task status. Please try again.');
  }
};

const handleEditTask = async (task) => {
  loadingEmployees.value = true;
  try {
    await fetchEmployees();
    editingTask.value = { ...task };
    showEditModal.value = true;
  } catch (err) {
    alert('Failed to load team members. Please try again.');
  } finally {
    loadingEmployees.value = false;
  }
};

const handleDeleteTask = async (taskId) => {
  if (confirm('Are you sure you want to delete this task?')) {
    try {
      await deleteTask(taskId);
    } catch (err) {
      alert('Failed to delete task. Please try again.');
    }
  }
};

const handleViewTask = (task) => {
  selectedTask.value = task;
  showDetailModal.value = true;
};

const handleSaveTask = async (taskData) => {
  try {
    if (editingTask.value?.id) {
      await updateTask(editingTask.value.id, taskData);
    } else {
      await createTask(taskData);
    }
    closeModals();
  } catch (err) {
    alert('Failed to save task. Please try again.');
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingTask.value = null;
};

const handleAddComment = async (taskId, comment) => {
  try {
    await addComment(taskId, comment);
  } catch (err) {
    alert('Failed to add comment. Please try again.');
  }
};

const handleDeleteComment = async (commentId, taskId) => {
  try {
    await deleteComment(commentId, taskId);
  } catch (err) {
    alert('Failed to delete comment. Please try again.');
  }
};
</script>

<style scoped>
/* =========================================
   CORE LAYOUT & BACKGROUND
   ========================================= */
.task-board {
  padding: 20px;
  min-height: 100vh;
  background-color: #f8fafc;
  color: #1e293b;
}

input, select, textarea {
  color: #1e293b;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 14px;
  transition: all 0.2s ease;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* =========================================
   HEADER SECTION
   ========================================= */
.board-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-left {
  flex: 1;
  min-width: 300px;
}

.header-left h1 {
  font-size: 28px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 15px;
}

.filter-controls {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.date-range-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.date-range-filter label {
  font-weight: 600;
  color: #334155;
}

.date-input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  color: #1e293b;
  background-color: #ffffff;
  min-width: 140px;
}

.btn-filter, .btn-clear {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-filter {
  background-color: #3b82f6;
  color: white;
}

.btn-filter:hover:not(:disabled) {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
}

.btn-filter:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-clear {
  background-color: #f1f5f9;
  color: #475569;
}

.btn-clear:hover {
  background-color: #e2e8f0;
}

.additional-filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  background-color: white;
  color: #1e293b;
  min-width: 150px;
  cursor: pointer;
}

.header-right {
  display: flex;
  align-items: flex-start;
}

.report-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-report, .btn-primary {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-report {
  background-color: #10b981;
  color: white;
}

.btn-report:hover:not(:disabled) {
  background-color: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
}

.btn-report:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
}

/* =========================================
   STATS SUMMARY — no shadow, clean border
   ========================================= */
.stats-summary {
  display: flex;
  gap: 16px;
  margin-bottom: 30px;
  flex-wrap: wrap;
}

.stat-card {
  background: white;
  padding: 16px 24px;
  border-radius: 12px;
  /* Shadow completely removed — clean 1px border keeps cards visible */
  box-shadow: none;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 130px;
  transition: border-color 0.2s ease;
}

.stat-card:hover {
  /* Subtle border darkening on hover instead of shadow lift */
  border-color: #cbd5e1;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  font-weight: 500;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

/* =========================================
   MODAL STYLES
   ========================================= */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.report-modal {
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  background-color: white;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
              0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: none;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  background-color: white;
  border-radius: 16px 16px 0 0;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-btn:hover {
  color: #0f172a;
  background-color: #f8fafc;
}

.modal-body {
  padding: 24px;
  background-color: white;
}

.report-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 24px;
  background-color: white;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #334155;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background-color: white;
  color: #1e293b;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.checkbox {
  margin-right: 8px;
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.selected-count {
  grid-column: 1 / -1;
  padding: 12px 16px;
  background-color: #f8fafc;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
  font-size: 14px;
  color: #475569;
}

.warning-text {
  color: #ef4444;
  font-weight: 500;
  margin-left: 8px;
}

.task-selection {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 2px solid #f1f5f9;
}

.task-selection h3 {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 16px;
}

.select-all-controls {
  margin-bottom: 16px;
  padding: 12px 16px;
  background-color: #f8fafc;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
}

.select-all-controls label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  color: #334155;
}

.tasks-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  background-color: white;
}

.task-select-item {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s;
}

.task-select-item:last-child {
  border-bottom: none;
}

.task-select-item:hover {
  background-color: #f8fafc;
}

.task-select-item.selected {
  background-color: #eff6ff;
}

.task-select-label {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
}

.task-checkbox {
  margin-top: 2px;
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.task-info {
  flex: 1;
}

.task-title {
  display: block;
  font-weight: 500;
  color: #0f172a;
  margin-bottom: 4px;
}

.task-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #64748b;
  flex-wrap: wrap;
}

.task-priority {
  font-weight: 600;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
}

.priority-critical { background-color: #fee2e2; color: #b91c1c; }
.priority-high     { background-color: #ffedd5; color: #c2410c; }
.priority-moderate { background-color: #fef9c3; color: #854d0e; }
.priority-low      { background-color: #dcfce7; color: #166534; }

.task-assignee {
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #f1f5f9;
  background-color: white;
  border-radius: 0 0 16px 16px;
}

.btn-secondary {
  background-color: #f1f5f9;
  color: #334155;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #e2e8f0;
  transform: translateY(-1px);
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* =========================================
   BOARD COLUMNS
   ========================================= */
.board-columns {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 20px;
}

.loading, .error {
  text-align: center;
  padding: 60px 40px;
  font-size: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  margin: 40px auto;
  max-width: 400px;
  color: #475569;
}

.error {
  color: #b91c1c;
  background-color: #fee2e2;
}

.tasks-list::-webkit-scrollbar { width: 8px; }
.tasks-list::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.tasks-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.tasks-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* =========================================
   RESPONSIVE
   ========================================= */
@media (max-width: 1200px) {
  .board-columns    { grid-template-columns: repeat(2, 1fr); }
  .report-options   { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .task-board       { padding: 16px; }
  .board-columns    { grid-template-columns: 1fr; }
  .board-header     { flex-direction: column; }
  .header-left, .header-right { width: 100%; }
  .stats-summary    { justify-content: center; }
  .stat-card        { min-width: calc(50% - 8px); flex: 1 1 auto; }
  .date-range-filter { flex-direction: column; align-items: stretch; }
  .date-range-filter label { margin-bottom: 5px; }
  .date-input       { width: 100%; }
  .additional-filters { flex-direction: column; }
  .filter-select    { width: 100%; }
  .report-actions   { flex-direction: column; width: 100%; }
  .btn-report, .btn-primary { width: 100%; justify-content: center; }
  .report-modal     { width: 95%; margin: 10px; }
  .task-meta        { flex-direction: column; gap: 4px; }
}

@media (max-width: 480px) {
  .task-board       { padding: 12px; }
  .stat-card        { min-width: 100%; }
  .stat-value       { font-size: 24px; }
  .modal-header     { padding: 16px 20px; }
  .modal-body       { padding: 20px; }
  .modal-footer     { padding: 16px 20px; flex-direction: column; }
  .btn-secondary, .btn-primary { width: 100%; }
  .form-control     { padding: 8px 10px; }
}
</style>