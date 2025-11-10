<template>
  <div class="task-board">
    <div class="board-header">
      <h1>Task Board</h1>
      <button v-if="userRole === 'manager'" @click="handleCreateTask" class="btn-primary">
        + Create Task
      </button>
    </div>

    <div v-if="loading" class="loading">Loading tasks...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

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
    <TaskModal
      v-if="showCreateModal || showEditModal"
      :task="editingTask"
      :employees="employees"
      :loading-employees="loadingEmployees"
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
import { ref, computed, onMounted } from 'vue';
import { useTasks } from '../../composables/useTasks';
import TaskColumn from './TaskColumn.vue';
import TaskModal from './TaskModal.vue';
import TaskDetailModal from './TaskDetailModal.vue';

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
const editingTask = ref(null);
const selectedTask = ref(null);
const loadingEmployees = ref(false);

onMounted(async () => {
  const data = await fetchTasks();
  userRole.value = data.user_role;
});

const getTasksByStatus = (status) => {
  return tasks.value.filter(task => task.status === status);
};

const handleCreateTask = async () => {
  if (userRole.value === 'manager') {
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
  } else {
    showCreateModal.value = true;
  }
};

const handleStatusUpdate = async (taskId, newStatus) => {
  try {
    await updateTaskStatus(taskId, newStatus);
  } catch (err) {
    console.error('Failed to update status:', err);
  }
};

const handleEditTask = async (task) => {
  if (userRole.value === 'manager') {
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
  } else {
    editingTask.value = { ...task };
    showEditModal.value = true;
  }
};

const handleDeleteTask = async (taskId) => {
  if (confirm('Are you sure you want to delete this task?')) {
    try {
      await deleteTask(taskId);
    } catch (err) {
      console.error('Failed to delete task:', err);
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
  }
};

const handleDeleteComment = async (commentId, taskId) => {
  try {
    await deleteComment(commentId, taskId);
  } catch (err) {
    console.error('Failed to delete comment:', err);
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
  align-items: center;
  margin-bottom: 30px;
}

.board-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #2d3748;
}

.btn-primary {
  background-color: #4299e1;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #3182ce;
}

.board-columns {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  font-size: 16px;
}

.error {
  color: #e53e3e;
}

@media (max-width: 1200px) {
  .board-columns {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .board-columns {
    grid-template-columns: 1fr;
  }
}
</style>