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

    <!-- Report Generation Modal -->
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
          
          <!-- Task Selection -->
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

    <!-- Create/Edit Task Modal -->
    <TaskModal
      v-if="showCreateModal || showEditModal"
      :task="editingTask"
      :employees="employees"
      :loading-employees="loadingEmployees"
      :user-role="userRole"
      @close="closeModals"
      @save="handleSaveTask"
    />

    <!-- View Task Modal -->
    <TaskDetailModal
      v-if="showDetailModal"
      :task="selectedTask"
      :user-role="userRole"
      @close="showDetailModal = false"
      @add-comment="handleAddComment"
      @delete-comment="handleDeleteComment"
    />
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

// Filter state
const filter = ref({
  startDate: '',
  endDate: '',
  priority: '',
  assignedTo: '',
  status: ''
});

// Report options
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
  
  // Set default date range to last 30 days
  const endDate = new Date();
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - 30);
  
  filter.value.endDate = formatDateForInput(endDate);
  filter.value.startDate = formatDateForInput(startDate);
  
  // Set report dates to last week by default
  const weekAgo = new Date();
  weekAgo.setDate(weekAgo.getDate() - 7);
  reportOptions.value.startDate = formatDateForInput(weekAgo);
  reportOptions.value.endDate = formatDateForInput(new Date());
});

// Computed properties
const filteredTasks = computed(() => {
  if (!tasks.value) return [];
  
  return tasks.value.filter(task => {
    // Date filter
    if (filter.value.startDate && filter.value.endDate) {
      const taskDate = task.created_at ? new Date(task.created_at) : new Date();
      const startDate = new Date(filter.value.startDate);
      const endDate = new Date(filter.value.endDate);
      endDate.setHours(23, 59, 59, 999); // Include entire end day
      
      if (taskDate < startDate || taskDate > endDate) {
        return false;
      }
    }
    
    // Priority filter
    if (filter.value.priority && task.priority !== filter.value.priority) {
      return false;
    }
    
    // Assigned to filter
    if (filter.value.assignedTo && task.assigned_to.id !== filter.value.assignedTo) {
      return false;
    }
    
    // Status filter (if needed)
    if (filter.value.status && task.status !== filter.value.status) {
      return false;
    }
    
    return true;
  });
});

const selectedTasksForReport = computed(() => {
  return filteredTasks.value.filter(task => selectedReportTasks.value.includes(task.id));
});

const allTasksSelected = computed(() => {
  return filteredTasks.value.length > 0 && 
         selectedReportTasks.value.length === filteredTasks.value.length;
});

const completedTasksCount = computed(() => {
  return filteredTasks.value.filter(task => task.status === 'completed').length;
});

const inProgressTasksCount = computed(() => {
  return filteredTasks.value.filter(task => task.status === 'in_progress').length;
});

const overdueTasksCount = computed(() => {
  return filteredTasks.value.filter(task => {
    if (!task.deadline || task.status === 'completed') return false;
    return new Date(task.deadline) < new Date();
  }).length;
});

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

// Watch for filter changes to update selected tasks
watch(filteredTasks, (newTasks) => {
  // Auto-select all tasks when filter changes
  selectedReportTasks.value = newTasks.map(task => task.id);
}, { deep: true });

// Methods
const getTasksByStatus = (status) => {
  return filteredTasks.value.filter(task => task.status === status);
};

const applyDateFilter = () => {
  if (!filter.value.startDate || !filter.value.endDate) {
    alert('Please select both start and end dates');
    return;
  }
  
  // Date filter is already applied in computed property
  console.log('Date filter applied:', filter.value.startDate, 'to', filter.value.endDate);
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
    
    // Prepare report data
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

    console.log('Generating report with data:', reportData);

    // Call the API endpoint
    const response = await axios.post('/api/tasks/reports/generate', reportData, {
      responseType: 'blob', // Important for file downloads
      headers: {
        'Content-Type': 'application/json',
        'Accept': reportOptions.value.format === 'pdf' 
          ? 'application/pdf' 
          : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    });

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    const fileName = `task-report-${new Date().toISOString().split('T')[0]}.${reportOptions.value.format}`;
    
    link.href = url;
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    
    // Clean up
    link.remove();
    window.URL.revokeObjectURL(url);
    
    showReportModal.value = false;
    alert('Report generated successfully!');
    
  } catch (err) {
    console.error('Failed to generate report:', err);
    let errorMessage = 'Failed to generate report. ';
    
    if (err.response) {
      if (err.response.status === 404) {
        errorMessage += 'Report endpoint not found. Please check if the backend route is configured.';
      } else if (err.response.status === 500) {
        errorMessage += 'Server error occurred. Please try again later.';
      } else {
        errorMessage += `Error: ${err.response.data?.message || err.response.statusText}`;
      }
    } else if (err.request) {
      errorMessage += 'No response from server. Please check your connection.';
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
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};

const handleCreateTask = async () => {
  loadingEmployees.value = true;
  try {
    await fetchEmployees();
    showCreateModal.value = true;
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    alert('Failed to load team members. Please try again.');
  } finally {
    loadingEmployees.value = false;
  }
};

const handleStatusUpdate = async (taskId, newStatus) => {
  try {
    await updateTaskStatus(taskId, newStatus);
  } catch (err) {
    console.error('Failed to update status:', err);
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
    console.error('Failed to fetch employees:', err);
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
      console.error('Failed to delete task:', err);
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
    console.error('Failed to save task:', err);
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
    console.error('Failed to add comment:', err);
    alert('Failed to add comment. Please try again.');
  }
};

const handleDeleteComment = async (commentId, taskId) => {
  try {
    await deleteComment(commentId, taskId);
  } catch (err) {
    console.error('Failed to delete comment:', err);
    alert('Failed to delete comment. Please try again.');
  }
};
</script>

<style scoped>
.task-board {
  padding: 20px;
  min-height: 100vh;
  background-color: #f5f7fa;
}

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
  color: #2d3748;
  margin-bottom: 15px;
}

.filter-controls {
  background: white;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.date-range-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.date-range-filter label {
  font-weight: 500;
  color: #4a5568;
}

.date-input {
  padding: 6px 10px;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 14px;
}

.date-input:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.1);
}

