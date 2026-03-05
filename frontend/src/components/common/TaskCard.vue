<template>
  <div
    class="task-card"
    :class="[priorityClass, { 'loading': loading, 'expanded': expanded }]"
    draggable="true"
    @dragstart="handleDragStart"
    @click="!loading && !expanded && $emit('view')"
    role="button"
    tabindex="0"
    @keydown.enter="!loading && !expanded && $emit('view')"
    @keydown.space="!loading && !expanded && $emit('view')"
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
        <span v-if="task.subtasks && task.subtasks.length > 0" class="subtask-badge">
          {{ completedSubtasks }}/{{ task.subtasks.length }} subtasks
        </span>
      </div>
      <div class="card-actions" @click.stop>
        <button
          v-if="canEdit"
          @click="!loading && $emit('edit')"
          class="action-btn"
          title="Edit task"
          :disabled="loading"
        >
          ✏️
        </button>
        <button
          v-if="canDelete"
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
        <div class="assigned-info">
          <span class="name">{{ task.assigned_to.name }}</span>
          <span v-if="task.created_by" class="created-by">
            Created by: {{ getCreatedByName() }}
          </span>
        </div>
      </div>
     
      <div class="footer-right">
        <div v-if="task.deadline" class="deadline" :class="{ 'overdue': isOverdue }">
          📅 {{ formatDate(task.deadline) }}
        </div>
        <button @click.stop="openTeleportModal" class="expand-btn" :disabled="loading">
          ▼ Show Details
        </button>
      </div>
    </div>

    <!-- Quick Indicators (always visible) -->
    <div v-if="!expanded" class="quick-indicators">
      <span v-if="task.subtasks && task.subtasks.length > 0" class="indicator">
        ☑️ {{ completedSubtasks }}/{{ task.subtasks.length }}
      </span>
      <span v-if="task.comments && task.comments.length > 0" class="indicator">
        💬 {{ task.comments.length }}
      </span>
      <span v-if="task.linked_items && task.linked_items.length > 0" class="indicator">
        🔗 {{ task.linked_items.length }}
      </span>
    </div>

    <!-- Teleport Modal for Expanded Details -->
    <Teleport to="body">
      <div v-if="showTeleportModal" class="teleport-overlay" @click.self="closeTeleportModal">
        <div class="teleport-modal" :class="{ 'closing': isClosing }">
          <div class="teleport-header">
            <div class="header-left">
              <div class="task-title-row">
                <h3 class="modal-task-title">{{ task.title }}</h3>
                <span class="modal-priority-badge" :class="`priority-${task.priority}`">
                  {{ task.priority }}
                </span>
                <span v-if="isOverdue" class="modal-overdue-badge">OVERDUE</span>
              </div>
              <div class="task-meta">
                <div class="assigned-to-modal">
                  <span class="avatar-modal">{{ getInitials(task.assigned_to.name) }}</span>
                  <div class="assigned-info-modal">
                    <span class="name-modal">Assigned to: {{ task.assigned_to.name }}</span>
                    <span v-if="task.created_by" class="created-by-modal">
                      Created by: {{ getCreatedByName() }}
                    </span>
                  </div>
                </div>
                <div v-if="task.deadline" class="deadline-modal" :class="{ 'overdue': isOverdue }">
                  📅 Due: {{ formatDate(task.deadline) }}
                </div>
              </div>
            </div>
            <div class="header-actions">
              <button
                v-if="canEdit"
                @click="handleEdit"
                class="modal-action-btn"
                title="Edit task"
              >
                ✏️ Edit
              </button>
              <button
                v-if="canDelete"
                @click="handleDelete"
                class="modal-action-btn delete"
                title="Delete task"
              >
                🗑️ Delete
              </button>
              <button @click="closeTeleportModal" class="modal-close-btn" title="Close">
                ✕
              </button>
            </div>
          </div>

          <div class="teleport-content">
            <div class="tabs">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="['tab-btn', { active: activeTab === tab.id }]"
              >
                {{ tab.label }}
                <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
              </button>
            </div>

            <!-- Description Tab -->
            <div v-if="activeTab === 'description'" class="tab-content">
              <div class="description-full">
                <h5>Description</h5>
                <p v-if="task.description">{{ task.description }}</p>
                <p v-else class="no-content">No description provided.</p>
              </div>
            </div>

            <!-- Subtasks Tab -->
            <div v-if="activeTab === 'subtasks'" class="tab-content">
              <div class="subtasks-section">
                <div class="section-header">
                  <h5>Subtasks ({{ completedSubtasks }}/{{ task.subtasks?.length || 0 }})</h5>
                  <button
                    v-if="canEdit"
                    @click="showAddSubtask = true"
                    class="add-btn"
                  >
                    + Add Subtask
                  </button>
                </div>

                <!-- Add Subtask Form -->
                <div v-if="showAddSubtask" class="add-form">
                  <input
                    v-model="newSubtask.title"
                    type="text"
                    placeholder="Subtask title"
                    class="form-input"
                    @keydown.enter="addSubtask"
                  />
                  <select v-model="newSubtask.priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                  </select>
                  <div class="form-actions">
                    <button @click="addSubtask" class="btn-primary-sm">Add</button>
                    <button @click="showAddSubtask = false" class="btn-secondary-sm">Cancel</button>
                  </div>
                </div>

                <!-- Subtasks List -->
                <div v-if="task.subtasks && task.subtasks.length > 0" class="subtasks-list">
                  <div
                    v-for="subtask in task.subtasks"
                    :key="subtask.id"
                    class="subtask-item"
                  >
                    <input
                      type="checkbox"
                      :checked="subtask.status === 'completed'"
                      @change="toggleSubtask(subtask)"
                      class="subtask-checkbox"
                    />
                    <div class="subtask-content">
                      <span :class="{ 'completed': subtask.status === 'completed' }">
                        {{ subtask.title }}
                      </span>
                      <span class="subtask-meta">
                        <span :class="`priority-text priority-${subtask.priority}`">
                          {{ subtask.priority }}
                        </span>
                        <span v-if="subtask.assignee" class="assignee-name">
                          {{ subtask.assignee.name }}
                        </span>
                      </span>
                    </div>
                    <button
                      v-if="canEdit"
                      @click="deleteSubtask(subtask)"
                      class="delete-subtask-btn"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
                <div v-else class="no-content">
                  No subtasks yet. Click "Add Subtask" to create one.
                </div>
              </div>
            </div>

            <!-- Comments Tab -->
            <div v-if="activeTab === 'comments'" class="tab-content">
              <div class="comments-section">
                <h5>Comments ({{ task.comments?.length || 0 }})</h5>
                
                <!-- Add Comment Form -->
                <div class="add-comment-form">
                  <textarea
                    v-model="newComment"
                    rows="3"
                    placeholder="Add a comment..."
                    class="comment-textarea"
                    :disabled="addingComment"
                  ></textarea>
                  <button
                    @click="addComment"
                    :disabled="addingComment || !newComment.trim()"
                    class="btn-primary-sm"
                  >
                    <span v-if="addingComment">Adding...</span>
                    <span v-else>Add Comment</span>
                  </button>
                </div>

                <!-- Comments List -->
                <div v-if="task.comments && task.comments.length > 0" class="comments-list">
                  <div
                    v-for="comment in task.comments"
                    :key="comment.id"
                    class="comment-item"
                  >
                    <div class="comment-header">
                      <div class="user-info">
                        <span class="avatar-sm">{{ getInitials(comment.user?.name) }}</span>
                        <span class="user-name">{{ comment.user?.name }}</span>
                        <span class="comment-date">{{ formatRelativeTime(comment.created_at) }}</span>
                      </div>
                      <button
                        v-if="comment.user_id === authStore.user?.id"
                        @click="deleteComment(comment)"
                        class="delete-btn-sm"
                        :disabled="deletingCommentId === comment.id"
                      >
                        <span v-if="deletingCommentId === comment.id">Deleting...</span>
                        <span v-else>Delete</span>
                      </button>
                    </div>
                    <p class="comment-text">{{ comment.comment }}</p>
                  </div>
                </div>
                <div v-else class="no-content">
                  No comments yet. Be the first to comment!
                </div>
              </div>
            </div>

            <!-- History/Activity Tab -->
            <div v-if="activeTab === 'history'" class="tab-content">
              <div class="history-section">
                <h5>Activity History</h5>
                
                <div v-if="task.history && task.history.length > 0" class="history-list">
                  <div
                    v-for="entry in task.history"
                    :key="entry.id"
                    class="history-item"
                  >
                    <div class="history-icon" :class="`icon-${entry.type}`">
                      {{ getHistoryIcon(entry.type) }}
                    </div>
                    <div class="history-content">
                      <p class="history-text">
                        <strong>{{ entry.user?.name }}</strong> {{ entry.action }}
                        <span v-if="entry.field_changed" class="field-changed">
                          changed <strong>{{ entry.field_changed }}</strong>
                          <span v-if="entry.old_value">
                            from <span class="old-value">{{ entry.old_value }}</span>
                          </span>
                          <span v-if="entry.new_value">
                            to <span class="new-value">{{ entry.new_value }}</span>
                          </span>
                        </span>
                      </p>
                      <span class="history-date">{{ formatRelativeTime(entry.created_at) }}</span>
                    </div>
                  </div>
                </div>
                <div v-else class="no-content">
                  No activity history available.
                </div>
              </div>
            </div>

            <!-- Work Logs Tab -->
            <div v-if="activeTab === 'worklogs'" class="tab-content">
              <div class="worklogs-section">
                <div class="section-header">
                  <h5>Work Logs ({{ totalTimeLogged }})</h5>
                  <button
                    @click="showAddWorklog = true"
                    class="add-btn"
                  >
                    + Log Time
                  </button>
                </div>

                <!-- Add Worklog Form -->
                <div v-if="showAddWorklog" class="add-form">
                  <input
                    v-model="newWorklog.hours"
                    type="number"
                    min="0"
                    step="0.5"
                    placeholder="Hours"
                    class="form-input"
                  />
                  <textarea
                    v-model="newWorklog.description"
                    rows="2"
                    placeholder="What did you work on?"
                    class="form-textarea"
                  ></textarea>
                  <div class="form-actions">
                    <button @click="addWorklog" class="btn-primary-sm">Log Time</button>
                    <button @click="showAddWorklog = false" class="btn-secondary-sm">Cancel</button>
                  </div>
                </div>

                <!-- Worklogs List -->
                <div v-if="task.worklogs && task.worklogs.length > 0" class="worklogs-list">
                  <div
                    v-for="worklog in task.worklogs"
                    :key="worklog.id"
                    class="worklog-item"
                  >
                    <div class="worklog-header">
                      <div class="user-info">
                        <span class="avatar-sm">{{ getInitials(worklog.user?.name) }}</span>
                        <span class="user-name">{{ worklog.user?.name }}</span>
                        <span class="worklog-time">{{ worklog.hours }}h</span>
                      </div>
                      <span class="worklog-date">{{ formatRelativeTime(worklog.created_at) }}</span>
                    </div>
                    <p class="worklog-description">{{ worklog.description }}</p>
                  </div>
                </div>
                <div v-else class="no-content">
                  No work logged yet. Click "Log Time" to add an entry.
                </div>
              </div>
            </div>

            <!-- Linked Work Tab -->
            <div v-if="activeTab === 'linked'" class="tab-content">
              <div class="linked-section">
                <div class="section-header">
                  <h5>Linked Work Items ({{ task.linked_items?.length || 0 }})</h5>
                  <button
                    v-if="canEdit"
                    @click="showLinkWork = true"
                    class="add-btn"
                  >
                    + Link Work
                  </button>
                </div>

                <!-- Link Work Form -->
                <div v-if="showLinkWork" class="add-form">
                  <select v-model="newLink.type" class="form-select">
                    <option value="blocks">Blocks</option>
                    <option value="blocked_by">Blocked By</option>
                    <option value="relates_to">Relates To</option>
                    <option value="duplicates">Duplicates</option>
                  </select>
                  <input
                    v-model="newLink.task_id"
                    type="text"
                    placeholder="Task ID or search..."
                    class="form-input"
                  />
                  <div class="form-actions">
                    <button @click="addLinkedWork" class="btn-primary-sm">Link</button>
                    <button @click="showLinkWork = false" class="btn-secondary-sm">Cancel</button>
                  </div>
                </div>

                <!-- Linked Items List -->
                <div v-if="task.linked_items && task.linked_items.length > 0" class="linked-list">
                  <div
                    v-for="item in task.linked_items"
                    :key="item.id"
                    class="linked-item"
                  >
                    <div class="link-type" :class="`type-${item.link_type}`">
                      {{ formatLinkType(item.link_type) }}
                    </div>
                    <div class="linked-content">
                      <span class="linked-title">{{ item.linked_task?.title || 'Unknown Task' }}</span>
                      <span class="linked-meta">
                        <span :class="`status-badge status-${item.linked_task?.status}`">
                          {{ item.linked_task?.status }}
                        </span>
                        <span :class="`priority-text priority-${item.linked_task?.priority}`">
                          {{ item.linked_task?.priority }}
                        </span>
                      </span>
                    </div>
                    <button
                      v-if="canEdit"
                      @click="removeLinkedWork(item)"
                      class="delete-btn-sm"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
                <div v-else class="no-content">
                  No linked work items. Click "Link Work" to connect related tasks.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

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
    validator: (value) => ['manager', 'employee', 'admin'].includes(value)
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update-status', 'edit', 'delete', 'view', 'refresh']);

