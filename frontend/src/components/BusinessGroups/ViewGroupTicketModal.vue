<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Center modal -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                #{{ ticket.id }} - {{ ticket.title }}
              </h3>
              <div class="mt-2 flex items-center space-x-2">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getPriorityClass(ticket.priority)
                  ]"
                >
                  {{ ticket.priority }}
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusClass(ticket.status)
                  ]"
                >
                  {{ ticket.status }}
                </span>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="px-4 py-5 sm:p-6">
          <div class="grid grid-cols-3 gap-6">
            <!-- Main Content (2/3) -->
            <div class="col-span-2 space-y-6">
              <!-- Description -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">Description</h4>
                <p class="text-sm text-gray-600">{{ ticket.description }}</p>
              </div>

              <!-- Comments Section -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Comments</h4>
                
                <!-- Comment List -->
                <div class="space-y-4 mb-4">
                  <div
                    v-for="comment in comments"
                    :key="comment.id"
                    class="bg-gray-50 rounded-lg p-4"
                  >
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                          <span class="text-sm font-medium text-purple-600">
                            {{ comment.user?.first_name?.charAt(0) || 'U' }}
                          </span>
                        </div>
                      </div>
                      <div class="flex-1">
                        <div class="flex items-center justify-between">
                          <p class="text-sm font-medium text-gray-900">
                            {{ comment.user?.first_name }} {{ comment.user?.last_name }}
                          </p>
                          <p class="text-xs text-gray-500">
                            {{ formatDate(comment.created_at) }}
                          </p>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ comment.comment }}</p>
                      </div>
                    </div>
                  </div>

                  <div v-if="comments.length === 0" class="text-center py-6 text-gray-500 text-sm">
                    No comments yet
                  </div>
                </div>

                <!-- Add Comment Form -->
                <div class="flex space-x-3">
                  <div class="flex-1">
                    <textarea
                      v-model="newComment"
                      rows="2"
                      placeholder="Add a comment..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                    ></textarea>
                  </div>
                  <button
                    @click="addComment"
                    :disabled="!newComment.trim() || addingComment"
                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed h-fit"
                  >
                    Post
                  </button>
                </div>
              </div>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="col-span-1 space-y-4">
              <!-- Details Card -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Details</h4>
                
                <dl class="space-y-3">
                  <div>
                    <dt class="text-xs font-medium text-gray-500">Business Group</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ ticket.business_group?.name || 'N/A' }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Assigned Business</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ ticket.assigned_business?.name || 'Not assigned' }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatDate(ticket.created_at) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatDate(ticket.updated_at) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Actions Card -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Actions</h4>
                
                <div class="space-y-2">
                  <button
                    @click="showAssignModal = true"
                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                  >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Assign to Business
                  </button>

                  <button
                    @click="showUpdateModal = true"
                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                  >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Update Ticket
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Assign to Business Modal -->
    <AssignBusinessModal
      v-if="showAssignModal"
      :ticket="ticket"
      @close="showAssignModal = false"
      @assigned="handleAssigned"
    />

    <!-- Update Ticket Modal -->
    <UpdateTicketModal
      v-if="showUpdateModal"
      :ticket="ticket"
      @close="showUpdateModal = false"
      @updated="handleUpdated"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AssignBusinessModal from './AssignBusinessModal.vue'
import UpdateTicketModal from './UpdateTicketModal.vue'

export default {
  name: 'ViewGroupTicketModal',
  
  components: {
    AssignBusinessModal,
    UpdateTicketModal,
  },
  
  props: {
    ticket: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'updated'],
  
  setup(props, { emit }) {
    const comments = ref([])
    const newComment = ref('')
    const addingComment = ref(false)
    const showAssignModal = ref(false)
    const showUpdateModal = ref(false)

    const fetchComments = async () => {
      try {
        const response = await axios.get(`/api/group-tickets/${props.ticket.id}/comments`)
        if (response.data.success) {
          comments.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching comments:', error)
      }
    }

    const addComment = async () => {
      if (!newComment.value.trim()) return

      addingComment.value = true
      try {
        const response = await axios.post(`/api/group-tickets/${props.ticket.id}/comments`, {
          comment: newComment.value
        })
        
        if (response.data.success) {
          comments.value.push(response.data.data)
          newComment.value = ''
        }
      } catch (error) {
        console.error('Error adding comment:', error)
      } finally {
        addingComment.value = false
      }
    }

    const handleAssigned = () => {
      showAssignModal.value = false
      emit('updated')
    }

    const handleUpdated = () => {
      showUpdateModal.value = false
      emit('updated')
    }

    const getPriorityClass = (priority) => {
      const classes = {
        low: 'bg-gray-100 text-gray-800',
        medium: 'bg-blue-100 text-blue-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800',
      }
      return classes[priority] || 'bg-gray-100 text-gray-800'
    }

    const getStatusClass = (status) => {
      const classes = {
        open: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    }

    onMounted(() => {
      fetchComments()
    })

    return {
      comments,
      newComment,
      addingComment,
      showAssignModal,
      showUpdateModal,
      fetchComments,
      addComment,
      handleAssigned,
      handleUpdated,
      getPriorityClass,
      getStatusClass,
      formatDate,
    }
  }
}
</script>