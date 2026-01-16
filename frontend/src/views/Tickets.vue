<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-indigo-100 selection:text-indigo-700">
   
    <!-- Top Navigation / Header -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center gap-3">
            <div class="bg-indigo-600/10 p-2 rounded-lg border border-indigo-100">
              <TicketIcon class="h-6 w-6 text-indigo-600" />
            </div>
            <div>
              <h1 class="text-lg font-bold text-slate-900 leading-tight tracking-tight">Ticket System</h1>
              <p class="text-xs text-slate-500 font-medium">Workspace Dashboard</p>
            </div>
          </div>
          <button
            @click="openCreateModal"
            class="group inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md focus:ring-2 focus:ring-offset-2 focus:ring-slate-900"
          >
            <PlusIcon class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-200" />
            Create Ticket
          </button>
        </div>
      </div>
    </header>
   
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
     
      <!-- Stats Overview -->
      <section>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="stat in statistics"
            :key="stat.name"
            class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden"
          >
            <!-- Decorative Accent -->
            <div :class="`absolute top-0 left-0 w-1 h-full ${stat.accent}`"></div>
            
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ stat.name }}</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ stat.value }}</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span v-if="stat.trend" class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    {{ stat.trend }}
                  </span>
                </div>
              </div>
              <div :class="`p-3 rounded-lg ${stat.bg} group-hover:scale-110 transition-transform duration-300`">
                <component :is="stat.icon" :class="`w-6 h-6 ${stat.color}`" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Filters & Toolbar -->
      <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-1">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 p-3">
          <!-- Search -->
          <div class="relative w-full md:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
            </div>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search by title, ID, or user..."
              class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-lg leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm transition-all"
            />
          </div>
         
          <!-- Filter Dropdowns -->
          <div class="flex items-center gap-3 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
            <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg shrink-0">
              <FunnelIcon class="w-4 h-4 text-slate-500" />
              <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Filters</span>
            </div>
            
            <div class="relative">
              <select
                v-model="filters.status"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="in_progress">In Progress</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>

            <div class="relative">
              <select
                v-model="filters.priority"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
              >
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
               <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>
           
            <div class="h-8 w-px bg-slate-200 mx-1"></div>

            <button
              @click="refreshData"
              class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 rounded-lg transition-all"
              title="Refresh Data"
            >
              <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isRefreshing }" />
            </button>
          </div>
        </div>
      </section>

      <!-- Main Data Table -->
      <section class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden relative min-h-[400px]">
        <!-- Loading Overlay -->
        <transition name="fade">
          <div v-if="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-[2px] z-20 flex items-center justify-center">
            <div class="flex flex-col items-center p-4 bg-white rounded-xl shadow-xl border border-slate-100">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
              <span class="mt-3 text-sm font-medium text-slate-600">Syncing tickets...</span>
            </div>
          </div>
        </transition>
       
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/50">
              <tr>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ticket Details</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Priority</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ownership</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <!-- Empty State -->
              <tr v-if="!isLoading && tickets.data.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                    <div class="h-16 w-16 rounded-full bg-slate-50 flex items-center justify-center mb-4 border border-slate-100">
                      <TicketIcon class="h-8 w-8 text-slate-300" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900">No tickets found</h3>
                    <p class="text-sm text-slate-500 mt-2 text-center">
                      There are no tickets matching your current filters. Try clearing filters or create a new one.
                    </p>
                    <button 
                      @click="filters = { status: '', priority: '', search: '', page: 1 }" 
                      class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                      Clear all filters
                    </button>
                  </div>
                </td>
              </tr>
             
              <!-- Data Rows -->
              <tr
                v-for="ticket in tickets.data"
                :key="ticket.id"
                class="hover:bg-slate-50/80 transition-colors duration-150 group"
              >
                <!-- Title & Desc -->
                <td class="px-6 py-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-1 min-w-[2rem] text-xs font-mono text-slate-400">#{{ ticket.id }}</div>
                    <div>
                      <div class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors cursor-pointer" @click="viewTicket(ticket)">
                        {{ ticket.title }}
                      </div>
                      <div class="text-sm text-slate-500 line-clamp-1 max-w-[240px] mt-0.5">
                        {{ ticket.description }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <StatusBadge :status="ticket.status" class="shadow-sm" />
                </td>

                <!-- Priority -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <PriorityBadge :priority="ticket.priority" />
                    <!-- Priority Edit Button -->
                    <button
                      v-if="canEditPriority(ticket)"
                      @click="openPriorityModal(ticket)"
                      class="p-1 text-slate-300 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-all opacity-0 group-hover:opacity-100 transform scale-90 hover:scale-100"
                      title="Change Priority"
                    >
                      <PencilIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>

                <!-- Users (Ownership) -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col gap-2">
                    <!-- Requester -->
                    <div class="flex items-center text-sm">
                       <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600 border border-slate-200 mr-2" title="Requester">
                        {{ getUserInitials(ticket.user) }}
                      </div>
                      <div class="flex flex-col">
                        <span class="text-xs text-slate-400 leading-none mb-0.5">From</span>
                        <span class="font-medium text-slate-900 text-xs">{{ getUserName(ticket.user) }}</span>
                      </div>
                    </div>

                    <!-- Approver -->
                    <div class="flex items-center text-sm">
                      <div class="h-6 w-6 rounded-full bg-indigo-50 flex items-center justify-center text-xs font-medium text-indigo-600 border border-indigo-100 mr-2" title="Approver">
                         <UserPlusIcon class="w-3 h-3" v-if="!ticket.approver" />
                         <span v-else>{{ getApproverInitials(ticket) }}</span>
                      </div>
                      <div class="flex flex-col relative w-full">
                         <span class="text-xs text-slate-400 leading-none mb-0.5">Assigned to</span>
                         <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-700 text-xs truncate max-w-[100px]" :class="{'text-slate-400 italic': !ticket.approver}">
                              {{ getApproverName(ticket) }}
                            </span>
                             <!-- Reassign Button -->
                            <button
                              v-if="canReassignTicket(ticket)"
                              @click="openReassignModal(ticket)"
                              class="text-slate-300 hover:text-indigo-600 transition-colors opacity-0 group-hover:opacity-100"
                              title="Reassign Ticket"
                            >
                              <UserPlusIcon class="w-3.5 h-3.5" />
                            </button>
                         </div>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Date -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center text-sm text-slate-600">
                    <ClockIcon class="w-4 h-4 mr-1.5 text-slate-400" />
                    <span>{{ formatDate(ticket.due_date) }}</span>
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <!-- Primary Action: View -->
                    <button
                      @click="viewTicket(ticket)"
                      class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 rounded-md transition-colors text-xs font-semibold border border-slate-200 shadow-sm"
                      title="View Details"
                    >
                      View
                    </button>
                    
                    <!-- Hero Action: Approve (Pending only) -->
                    <button
                      v-if="canApproveTicket(ticket)"
                      @click="openApprovalModal(ticket)"
                      class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-md transition-colors text-xs font-semibold shadow-sm ring-1 ring-emerald-700 ring-offset-1 ring-offset-transparent"
                    >
                      <CheckCircleIcon class="w-3.5 h-3.5 mr-1.5" />
                      Approve
                    </button>
                    
                    <!-- Hero Action: Update Status (Active only) -->
                    <button
                      v-if="canUpdateStatus(ticket) && ticket.status !== 'pending'"
                      @click="openStatusModal(ticket)"
                      class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-md transition-colors text-xs font-semibold shadow-sm"
                    >
                      <CheckCircleIcon class="w-3.5 h-3.5 mr-1.5" />
                      Update
                    </button>
                    
                    <!-- Context Menu for Secondary Actions -->
                    <div class="relative inline-block text-left">
                      <button
                        @click.stop="toggleQuickActions(ticket.id)"
                        class="inline-flex items-center p-1.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        :class="{'bg-slate-100 text-slate-600': showQuickActions === ticket.id}"
                      >
                        <EllipsisHorizontalIcon class="w-5 h-5" />
                      </button>
                      
                      <!-- Dropdown Menu -->
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                      >
                        <div
                          v-if="showQuickActions === ticket.id"
                          class="absolute right-0 mt-2 w-48 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 divide-y divide-slate-100 focus:outline-none"
                          role="menu"
                          @click.stop
                        >
                          <div class="py-1">
                            <button
                              v-if="canEditTicket(ticket)"
                              @click="editTicket(ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600"
                            >
                              <PencilIcon class="w-4 h-4 mr-3 text-slate-400 group-hover:text-indigo-500" />
                              Edit Details
                            </button>
                             <button
                              v-if="canTakeQuickActions(ticket)"
                              @click="handleActionClick('priority', ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600"
                            >
                              <AdjustmentsHorizontalIcon class="w-4 h-4 mr-3 text-slate-400 group-hover:text-indigo-500" />
                              Change Priority
                            </button>
                            <button
                              v-if="canTakeQuickActions(ticket)"
                              @click="handleActionClick('reassign', ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600"
                            >
                              <UserPlusIcon class="w-4 h-4 mr-3 text-slate-400 group-hover:text-indigo-500" />
                              Reassign Ticket
                            </button>
                          </div>
                          
                          <div class="py-1">
                            <button
                              v-if="canDeleteTicket(ticket)"
                              @click="deleteTicket(ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                            >
                              <TrashIcon class="w-4 h-4 mr-3 text-red-400 group-hover:text-red-500" />
                              Delete Ticket
                            </button>
                          </div>
                        </div>
                      </transition>
                    </div>
                  </div>
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

      <!-- Modals -->
      <!-- Keep existing modals as they are logic-bound, styling is handled within them usually -->
      <CreateTicketModal
        v-if="showCreateModal"
        :show="showCreateModal"
        :approvers="approvers"
        @close="closeCreateModal"
        @created="handleTicketCreated"
      />
     
      <ViewTicketModal
        v-if="showViewModal"
        :show="showViewModal"
        :ticket="selectedTicket"
        @close="closeViewModal"
        @status-updated="handleStatusUpdated"
      />
     
      <ApprovalModal
        v-if="showApprovalModal"
        :show="showApprovalModal"
        :ticket="selectedTicket"
        @close="closeApprovalModal"
        @approved="handleStatusUpdated"
      />
      
      <PriorityEditModal
        v-if="showPriorityModal"
        :show="showPriorityModal"
        :ticket="selectedTicket"
        @close="closePriorityModal"
        @priority-updated="handlePriorityUpdated"
      />
      
      <ReassignTicketModal
        v-if="showReassignModal"
        :show="showReassignModal"
        :ticket="selectedTicket"
        :approvers="filteredApprovers"
        @close="closeReassignModal"
        @reassigned="handleTicketReassigned"
      />
      
      <StatusUpdateModal
        v-if="showStatusModal"
        :show="showStatusModal"
        :ticket="selectedTicket"
        @close="closeStatusModal"
        @status-updated="handleStatusUpdated"
      />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue'
import axios from 'axios'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ArrowPathIcon,
  TicketIcon,
  ClockIcon,
  CheckCircleIcon,
  PlayIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  UserPlusIcon,
  EllipsisHorizontalIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'
import StatusBadge from '@/components/StatusBadge.vue'
import PriorityBadge from '@/components/PriorityBadge.vue'
import Pagination from '@/components/Pagination.vue'
import CreateTicketModal from '@/components/Tickets/CreateTicketModal.vue'
import ViewTicketModal from '@/components/Tickets/ViewTicketModal.vue'
import ApprovalModal from '@/components/Tickets/ApprovalModal.vue'
import PriorityEditModal from '@/components/Tickets/PriorityEditModal.vue'
import ReassignTicketModal from '@/components/Tickets/ReassignTicketModal.vue'
import StatusUpdateModal from '@/components/Tickets/StatusUpdateModal.vue'

// ===== Reactive State =====
const tickets = ref({ data: [], meta: {} })
const statistics = ref([])
const approvers = ref([])
const filters = ref({
  status: '',
  priority: '',
  search: '',
  page: 1
})
const isLoading = ref(false)
const isRefreshing = ref(false)
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showApprovalModal = ref(false)
const showPriorityModal = ref(false)
const showReassignModal = ref(false)
const showStatusModal = ref(false)
const selectedTicket = ref(null)
const currentUser = ref(null)
const showQuickActions = ref(null)

// ===== Computed Properties =====
const filteredApprovers = computed(() => {
  if (!selectedTicket.value || !approvers.value.length) return []
  return approvers.value.filter(approver => 
    approver.id !== selectedTicket.value.approver_id
  )
})

// ===== Helper Functions for Initials =====
const getInitials = (name) => {
  if (!name) return '??'
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getUserInitials = (user) => {
  return getInitials(getUserName(user))
}

const getApproverInitials = (ticket) => {
  return getInitials(getApproverName(ticket))
}

// ===== API Fetchers =====
const fetchTickets = async () => {
  isLoading.value = true
  try {
    const params = { ...filters.value }
    Object.keys(params).forEach(key => !params[key] && delete params[key])
   
    const response = await axios.get('/api/tickets', { params })
   
    if (response.data) {
      tickets.value = {
        data: Array.isArray(response.data.data) ? response.data.data : [],
        meta: response.data.meta || {}
      }
    }
  } catch (error) {
    console.error('Error fetching tickets:', error)
    tickets.value = { data: [], meta: {} }
  } finally {
    setTimeout(() => isLoading.value = false, 300)
  }
}

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/api/tickets/statistics')
    const stats = response.data || {}
   
    statistics.value = [
      {
        name: 'Total Tickets',
        value: stats.total || 0,
        trend: '+12%',
        icon: TicketIcon,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        accent: 'bg-blue-600'
      },
      {
        name: 'Pending Review',
        value: stats.pending || 0,
        trend: 'Wait time: 2h',
        icon: ClockIcon,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        accent: 'bg-amber-500'
      },
      {
        name: 'Approved',
        value: stats.approved || 0,
        trend: 'This week',
        icon: CheckCircleIcon,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50',
        accent: 'bg-emerald-500'
      },
      {
        name: 'In Progress',
        value: stats.in_progress || 0,
        trend: 'Active',
        icon: PlayIcon,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
        accent: 'bg-indigo-500'
      },
    ]
  } catch (error) {
    console.error('Failed to load stats:', error)
    statistics.value = []
  }
}