const authStore = useAuthStore();
const showTeleportModal = ref(false);
const isClosing = ref(false);
const activeTab = ref('description');
const newComment = ref('');
const addingComment = ref(false);
const deletingCommentId = ref(null);
const showAddSubtask = ref(false);
const showAddWorklog = ref(false);
const showLinkWork = ref(false);

const newSubtask = ref({
  title: '',
  priority: 'medium'
});

const newWorklog = ref({
  hours: '',
  description: ''
});

const newLink = ref({
  link_type: 'relates_to',
  linked_task_id: ''
});

const tabs = computed(() => [
  { id: 'description', label: 'Description' },
  { id: 'subtasks', label: 'Subtasks', count: props.task.subtasks?.length || 0 },
  { id: 'comments', label: 'Comments', count: props.task.comments?.length || 0 },
  { id: 'history', label: 'History', count: props.task.history?.length || 0 },
  { id: 'worklogs', label: 'Work Logs', count: props.task.worklogs?.length || 0 },
  { id: 'linked', label: 'Linked Work', count: props.task.linked_items?.length || 0 }
]);

// Authorization logic updated for universal access
const canEdit = computed(() => {
  const isCreator = props.task.created_by?.id === authStore.user?.id;
  const isAssigned = props.task.assigned_to?.id === authStore.user?.id;
  const isAdmin = props.userRole === 'admin';
  const isManager = props.userRole === 'manager';
  
  // Creator can always edit
  // Assigned user can edit if they are the assignee
  // Admin/Manager can edit if they are in the same business
  return isCreator || isAssigned || isAdmin || isManager;
});

