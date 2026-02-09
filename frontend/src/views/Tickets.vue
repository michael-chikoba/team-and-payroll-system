<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-indigo-100 selection:text-indigo-700">
   
      <!-- Top Navigation / Header -->
      <header class="sticky top-0 z-40 w-full backdrop-blur-xl bg-white/70 border-b border-gray-200/60 transition-all duration-300">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
          <div class="flex items-center gap-4">
            <div class="relative group">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-200"></div>
              <div class="relative bg-white p-2.5 rounded-xl border border-gray-200 shadow-sm">
                <TicketIcon class="h-6 w-6 text-indigo-600" />
              </div>
            </div>
            <div>
              <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">HelpDesk</h1>
              <p class="text-xs text-gray-500 font-medium tracking-wide">Workspace Dashboard</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <!-- Ticket Type Quick Stats Pills -->
            <div class="hidden md:flex items-center gap-3 bg-gray-100/50 p-1.5 rounded-xl border border-gray-200/60">
              <div class="px-3 py-1.5 rounded-lg flex items-center gap-2 bg-white shadow-sm border border-gray-100">
                <div class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></div>
                <span class="text-xs font-semibold text-gray-600">{{ computedTicketStats.issues }} Issues</span>
              </div>
              <div class="px-3 py-1.5 rounded-lg flex items-center gap-2 hover:bg-white hover:shadow-sm transition-all cursor-default">
                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-medium text-gray-500">{{ computedTicketStats.requests }} Requests</span>
              </div>
              <div class="px-3 py-1.5 rounded-lg flex items-center gap-2 hover:bg-white hover:shadow-sm transition-all cursor-default">
                <div class="h-1.5 w-1.5 rounded-full bg-purple-500"></div>
                <span class="text-xs font-medium text-gray-500">{{ computedTicketStats.change_requests }} Changes</span>
              </div>
            </div>
            <div class="h-8 w-px bg-gray-200 hidden md:block"></div>
            
            <!-- Refresh Button -->
            <button
              @click="refreshData"
              class="relative inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-gray-700 transition-all duration-200 bg-white border-2 border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 shadow-sm hover:shadow active:scale-95"
              title="Refresh Data"
            >
              <ArrowPathIcon class="w-5 h-5 mr-2 -ml-1" :class="{ 'animate-spin': isRefreshing }" />
              Refresh
            </button>
            
            <!-- Slack Integration Button (Admin Only) -->
            <button
              v-if="currentUser?.role === 'admin'"
              @click="openSlackConfigModal"
              class="relative inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-purple-700 transition-all duration-200 bg-white border-2 border-purple-200 rounded-xl hover:bg-purple-50 hover:border-purple-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-sm hover:shadow active:scale-95"
              title="Configure Slack Integration"
            >
              <svg class="w-5 h-5 mr-2 -ml-1" viewBox="0 0 24 24" fill="currentColor">
                <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
              </svg>
              Slack
            </button>
            
            <button
              @click="openCreateModal"
              class="relative inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 bg-gray-900 border border-transparent rounded-xl hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 shadow-lg shadow-gray-900/20 hover:shadow-gray-900/30 active:scale-95"
            >
              <PlusIcon class="w-5 h-5 mr-2 -ml-1" />
              New Ticket
            </button>
          </div>
        </div>
      </div>
    </header>
   
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
     
      <!-- Enhanced Stats Overview -->
      <section>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
          <!-- Total Tickets -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-600"></div>
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tickets</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ computedStatistics.total }}</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    All Types
                  </span>
                </div>
              </div>
              <div class="p-3 rounded-lg bg-blue-50 group-hover:scale-110 transition-transform duration-300">
                <TicketIcon class="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>
          
          <!-- Pending Review -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Review</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ computedStatistics.pending }}</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    Any admin can approve
                  </span>
                </div>
              </div>
              <div class="p-3 rounded-lg bg-amber-50 group-hover:scale-110 transition-transform duration-300">
                <ClockIcon class="w-6 h-6 text-amber-600" />
              </div>
            </div>
          </div>
          
          <!-- In Progress -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">In Progress</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ computedStatistics.in_progress }}</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    Active work
                  </span>
                </div>
              </div>
              <div class="p-3 rounded-lg bg-indigo-50 group-hover:scale-110 transition-transform duration-300">
                <PlayIcon class="w-6 h-6 text-indigo-600" />
              </div>
            </div>
          </div>
          
          <!-- Overdue -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Overdue</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ computedStatistics.overdue }}</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                    Needs attention
                  </span>
                </div>
              </div>
              <div class="p-3 rounded-lg bg-red-50 group-hover:scale-110 transition-transform duration-300">
                <ExclamationCircleIcon class="w-6 h-6 text-red-600" />
              </div>
            </div>
          </div>
          
          <!-- SLA Compliance -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 group relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">SLA Compliance</p>
                <h3 class="mt-2 text-3xl font-bold text-slate-900 tracking-tight">{{ computedStatistics.sla_percentage }}%</h3>
                <div class="flex items-center mt-2 gap-1">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    {{ computedStatistics.sla_compliant }}/{{ computedStatistics.sla_total }}
                  </span>
                </div>
              </div>
              <div class="p-3 rounded-lg bg-emerald-50 group-hover:scale-110 transition-transform duration-300">
                <CheckCircleIcon class="w-6 h-6 text-emerald-600" />
              </div>
            </div>
          </div>
        </div>
      </section>
      
      <!-- Enhanced Filters & Toolbar -->
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
              placeholder="Search by title, ID, category, or description..."
              class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-lg leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 sm:text-sm transition-all"
            />
          </div>
         
          <!-- Enhanced Filter Dropdowns -->
          <div class="flex items-center gap-3 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
            <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg shrink-0">
              <FunnelIcon class="w-4 h-4 text-slate-500" />
              <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Filters</span>
            </div>
           
            <!-- Type Filter -->
            <div class="relative">
              <select
                v-model="filters.type"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
              >
                <option value="">All Types</option>
                <option value="issue">Issue</option>
                <option value="request">Request</option>
                <option value="change_request">Change Request</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>
            
            <!-- Status Filter -->
            <div class="relative">
              <select
                v-model="filters.status"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
              >
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="pending">Pending</option>
                <option value="in_review">In Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="in_progress">In Progress</option>
                <option value="on_hold">On Hold</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>
            
            <!-- Priority Filter -->
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
            
            <!-- Category Filter -->
            <div class="relative">
              <select
                v-model="filters.category"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
              >
                <option value="">All Categories</option>
                <option v-for="category in uniqueCategories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>
            
            <!-- Assigned Filter -->
            <div class="relative">
              <select
                v-model="filters.assigned"
                class="appearance-none block w-36 pl-3 pr-8 py-2 text-sm border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-shadow cursor-pointer hover:border-slate-300"
                @change="handleAssignedFilterChange"
              >
                <option value="">All Tickets</option>
                <option value="assigned_to_me">Assigned to Me</option>
                <option value="created_by_me">Created by Me</option>
                <option value="unassigned">Unassigned</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </div>
            </div>
           
            <div class="h-8 w-px bg-slate-200 mx-1"></div>
            
            <!-- Clear Filters Button -->
            <button
              @click="clearFilters"
              class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 rounded-lg transition-all"
              title="Clear All Filters"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
            
            <!-- Refresh Button -->
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
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    Ticket Details
                    <span class="text-xs font-normal text-slate-400">(Type)</span>
                  </div>
                </th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Priority</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned To</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <!-- Empty State -->
              <tr v-if="!isLoading && tickets.data.length === 0">
                <td colspan="7" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                    <div class="h-16 w-16 rounded-full bg-slate-50 flex items-center justify-center mb-4 border border-slate-100">
                      <TicketIcon class="h-8 w-8 text-slate-300" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900">No tickets found</h3>
                    <p class="text-sm text-slate-500 mt-2 text-center">
                      There are no tickets matching your current filters. Try clearing filters or create a new one.
                    </p>
                    <button
                      @click="clearFilters"
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
                <!-- Ticket Details with Type -->
                <td class="px-6 py-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-1 min-w-[2rem] text-xs font-mono text-slate-400">#{{ ticket.id }}</div>
                    <div class="min-w-0 flex-1">
                      <!-- Type Badge -->
                      <div class="mb-1">
                        <span :class="[
                          'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                          getTypeBadgeClass(ticket.type)
                        ]">
                          <component :is="getTypeIcon(ticket.type)" class="w-3 h-3 mr-1" />
                          {{ getTypeLabel(ticket.type) }}
                        </span>
                      </div>
                      <!-- Title -->
                      <div class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors cursor-pointer truncate" @click="viewTicket(ticket)">
                        {{ ticket.title }}
                      </div>
                      <!-- Description -->
                      <div class="text-sm text-slate-500 line-clamp-1 mt-0.5">
                        {{ ticket.description }}
                      </div>
                      <!-- Tags -->
                      <div class="flex items-center gap-1 mt-1">
                        <span v-if="ticket.department" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700">
                          {{ ticket.department?.name }}
                        </span>
                        <span v-if="ticket.estimated_hours" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">
                          <ClockIcon class="w-3 h-3 mr-1" />
                          {{ ticket.estimated_hours }}h
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                
                <!-- Category -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-900">{{ ticket.category }}</span>
                    <span v-if="ticket.subcategory" class="text-xs text-slate-500">{{ ticket.subcategory }}</span>
                  </div>
                </td>
                
                <!-- Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col gap-1">
                    <StatusBadge :status="ticket.status" class="shadow-sm" />
                    <div v-if="ticket.is_overdue" class="mt-1">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                        <ClockIcon class="w-3 h-3 mr-1" />
                        Overdue
                      </span>
                    </div>
                  </div>
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
                
                <!-- Assigned To (Multiple Users) -->
                <td class="px-6 py-4">
                  <div class="flex flex-col gap-2">
                    <!-- Requester -->
                    <div class="flex items-center text-sm">
                      <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600 border border-slate-200 mr-2" title="Requester">
                        {{ getUserInitials(ticket.user) }}
                      </div>
                      <div class="flex flex-col">
                        <span class="text-xs text-slate-400 leading-none mb-0.5">From</span>
                        <span class="font-medium text-slate-900 text-xs truncate max-w-[120px]">{{ getUserName(ticket.user) }}</span>
                      </div>
                    </div>
                    <!-- Assigned Users -->
                    <div v-if="ticket.assigned_users && ticket.assigned_users.length > 0" class="flex flex-col gap-1">
                      <span class="text-xs text-slate-400 leading-none">Assigned to</span>
                      <div class="flex items-center flex-wrap gap-1">
                        <div
                          v-for="user in ticket.assigned_users.slice(0, 2)"
                          :key="user.id"
                          class="flex items-center gap-1 px-2 py-1 bg-indigo-50 rounded-full text-xs"
                          :title="getUserName(user)"
                        >
                          <div class="h-4 w-4 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-medium text-indigo-600">
                            {{ getUserInitials(user) }}
                          </div>
                          <span class="truncate max-w-[60px] text-indigo-700">{{ getUserName(user) }}</span>
                        </div>
                        <span v-if="ticket.assigned_users.length > 2" class="text-xs text-slate-500">
                          +{{ ticket.assigned_users.length - 2 }} more
                        </span>
                      </div>
                    </div>
                    <div v-else-if="ticket.approver" class="flex flex-col gap-1">
                      <span class="text-xs text-slate-400 leading-none">Approver</span>
                      <div class="flex items-center flex-wrap gap-1">
                        <div
                          class="flex items-center gap-1 px-2 py-1 bg-indigo-50 rounded-full text-xs"
                          :title="getApproverName(ticket)"
                        >
                          <div class="h-4 w-4 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-medium text-indigo-600">
                            {{ getApproverInitials(ticket) }}
                          </div>
                          <span class="truncate max-w-[60px] text-indigo-700">{{ getApproverName(ticket) }}</span>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 italic">Not assigned</div>
                  </div>
                </td>
                
                <!-- Date with SLA indicator -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col">
                    <div class="flex items-center text-sm text-slate-600">
                      <ClockIcon class="w-4 h-4 mr-1.5 text-slate-400" />
                      <span>{{ formatDate(ticket.due_date) }}</span>
                    </div>
                    <div class="mt-1">
                      <span :class="[
                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                        getSlaBadgeClass(ticket.sla_status)
                      ]">
                        {{ getSlaLabel(ticket.sla_status) }}
                      </span>
                    </div>
                    <div v-if="ticket.time_spent > 0" class="mt-1 text-xs text-slate-500">
                      Spent: {{ ticket.time_spent }}h
                    </div>
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
                   
                    <!-- Hero Action: Approve (Pending only - ANY ADMIN can approve) -->
                    <button
                      v-if="canApproveTicket(ticket)"
                      @click="openApprovalModal(ticket)"
                      class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-md transition-colors text-xs font-semibold shadow-sm ring-1 ring-emerald-700 ring-offset-1 ring-offset-transparent"
                      :title="getApproveButtonTitle(ticket)"
                    >
                      <CheckCircleIcon class="w-3.5 h-3.5 mr-1.5" />
                      Approve
                    </button>
                   
                    <!-- Hero Action: Update Status (Active only - ANY ADMIN) -->
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
                            <button
                              v-if="canTakeQuickActions(ticket)"
                              @click="handleActionClick('assign', ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600"
                            >
                              <UserGroupIcon class="w-4 h-4 mr-3 text-slate-400 group-hover:text-indigo-500" />
                              Assign Users
                            </button>
                          </div>
                         
                          <div class="py-1">
                            <button
                              v-if="canResolveTicket(ticket)"
                              @click="resolveTicket(ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50"
                            >
                              <CheckCircleIcon class="w-4 h-4 mr-3 text-emerald-400 group-hover:text-emerald-500" />
                              Mark Resolved
                            </button>
                            <button
                              v-if="canReopenTicket(ticket)"
                              @click="reopenTicket(ticket)"
                              class="group flex items-center w-full px-4 py-2 text-sm text-amber-600 hover:bg-amber-50"
                            >
                              <ArrowPathIcon class="w-4 h-4 mr-3 text-amber-400 group-hover:text-amber-500" />
                              Reopen Ticket
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
      <CreateTicketModal
        v-if="showCreateModal"
        :show="showCreateModal"
        :assignable-users="assignableUsers"
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
        :assignable-users="assignableUsers"
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
      
      <!-- Assign Users Modal -->
      <AssignUsersModal
        v-if="showAssignModal"
        :show="showAssignModal"
        :ticket="selectedTicket"
        :assignable-users="assignableUsers"
        @close="closeAssignModal"
        @assigned="handleUsersAssigned"
      />
      
      <!-- Slack Configuration Modal -->
      <SlackConfigModal
        v-if="showSlackConfigModal"
        :show="showSlackConfigModal"
        @close="closeSlackConfigModal"
        @saved="handleSlackConfigSaved"
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
  PencilIcon,
  TrashIcon,
  UserPlusIcon,
  EllipsisHorizontalIcon,
  AdjustmentsHorizontalIcon,
  XMarkIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
  UserGroupIcon
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
import AssignUsersModal from '@/components/Tickets/AssignUsersModal.vue'
import SlackConfigModal from '@/components/Tickets/SlackConfigModal.vue'