const fetchApprovers = async () => {
  try {
    const response = await axios.get('/api/tickets/approvers')
    if (Array.isArray(response.data)) {
      approvers.value = response.data.map(approver => ({
        id: approver.id,
        name: approver.name || `${approver.first_name || ''} ${approver.last_name || ''}`.trim(),
        email: approver.email || '',
        business_id: approver.business_id,
        position: approver.position || 'Admin'
      }))
    } else {
      approvers.value = []
    }
  } catch (error) {
    console.error('Error fetching approvers:', error)
    approvers.value = []
  }
}

const fetchUserWithRoles = async () => {
  try {
    const response = await axios.get('/api/user')
    if (response.data) {
      const userData = response.data.user || response.data
      currentUser.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
    }
  } catch (error) {
    console.error('Failed to fetch user:', error)
    const userStr = window.localStorage?.getItem('user')
    if (userStr) {
      const parsedUser = JSON.parse(userStr)
      currentUser.value = parsedUser.user || parsedUser
    }
  }
}

// ===== Lifecycle =====
onMounted(() => {
  fetchUserWithRoles()
  fetchTickets()
  fetchStatistics()
  fetchApprovers()
  
  document.addEventListener('click', closeQuickActions)
})

onUnmounted(() => {
  document.removeEventListener('click', closeQuickActions)
})