const canDelete = computed(() => {
  // Only creator or admin can delete tasks
  const isCreator = props.task.created_by?.id === authStore.user?.id;
  const isAdmin = props.userRole === 'admin';
  
  return isCreator || isAdmin;
});

const priorityClass = computed(() => `priority-border-${props.task.priority}`);

const isOverdue = computed(() => {
  if (!props.task.deadline) return false;
  return new Date(props.task.deadline) < new Date() && props.task.status !== 'completed';
});

const completedSubtasks = computed(() => {
  if (!props.task.subtasks) return 0;
  return props.task.subtasks.filter(st => st.status === 'completed').length;
});

const totalTimeLogged = computed(() => {
  if (!props.task.worklogs) return '0h';
  const total = props.task.worklogs.reduce((sum, log) => sum + (log.hours || 0), 0);
  return `${total}h`;
});

const getAriaLabel = computed(() => {
  const base = `Task: ${props.task.title}. Priority: ${props.task.priority}.`;
  const assigned = `Assigned to: ${props.task.assigned_to.name}.`;
  const creator = props.task.created_by ? `Created by: ${getCreatedByName()}.` : '';
  const deadline = props.task.deadline
    ? `Due: ${formatDate(props.task.deadline)}.`
    : 'No deadline.';
  const overdue = isOverdue.value ? 'This task is overdue.' : '';
  
  return `${base} ${assigned} ${creator} ${deadline} ${overdue}`;
});