// ===== Reactive State =====
const tickets = ref({ data: [], meta: {} })
const statistics = ref({})
const approvers = ref([])
const assignableUsers = ref([])
const filters = ref({
  type: '',
  status: '',
  priority: '',
  category: '',
  assigned: '', // New filter for assigned tickets
  search: '',
  page: 1,
  sort_by: 'created_at',
  sort_order: 'desc'
})

const isLoading = ref(false)
const isRefreshing = ref(false)
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showApprovalModal = ref(false)
const showPriorityModal = ref(false)
const showReassignModal = ref(false)
const showStatusModal = ref(false)
const showAssignModal = ref(false)
const showSlackConfigModal = ref(false)
const selectedTicket = ref(null)
const currentUser = ref(null)
const showQuickActions = ref(null)

// ===== Computed Properties =====

/**
 * Computed statistics with fallback values
 * This ensures we always have valid numbers even if the backend returns null/undefined
 */
const computedStatistics = computed(() => {
  const stats = statistics.value || {}
  
  // Calculate total from by_type if not provided directly
  let total = stats.total || 0
  if (!total && stats.by_type) {
    const issueCount = Array.isArray(stats.by_type.issue) 
      ? stats.by_type.issue.reduce((sum, item) => sum + (item.count || 0), 0) 
      : 0
    const requestCount = Array.isArray(stats.by_type.request) 
      ? stats.by_type.request.reduce((sum, item) => sum + (item.count || 0), 0) 
      : 0
    const changeCount = Array.isArray(stats.by_type.change_request) 
      ? stats.by_type.change_request.reduce((sum, item) => sum + (item.count || 0), 0) 
      : 0
    total = issueCount + requestCount + changeCount
  }
  
  // Calculate SLA compliance
  const slaCompliant = stats.sla_compliance?.compliant || 0
  const slaTotal = stats.sla_compliance?.total || total
  const slaPercentage = slaTotal > 0 
    ? Math.round((slaCompliant / slaTotal) * 100) 
    : stats.sla_compliance?.percentage || 0
  
  return {
    // Total tickets
    total: total,
    
    // Pending review count
    pending: stats.pending || 0,
    
    // In progress count
    in_progress: stats.in_progress || 0,
    
    // Overdue count
    overdue: stats.overdue || 0,
    
    // SLA compliance - handle nested object
    sla_compliant: slaCompliant,
    sla_total: slaTotal,
    sla_percentage: slaPercentage
  }
})

