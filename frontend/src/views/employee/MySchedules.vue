<template>
  <div class="employee-schedules-page">
    <!-- Background Decoration -->
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50"></div>
    
    <div class="relative z-10 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center">
              <div class="p-3 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-indigo-600 to-purple-700 bg-clip-text text-transparent">
                  My Schedules
                </h1>
                <p class="text-gray-600 mt-2 text-lg">View and manage your assigned schedules</p>
              </div>
            </div>
            
            <div class="flex items-center gap-3">
              <!-- Notifications Bell -->
              <div class="relative" ref="notificationContainer">
                <button 
                  type="button"
                  @click="toggleNotifications"
                  class="relative p-2 text-gray-600 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full transition-colors bg-white shadow-sm"
                >
                  <span class="sr-only">View notifications</span>
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                  <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full shadow-sm animate-pulse">
                    {{ unreadCount }}
                  </span>
                </button>
                
                <!-- Notifications Dropdown -->
                <transition
                  enter-active-class="transition ease-out duration-100"
                  enter-from-class="transform opacity-0 scale-95"
                  enter-to-class="transform opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-75"
                  leave-from-class="transform opacity-100 scale-100"
                  leave-to-class="transform opacity-0 scale-95"
                >
                  <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200 max-h-96 overflow-y-auto ring-1 ring-black ring-opacity-5">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                      <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                      <button type="button" v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        Mark all read
                      </button>
                    </div>
                    <div v-if="!notifications || notifications.length === 0" class="p-8 text-center text-gray-500">
                      <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                      </svg>
                      <p>No notifications</p>
                    </div>
                    <div v-else>
                      <div 
                        v-for="notif in notifications" 
                        :key="notif.id"
                        @click="handleNotificationClick(notif)"
                        :class="['p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors', !notif.is_read ? 'bg-blue-50' : 'bg-white']"
                      >
                        <div class="flex items-start">
                          <div :class="['p-2 rounded-full mr-3 flex-shrink-0', getNotificationColor(notif.notification_type)]">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ getNotificationMessage(notif) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(notif.created_at) }}</p>
                          </div>
                          <div v-if="!notif.is_read" class="w-2 h-2 bg-blue-600 rounded-full mt-2 ml-2"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>

              <button 
                @click="fetchSchedules" 
                :disabled="loading"
                class="inline-flex items-center px-4 py-2 bg-white border-2 border-indigo-200 text-indigo-700 font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:bg-indigo-50 disabled:opacity-50"
              >
                <svg :class="{'animate-spin': loading}" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Assigned</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
              </div>
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V3"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">In Progress</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.inProgress }}</p>
              </div>
              <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
              </div>
              <div class="p-3 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Overdue</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.overdue }}</p>
              </div>
              <div class="p-3 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Task Type</label>
              <select v-model="filters.type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="banner_creation">Banner Creation</option>
                <option value="weekly_overview">Weekly Overview</option>
                <option value="test_sequence">Test Sequence</option>
                <option value="live_games">Live Games</option>
                <option value="multibets">Multibets</option>
                <option value="news_section">News Section</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="filters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>
            
            <div class="flex items-end">
              <button @click="resetFilters" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                Reset Filters
              </button>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>

        <!-- Content Area -->
        <div v-if="loading" class="flex flex-col items-center justify-center p-12 bg-white rounded-lg shadow-sm">
          <div class="spinner mb-4"></div>
          <p class="text-lg text-gray-600">Loading schedules...</p>
        </div>

        <div v-else-if="filteredSchedules.length > 0" class="space-y-6">
          <!-- Schedule Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="schedule in filteredSchedules"
              :key="schedule.id"
              class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 cursor-pointer"
              @click="showScheduleDetails(schedule)"
            >
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900 truncate">{{ schedule.title }}</h3>
                  <p class="text-sm text-gray-500 mt-1">{{ getTypeName(schedule.schedule_type) }}</p>
                </div>
                <span :class="getPriorityClass(schedule.priority)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ schedule.priority?.toUpperCase() }}
                </span>
              </div>

              <!-- Description -->
              <p v-if="schedule.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                {{ schedule.description }}
              </p>

              <!-- Status -->
              <div class="mb-3">
                <span :class="getStatusClass(schedule.status)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                  <span :class="getStatusDotClass(schedule.status)" class="w-2 h-2 rounded-full mr-2"></span>
                  {{ formatStatus(schedule.status) }}
                </span>
                
                <!-- Report Status Badge -->
                <span v-if="schedule.has_report" class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                  </svg>
                  Report Submitted
                </span>
              </div>

              <!-- Dates -->
              <div class="space-y-2 mb-4">
                <div class="flex items-center text-sm text-gray-600">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span>Start: {{ formatDate(schedule.scheduled_date) }}</span>
                </div>
                <div class="flex items-center text-sm">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span :class="isOverdue(schedule) ? 'text-red-600 font-medium' : 'text-gray-600'">
                    Due: {{ formatDate(schedule.due_date) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2">
                <button
                  v-if="schedule.status === 'pending'"
                  @click.stop="updateStatus(schedule.id, 'in_progress')"
                  class="flex-1 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors"
                >
                  Start
                </button>
                <button
                  v-if="schedule.status === 'in_progress'"
                  @click.stop="updateStatus(schedule.id, 'completed')"
                  class="flex-1 px-3 py-2 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors"
                >
                  Complete
                </button>
                
                <!-- Submit Report Button -->
                <button
                  v-if="schedule.status === 'completed' && !schedule.has_report"
                  @click.stop="openReportModal(schedule)"
                  class="flex-1 px-3 py-2 text-xs font-medium text-white bg-purple-600 rounded hover:bg-purple-700 transition-colors"
                >
                  Submit Report
                </button>
                
                <button
                  @click.stop="showScheduleDetails(schedule)"
                  class="px-3 py-2 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200 transition-colors"
                >
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-xl border border-gray-200 p-12 text-center">
          <div class="text-6xl mb-6">📋</div>
          <h3 class="text-2xl font-semibold text-gray-800 mb-2">No Schedules Found</h3>
          <p class="text-gray-600 text-lg">
            {{ (filters.type || filters.status) ? 'Try adjusting your filters' : 'You have no schedules assigned at the moment' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="selectedSchedule"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="selectedSchedule = null"
      >
        <transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="selectedSchedule"
            class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
            @click.stop
          >
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
              <h2 class="text-2xl font-bold text-gray-900">Schedule Details</h2>
              <button
                type="button"
                @click="selectedSchedule = null"
                class="text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-5">
              <div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ selectedSchedule.title }}</h3>
                <div class="flex gap-2 mb-4">
                  <span :class="getPriorityClass(selectedSchedule.priority)" class="px-3 py-1 text-xs font-semibold rounded-full">
                    {{ selectedSchedule.priority?.toUpperCase() }}
                  </span>
                  <span :class="getStatusClass(selectedSchedule.status)" class="px-3 py-1 text-xs font-semibold rounded-full">
                    {{ formatStatus(selectedSchedule.status) }}
                  </span>
                  <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                    {{ getTypeName(selectedSchedule.schedule_type) }}
                  </span>
                  <span v-if="selectedSchedule.has_report" class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                    Report Submitted
                  </span>
                </div>
              </div>

              <div v-if="selectedSchedule.description">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <p class="text-gray-700 bg-gray-50 p-4 rounded-lg">{{ selectedSchedule.description }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                  <p class="text-gray-900">{{ formatDate(selectedSchedule.scheduled_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                  <p :class="isOverdue(selectedSchedule) ? 'text-red-600 font-semibold' : 'text-gray-900'">
                    {{ formatDate(selectedSchedule.due_date) }}
                  </p>
                </div>
              </div>

              <div v-if="selectedSchedule.notes">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <p class="text-gray-700 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">{{ selectedSchedule.notes }}</p>
              </div>

              <div v-if="selectedSchedule.meta_data && Object.keys(selectedSchedule.meta_data).length > 0">
                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Information</label>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <pre class="text-sm text-gray-700">{{ JSON.stringify(selectedSchedule.meta_data, null, 2) }}</pre>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-end gap-3">
              <button
                @click="selectedSchedule = null"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Close
              </button>
              <button
                v-if="selectedSchedule.status === 'pending'"
                @click="updateStatus(selectedSchedule.id, 'in_progress'); selectedSchedule = null"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Start Task
              </button>
              <button
                v-if="selectedSchedule.status === 'in_progress'"
                @click="updateStatus(selectedSchedule.id, 'completed'); selectedSchedule = null"
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
              >
                Mark Complete
              </button>
              <button
                v-if="selectedSchedule.status === 'completed' && !selectedSchedule.has_report"
                @click="openReportModal(selectedSchedule); selectedSchedule = null"
                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700"
              >
                Submit Report
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

    <!-- Submit Report Modal -->
    <SubmitReportModal
      :show="showReportModal"
      :schedule="selectedScheduleForReport"
      @close="showReportModal = false"
      @submitted="handleReportSubmitted"
    />

    <!-- Hidden audio element for notification sound -->
    <audio ref="notificationSound" preload="auto">
      <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTcIGWi77eeeTRAMUKbj8LdjHAU7k9nyyXkqBSl+zPLaizsKGGS57OahUBELTKXh77BYGwcugs/y24k3CBlou+3nnk0QDFCm4/C3YxwFO5PZ8sl5KgUpfszy2os7ChhluezmAA" type="audio/wav">
    </audio>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import SubmitReportModal from '@/components/reports/Reportmodel.vue';

const schedules = ref([]);
const notifications = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedSchedule = ref(null);
const showNotifications = ref(false);
const notificationContainer = ref(null);
const notificationSound = ref(null);
const lastNotificationCount = ref(0);

// Report modal state
const showReportModal = ref(false);
const selectedScheduleForReport = ref(null);

const filters = ref({
  type: '',
  status: ''
});

const unreadCount = computed(() => {
  return (notifications.value || []).filter(n => !n.is_read).length;
});

const fetchSchedules = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api/employee/schedules');
    schedules.value = response.data.schedules || response.data.data || response.data || [];
    console.log('Fetched schedules:', schedules.value);
  } catch (err) {
    console.error('Failed to fetch schedules:', err);
    error.value = err.response?.data?.message || 'Failed to load schedules. Please try again.';
    schedules.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/api/notifications');
    const rawNotifications = response.data.notifications || [];
    
    // Validate notification dates
    const validatedNotifications = rawNotifications.map(notif => ({
      ...notif,
      created_at: notif.created_at || new Date().toISOString()
    }));
    
    // Check if there are new notifications
    const newUnreadCount = validatedNotifications.filter(n => !n.is_read).length;
    
    // Play sound if new notifications arrived
    if (newUnreadCount > lastNotificationCount.value && lastNotificationCount.value > 0) {
      playNotificationSound();
      showBrowserNotification(validatedNotifications.find(n => !n.is_read));
    }
    
    lastNotificationCount.value = newUnreadCount;
    notifications.value = validatedNotifications;
  } catch (err) {
    console.error('Failed to fetch notifications:', err);
  }
};

const playNotificationSound = () => {
  try {
    if (notificationSound.value) {
      notificationSound.value.play().catch(err => {
        console.log('Could not play notification sound:', err);
      });
    }
  } catch (err) {
    console.log('Notification sound error:', err);
  }
};

const showBrowserNotification = (notif) => {
  if (!notif) return;
  
  // Check if browser supports notifications
  if ('Notification' in window && Notification.permission === 'granted') {
    const title = 'New Task Assigned';
    const body = getNotificationMessage(notif);
    
    new Notification(title, {
      body,
      icon: '/favicon.ico',
      badge: '/favicon.ico'
    });
  }
};

// Request notification permission
const requestNotificationPermission = async () => {
  if ('Notification' in window && Notification.permission === 'default') {
    await Notification.requestPermission();
  }
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
};

const handleNotificationClick = async (notif) => {
  if (!notif.is_read) await markAsRead(notif.id);
  
  if (notif.schedule_id) {
    const sched = schedules.value.find(s => s.id === notif.schedule_id);
    if (sched) {
      showScheduleDetails(sched);
    }
  }
  showNotifications.value = false;
};

const markAsRead = async (id) => {
  try {
    const idx = notifications.value.findIndex(n => n.id === id);
    if (idx !== -1) notifications.value[idx].is_read = true;
    await axios.put(`/api/notifications/${id}/read`);
  } catch (err) {
    console.error('Failed to mark as read:', err);
  }
};

const markAllRead = async () => {
  try {
    await axios.put('/api/notifications/read-all');
    notifications.value.forEach(n => n.is_read = true);
  } catch (err) {
    console.error('Failed to mark all as read:', err);
  }
};

const getNotificationColor = (type) => {
  const colors = {
    assigned: 'bg-blue-500',
    reminder: 'bg-yellow-500',
    overdue: 'bg-red-500',
    completed: 'bg-green-500'
  };
  return colors[type] || 'bg-gray-500';
};

const getNotificationMessage = (notif) => {
  if (!notif) return 'New notification';
  
  const title = (notif.schedule && notif.schedule.title) ? notif.schedule.title : 'Unknown Task';
  
  const messages = {
    assigned: `You've been assigned: ${title}`,
    reminder: `Reminder: ${title} is due soon`,
    overdue: `Overdue: ${title}`,
    completed: `Completed: ${title}`
  };
  
  return messages[notif.notification_type] || notif.message || 'New notification';
};

const formatDateTime = (dateString) => {
  if (!dateString || dateString === '') {
    return 'Just now';
  }
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return 'Recently';
    }
    
    const now = new Date();
    const diffMinutes = Math.floor((now - date) / 60000);
    
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
    
    const options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-GB', options);
  } catch (err) {
    console.error('Date formatting error:', err);
    return 'Recently';
  }
};

const handleClickOutside = (event) => {
  if (showNotifications.value && notificationContainer.value && !notificationContainer.value.contains(event.target)) {
    showNotifications.value = false;
  }
};

const stats = computed(() => {
  const total = schedules.value.length;
  const inProgress = schedules.value.filter(s => s.status === 'in_progress').length;
  const completed = schedules.value.filter(s => s.status === 'completed').length;
  const overdue = schedules.value.filter(s => {
    if (s.status === 'completed') return false;
    return s.due_date && new Date(s.due_date) < new Date();
  }).length;
  
  return { total, inProgress, completed, overdue };
});

const filteredSchedules = computed(() => {
  return schedules.value.filter(schedule => {
    if (filters.value.type && schedule.schedule_type !== filters.value.type) return false;
    if (filters.value.status && schedule.status !== filters.value.status) return false;
    return true;
  });
});

const resetFilters = () => {
  filters.value = { type: '', status: '' };
};

const updateStatus = async (scheduleId, newStatus) => {
  try {
    await axios.put(`/api/schedules/${scheduleId}`, { status: newStatus });
    
    const schedule = schedules.value.find(s => s.id === scheduleId);
    if (schedule) {
      schedule.status = newStatus;
      if (newStatus === 'completed') {
        schedule.completed_at = new Date().toISOString();
      }
    }
  } catch (err) {
    console.error('Failed to update status:', err);
    alert('Failed to update status. Please try again.');
  }
};

const showScheduleDetails = (schedule) => {
  selectedSchedule.value = schedule;
};

const openReportModal = (schedule) => {
  selectedScheduleForReport.value = schedule;
  showReportModal.value = true;
};

const handleReportSubmitted = async (report) => {
  console.log('Report submitted:', report);
  alert('Report submitted successfully!');
  
  // Update the schedule to reflect that a report has been submitted
  const schedule = schedules.value.find(s => s.id === report.schedule_id);
  if (schedule) {
    schedule.has_report = true;
  }
  
  // Refresh schedules to get updated data
  await fetchSchedules();
};

const isOverdue = (schedule) => {
  if (schedule.status === 'completed') return false;
  return schedule.due_date && new Date(schedule.due_date) < new Date();
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric' 
    });
  } catch {
    return 'N/A';
  }
};