const getCreatedByName = () => {
  if (props.task.created_by?.name) {
    return props.task.created_by.name;
  } else if (props.task.created_by?.first_name && props.task.created_by?.last_name) {
    return `${props.task.created_by.first_name} ${props.task.created_by.last_name}`;
  }
  return 'Unknown';
};

const openTeleportModal = () => {
  showTeleportModal.value = true;
  activeTab.value = 'description';
  document.body.style.overflow = 'hidden'; // Prevent scrolling
};

const closeTeleportModal = () => {
  isClosing.value = true;
  setTimeout(() => {
    showTeleportModal.value = false;
    isClosing.value = false;
    document.body.style.overflow = ''; // Re-enable scrolling
  }, 300);
};

const handleEdit = () => {
  closeTeleportModal();
  emit('edit');
};

const handleDelete = () => {
  if (confirm('Are you sure you want to delete this task?')) {
    closeTeleportModal();
    emit('delete');
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Escape' && showTeleportModal.value) {
    closeTeleportModal();
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = ''; // Cleanup
});

const handleDragStart = (event) => {
  if (props.loading || showTeleportModal.value) {
    event.preventDefault();
    return;
  }
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('taskId', props.task.id.toString());
};

const addComment = async () => {
  if (!newComment.value.trim()) return;
  addingComment.value = true;
  try {
    const response = await axios.post(`/api/tasks/${props.task.id}/comments`, {
      comment: newComment.value
    });
    if (!props.task.comments) props.task.comments = [];
    props.task.comments.unshift(response.data.comment);
    newComment.value = '';
    emit('refresh');
  } catch (err) {
    console.error('Failed to add comment:', err);
    alert('Failed to add comment. Please try again.');
  } finally {
    addingComment.value = false;
  }
};

