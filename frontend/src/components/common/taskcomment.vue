<template>
  <div class="task-detail-page">
    <!-- Background Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50"></div>
    
    <!-- Main Content -->
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 text-center">
          <div class="flex items-center justify-center mb-4">
            <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg mr-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <div>
              <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-blue-600 to-indigo-700 bg-clip-text text-transparent">
                Task Details
              </h1>
              <p class="text-gray-600 mt-2 text-lg">Manage comments and updates for this task.</p>
            </div>
          </div>
        </div>

        <!-- Back Button -->
        <div class="mb-6 text-center">
          <router-link 
            to="/employee/tasks" 
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:bg-gray-50"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Tasks
          </router-link>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
          <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="spinner mb-4"></div>
            <p class="text-lg text-gray-600">Loading task details...</p>
          </div>
        </div>

        <!-- Task Details Section -->
        <div v-else-if="task" class="task-details-section">
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
              <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Task Information
              </h2>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                  <p class="text-lg font-semibold text-gray-900">{{ task.title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    {{ task.status }}
                  </span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Assigned By</label>
                  <p class="text-gray-600">{{ task.assigned_by?.name || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                  <p class="text-gray-600">{{ formatDate(task.due_date) }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                  <p class="text-gray-600">{{ task.description || 'No description provided.' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Comments Section -->
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
              <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Comments ({{ comments.length }})
              </h2>
            </div>
            <div class="p-6">
              <!-- Add Comment Form -->
              <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <form @submit.prevent="addComment" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add a Comment</label>
                    <textarea
                      v-model="newComment"
                      rows="3"
                      placeholder="Share your thoughts or updates..."
                      class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                      :disabled="addingComment"
                      required
                    ></textarea>
                  </div>
                  <button
                    type="submit"
                    :disabled="addingComment || !newComment.trim()"
                    class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="addingComment" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ addingComment ? 'Adding...' : 'Add Comment' }}
                  </button>
                </form>
              </div>

              <!-- Comments List -->
              <div v-if="comments.length > 0" class="space-y-4">
                <div
                  v-for="comment in comments"
                  :key="comment.id"
                  class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-200"
                >
                  <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                      <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                        {{ getInitials(comment.user.name) }}
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900">{{ comment.user.name }}</h4>
                        <span class="text-sm text-gray-500">{{ formatDate(comment.created_at) }}</span>
                      </div>
                      <p class="mt-1 text-sm text-gray-700">{{ comment.comment }}</p>
                      <div v-if="comment.user_id === authStore.user?.id" class="mt-2 flex justify-end">
                        <button
                          @click="deleteComment(comment)"
                          class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors duration-200"
                          :disabled="deletingCommentId === comment.id"
                        >
                          <svg v-if="deletingCommentId === comment.id" class="w-4 h-4 inline mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                          {{ deletingCommentId === comment.id ? 'Deleting...' : 'Delete' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8 text-gray-500">
                <p>No comments yet. Be the first to add one!</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-else class="text-center py-12">
          <div class="text-gray-500">
            <p class="text-lg">Task not found.</p>
            <router-link to="/employee/tasks" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">Go back to tasks</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const taskId = route.params.id;
const task = ref(null);
const comments = ref([]);
const loading = ref(true);
const addingComment = ref(false);
const deletingCommentId = ref(null);
const newComment = ref('');

onMounted(async () => {
  await fetchTask();
  await fetchComments();
  loading.value = false;
});

async function fetchTask() {
  try {
    const response = await axios.get(`/api/tasks/${taskId}`);
    task.value = response.data;
  } catch (err) {
    console.error('Failed to fetch task:', err);
    router.push('/employee/tasks');
  }
}

async function fetchComments() {
  try {
    const response = await axios.get(`/api/tasks/${taskId}/comments`);
    comments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to fetch comments:', err);
  }
}

async function addComment() {
  if (!newComment.value.trim()) return;
  addingComment.value = true;
  try {
    const response = await axios.post(`/api/tasks/${taskId}/comments`, {
      comment: newComment.value
    });
    comments.value.unshift(response.data.comment);
    newComment.value = '';
  } catch (err) {
    console.error('Failed to add comment:', err);
    alert('Failed to add comment. Please try again.');
  } finally {
    addingComment.value = false;
  }
}

async function deleteComment(comment) {
  if (!confirm('Are you sure you want to delete this comment?')) return;
  deletingCommentId.value = comment.id;
  try {
    await axios.delete(`/api/comments/${comment.id}`);
    comments.value = comments.value.filter(c => c.id !== comment.id);
  } catch (err) {
    console.error('Failed to delete comment:', err);
    alert('Failed to delete comment. Please try again.');
  } finally {
    deletingCommentId.value = null;
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
}
</script>

<style scoped>
.task-detail-page {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  position: relative;
}

.task-detail-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(147, 51, 234, 0.05) 100%);
  pointer-events: none;
  z-index: 0;
}

/* Loading Spinner */
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 640px) {
  .max-w-4xl {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .text-4xl {
    font-size: 2rem;
  }
}
</style>