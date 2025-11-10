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
        @update-status="$emit('update-status', task.id, $event)"
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
  background-color: #edf2f7;
  border-radius: 8px;
  padding: 16px;
  min-height: 500px;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.column-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

.task-count {
  background-color: #cbd5e0;
  color: #2d3748;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.column-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 400px;
}

.empty-column {
  text-align: center;
  padding: 40px 20px;
  color: #a0aec0;
  font-size: 14px;
}
</style>