const formatStatus = (status) => {
  if (!status) return 'Unknown';
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const getTypeName = (type) => {
  const names = {
    banner_creation: 'Banner Creation',
    weekly_overview: 'Weekly Overview',
    test_sequence: 'Test Sequence',
    live_games: 'Live Games',
    multibets: 'Multibets',
    news_section: 'News Section'
  };
  return names[type] || type;
};

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-gray-100 text-gray-800',
    moderate: 'bg-blue-100 text-blue-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  };
  return classes[priority] || 'bg-gray-100 text-gray-800';
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusDotClass = (status) => {
  const classes = {
    pending: 'bg-yellow-500',
    in_progress: 'bg-blue-500',
    completed: 'bg-green-500',
    overdue: 'bg-red-500'
  };
  return classes[status] || 'bg-gray-500';
};

onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  
  // Initial data fetch
  await fetchSchedules();
  await fetchNotifications();
  
  // Set initial notification count
  lastNotificationCount.value = unreadCount.value;
  
  // Request notification permission
  await requestNotificationPermission();
  
  // Poll for new notifications every 30 seconds
  const pollInterval = setInterval(fetchNotifications, 30000);
  
  // Cleanup on unmount
  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearInterval(pollInterval);
  });
});
</script>

<style scoped>
.employee-schedules-page {
  font-family: 'Inter', sans-serif;
  position: relative;
}

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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>