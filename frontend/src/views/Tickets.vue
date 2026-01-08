<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
// Modern UI requires intuitive icons. 
// Note: Ensure you have @heroicons/vue installed.
import { 
  PlusIcon, 
  SearchIcon, 
  FilterIcon, 
  RefreshIcon,
  TicketIcon,
  ClockIcon,
  CheckCircleIcon,
  PlayIcon
} from '@heroicons/vue/outline'

import StatCard from '@/components/StatCard.vue' 
import StatusBadge from '@/components/StatusBadge.vue'
import PriorityBadge from '@/components/PriorityBadge.vue'
import Pagination from '@/components/Pagination.vue'
import CreateTicketModal from '@/components/Tickets/CreateTicketModal.vue'
import ViewTicketModal from '@/components/Tickets/ViewTicketModal.vue'
import ApprovalModal from '@/components/Tickets/ApprovalModal.vue'

// Reactive state
const tickets = ref({ data: [], meta: {} })
const statistics = ref([])
const approvers = ref([])
const filters = ref({
  status: '',
  priority: '',
  search: '',
  page: 1
})
const isLoading = ref(false) // Added for modern UX
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showApprovalModal = ref(false)
const selectedTicket = ref(null)

// Fetch data
const fetchTickets = async () => {
  isLoading.value = true
  try {
    const params = { ...filters.value }
    // Clean empty filters
    Object.keys(params).forEach(key => !params[key] && delete params[key])
    
    const response = await axios.get('/api/tickets', { params })
    tickets.value = response.data
  } catch (error) {
    console.error("Error fetching tickets:", error)
  } finally {
    // Add a small delay to prevent flickering on fast connections for a smoother feel
    setTimeout(() => isLoading.value = false, 300)
  }
}

const fetchStatistics = async () => {
  try {
      const response = await axios.get('/api/ticket-statistics')
      const stats = response.data
      
      // Mapped with distinct colors and modern icons
      statistics.value = [
        { name: 'Total Tickets', value: stats.total, trend: '+12%', icon: TicketIcon, color: 'text-blue-600', bg: 'bg-blue-50' },
        { name: 'Pending Review', value: stats.pending, trend: 'Wait time: 2h', icon: ClockIcon, color: 'text-amber-600', bg: 'bg-amber-50' },
        { name: 'Approved', value: stats.approved, trend: 'This week', icon: CheckCircleIcon, color: 'text-emerald-600', bg: 'bg-emerald-50' },
        { name: 'In Progress', value: stats.in_progress, trend: 'Active', icon: PlayIcon, color: 'text-indigo-600', bg: 'bg-indigo-50' },
      ]
  } catch (e) {
      console.error("Failed to load stats", e)
  }
}

const fetchApprovers = async () => {
  try {
    const response = await axios.get('/api/approvers')
    approvers.value = response.data
  } catch (e) { console.error(e) }
}

// Lifecycle
onMounted(() => {
  fetchTickets()
  fetchStatistics()
  fetchApprovers()
})

// Watchers
watch(filters, () => {
  // Reset to page 1 when filtering changes (except pagination)
  // Assuming the Pagination component emits page updates to filters.page
  fetchTickets()
}, { deep: true })

// Utilities
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const canApproveTicket = (ticket) => {
  const user = JSON.parse(localStorage.getItem('user')) 
  return user?.id === ticket.approver_id && ticket.status === 'pending'
}

// Actions
const viewTicket = (ticket) => {
  selectedTicket.value = ticket
  showViewModal.value = true
}

const openApprovalModal = (ticket) => {
  selectedTicket.value = ticket
  showApprovalModal.value = true
}

const changePage = (page) => {
  filters.value.page = page
}

const handleTicketCreated = () => {
  showCreateModal.value = false
  filters.value.status = '' // Reset filters to see new ticket
  fetchTickets()
  fetchStatistics()
}