// ===== Watchers =====
watch(filters, () => {
  fetchTickets()
}, { deep: true })

// ===== Permission Check Functions =====
const canApproveTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  const isPending = ticket.status === 'pending'
  return isAdmin && isAssignedApprover && isPending
}

const canEditTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  return ticket.user_id === currentUser.value.id && ticket.status === 'pending'
}

const canDeleteTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  return ticket.user_id === currentUser.value.id && ticket.status === 'pending'
}

const canEditPriority = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  const isEditableStatus = ['pending', 'approved'].includes(ticket.status)
  return isAdmin && isAssignedApprover && isEditableStatus
}

const canReassignTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  const isReassignableStatus = ['pending', 'approved'].includes(ticket.status)
  return isAdmin && isAssignedApprover && isReassignableStatus
}

const canTakeQuickActions = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  return isAdmin && isAssignedApprover
}

const canUpdateStatus = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  const isUpdatable = !['completed', 'closed'].includes(ticket.status)
  return isAdmin && isAssignedApprover && isUpdatable
}

const getApproverName = (ticket) => {
  const approver = ticket?.approver
  if (!approver) return 'Unassigned'
  if (approver.name) return approver.name
  if (approver.first_name || approver.last_name) {
    return `${approver.first_name || ''} ${approver.last_name || ''}`.trim()
  }
  return approver.email || 'Unassigned'
}

