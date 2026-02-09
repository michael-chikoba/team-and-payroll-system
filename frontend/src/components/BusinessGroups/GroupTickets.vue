<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Cross-Business Tickets</h1>
        <p class="mt-2 text-sm text-gray-600">
          Manage tickets across your business groups
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
      >
        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create Group Ticket
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Business Group Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Business Group</label>
          <select
            v-model="filters.business_group_id"
            @change="fetchTickets"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
          >
            <option value="">All Groups</option>
            <option v-for="group in businessGroups" :key="group.id" :value="group.id">
              {{ group.name }}
            </option>
          </select>
        </div>

        <!-- Assigned Business Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Business</label>
          <select
            v-model="filters.assigned_business_id"
            @change="fetchTickets"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
          >
            <option value="">All Businesses</option>
            <option v-for="business in businesses" :key="business.id" :value="business.id">
              {{ business.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            @change="fetchTickets"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
          >
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>

        <!-- Priority Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select
            v-model="filters.priority"
            @change="fetchTickets"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
          >
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Tickets</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Open</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.open || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">In Progress</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.in_progress || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Resolved</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.resolved || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-purple-200 border-t-purple-600"></div>
      <p class="mt-4 text-sm text-gray-500">Loading tickets...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!tickets || tickets.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets found</h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new cross-business ticket.
      </p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Ticket
        </button>
      </div>
    </div>

    <!-- Tickets List -->
    <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200">
        <li
          v-for="ticket in validTickets"
          :key="ticket.id"
          @click="viewTicket(ticket)"
          class="hover:bg-gray-50 cursor-pointer transition-colors duration-150"
        >
          <div class="px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3">
                  <p class="text-sm font-medium text-purple-600 truncate">
                    #{{ ticket.id }} - {{ ticket.title }}
                  </p>
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
                <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                  {{ ticket.description }}
                </p>
                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                  <span class="flex items-center">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ ticket.business_group?.name || 'N/A' }}
                  </span>
                  <span v-if="ticket.assigned_business" class="flex items-center">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Assigned to: {{ ticket.assigned_business.name }}
                  </span>
                  <span class="flex items-center">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(ticket.created_at) }}
                  </span>
                </div>
              </div>
              <div class="ml-4 flex-shrink-0">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Create Ticket Modal -->
    <CreateGroupTicketModal
      v-if="showCreateModal"
      :business-groups="businessGroups"
      @close="showCreateModal = false"
      @created="handleTicketCreated"
    />

    <!-- View Ticket Modal -->
    <ViewGroupTicketModal
      v-if="selectedTicket"
      :ticket="selectedTicket"
      @close="selectedTicket = null"
      @updated="handleTicketUpdated"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import CreateGroupTicketModal from './CreateGroupTicketModal.vue'
import ViewGroupTicketModal from './ViewGroupTicketModal.vue'

export default {
  name: 'GroupTickets',
  
  components: {
    CreateGroupTicketModal,
    ViewGroupTicketModal,
  },
  
  setup() {
    const loading = ref(false)
    const tickets = ref([])
    const businessGroups = ref([])
    const businesses = ref([])
    const showCreateModal = ref(false)
    const selectedTicket = ref(null)
    
    const filters = ref({
      business_group_id: '',
      assigned_business_id: '',
      status: '',
      priority: '',
    })

    // Filter out null/undefined tickets to prevent errors
    const validTickets = computed(() => {
      if (!Array.isArray(tickets.value)) {
        return []
      }
      return tickets.value.filter(ticket => ticket && ticket.id)
    })

    const stats = computed(() => {
      const validTicketsList = validTickets.value
      return {
        total: validTicketsList.length,
        open: validTicketsList.filter(t => t && t.status === 'open').length,
        in_progress: validTicketsList.filter(t => t && t.status === 'in_progress').length,
        resolved: validTicketsList.filter(t => t && t.status === 'resolved').length,
      }
    })

    const fetchTickets = async () => {
      loading.value = true
      try {
        const params = Object.fromEntries(
          Object.entries(filters.value).filter(([_, v]) => v !== '')
        )
        
        const response = await axios.get('/api/group-tickets', { params })
        
        // Handle different response formats
        if (response.data) {
          if (response.data.success && Array.isArray(response.data.data)) {
            tickets.value = response.data.data
          } else if (Array.isArray(response.data)) {
            tickets.value = response.data
          } else if (Array.isArray(response.data.tickets)) {
            tickets.value = response.data.tickets
          } else {
            tickets.value = []
            console.warn('Unexpected response format:', response.data)
          }
        } else {
          tickets.value = []
        }
      } catch (error) {
        console.error('Error fetching tickets:', error)
        tickets.value = []
      } finally {
        loading.value = false
      }
    }

    const fetchBusinessGroups = async () => {
      try {
        const response = await axios.get('/api/business-groups')
        if (response.data) {
          if (response.data.success && Array.isArray(response.data.data)) {
            businessGroups.value = response.data.data
          } else if (Array.isArray(response.data)) {
            businessGroups.value = response.data
          } else if (Array.isArray(response.data.business_groups)) {
            businessGroups.value = response.data.business_groups
          } else {
            businessGroups.value = []
          }
        }
      } catch (error) {
        console.error('Error fetching business groups:', error)
        businessGroups.value = []
      }
    }

    const fetchBusinesses = async () => {
      try {
        const response = await axios.get('/api/businesses')
        if (response.data) {
          if (response.data.success && Array.isArray(response.data.data)) {
            businesses.value = response.data.data
          } else if (Array.isArray(response.data)) {
            businesses.value = response.data
          } else if (Array.isArray(response.data.businesses)) {
            businesses.value = response.data.businesses
          } else {
            businesses.value = []
          }
        }
      } catch (error) {
        console.error('Error fetching businesses:', error)
        businesses.value = []
      }
    }

    const viewTicket = (ticket) => {
      if (ticket && ticket.id) {
        selectedTicket.value = ticket
      }
    }

    const handleTicketCreated = () => {
      showCreateModal.value = false
      fetchTickets()
    }

    const handleTicketUpdated = () => {
      selectedTicket.value = null
      fetchTickets()
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
      if (!date) return 'N/A'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
        })
      } catch (error) {
        console.error('Error formatting date:', date, error)
        return 'Invalid Date'
      }
    }

    onMounted(() => {
      fetchTickets()
      fetchBusinessGroups()
      fetchBusinesses()
    })

    return {
      loading,
      tickets,
      businessGroups,
      businesses,
      showCreateModal,
      selectedTicket,
      filters,
      stats,
      validTickets,
      fetchTickets,
      viewTicket,
      handleTicketCreated,
      handleTicketUpdated,
      getPriorityClass,
      getStatusClass,
      formatDate,
    }
  }
}
</script>