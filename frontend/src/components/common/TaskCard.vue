<template>
  <div 
    class="task-card"
    :class="[priorityClass, { 'loading': loading }]"
    draggable="true"
    @dragstart="handleDragStart"
    @click="!loading && $emit('view')"
    role="button"
    tabindex="0"
    @keydown.enter="!loading && $emit('view')"
    @keydown.space="!loading && $emit('view')"
    :aria-label="getAriaLabel"
  >
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>

    <div class="card-header">
      <div class="status-indicators">
        <span class="priority-badge" :class="`priority-${task.priority}`">
          {{ task.priority }}
        </span>
        <span v-if="isOverdue" class="overdue-badge">OVERDUE</span>
      </div>
      <div class="card-actions" @click.stop>
        <button 
          v-if="userRole === 'manager'" 
          @click="!loading && $emit('edit')"
          class="action-btn"
          title="Edit task"
          :disabled="loading"
        >
          ✏️
        </button>
        <button 
          v-if="userRole === 'manager'" 
          @click="!loading && $emit('delete')"
          class="action-btn"
          title="Delete task"
          :disabled="loading"
        >
          🗑️
        </button>
      </div>
    </div>

    <h4 class="task-title">{{ task.title }}</h4>
    
    <p 
      v-if="task.description" 
      class="task-description"
      :title="task.description.length > 100 ? task.description : ''"
    >
      {{ truncateText(task.description, 100) }}
    </p>

    <!-- Tags Section -->
    <div v-if="task.tags && task.tags.length" class="task-tags">
      <span 
        v-for="tag in task.tags.slice(0, 2)" 
        :key="tag" 
        class="tag"
      >
        {{ tag }}
      </span>
      <span v-if="task.tags.length > 2" class="tag-more">
        +{{ task.tags.length - 2 }}
      </span>
    </div>

    <div class="card-footer">
      <div class="assigned-to">
        <span class="avatar">{{ getInitials(task.assigned_to.name) }}</span>
        <span class="name">{{ task.assigned_to.name }}</span>
      </div>
      
      <div v-if="task.deadline" class="deadline" :class="{ 'overdue': isOverdue }">
        📅 {{ formatDate(task.deadline) }}
      </div>
    </div>

    <div v-if="task.comments && task.comments.length > 0" class="comment-indicator">
      💬 {{ task.comments.length }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  task: {
    type: Object,
    required: true,
    validator: (value) => {
      return value && value.title && value.priority && value.assigned_to;
    }
  },
  userRole: {
    type: String,
    required: true,
    validator: (value) => ['manager', 'employee'].includes(value)
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update-status', 'edit', 'delete', 'view']);

const priorityClass = computed(() => `priority-border-${props.task.priority}`);

const isOverdue = computed(() => {
  if (!props.task.deadline) return false;
  return new Date(props.task.deadline) < new Date() && props.task.status !== 'completed';
});

const getAriaLabel = computed(() => {
  const base = `Task: ${props.task.title}. Priority: ${props.task.priority}.`;
  const assigned = `Assigned to: ${props.task.assigned_to.name}.`;
  const deadline = props.task.deadline 
    ? `Due: ${formatDate(props.task.deadline)}.` 
    : 'No deadline.';
  const overdue = isOverdue.value ? 'This task is overdue.' : '';
  const comments = props.task.comments && props.task.comments.length > 0 
    ? `Has ${props.task.comments.length} comments.` 
    : '';
  
  return `${base} ${assigned} ${deadline} ${overdue} ${comments}`;
});

const handleDragStart = (event) => {
  if (props.loading) {
    event.preventDefault();
    return;
  }
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('taskId', props.task.id.toString());
};

const getInitials = (name) => {
  if (!name) return '??';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const formatDate = (date) => {
  if (!date) return 'No date';
  
  const taskDate = new Date(date);
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  // Reset time part for date comparison
  today.setHours(0, 0, 0, 0);
  tomorrow.setHours(0, 0, 0, 0);
  taskDate.setHours(0, 0, 0, 0);
  
  if (taskDate.getTime() === today.getTime()) {
    return 'Today';
  } else if (taskDate.getTime() === tomorrow.getTime()) {
    return 'Tomorrow';
  } else {
    return taskDate.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
    });
  }
};

const truncateText = (text, length) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};
</script>

<style scoped>
.task-card {
  background-color: white;
  border-radius: 8px;
  padding: 16px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  border-left: 4px solid;
  position: relative;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.task-card:focus {
  outline: 2px solid #4299e1;
  outline-offset: 2px;
}

.task-card.loading {
  opacity: 0.7;
  pointer-events: none;
  cursor: not-allowed;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  z-index: 10;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #4299e1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Priority Border Colors */
.priority-border-critical {
  border-left-color: #e53e3e;
}

.priority-border-high {
  border-left-color: #ed8936;
}

.priority-border-moderate {
  border-left-color: #ecc94b;
}

.priority-border-low {
  border-left-color: #48bb78;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
  gap: 8px;
}

.status-indicators {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
}

.priority-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  white-space: nowrap;
}

.priority-critical {
  background-color: #fed7d7;
  color: #c53030;
}

.priority-high {
  background-color: #feebc8;
  color: #c05621;
}

.priority-moderate {
  background-color: #fefcbf;
  color: #975a16;
}

.priority-low {
  background-color: #c6f6d5;
  color: #276749;
}

.overdue-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background-color: #fed7d7;
  color: #c53030;
  text-transform: uppercase;
  white-space: nowrap;
}

.card-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 4px;
  opacity: 0.6;
  transition: opacity 0.2s;
  border-radius: 4px;
}

.action-btn:hover:not(:disabled) {
  opacity: 1;
  background-color: #f7fafc;
}

.action-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.task-title {
  font-size: 15px;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 8px 0;
  line-height: 1.4;
  word-wrap: break-word;
}

.task-description {
  font-size: 13px;
  color: #718096;
  margin: 0 0 12px 0;
  line-height: 1.5;
  word-wrap: break-word;
}

.task-tags {
  display: flex;
  gap: 4px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.tag {
  font-size: 11px;
  padding: 2px 6px;
  background-color: #e2e8f0;
  color: #4a5568;
  border-radius: 4px;
  white-space: nowrap;
}

.tag-more {
  font-size: 11px;
  color: #718096;
  padding: 2px 4px;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
  gap: 8px;
}

.assigned-to {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background-color: #4299e1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.name {
  font-size: 13px;
  color: #4a5568;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.deadline {
  font-size: 12px;
  color: #718096;
  white-space: nowrap;
  flex-shrink: 0;
}

.deadline.overdue {
  color: #e53e3e;
  font-weight: 600;
}

.comment-indicator {
  position: absolute;
  top: 12px;
  right: 12px;
  font-size: 12px;
  color: #718096;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 4px;
}

/* Responsive Design */
@media (max-width: 480px) {
  .task-card {
    padding: 12px;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .card-actions {
    align-self: flex-end;
  }
  
  .card-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .assigned-to {
    width: 100%;
  }
}
</style>