const deleteComment = async (comment) => {
  if (!confirm('Are you sure you want to delete this comment?')) return;
  deletingCommentId.value = comment.id;
  try {
    await axios.delete(`/api/comments/${comment.id}`);
    props.task.comments = props.task.comments.filter(c => c.id !== comment.id);
    emit('refresh');
  } catch (err) {
    console.error('Failed to delete comment:', err);
    alert('Failed to delete comment. Please try again.');
  } finally {
    deletingCommentId.value = null;
  }
};

const addSubtask = async () => {
  if (!newSubtask.value.title.trim()) return;
  try {
    const response = await axios.post(`/api/tasks/${props.task.id}/subtasks`, newSubtask.value);
    if (!props.task.subtasks) props.task.subtasks = [];
    props.task.subtasks.push(response.data.subtask);
    newSubtask.value = { title: '', priority: 'medium' };
    showAddSubtask.value = false;
    emit('refresh');
  } catch (err) {
    console.error('Failed to add subtask:', err);
    alert('Failed to add subtask. Please try again.');
  }
};

const toggleSubtask = async (subtask) => {
  try {
    const newStatus = subtask.status === 'completed' ? 'todo' : 'completed';
    await axios.patch(`/api/subtasks/${subtask.id}`, { status: newStatus });
    subtask.status = newStatus;
    emit('refresh');
  } catch (err) {
    console.error('Failed to update subtask:', err);
    alert('Failed to update subtask. Please try again.');
  }
};

