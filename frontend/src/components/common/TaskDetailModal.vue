<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h2>{{ task.title }}</h2>
          <span class="priority-badge" :class="`priority-${task.priority}`">
            {{ task.priority }}
          </span>
        </div>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="task-details">
          <div class="detail-row">
            <span class="label">Status:</span>
            <span class="status-badge" :class="`status-${task.status}`">
              {{ formatStatus(task.status) }}
            </span>
          </div>

          <div class="detail-row">
            <span class="label">Assigned To:</span>
            <div class="user-info">
              <span class="avatar">{{ getInitials(task.assigned_to.name) }}</span>
              <span>{{ task.assigned_to.name }}</span>
            </div>
          </div>

          <div class="detail-row">
            <span class="label">Created By:</span>
            <span>{{ task.created_by.name }}</span>
          </div>

          <div v-if="task.deadline" class="detail-row">
            <span class="label">Deadline:</span>
            <span :class="{ 'overdue': isOverdue }">
              {{ formatFullDate(task.deadline) }}
            </span>
          </div>

          <div v-if="task.description" class="detail-row description">
            <span class="label">Description:</span>
            <p>{{ task.description }}</p>
          </div>
        </div>

        <div class="comments-section">
          <h3>Comments ({{ task.comments?.length || 0 }})</h3>
          
          <div class="comment-form">
            <textarea
              v-model="newComment"
              placeholder="Add a comment..."
              rows="3"
              class="form-control"
            ></textarea>
            <button
              @click="handleAddComment"
              :disabled="!newComment.trim()"
              class="btn-primary"
            >
              Add Comment
            </button>
          </div>

          <div class="comments-list">
            <div
              v-for="comment in task.comments"
              :key="comment.id"
              class="comment-item"
            >
              <div class="comment-header">
                <div class="user-info">
                  <span class="avatar small">{{ getInitials(comment.user.name) }}</span>
                  <span class="name">{{ comment.user.name }}</span>
                  <span class="date">{{ formatRelativeTime(comment.created_at) }}</span>
                </div>
                <button
                  v-if="canDeleteComment(comment)"
                  @click="handleDeleteComment(comment.id)"
                  class="delete-comment-btn"
                  title="Delete comment"
                >
                  🗑️
                </button>
              </div>
              <p class="comment-text">{{ comment.comment }}</p>
            </div>

            <div v-if="!task.comments || task.comments.length === 0" class="no-comments">
              No comments yet. Be the first to comment!
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  task: Object,
  userRole: String,
});

const emit = defineEmits(['close', 'add-comment', 'delete-comment']);

const newComment = ref('');

const isOverdue = computed(() => {
  if (!props.task.deadline) return false;
  return new Date(props.task.deadline) < new Date() && props.task.status !== 'completed';
});

const canDeleteComment = (comment) => {
  // Users can only delete their own comments
  // In a real app, you'd check against the current user ID
  return true; // Simplified for this example
};

const handleAddComment = () => {
  if (newComment.value.trim()) {
    emit('add-comment', props.task.id, newComment.value);
    newComment.value = '';
  }
};

const handleDeleteComment = (commentId) => {
  if (confirm('Are you sure you want to delete this comment?')) {
    emit('delete-comment', commentId, props.task.id);
  }
};

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatFullDate = (date) => {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatRelativeTime = (date) => {
  const now = new Date();
  const past = new Date(date);
  const diffInSeconds = Math.floor((now - past) / 1000);

  if (diffInSeconds < 60) return 'just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  
  return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<style scoped>
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
}

.modal-content {
  background-color: white;
  border-radius: 12px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 8px 0;
}

.priority-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  text-transform: uppercase;
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

.close-btn {
  background: none;
  border: none;
  font-size: 32px;
  color: #a0aec0;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #2d3748;
}

.modal-body {
  padding: 24px;
}

.task-details {
  margin-bottom: 32px;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.detail-row.description {
  flex-direction: column;
  align-items: flex-start;
}

.label {
  font-weight: 600;
  color: #4a5568;
  min-width: 100px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.status-todo {
  background-color: #e2e8f0;
  color: #2d3748;
}

.status-in_progress {
  background-color: #bee3f8;
  color: #2c5282;
}

.status-under_review {
  background-color: #feebc8;
  color: #7c2d12;
}

.status-completed {
  background-color: #c6f6d5;
  color: #22543d;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #4299e1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
}

.avatar.small {
  width: 24px;
  height: 24px;
  font-size: 10px;
}

.overdue {
  color: #e53e3e;
  font-weight: 600;
}

.comments-section {
  border-top: 2px solid #e2e8f0;
  padding-top: 24px;
}

.comments-section h3 {
  font-size: 18px;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 16px 0;
}

.comment-form {
  margin-bottom: 24px;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 14px;
  margin-bottom: 8px;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.btn-primary {
  background-color: #4299e1;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background-color: #3182ce;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.comment-item {
  background-color: #f7fafc;
  padding: 12px;
  border-radius: 8px;
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.name {
  font-weight: 600;
  color: #2d3748;
  font-size: 14px;
}

.date {
  font-size: 12px;
  color: #a0aec0;
}

.delete-comment-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.delete-comment-btn:hover {
  opacity: 1;
}

.comment-text {
  margin: 0;
  color: #4a5568;
  font-size: 14px;
  line-height: 1.5;
}

.no-comments {
  text-align: center;
  padding: 40px 20px;
  color: #a0aec0;
  font-size: 14px;
}
</style>