/**
 * Computed ticket stats by type
 * Processes backend data to show counts for each ticket type
 */
const computedTicketStats = computed(() => {
  const stats = statistics.value || {}
  
  // If backend provides by_type breakdown
  if (stats.by_type) {
    return {
      issues: calculateTypeCount(stats.by_type.issue),
      requests: calculateTypeCount(stats.by_type.request),
      change_requests: calculateTypeCount(stats.by_type.change_request)
    }
  }
  
  // Fallback: calculate from tickets array if needed
  if (tickets.value.data && tickets.value.data.length > 0) {
    return {
      issues: tickets.value.data.filter(t => t.type === 'issue').length,
      requests: tickets.value.data.filter(t => t.type === 'request').length,
      change_requests: tickets.value.data.filter(t => t.type === 'change_request').length
    }
  }
  
  // Default fallback
  return {
    issues: 0,
    requests: 0,
    change_requests: 0
  }
})

/**
 * Helper function to calculate count from type data
 */
const calculateTypeCount = (typeData) => {
  if (!typeData) return 0
  
  if (Array.isArray(typeData)) {
    return typeData.reduce((sum, item) => sum + (item.count || 0), 0)
  }
  
  return typeData.count || 0
}

const filteredApprovers = computed(() => {
  if (!selectedTicket.value || !approvers.value.length) return []
  return approvers.value.filter(approver =>
    approver.id !== selectedTicket.value.approver_id
  )
})