const deleteSubtask = async (subtask) => {
  if (!confirm('Delete this subtask?')) return;
  try {
    await axios.delete(`/api/subtasks/${subtask.id}`);
    props.task.subtasks = props.task.subtasks.filter(st => st.id !== subtask.id);
    emit('refresh');
  } catch (err) {
    console.error('Failed to delete subtask:', err);
    alert('Failed to delete subtask. Please try again.');
  }
};

const addWorklog = async () => {
  if (!newWorklog.value.hours || !newWorklog.value.description.trim()) return;
  try {
    const response = await axios.post(`/api/tasks/${props.task.id}/worklogs`, newWorklog.value);
    if (!props.task.worklogs) props.task.worklogs = [];
    props.task.worklogs.unshift(response.data.worklog);
    newWorklog.value = { hours: '', description: '' };
    showAddWorklog.value = false;
    emit('refresh');
  } catch (err) {
    console.error('Failed to add worklog:', err);
    alert('Failed to log time. Please try again.');
  }
};

const addLinkedWork = async () => {
  if (!newLink.value.linked_task_id) return;
  try {
    const response = await axios.post(`/api/tasks/${props.task.id}/links`, newLink.value);
    if (!props.task.linked_items) props.task.linked_items = [];
    props.task.linked_items.push(response.data.link);
    newLink.value = { link_type: 'relates_to', linked_task_id: '' };
    showLinkWork.value = false;
    emit('refresh');
  } catch (err) {
    console.error('Failed to link work:', err);
    alert('Failed to link work item. Please try again.');
  }
};

const removeLinkedWork = async (item) => {
  if (!confirm('Remove this link?')) return;
  try {
    await axios.delete(`/api/task-links/${item.id}`);
    props.task.linked_items = props.task.linked_items.filter(li => li.id !== item.id);
    emit('refresh');
  } catch (err) {
    console.error('Failed to remove link:', err);
    alert('Failed to remove link. Please try again.');
  }
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
      year: 'numeric'
    });
  }
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

const truncateText = (text, length) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};

const getHistoryIcon = (type) => {
  const icons = {
    created: '➕',
    updated: '✏️',
    status_change: '🔄',
    comment: '💬',
    assigned: '👤',
    priority_change: '⚠️'
  };
  return icons[type] || '📝';
};

const formatLinkType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
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
  min-height: 120px;
}