.btn-filter, .btn-clear {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-filter {
  background-color: #4299e1;
  color: white;
}

.btn-filter:hover:not(:disabled) {
  background-color: #3182ce;
}

.btn-filter:disabled {
  background-color: #a0aec0;
  cursor: not-allowed;
}

.btn-clear {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn-clear:hover {
  background-color: #cbd5e0;
}

.additional-filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 6px 10px;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 14px;
  background-color: white;
  min-width: 150px;
}

.filter-select:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.1);
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
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-report {
  background-color: #48bb78;
  color: white;
}

.btn-report:hover:not(:disabled) {
  background-color: #38a169;
}

.btn-report:disabled {
  background-color: #a0aec0;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #4299e1;
  color: white;
}

.btn-primary:hover {
  background-color: #3182ce;
}

/* Stats Summary */
.stats-summary {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.stat-card {
  background: white;
  padding: 15px 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 120px;
}

.stat-label {
  font-size: 12px;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #2d3748;
}

/* Report Modal - UPDATED WITH WHITE BACKGROUND */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.report-modal {
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  background-color: white !important; /* Force white background */
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  border: 1px solid #e2e8f0;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background-color: white;
  border-radius: 12px 12px 0 0;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #a0aec0;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: color 0.2s, background-color 0.2s;
}

.close-btn:hover {
  color: #2d3748;
  background-color: #f7fafc;
}

.modal-body {
  padding: 24px;
  background-color: white;
}

.report-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 24px;
  background-color: white;
}

.form-group {
  margin-bottom: 16px;
  background-color: transparent;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  background-color: transparent;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 14px;
  background-color: white;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
  background-color: white;
}

.checkbox {
  margin-right: 8px;
  accent-color: #4299e1;
}

.selected-count {
  grid-column: 1 / -1;
  padding: 12px 16px;
  background-color: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 14px;
  color: #4a5568;
  background-color: white;
}

.warning-text {
  color: #e53e3e;
  font-weight: 500;
}

/* Task Selection */
.task-selection {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 2px solid #e2e8f0;
  background-color: white;
}

.task-selection h3 {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 16px;
  background-color: transparent;
}

.select-all-controls {
  margin-bottom: 16px;
  padding: 12px 16px;
  background-color: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.tasks-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: white;
}

.task-select-item {
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
  transition: background-color 0.2s;
  background-color: white;
}

.task-select-item:last-child {
  border-bottom: none;
}

.task-select-item:hover {
  background-color: #f7fafc;
}

.task-select-item.selected {
  background-color: #ebf8ff;
}

.task-select-label {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  background-color: transparent;
}

.task-checkbox {
  margin-top: 2px;
  accent-color: #4299e1;
}

.task-info {
  flex: 1;
  background-color: transparent;
}

.task-title {
  display: block;
  font-weight: 500;
  color: #2d3748;
  margin-bottom: 4px;
  background-color: transparent;
}

.task-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #718096;
  background-color: transparent;
}

.task-priority {
  font-weight: 600;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
}

.priority-critical { background-color: #fed7d7; color: #c53030; }
.priority-high { background-color: #feebc8; color: #c05621; }
.priority-moderate { background-color: #fefcbf; color: #975a16; }
.priority-low { background-color: #c6f6d5; color: #276749; }

.task-assignee {
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e2e8f0;
  background-color: white;
  border-radius: 0 0 12px 12px;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #2d3748;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #cbd5e0;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Board Columns */
.board-columns {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  font-size: 16px;
  background-color: transparent;
}

.error {
  color: #e53e3e;
}

/* Scrollbar styling for the task list */
.tasks-list::-webkit-scrollbar {
  width: 8px;
}

.tasks-list::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.tasks-list::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 4px;
}

.tasks-list::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .board-columns {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .report-options {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .board-columns {
    grid-template-columns: 1fr;
  }
  
  .board-header {
    flex-direction: column;
  }
  
  .header-left, .header-right {
    width: 100%;
  }
  
  .stats-summary {
    justify-content: center;
  }
  
  .date-range-filter {
    flex-direction: column;
    align-items: stretch;
  }
  
  .date-range-filter label {
    margin-bottom: 5px;
  }
  
  .date-input {
    width: 100%;
  }
  
  .additional-filters {
    flex-direction: column;
  }
  
  .filter-select {
    width: 100%;
  }
  
  .report-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .btn-report, .btn-primary {
    width: 100%;
    justify-content: center;
  }
  
  .report-modal {
    width: 95%;
    margin: 10px;
  }
}

@media (max-width: 480px) {
  .task-board {
    padding: 10px;
  }
  
  .stat-card {
    min-width: 100px;
    padding: 12px 15px;
  }
  
  .stat-value {
    font-size: 20px;
  }
  
  .modal-header {
    padding: 16px 20px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-footer {
    padding: 16px 20px;
  }
  
  .form-control {
    padding: 8px 10px;
  }
}
</style>