<template>
  <div class="task-column">
    <div class="column-header">
      <h3>{{ title }}</h3>
      <span class="task-count">{{ tasks.length }}</span>
    </div>
    
    <div 
      class="column-content"
      @dragover.prevent
      @drop="handleDrop"
    >
      <TaskCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        :user-role="userRole"
        @update-status="handleStatusUpdate"
        @edit="$emit('edit-task', task)"
        @delete="$emit('delete-task', task.id)"
        @view="$emit('view-task', task)"
      />
      
      <div v-if="tasks.length === 0" class="empty-column">
        No tasks
      </div>
    </div>
  </div>
</template>

<script setup>
import TaskCard from './TaskCard.vue';

const props = defineProps({
  title: String,
  status: String,
  tasks: Array,
  userRole: String,
});

const emit = defineEmits(['update-status', 'edit-task', 'delete-task', 'view-task']);

const handleStatusUpdate = (taskId, newStatus) => {
  emit('update-status', taskId, props.status);
};

const handleDrop = (event) => {
  event.preventDefault();
  const taskId = event.dataTransfer.getData('taskId');
  if (taskId) {
    emit('update-status', parseInt(taskId), props.status);
  }
};
</script>

<style scoped>
.task-column {
  background-color: #f7fafc;
  border-radius: 8px;
  padding: 16px;
  min-height: 500px;
  border: 1px solid #e2e8f0;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.column-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.task-count {
  background-color: #e2e8f0;
  color: #4a5568;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  min-width: 24px;
  text-align: center;
}

.column-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 400px;
}

.empty-column {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #a0aec0;
  font-size: 14px;
  font-style: italic;
  border: 2px dashed #cbd5e0;
  border-radius: 6px;
  background-color: #f8fafc;
}
</style>