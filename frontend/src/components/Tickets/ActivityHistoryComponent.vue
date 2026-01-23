<template>
  <div class="space-y-4">
    <!-- Activities List -->
    <div class="flow-root">
      <ul class="-mb-8">
        <li v-for="(activity, index) in activities" :key="activity.id">
          <div class="relative pb-8">
            <span v-if="index !== activities.length - 1" 
                  class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" 
                  aria-hidden="true" />
            <div class="relative flex space-x-3">
              <!-- Icon -->
              <div>
                <span :class="[
                  'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                  activity.color_class.split(' ')[0]
                ]">
                  <component :is="getActivityIcon(activity.action)" 
                           class="h-4 w-4" 
                           :class="activity.color_class.split(' ')[1]" />
                </span>
              </div>
              
              <!-- Content -->
              <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                <div>
                  <p class="text-sm text-gray-700">
                    <span class="font-medium text-gray-900">{{ activity.user_name }}</span>
                    {{ activity.description }}
                  </p>
                  <div v-if="activity.changes" class="mt-2">
                    <div v-for="(change, field) in activity.changes" :key="field" 
                         class="text-xs text-gray-500 mb-1">
                      <span class="font-medium">{{ formatFieldName(field) }}:</span>
                      <span v-if="change.old" class="line-through text-red-500 mx-1">{{ change.old }}</span>
                      <span v-if="change.new" class="text-green-500">{{ change.new }}</span>
                    </div>
                  </div>
                </div>
                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                  <time :datetime="activity.created_at">{{ activity.formatted_created_at }}</time>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
    
    <!-- Load More Button -->
    <div v-if="hasMorePages" class="text-center">
      <button
        @click="loadMore"
        :disabled="isLoading"
        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <ArrowPathIcon v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
        Load More
      </button>
    </div>
    
    <!-- No Activities -->
    <div v-if="!isLoading && activities.length === 0" class="text-center py-8">
      <ClockIcon class="mx-auto h-12 w-12 text-gray-300" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No activity yet</h3>
      <p class="mt-1 text-sm text-gray-500">Activity will appear here as changes are made.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  ClockIcon,
  ArrowPathIcon,
  PlusCircleIcon,
  PencilIcon,
  ArrowsUpDownIcon,
  ChatBubbleLeftRightIcon,
  PaperClipIcon,
  ExclamationTriangleIcon,
  UserPlusIcon,
  UserGroupIcon,
  CheckCircleIcon,
  XCircleIcon,
  CheckBadgeIcon,
  LockClosedIcon,
  ArrowPathIcon as ReopenIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  ticketId: {
    type: [String, Number],
    required: true
  }
})

// State
const activities = ref([])
const isLoading = ref(false)
const currentPage = ref(1)
const hasMorePages = ref(true)

// Methods
const loadActivities = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await axios.get(`/api/tickets/${props.ticketId}/activities`, {
      params: { page }
    })
    
    if (page === 1) {
      activities.value = response.data.data
    } else {
      activities.value = [...activities.value, ...response.data.data]
    }
    
    hasMorePages.value = response.data.current_page < response.data.last_page
    currentPage.value = response.data.current_page
    
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => {
  if (hasMorePages.value && !isLoading.value) {
    loadActivities(currentPage.value + 1)
  }
}

const getActivityIcon = (action) => {
  const icons = {
    'created': PlusCircleIcon,
    'updated': PencilIcon,
    'status_changed': ArrowsUpDownIcon,
    'commented': ChatBubbleLeftRightIcon,
    'attachment_added': PaperClipIcon,
    'priority_changed': ExclamationTriangleIcon,
    'assigned': UserPlusIcon,
    'reassigned': UserGroupIcon,
    'approved': CheckCircleIcon,
    'rejected': XCircleIcon,
    'resolved': CheckBadgeIcon,
    'closed': LockClosedIcon,
    'reopened': ReopenIcon
  }
  return icons[action] || DocumentTextIcon
}

const formatFieldName = (field) => {
  return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
  loadActivities()
})
</script>