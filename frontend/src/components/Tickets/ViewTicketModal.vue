<template>
  <!-- Teleport the modal to body to avoid z-index conflicts -->
  <Teleport to="body">
    <TransitionRoot appear :show="show" as="template">
      <Dialog as="div" class="relative z-[9999]" @close="closeModal">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel
                class="w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all"
              >
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-2000">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div :class="[
                        'p-2 rounded-lg',
                        getTypeBadgeClass(ticket?.type).replace('text-', 'bg-').replace('800', '100')
                      ]">
                        <component :is="getTypeIcon(ticket?.type)" class="w-6 h-6" :class="getTypeBadgeClass(ticket?.type).split(' ')[1]" />
                      </div>
                      <div>
                        <DialogTitle class="text-xl font-bold text-gray-900 line-clamp-1">
                          {{ ticket?.title }}
                        </DialogTitle>
                        <div class="flex items-center space-x-2 mt-1">
                          <StatusBadge :status="ticket?.status" />
                          <PriorityBadge :priority="ticket?.priority" />
                          <span class="text-sm text-gray-500">
                            #{{ ticket?.id }} • {{ getTypeLabel(ticket?.type) }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <button
                      @click="closeModal"
                      class="rounded-full p-2 hover:bg-gray-100 transition-colors"
                    >
                      <XMarkIcon class="w-6 h-6 text-gray-500" />
                    </button>
                  </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 bg-gray-50 px-6">
                  <nav class="-mb-px flex space-x-8">
                    <button
                      v-for="tab in tabs"
                      :key="tab.name"
                      @click="activeTab = tab.name"
                      :class="[
                        'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                        activeTab === tab.name
                          ? 'border-blue-500 text-blue-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                      ]"
                    >
                      {{ tab.label }}
                      <span v-if="tab.count" :class="[
                        'ml-2 py-0.5 px-2 rounded-full text-xs',
                        activeTab === tab.name
                          ? 'bg-blue-100 text-blue-600'
                          : 'bg-gray-200 text-gray-600'
                      ]">
                        {{ tab.count }}
                      </span>
                    </button>
                  </nav>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 max-h-[calc(100vh-250px)] overflow-y-auto">
                  <!-- Loading State -->
                  <div v-if="loading" class="flex items-center justify-center py-12">
                    <ArrowPathIcon class="w-8 h-8 text-blue-600 animate-spin" />
                  </div>

                  <!-- Error State -->
                  <div v-else-if="error" class="text-center py-12">
                    <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-4" />
                    <p class="text-lg font-medium text-gray-900 mb-2">Unable to load ticket</p>
                    <p class="text-gray-600">{{ error }}</p>
                  </div>

                  <!-- Content by Tab -->
                  <div v-else-if="ticketDetails" class="space-y-6">
                    <!-- Details Tab -->
                    <div v-if="activeTab === 'details'" class="space-y-6">
                      <!-- Basic Info Grid -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                          <div>
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                              Created By
                            </label>
                            <div class="flex items-center">
                              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <span class="text-sm font-medium text-blue-600">
                                  {{ getUserInitials(ticketDetails.user) }}
                                </span>
                              </div>
                              <div>
                                <p class="font-medium text-gray-900">{{ getUserName(ticketDetails.user) }}</p>
                                <p class="text-sm text-gray-500">{{ ticketDetails.user?.email }}</p>
                              </div>
                            </div>
                          </div>

                          <div v-if="ticketDetails.category">
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                              Category
                            </label>
                            <div class="flex items-center">
                              <FolderIcon class="w-5 h-5 text-gray-400 mr-2" />
                              <span class="font-medium text-gray-900">{{ ticketDetails.category }}</span>
                              <span v-if="ticketDetails.subcategory" class="ml-2 text-sm text-gray-500">
                                › {{ ticketDetails.subcategory }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="space-y-4">
                          <div>
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                              Due Date
                            </label>
                            <div class="flex items-center">
                              <CalendarDaysIcon class="w-5 h-5 text-gray-400 mr-2" />
                              <span :class="[
                                'font-medium',
                                isOverdue ? 'text-red-600' : 'text-gray-900'
                              ]">
                                {{ formatDate(ticketDetails.due_date) || 'No due date' }}
                              </span>
                              <span v-if="isOverdue" class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                Overdue
                              </span>
                            </div>
                          </div>

                          <div>
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                              Time Tracking
                            </label>
                            <div class="flex items-center space-x-4">
                              <div class="flex items-center">
                                <ClockIcon class="w-5 h-5 text-gray-400 mr-2" />
                                <span class="text-sm font-medium text-gray-900">
                                  {{ ticketDetails.estimated_hours || 0 }}h estimated
                                </span>
                              </div>
                              <div class="flex items-center">
                                <ClockIcon class="w-5 h-5 text-gray-400 mr-2" />
                                <span class="text-sm font-medium text-gray-900">
                                  {{ ticketDetails.time_spent || 0 }}h spent
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Description -->
                      <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                          Description
                        </label>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg border border-gray-200">
                          <p class="text-gray-700 whitespace-pre-wrap">{{ ticketDetails.description }}</p>
                        </div>
                      </div>

                      <!-- Assignments -->
                      <div v-if="ticketDetails.assigned_users?.length > 0">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                          Assigned To
                        </label>
                        <div class="flex flex-wrap gap-2 mt-2">
                          <div
                            v-for="user in ticketDetails.assigned_users"
                            :key="user.id"
                            class="flex items-center px-3 py-2 bg-blue-50 rounded-lg"
                          >
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                              <span class="text-xs font-medium text-blue-600">
                                {{ getUserInitials(user) }}
                              </span>
                            </div>
                            <span class="text-sm font-medium text-blue-700">{{ getUserName(user) }}</span>
                          </div>
                        </div>
                      </div>

                      <!-- Approver Information -->
                      <div v-if="ticketDetails.approver">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                          Approval Information
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                          <div class="flex items-center p-3 bg-green-50 rounded-lg border border-green-100">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 mr-3" />
                            <div>
                              <p class="font-medium text-gray-900">{{ ticketDetails.approver?.name || 'Not assigned' }}</p>
                              <p class="text-sm text-gray-500">{{ ticketDetails.approver?.email }}</p>
                            </div>
                          </div>
                          <div v-if="ticketDetails.approved_at" class="flex items-center p-3 bg-green-50 rounded-lg border border-green-100">
                            <CalendarDaysIcon class="w-5 h-5 text-green-600 mr-3" />
                            <div>
                              <p class="font-medium text-gray-900">Approved</p>
                              <p class="text-sm text-gray-500">{{ formatDateTime(ticketDetails.approved_at) }}</p>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- SLA Information -->
                      <div v-if="ticketDetails.sla_status">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">
                          SLA Status
                        </label>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                          <div :class="[
                            'w-3 h-3 rounded-full mr-3',
                            getSlaBadgeClass(ticketDetails.sla_status).split(' ')[0]
                          ]"></div>
                          <span :class="[
                            'font-medium',
                            getSlaBadgeClass(ticketDetails.sla_status).split(' ')[1]
                          ]">
                            {{ getSlaLabel(ticketDetails.sla_status) }}
                          </span>
                          <span class="ml-auto text-sm text-gray-500">
                            Due: {{ formatDate(ticketDetails.due_date) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Comments Tab -->
                    <div v-if="activeTab === 'comments'">
                      <div class="space-y-4">
                        <!-- Comment Input -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          <textarea
                            v-model="newComment"
                            rows="3"
                            placeholder="Add a comment..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          ></textarea>
                          <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center space-x-3">
                              <label class="flex items-center">
                                <input
                                  v-model="isInternalComment"
                                  type="checkbox"
                                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-600">Internal note</span>
                              </label>
                              <input
                                type="file"
                                ref="commentFileInput"
                                multiple
                                @change="handleCommentFileSelect"
                                class="hidden"
                              />
                              <button
                                @click="$refs.commentFileInput.click()"
                                type="button"
                                class="text-sm text-gray-600 hover:text-gray-900"
                              >
                                <PaperClipIcon class="w-4 h-4 inline mr-1" />
                                Add files
                              </button>
                            </div>
                            <button
                              @click="submitComment"
                              :disabled="!newComment.trim() || submittingComment"
                              :class="[
                                'px-4 py-2 text-sm font-medium text-white rounded-md transition-colors',
                                !newComment.trim() || submittingComment
                                  ? 'bg-blue-400 cursor-not-allowed'
                                  : 'bg-blue-600 hover:bg-blue-700'
                              ]"
                            >
                              <ArrowPathIcon v-if="submittingComment" class="w-4 h-4 inline mr-2 animate-spin" />
                              {{ submittingComment ? 'Posting...' : 'Post Comment' }}
                            </button>
                          </div>
                          <!-- Selected Files -->
                          <div v-if="commentFiles.length > 0" class="mt-3 space-y-2">
                            <div
                              v-for="(file, index) in commentFiles"
                              :key="index"
                              class="flex items-center justify-between bg-gray-50 p-2 rounded"
                            >
                              <div class="flex items-center">
                                <DocumentIcon class="w-4 h-4 text-gray-400 mr-2" />
                                <span class="text-sm truncate max-w-xs">{{ file.name }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ formatFileSize(file.size) }}</span>
                              </div>
                              <button
                                @click="removeCommentFile(index)"
                                class="text-red-500 hover:text-red-700"
                              >
                                <XMarkIcon class="w-4 h-4" />
                              </button>
                            </div>
                          </div>
                        </div>

                        <!-- Comments List -->
                        <div v-if="comments.length > 0" class="space-y-4">
                          <div
                            v-for="comment in comments"
                            :key="comment.id"
                            :class="[
                              'border rounded-lg p-4',
                              comment.is_internal
                                ? 'border-yellow-200 bg-yellow-50'
                                : 'border-gray-200 bg-white'
                            ]"
                          >
                            <!-- Comment Header -->
                            <div class="flex items-start justify-between mb-3">
                              <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                  <span class="text-sm font-medium text-blue-600">
                                    {{ getUserInitials(comment.user) }}
                                  </span>
                                </div>
                                <div>
                                  <div class="flex items-center">
                                    <span class="font-medium text-gray-900">{{ comment.user_name }}</span>
                                    <span v-if="comment.is_internal" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                      Internal
                                    </span>
                                  </div>
                                  <div class="text-xs text-gray-500">{{ comment.formatted_created_at }}</div>
                                </div>
                              </div>
                              <!-- Comment Actions -->
                              <div v-if="comment.can_edit || comment.can_delete" class="flex items-center space-x-2">
                                <button
                                  v-if="comment.can_edit"
                                  @click="editComment(comment)"
                                  class="text-gray-400 hover:text-blue-600"
                                  title="Edit comment"
                                >
                                  <PencilIcon class="w-4 h-4" />
                                </button>
                                <button
                                  v-if="comment.can_delete"
                                  @click="deleteComment(comment)"
                                  class="text-gray-400 hover:text-red-600"
                                  title="Delete comment"
                                >
                                  <TrashIcon class="w-4 h-4" />
                                </button>
                              </div>
                            </div>
                            <!-- Comment Content -->
                            <div class="text-gray-700 whitespace-pre-wrap mb-3">
                              {{ comment.content }}
                            </div>
                            <!-- Attachments -->
                            <div v-if="comment.attachments?.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div
                                  v-for="attachment in comment.attachments"
                                  :key="attachment.id"
                                  class="flex items-center p-2 bg-gray-50 rounded hover:bg-gray-100"
                                >
                                  <a
                                    :href="attachment.download_url"
                                    target="_blank"
                                    class="flex items-center flex-1 text-sm text-gray-700 hover:text-blue-600"
                                  >
                                    <component :is="getFileIcon(attachment)" class="w-5 h-5 mr-2 text-gray-400" />
                                    <span class="truncate">{{ attachment.original_name }}</span>
                                    <span class="ml-auto text-xs text-gray-500">{{ attachment.formatted_size }}</span>
                                  </a>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <!-- No Comments -->
                        <div v-else class="text-center py-8">
                          <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-300" />
                          <h3 class="mt-2 text-sm font-medium text-gray-900">No comments yet</h3>
                          <p class="mt-1 text-sm text-gray-500">Be the first to add a comment.</p>
                        </div>
                      </div>
                    </div>

                    <!-- Attachments Tab -->
                    <div v-if="activeTab === 'attachments'" class="space-y-4">
                      <!-- Upload Section -->
                      <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <input
                          type="file"
                          ref="attachmentInput"
                          multiple
                          @change="handleAttachmentUpload"
                          class="hidden"
                        />
                        <button
                          @click="$refs.attachmentInput.click()"
                          type="button"
                          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                          <PaperClipIcon class="h-4 w-4 mr-2" />
                          Upload Files
                        </button>
                        <p class="mt-2 text-xs text-gray-500">
                          Upload images, documents, or other files. Max 10MB per file.
                        </p>
                      </div>

                      <!-- Attachments List -->
                      <div v-if="attachments.length > 0" class="space-y-3">
                        <div
                          v-for="attachment in attachments"
                          :key="attachment.id"
                          class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                          <div class="flex items-start">
                            <div class="flex-shrink-0">
                              <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                <component :is="getFileIcon(attachment)" class="h-6 w-6 text-gray-400" />
                              </div>
                            </div>
                            <div class="ml-4 flex-1 min-w-0">
                              <p class="text-sm font-medium text-gray-900 truncate">
                                {{ attachment.original_name }}
                              </p>
                              <p class="text-xs text-gray-500">
                                {{ attachment.formatted_size }} • Uploaded {{ formatDate(attachment.created_at) }}
                              </p>
                              <div class="mt-2 flex space-x-3">
                                <a
                                  :href="attachment.url"
                                  target="_blank"
                                  class="text-xs text-blue-600 hover:text-blue-800"
                                >
                                  View
                                </a>
                                <a
                                  :href="attachment.download_url"
                                  class="text-xs text-blue-600 hover:text-blue-800"
                                >
                                  Download
                                </a>
                                <button
                                  v-if="canDeleteAttachment(attachment)"
                                  @click="deleteAttachment(attachment)"
                                  class="text-xs text-red-600 hover:text-red-800"
                                >
                                  Delete
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- No Attachments -->
                      <div v-else class="text-center py-8">
                        <PaperClipIcon class="mx-auto h-12 w-12 text-gray-300" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No attachments</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload files to share with the team.</p>
                      </div>
                    </div>

                    <!-- History Tab -->
                    <div v-if="activeTab === 'history'">
                      <!-- ActivityHistory Component -->
                      <div class="space-y-4">
                        <ActivityHistoryComponent :ticket-id="ticketDetails.id" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                      <span v-if="ticketDetails">
                        Last updated {{ timeAgo(ticketDetails.updated_at) }}
                      </span>
                    </div>
                    <div class="flex items-center space-x-3">
                      <button
                        v-if="canApprove"
                        @click="openApproval"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all"
                      >
                        <CheckCircleIcon class="w-4 h-4 mr-2 inline" />
                        Review & Approve
                      </button>
                      <button
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                      >
                        Close
                      </button>
                    </div>
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import axios from 'axios'
import StatusBadge from '@/components/StatusBadge.vue'
import PriorityBadge from '@/components/PriorityBadge.vue'
import ActivityHistoryComponent from '@/components/Tickets/ActivityHistoryComponent.vue'
import {
  XMarkIcon,
  TicketIcon,
  UserIcon,
  CheckCircleIcon,
  CalendarDaysIcon,
  ClockIcon,
  PaperClipIcon,
  ChatBubbleLeftRightIcon,
  ArrowPathIcon,
  ExclamationCircleIcon,
  PencilIcon,
  TrashIcon,
  DocumentIcon,
  FolderIcon,
  ExclamationCircleIcon as IssueIcon,
  DocumentTextIcon as RequestIcon,
  AdjustmentsHorizontalIcon as ChangeIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  ticket: Object
})

