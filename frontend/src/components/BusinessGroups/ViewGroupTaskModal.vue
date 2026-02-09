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
                {{ task.title }}
              </h3>
              <div class="mt-2 flex items-center space-x-2">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getPriorityClass(task.priority)
                  ]"
                >
                  {{ task.priority }}
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusClass(task.status)
                  ]"
                >
                  {{ task.status }}
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
                <p class="text-sm text-gray-600">{{ task.description }}</p>
              </div>

              <!-- Task Details -->
              <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Progress</h4>
                <div class="space-y-2">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Completion</span>
                    <span class="font-medium">{{ task.progress || 0 }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${task.progress || 0}%` }"
                    ></div>
                  </div>
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
                      {{ task.business_group?.name || 'N/A' }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Assigned Business</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ task.assigned_business?.name || 'Not assigned' }}
                    </dd>
                  </div>

                  <div v-if="task.due_date">
                    <dt class="text-xs font-medium text-gray-500">Due Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatDate(task.due_date) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatDate(task.created_at) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatDate(task.updated_at) }}
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
                    Update Task
                  </button>

                  <button
                    v-if="task.status !== 'completed'"
                    @click="markAsCompleted"
                    :disabled="completing"
                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mark as Completed
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
      :task="task"
      @close="showAssignModal = false"
      @assigned="handleAssigned"
    />

    <!-- Update Task Modal -->
    <UpdateTaskModal
      v-if="showUpdateModal"
      :task="task"
      @close="showUpdateModal = false"
      @updated="handleUpdated"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'
import AssignBusinessModal from './AssignBusinessModal.vue'
import UpdateTaskModal from './UpdateTaskModal.vue'

export default {
  name: 'ViewGroupTaskModal',
  
  components: {
    AssignBusinessModal,
    UpdateTaskModal,
  },
  
  props: {
    task: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'updated'],
  
  setup(props, { emit }) {
    const showAssignModal = ref(false)
    const showUpdateModal = ref(false)
    const completing = ref(false)

    const handleAssigned = () => {
      showAssignModal.value = false
      emit('updated')
    }

    const handleUpdated = () => {
      showUpdateModal.value = false
      emit('updated')
    }

    const markAsCompleted = async () => {
      completing.value = true
      try {
        const response = await axios.put(`/api/group-tasks/${props.task.id}`, {
          status: 'completed',
          progress: 100
        })
        
        if (response.data.success) {
          emit('updated')
        }
      } catch (error) {
        console.error('Error marking task as completed:', error)
      } finally {
        completing.value = false
      }
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
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-800',
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    }

    return {
      showAssignModal,
      showUpdateModal,
      completing,
      handleAssigned,
      handleUpdated,
      markAsCompleted,
      getPriorityClass,
      getStatusClass,
      formatDate,
    }
  }
}
</script>