const uniqueCategories = computed(() => {
  const categories = new Set()
  tickets.value.data.forEach(ticket => {
    if (ticket.category) {
      categories.add(ticket.category)
    }
  })
  return Array.from(categories).sort()
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

// ===== Ticket Type Helpers =====
const getTypeIcon = (type) => {
  switch(type) {
    case 'issue':
      return ExclamationCircleIcon
    case 'request':
      return DocumentTextIcon
    case 'change_request':
      return AdjustmentsHorizontalIcon
    default:
      return TicketIcon
  }
}

const getTypeLabel = (type) => {
  switch(type) {
    case 'issue':
      return 'Issue'
    case 'request':
      return 'Request'
    case 'change_request':
      return 'Change Request'
    default:
      return type
  }
}

const getTypeBadgeClass = (type) => {
  switch(type) {
    case 'issue':
      return 'bg-red-100 text-red-800'
    case 'request':
      return 'bg-emerald-100 text-emerald-800'
    case 'change_request':
      return 'bg-purple-100 text-purple-800'
    default:
      return 'bg-slate-100 text-slate-800'
  }
}

const getSlaBadgeClass = (slaStatus) => {
  switch(slaStatus) {
    case 'on_track':
      return 'bg-green-100 text-green-800'
    case 'warning':
      return 'bg-amber-100 text-amber-800'
    case 'breached':
      return 'bg-red-100 text-red-800'
    case 'completed':
      return 'bg-slate-100 text-slate-800'
    default:
      return 'bg-slate-100 text-slate-800'
  }
}

const getSlaLabel = (slaStatus) => {
  switch(slaStatus) {
    case 'on_track':
      return 'On Track'
    case 'warning':
      return 'Warning'
    case 'breached':
      return 'Breached'
    case 'completed':
      return 'Completed'
    default:
      return slaStatus
  }
}

// ===== API Fetchers =====
const fetchTickets = async () => {
  isLoading.value = true
  try {
    const params = { ...filters.value }
    Object.keys(params).forEach(key => !params[key] && delete params[key])
    
    // Use the assigned-to-me endpoint if filter is set
    if (params.assigned === 'assigned_to_me') {
      const response = await axios.get('/api/tickets/assigned-to-me')
      console.log('Assigned to me response:', response.data)
      
      if (response.data) {
        tickets.value = {
          data: Array.isArray(response.data.data) ? response.data.data : response.data,
          meta: response.data.meta || {}
        }
      }
    } else {
      // Use the regular tickets endpoint
      const response = await axios.get('/api/tickets', { params })
      console.log('Tickets response:', response.data)
      
      if (response.data) {
        tickets.value = {
          data: Array.isArray(response.data.data) ? response.data.data : [],
          meta: response.data.meta || {}
        }
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
    statistics.value = response.data || {}
  } catch (error) {
    console.error('Failed to load statistics:', error)
    statistics.value = {}
  }
}

const fetchAssignableUsers = async () => {
  try {
    const response = await axios.get('/api/tickets/assignable-users')
    
    if (response.data) {
      if (response.data.assignable_users && response.data.approvers) {
        assignableUsers.value = response.data.assignable_users.map(user => ({
          id: user.id,
          name: user.name || `${user.first_name || ''} ${user.last_name || ''}`.trim(),
          email: user.email || '',
          position: user.position || '',
          department: user.department || '',
          is_admin: user.is_admin || false
        }))
       
        approvers.value = response.data.approvers.map(approver => ({
          id: approver.id,
          name: approver.name || `${approver.first_name || ''} ${approver.last_name || ''}`.trim(),
          email: approver.email || '',
          business_id: approver.business_id,
          position: approver.position || 'Admin'
        }))
      } else if (Array.isArray(response.data)) {
        approvers.value = response.data.map(approver => ({
          id: approver.id,
          name: approver.name || `${approver.first_name || ''} ${approver.last_name || ''}`.trim(),
          email: approver.email || '',
          business_id: approver.business_id,
          position: approver.position || 'Admin'
        }))
       
        assignableUsers.value = [...approvers.value]
      } else {
        console.warn('Unexpected response format:', response.data)
        assignableUsers.value = []
        approvers.value = []
      }
    } else {
      assignableUsers.value = []
      approvers.value = []
    }
  } catch (error) {
    console.error('Error fetching assignable users:', error.response || error)
    assignableUsers.value = []
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

// ===== Helper Functions =====
const clearFilters = () => {
  filters.value = {
    type: '',
    status: '',
    priority: '',
    category: '',
    assigned: '', // Clear assigned filter too
    search: '',
    page: 1,
    sort_by: 'created_at',
    sort_order: 'desc'
  }
}

// Handle assigned filter change
const handleAssignedFilterChange = () => {
  // When selecting "Assigned to Me", we want to fetch from a different endpoint
  // Reset other filters that might conflict
  if (filters.value.assigned === 'assigned_to_me') {
    filters.value.page = 1
  }
}

// ===== Lifecycle =====
onMounted(() => {
  fetchUserWithRoles()
  fetchTickets()
  fetchStatistics()
  fetchAssignableUsers()
 
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
  const isPending = ticket.status === 'pending'
  
  return isAdmin && isPending
}

const getApproveButtonTitle = (ticket) => {
  if (!currentUser.value || !ticket) return 'Approve'
  
  const isAssignedApprover = ticket.approver_id === currentUser.value.id
  if (isAssignedApprover) {
    return 'Approve ticket (assigned to you)'
  } else {
    return 'Approve ticket (as business admin)'
  }
}

const canEditTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  return ticket.user_id === currentUser.value.id && ['draft', 'pending'].includes(ticket.status)
}

const canDeleteTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  return ticket.user_id === currentUser.value.id && ['draft', 'pending', 'rejected'].includes(ticket.status)
}

const canEditPriority = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isEditableStatus = ['pending', 'approved', 'in_progress'].includes(ticket.status)
  
  return isAdmin && isEditableStatus
}

const canReassignTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isReassignableStatus = ['pending', 'approved'].includes(ticket.status)
  
  return isAdmin && isReassignableStatus
}

const canTakeQuickActions = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  
  return isAdmin
}

const canUpdateStatus = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isUpdatable = !['completed', 'closed'].includes(ticket.status)
  
  return isAdmin && isUpdatable
}

const canResolveTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  const isAdmin = currentUser.value.role === 'admin'
  const isAssigned = ticket.assigned_users?.some(user => user.id === currentUser.value.id)
  const canResolve = ['in_progress', 'on_hold'].includes(ticket.status)
  return (isAdmin || isAssigned) && canResolve
}