const emit = defineEmits(['close', 'status-updated'])

// Reactive state
const loading = ref(false)
const error = ref('')
const ticketDetails = ref(null)
const activeTab = ref('details')

// Comments
const comments = ref([])
const newComment = ref('')
const isInternalComment = ref(false)
const commentFiles = ref([])
const submittingComment = ref(false)

// Attachments
const attachments = ref([])

// Computed properties
const isOverdue = computed(() => {
  if (!ticketDetails.value?.due_date) return false
  const dueDate = new Date(ticketDetails.value.due_date)
  const now = new Date()
  return dueDate < now && !['resolved', 'closed'].includes(ticketDetails.value.status)
})

const canApprove = computed(() => {
  if (!ticketDetails.value) return false
  const user = JSON.parse(localStorage.getItem('user'))
  return user?.role === 'admin' && ticketDetails.value.status === 'pending'
})

const tabs = computed(() => [
  { name: 'details', label: 'Details' },
  { name: 'comments', label: 'Comments', count: comments.value.length },
  { name: 'attachments', label: 'Attachments', count: attachments.value.length },
  { name: 'history', label: 'History' }
])

// Methods
const closeModal = () => {
  ticketDetails.value = null
  error.value = ''
  comments.value = []
  attachments.value = []
  activeTab.value = 'details'
  emit('close')
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const timeAgo = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  
  if (seconds < 60) return 'just now'
  
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
  
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`
  
  const days = Math.floor(hours / 24)
  return `${days} day${days > 1 ? 's' : ''} ago`
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const getUserInitials = (user) => {
  if (!user) return '??'
  const firstName = user.first_name || ''
  const lastName = user.last_name || ''
  return (firstName[0] + lastName[0]).toUpperCase().slice(0, 2)
}

const getUserName = (user) => {
  if (!user) return 'Unknown User'
  return user.name || `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.email
}