const getUserName = (user) => {
  if (!user) return 'Unknown User'
  if (user.name) return user.name
  if (user.first_name || user.last_name) {
    return `${user.first_name || ''} ${user.last_name || ''}`.trim()
  }
  return user.email || 'Unknown User'
}

// ===== Actions =====
const openCreateModal = async () => {
  if (approvers.value.length === 0) {
    await fetchApprovers()
  }
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const editTicket = (ticket) => {
  // Logic to trigger edit mode or modal
  console.log('Edit ticket', ticket.id)
  closeQuickActions()
}

const deleteTicket = async (ticket) => {
  closeQuickActions()
  if (confirm(`Are you sure you want to delete ticket #${ticket.id}?`)) {
    try {
      await axios.delete(`/api/tickets/${ticket.id}`)
      fetchTickets()
      fetchStatistics()
    } catch (error) {
      console.error('Error deleting ticket:', error)
      alert('Failed to delete ticket')
    }
  }
}

const viewTicket = (ticket) => {
  selectedTicket.value = ticket
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedTicket.value = null
}

const openApprovalModal = (ticket) => {
  selectedTicket.value = ticket
  showApprovalModal.value = true
  closeQuickActions()
}

const closeApprovalModal = () => {
  showApprovalModal.value = false
  selectedTicket.value = null
}

const openPriorityModal = (ticket) => {
  selectedTicket.value = ticket
  showPriorityModal.value = true
  closeQuickActions()
}

const closePriorityModal = () => {
  showPriorityModal.value = false
  selectedTicket.value = null
}

const openReassignModal = (ticket) => {
  selectedTicket.value = ticket
  showReassignModal.value = true
  closeQuickActions()
}

const closeReassignModal = () => {
  showReassignModal.value = false
  selectedTicket.value = null
}

const openStatusModal = (ticket) => {
  selectedTicket.value = ticket
  showStatusModal.value = true
  closeQuickActions()
}

const closeStatusModal = () => {
  showStatusModal.value = false
  selectedTicket.value = null
}

const handleActionClick = (actionType, ticket) => {
  closeQuickActions()
  switch(actionType) {
    case 'priority':
      openPriorityModal(ticket)
      break
    case 'reassign':
      openReassignModal(ticket)
      break
    case 'status':
      openStatusModal(ticket)
      break
  }
}

const toggleQuickActions = (ticketId) => {
  if (showQuickActions.value === ticketId) {
    showQuickActions.value = null
  } else {
    showQuickActions.value = ticketId
  }
}

const closeQuickActions = () => {
  showQuickActions.value = null
}

const changePage = (page) => {
  filters.value.page = page
}

const refreshData = async () => {
  isRefreshing.value = true
  await Promise.all([
    fetchTickets(),
    fetchStatistics(),
    fetchApprovers()
  ])
  isRefreshing.value = false
}

const handleTicketCreated = () => {
  closeCreateModal()
  filters.value.status = ''
  fetchTickets()
  fetchStatistics()
}

const handleStatusUpdated = () => {
  closeViewModal()
  closeApprovalModal()
  closeStatusModal()
  fetchTickets()
  fetchStatistics()
  window.dispatchEvent(new CustomEvent('ticket-updated'))
}

const handlePriorityUpdated = () => {
  closePriorityModal()
  fetchTickets()
  fetchStatistics()
  window.dispatchEvent(new CustomEvent('ticket-updated'))
}

const handleTicketReassigned = () => {
  closeReassignModal()
  fetchTickets()
  fetchStatistics()
  window.dispatchEvent(new CustomEvent('ticket-updated'))
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch (error) {
    return 'Invalid Date'
  }
}
</script>

<style scoped>
/* Scrollbar Styling */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}
.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>