const canReopenTicket = (ticket) => {
  if (!currentUser.value || !ticket) return false
  
  const isAdmin = currentUser.value.role === 'admin'
  const isCreator = ticket.user_id === currentUser.value.id
  const canReopen = ['resolved', 'closed'].includes(ticket.status)
  
  // Allow both admins AND ticket creators to reopen
  return (isAdmin || isCreator) && canReopen
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
  if (assignableUsers.value.length === 0 || approvers.value.length === 0) {
    await fetchAssignableUsers()
  }
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const editTicket = (ticket) => {
  closeQuickActions()
}

const deleteTicket = async (ticket) => {
  closeQuickActions()
  if (confirm(`Are you sure you want to delete ticket #${ticket.id}? This action cannot be undone.`)) {
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

const openAssignModal = (ticket) => {
  selectedTicket.value = ticket
  showAssignModal.value = true
  closeQuickActions()
}

const closeAssignModal = () => {
  showAssignModal.value = false
  selectedTicket.value = null
}

const openSlackConfigModal = () => {
  showSlackConfigModal.value = true
}

const closeSlackConfigModal = () => {
  showSlackConfigModal.value = false
}

const handleSlackConfigSaved = () => {
  closeSlackConfigModal()
  console.log('Slack configuration saved successfully')
}

const resolveTicket = async (ticket) => {
  closeQuickActions()
  if (confirm(`Mark ticket #${ticket.id} as resolved?`)) {
    try {
      await axios.post(`/api/tickets/${ticket.id}/update-status`, {
        status: 'resolved'
      })
      fetchTickets()
      fetchStatistics()
    } catch (error) {
      console.error('Error resolving ticket:', error)
      alert('Failed to resolve ticket')
    }
  }
}

const reopenTicket = async (ticket) => {
  closeQuickActions()
  if (confirm(`Reopen ticket #${ticket.id}?`)) {
    try {
      await axios.post(`/api/tickets/${ticket.id}/update-status`, {
        status: 'reopened'
      })
      fetchTickets()
      fetchStatistics()
    } catch (error) {
      console.error('Error reopening ticket:', error)
      alert('Failed to reopen ticket')
    }
  }
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
    case 'assign':
      openAssignModal(ticket)
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
    fetchAssignableUsers()
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

const handleUsersAssigned = () => {
  closeAssignModal()
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

/* Line clamp utility */
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}
</style>