const getTypeIcon = (type) => {
  switch(type) {
    case 'issue': return IssueIcon
    case 'request': return RequestIcon
    case 'change_request': return ChangeIcon
    default: return TicketIcon
  }
}

const getTypeLabel = (type) => {
  switch(type) {
    case 'issue': return 'Issue'
    case 'request': return 'Request'
    case 'change_request': return 'Change Request'
    default: return type
  }
}

const getTypeBadgeClass = (type) => {
  switch(type) {
    case 'issue': return 'bg-red-100 text-red-800'
    case 'request': return 'bg-emerald-100 text-emerald-800'
    case 'change_request': return 'bg-purple-100 text-purple-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getSlaBadgeClass = (slaStatus) => {
  switch(slaStatus) {
    case 'on_track': return 'bg-green-100 text-green-800'
    case 'warning': return 'bg-amber-100 text-amber-800'
    case 'breached': return 'bg-red-100 text-red-800'
    case 'completed': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getSlaLabel = (slaStatus) => {
  switch(slaStatus) {
    case 'on_track': return 'On Track'
    case 'warning': return 'Warning'
    case 'breached': return 'Breached'
    case 'completed': return 'Completed'
    default: return slaStatus
  }
}

const getFileIcon = (attachment) => {
  if (attachment.mime_type?.startsWith('image/')) return PhotoIcon
  if (attachment.mime_type?.startsWith('video/')) return VideoCameraIcon
  if (attachment.mime_type?.startsWith('audio/')) return MusicalNoteIcon
  return DocumentIcon
}

// Comment methods
const handleCommentFileSelect = (event) => {
  const files = Array.from(event.target.files)
  commentFiles.value = [...commentFiles.value, ...files]
  event.target.value = ''
}

const removeCommentFile = (index) => {
  commentFiles.value.splice(index, 1)
}

const submitComment = async () => {
  if (!newComment.value.trim()) return
  
  // Check if we have a valid ticket ID
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    console.error('No ticket ID available for posting comment')
    alert('Cannot post comment: Ticket information is not available. Please refresh the page.')
    return
  }
  
  submittingComment.value = true
  
  try {
    const formData = new FormData()
    formData.append('content', newComment.value)
    formData.append('is_internal', isInternalComment.value)
    
    commentFiles.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file)
    })
    
    const response = await axios.post(`/api/tickets/${ticketId}/comments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // Fix: Check if response.data exists and has the expected structure
    const commentData = response.data?.comment || response.data
    
    if (commentData && commentData.id) {
      comments.value.push(commentData)
      newComment.value = ''
      isInternalComment.value = false
      commentFiles.value = []
    } else {
      console.error('Invalid comment data received:', response.data)
      alert('Comment posted but data format unexpected. Please refresh to see the comment.')
    }
    
  } catch (err) {
    console.error('Failed to post comment:', err)
    
    // Provide more specific error messages
    if (err.response?.status === 401) {
      alert('Your session has expired. Please log in again.')
      // Redirect to login or refresh token
    } else if (err.response?.status === 404) {
      alert('Ticket not found. It may have been deleted.')
    } else if (err.response?.status === 403) {
      alert('You are not authorized to comment on this ticket.')
    } else {
      alert('Failed to post comment: ' + (err.response?.data?.message || err.message))
    }
  } finally {
    submittingComment.value = false
  }
}

const editComment = async (comment) => {
  const newContent = prompt('Edit your comment:', comment.content)
  if (newContent === null || newContent === comment.content) return
  
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    alert('Cannot edit comment: Ticket information is not available.')
    return
  }
  
  try {
    await axios.put(`/api/tickets/${ticketId}/comments/${comment.id}`, {
      content: newContent
    })
    
    const index = comments.value.findIndex(c => c.id === comment.id)
    if (index !== -1) {
      comments.value[index].content = newContent
    }
  } catch (err) {
    console.error('Failed to update comment:', err)
    alert('Failed to update comment')
  }
}

const deleteComment = async (comment) => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    alert('Cannot delete comment: Ticket information is not available.')
    return
  }
  
  try {
    await axios.delete(`/api/tickets/${ticketId}/comments/${comment.id}`)
    comments.value = comments.value.filter(c => c.id !== comment.id)
  } catch (err) {
    console.error('Failed to delete comment:', err)
    alert('Failed to delete comment')
  }
}

// Attachment methods
const handleAttachmentUpload = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    alert('Cannot upload attachment: Ticket information is not available.')
    return
  }
  
  try {
    // Refresh CSRF cookie
    await axios.get('/sanctum/csrf-cookie');

    for (const file of files) {
      const formData = new FormData()
      formData.append('file', file)
      
      await axios.post(`/api/tickets/${ticketId}/attachments`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    
    await loadAttachments()
    event.target.value = ''
    
  } catch (err) {
     if (error.response?.status === 401) {
        // Redirect to login
        window.location.href = '/login';
    }
    console.error('Failed to upload attachment:', err)
    alert('Failed to upload attachment: ' + (err.response?.data?.message || err.message))
  }
}

const deleteAttachment = async (attachment) => {
  if (!confirm('Are you sure you want to delete this attachment?')) return
  
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    alert('Cannot delete attachment: Ticket information is not available.')
    return
  }
  
  try {
    await axios.delete(`/api/tickets/${ticketId}/attachments/${attachment.id}`)
    attachments.value = attachments.value.filter(a => a.id !== attachment.id)
  } catch (err) {
    console.error('Failed to delete attachment:', err)
    alert('Failed to delete attachment')
  }
}

const canDeleteAttachment = (attachment) => {
  const currentUser = JSON.parse(localStorage.getItem('user'))
  return currentUser?.id === attachment.user_id || currentUser?.role === 'admin'
}

// Data loading methods
const loadTicketDetails = async () => {
  const ticketId = props.ticket?.id
  if (!ticketId) {
    console.error('No ticket ID provided for loading details')
    return
  }
  
  try {
    const response = await axios.get(`/api/tickets/${ticketId}`)
    ticketDetails.value = response.data
  } catch (err) {
    console.error('Failed to fetch ticket details:', err)
    error.value = err.response?.data?.message || 'Failed to load ticket details'
    throw err
  }
}

const loadComments = async () => {
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    console.error('No ticket ID available for loading comments')
    return
  }
  
  try {
    const response = await axios.get(`/api/tickets/${ticketId}/comments`)
    comments.value = response.data || []
  } catch (err) {
    console.error('Failed to load comments:', err)
    comments.value = []
  }
}

const loadAttachments = async () => {
  const ticketId = ticketDetails.value?.id || props.ticket?.id
  if (!ticketId) {
    console.error('No ticket ID available for loading attachments')
    return
  }
  
  try {
    const response = await axios.get(`/api/tickets/${ticketId}/attachments`)
    attachments.value = response.data || []
  } catch (err) {
    console.error('Failed to load attachments:', err)
    attachments.value = []
  }
}

const loadAllData = async () => {
  const ticketId = props.ticket?.id
  if (!ticketId) {
    console.error('No ticket ID provided')
    error.value = 'No ticket selected'
    loading.value = false
    return
  }
  
  loading.value = true
  error.value = ''
  
  try {
    // Load ticket details first
    await loadTicketDetails()
    
    // Only load comments and attachments if we have a valid ticket
    if (ticketDetails.value?.id) {
      await Promise.all([
        loadComments(),
        loadAttachments()
      ])
    }
  } catch (err) {
    console.error('Failed to load ticket data:', err)
    error.value = 'Failed to load ticket information'
  } finally {
    loading.value = false
  }
}

const openApproval = () => {
  if (ticketDetails.value) {
    emit('status-updated', ticketDetails.value)
    closeModal()
  } else {
    alert('Ticket information is not available for approval.')
  }
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.ticket?.id) {
    loadAllData()
  } else if (!props.ticket?.id) {
    error.value = 'No valid ticket selected'
  }
})

watch(() => props.ticket, (newTicket) => {
  if (newTicket?.id) {
    loadAllData()
  }
}, { immediate: true })
</script>

<style scoped>
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>