const handleStatusUpdated = () => {
  showViewModal.value = false
  showApprovalModal.value = false
  fetchTickets()
  fetchStatistics()
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900">
    
    <!-- Top Navigation / Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-10 backdrop-blur-md bg-white/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center gap-3">
            <div class="bg-indigo-600 p-2 rounded-lg">
              <TicketIcon class="h-6 w-6 text-white" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-slate-900 leading-tight">Ticket System</h1>
              <p class="text-xs text-slate-500 font-medium">Workspace Dashboard</p>
            </div>
          </div>
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md focus:ring-2 focus:ring-offset-2 focus:ring-slate-900"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Create Ticket
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Stats Overview -->
      <section>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Using the passed props to style the slots or the component itself. 
               Assuming StatCard accepts these props. Adjusted for modern styling logic. -->
          <div 
            v-for="stat in statistics" 
            :key="stat.name"
            class="bg-white rounded-xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 flex items-start justify-between transition-transform hover:-translate-y-1 duration-300"
          >
            <div>
              <p class="text-sm font-medium text-slate-500">{{ stat.name }}</p>
              <h3 class="mt-2 text-3xl font-bold text-slate-900">{{ stat.value }}</h3>
              <p v-if="stat.trend" class="mt-1 text-xs font-medium text-emerald-600">{{ stat.trend }}</p>
            </div>
            <div :class="`p-3 rounded-lg ${stat.bg}`">
              <component :is="stat.icon" :class="`w-6 h-6 ${stat.color}`" />
            </div>
          </div>
        </div>
      </section>

      <!-- Filters & Toolbar -->
      <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          
          <!-- Search -->
          <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <SearchIcon class="h-5 w-5 text-slate-400" />
            </div>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search by title or ID..."
              class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-lg leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
            />
          </div>

          <!-- Filter Dropdowns -->
          <div class="flex items-center gap-3 overflow-x-auto pb-2 md:pb-0">
            <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
              <FilterIcon class="w-4 h-4 text-slate-500" />
              <span class="text-sm font-medium text-slate-600">Filters:</span>
            </div>

            <select
              v-model="filters.status"
              class="block w-40 pl-3 pr-10 py-2 text-base border-slate-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg"
            >
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="in_progress">In Progress</option>
            </select>

            <select
              v-model="filters.priority"
              class="block w-40 pl-3 pr-10 py-2 text-base border-slate-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg"
            >
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
            
            <button 
              @click="fetchTickets" 
              class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
              title="Refresh Data"
            >
              <RefreshIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </section>

      <!-- Main Data Table -->
      <section class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden relative min-h-[400px]">
        
        <!-- Loading Overlay -->
        <div v-if="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-20 flex items-center justify-center">
          <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
            <span class="mt-3 text-sm font-medium text-indigo-600">Loading tickets...</span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ticket Details</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Priority</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Users</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <!-- Empty State -->
              <tr v-if="!isLoading && tickets.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                      <TicketIcon class="h-6 w-6 text-slate-400" />
                    </div>
                    <h3 class="text-sm font-medium text-slate-900">No tickets found</h3>
                    <p class="text-sm text-slate-500 mt-1">Try adjusting your filters or create a new ticket.</p>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr 
                v-for="ticket in tickets.data" 
                :key="ticket.id" 
                class="hover:bg-slate-50 transition-colors duration-150 group"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div>
                      <div class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">
                        {{ ticket.title }}
                      </div>
                      <div class="text-sm text-slate-500 truncate max-w-xs mt-0.5">
                        {{ ticket.description }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <StatusBadge :status="ticket.status" class="shadow-sm" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <PriorityBadge :priority="ticket.priority" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col gap-1">
                    <div class="flex items-center text-sm text-slate-900">
                      <span class="text-slate-400 text-xs mr-2">From:</span> {{ ticket.user.name }}
                    </div>
                    <div v-if="ticket.approver" class="flex items-center text-sm text-slate-600">
                      <span class="text-slate-400 text-xs mr-2">To:</span> {{ ticket.approver.name }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-slate-600 bg-slate-100 px-2 py-1 rounded">
                    {{ formatDate(ticket.due_date) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click="viewTicket(ticket)"
                    class="text-slate-400 hover:text-indigo-600 transition-colors mr-4 font-medium"
                  >
                    View Details
                  </button>
                  <button
                    v-if="canApproveTicket(ticket)"
                    @click="openApprovalModal(ticket)"
                    class="inline-flex items-center px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-md transition-colors text-xs font-semibold uppercase tracking-wide"
                  >
                    Review
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
          <Pagination :meta="tickets.meta" @page-change="changePage" />
        </div>
      </section>

      <!-- Modals (Kept logic, ensure they have internal modern styling too) -->
      <transition 
        enter-active-class="transition ease-out duration-200"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <CreateTicketModal
          v-if="showCreateModal"
          :show="showCreateModal"
          :approvers="approvers"
          @close="showCreateModal = false"
          @created="handleTicketCreated"
        />
      </transition>

      <ViewTicketModal
        v-if="showViewModal"
        :show="showViewModal"
        :ticket="selectedTicket"
        @close="showViewModal = false"
        @status-updated="handleStatusUpdated"
      />

      <ApprovalModal
        v-if="showApprovalModal"
        :show="showApprovalModal"
        :ticket="selectedTicket"
        @close="showApprovalModal = false"
        @approved="handleStatusUpdated"
      />
    </main>
  </div>
</template>

<style scoped>
/* Optional: Custom scrollbar for table container if needed */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}
.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>