.task-card:hover:not(.expanded) {
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
.priority-border-critical { border-left-color: #e53e3e; }
.priority-border-high { border-left-color: #ed8936; }
.priority-border-moderate { border-left-color: #ecc94b; }
.priority-border-low { border-left-color: #48bb78; }

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

.priority-badge, .overdue-badge, .subtask-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  white-space: nowrap;
}

.priority-critical { background-color: #fed7d7; color: #c53030; }
.priority-high { background-color: #feebc8; color: #c05621; }
.priority-moderate { background-color: #fefcbf; color: #975a16; }
.priority-low { background-color: #c6f6d5; color: #276749; }

.overdue-badge {
  background-color: #fed7d7;
  color: #c53030;
}

.subtask-badge {
  background-color: #e6fffa;
  color: #234e52;
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
  flex: 1;
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

.assigned-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.name {
  font-size: 13px;
  color: #4a5568;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.created-by {
  font-size: 11px;
  color: #718096;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.footer-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}

.deadline {
  font-size: 12px;
  color: #718096;
  white-space: nowrap;
}

.deadline.overdue {
  color: #e53e3e;
  font-weight: 600;
}

.expand-btn {
  background: #4299e1;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
  font-weight: 500;
}

.expand-btn:hover:not(:disabled) {
  background-color: #3182ce;
}

.expand-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quick-indicators {
  display: flex;
  gap: 8px;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #e2e8f0;
}

.indicator {
  font-size: 11px;
  color: #718096;
}

/* Teleport Modal Styles */
.teleport-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fadeIn 0.3s ease-out;
}

.teleport-modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideIn 0.3s ease-out;
}

.teleport-modal.closing {
  animation: slideOut 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes slideOut {
  from { transform: translateY(0); opacity: 1; }
  to { transform: translateY(-20px); opacity: 0; }
}

.teleport-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: white;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.header-left {
  flex: 1;
  min-width: 0;
}

.task-title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.modal-task-title {
  font-size: 20px;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  flex: 1;
  min-width: 200px;
}

.modal-priority-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  white-space: nowrap;
}

.modal-overdue-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 20px;
  background-color: #fed7d7;
  color: #c53030;
  white-space: nowrap;
}

.task-meta {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.assigned-to-modal {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar-modal {
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
  flex-shrink: 0;
}

.assigned-info-modal {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.name-modal {
  font-size: 13px;
  color: #4a5568;
  font-weight: 500;
}

.created-by-modal {
  font-size: 12px;
  color: #718096;
}

.deadline-modal {
  font-size: 13px;
  color: #718096;
  font-weight: 500;
}

.deadline-modal.overdue {
  color: #e53e3e;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  flex-shrink: 0;
}

.modal-action-btn {
  padding: 8px 12px;
  border: 1px solid #cbd5e0;
  background: white;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.modal-action-btn:hover {
  background-color: #f7fafc;
  border-color: #a0aec0;
}

.modal-action-btn.delete {
  color: #e53e3e;
  border-color: #fed7d7;
}

.modal-action-btn.delete:hover {
  background-color: #fed7d7;
}

.modal-close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: none;
  font-size: 18px;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #718096;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background-color: #f7fafc;
  color: #2d3748;
}

.teleport-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

/* Tabs inside modal */
.tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e2e8f0;
  overflow-x: auto;
}

.tab-btn {
  background: none;
  border: none;
  padding: 12px 16px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #718096;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-btn:hover {
  color: #4299e1;
}

.tab-btn.active {
  color: #4299e1;
  border-bottom-color: #4299e1;
}

.tab-count {
  background-color: #e2e8f0;
  color: #4a5568;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
}

.tab-btn.active .tab-count {
  background-color: #4299e1;
  color: white;
}

/* Tab content styles */
.tab-content {
  animation: fadeIn 0.3s ease-in;
}

.tab-content h5 {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 16px 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.add-btn {
  background-color: #4299e1;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.add-btn:hover {
  background-color: #3182ce;
}

.no-content {
  text-align: center;
  padding: 32px;
  color: #a0aec0;
  font-size: 14px;
  font-style: italic;
}

/* Form styles for modal */
.add-form {
  background-color: #f7fafc;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  border: 1px solid #e2e8f0;
}

.form-input, .form-select, .form-textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 13px;
  margin-bottom: 12px;
  font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-primary-sm, .btn-secondary-sm {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary-sm {
  background-color: #4299e1;
  color: white;
}

.btn-primary-sm:hover:not(:disabled) {
  background-color: #3182ce;
}

.btn-primary-sm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary-sm {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn-secondary-sm:hover {
  background-color: #cbd5e0;
}

/* List styles for modal */
.subtasks-list, .comments-list, .history-list, .worklogs-list, .linked-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.subtask-item, .comment-item, .history-item, .worklog-item, .linked-item {
  padding: 16px;
  background-color: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.subtask-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.subtask-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
  flex-shrink: 0;
}

.subtask-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.subtask-content span.completed {
  text-decoration: line-through;
  color: #a0aec0;
}

.subtask-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
}

.priority-text {
  font-weight: 600;
  text-transform: capitalize;
}

.priority-text.priority-high { color: #c05621; }
.priority-text.priority-medium { color: #975a16; }
.priority-text.priority-low { color: #276749; }

.assignee-name {
  color: #718096;
}

.delete-subtask-btn, .delete-btn-sm {
  background: none;
  border: none;
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.2s;
  flex-shrink: 0;
}

.delete-subtask-btn:hover, .delete-btn-sm:hover:not(:disabled) {
  opacity: 1;
}

.delete-btn-sm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Comment styles */
.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar-sm {
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

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
}

.comment-date, .history-date, .worklog-date {
  font-size: 12px;
  color: #a0aec0;
}

.comment-text {
  font-size: 14px;
  color: #4a5568;
  line-height: 1.6;
  margin: 0;
}

/* History styles */
.history-item {
  display: flex;
  gap: 16px;
}

.history-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.icon-created { background-color: #c6f6d5; }
.icon-updated { background-color: #bee3f8; }
.icon-status_change { background-color: #feebc8; }
.icon-comment { background-color: #e6fffa; }

.history-content {
  flex: 1;
}

.history-text {
  font-size: 14px;
  color: #4a5568;
  margin: 0 0 6px 0;
  line-height: 1.5;
}

.field-changed {
  color: #718096;
}

.old-value {
  color: #e53e3e;
  font-weight: 500;
}

.new-value {
  color: #48bb78;
  font-weight: 500;
}

/* Worklog styles */
.worklog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.worklog-time {
  font-size: 13px;
  font-weight: 600;
  color: #4299e1;
  background-color: #e6f7ff;
  padding: 4px 10px;
  border-radius: 6px;
}

.worklog-description {
  font-size: 14px;
  color: #4a5568;
  line-height: 1.6;
  margin: 0;
}

/* Linked work styles */
.linked-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.link-type {
  font-size: 12px;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 6px;
  text-transform: uppercase;
  white-space: nowrap;
  flex-shrink: 0;
}

.type-blocks { background-color: #fed7d7; color: #c53030; }
.type-blocked_by { background-color: #feebc8; color: #c05621; }
.type-relates_to { background-color: #bee3f8; color: #2c5282; }
.type-duplicates { background-color: #e2e8f0; color: #4a5568; }

.linked-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.linked-title {
  font-size: 14px;
  font-weight: 500;
  color: #2d3748;
}

.linked-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-todo { background-color: #e2e8f0; color: #2d3748; }
.status-in_progress { background-color: #bee3f8; color: #2c5282; }
.status-under_review { background-color: #feebc8; color: #7c2d12; }
.status-completed { background-color: #c6f6d5; color: #22543d; }

/* Comment textarea */
.add-comment-form {
  margin-bottom: 24px;
}

.comment-textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #cbd5e0;
  border-radius: 8px;
  font-size: 14px;
  resize: vertical;
  margin-bottom: 12px;
  font-family: inherit;
  min-height: 80px;
}

.comment-textarea:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
  .teleport-modal {
    width: 95%;
    max-height: 95vh;
  }
  
  .teleport-header {
    padding: 16px;
    flex-direction: column;
    gap: 12px;
  }
  
  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .task-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .tabs {
    font-size: 12px;
  }
  
  .tab-btn {
    padding: 10px 12px;
    font-size: 13px;
  }
  
  .teleport-content {
    padding: 16px;
  }
}

@media (max-width: 480px) {
  .task-card {
    padding: 12px;
  }
  
  .tabs {
    font-size: 11px;
  }
  
  .tab-btn {
    padding: 6px 8px;
  }
  
  .assigned-info {
    min-width: 120px;
  }
  
  .task-title-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .modal-task-title {
    min-width: 0;
    font-size: 18px;
  }
}
</style>