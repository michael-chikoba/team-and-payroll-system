<template>
  <div class="min-h-screen bg-gray-50 pb-8">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-indigo-600 shadow-lg sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center space-x-3">
              <div class="bg-white/20 p-2 rounded-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-white">
                  SuperAdmin Dashboard
                </h1>
                <p class="text-purple-100 mt-1">
                  System-wide business management & control
                </p>
              </div>
            </div>
          </div>
          
          <div class="flex flex-wrap items-center gap-3">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-purple-600 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition-colors"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Create Business
            </button>
          </div>
        </div>

        <!-- System Stats -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white/10 backdrop-blur-sm overflow-hidden shadow rounded-lg border border-white/20">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-purple-100 truncate">Total Businesses</dt>
              <dd class="mt-1 text-3xl font-semibold text-white">{{ stats.total_businesses || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm overflow-hidden shadow rounded-lg border border-white/20">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-purple-100 truncate">Active Businesses</dt>
              <dd class="mt-1 text-3xl font-semibold text-green-300">{{ stats.active_businesses || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm overflow-hidden shadow rounded-lg border border-white/20">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-purple-100 truncate">Total Employees</dt>
              <dd class="mt-1 text-3xl font-semibold text-white">{{ stats.total_employees || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm overflow-hidden shadow rounded-lg border border-white/20">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-purple-100 truncate">Suspended</dt>
              <dd class="mt-1 text-3xl font-semibold text-red-300">{{ stats.suspended_businesses || 0 }}</dd>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Filters and Search -->
      <div class="mb-6 bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="searchQuery"
                @input="debouncedSearch"
                type="search"
                placeholder="Search by name, email, ID, registration..."
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent sm:text-sm"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="filters.status"
              @change="fetchBusinesses"
              class="block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-purple-500 focus:outline-none focus:ring-purple-500 sm:text-sm"
            >
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- Subscription Tier Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Subscription Tier</label>
            <select
              v-model="filters.subscription_tier"
              @change="fetchBusinesses"
              class="block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-purple-500 focus:outline-none focus:ring-purple-500 sm:text-sm"
            >
              <option value="">All Tiers</option>
              <option value="basic">Basic</option>
              <option value="standard">Standard</option>
              <option value="premium">Premium</option>
              <option value="enterprise">Enterprise</option>
            </select>
          </div>
        </div>

        <!-- Additional Filters -->
        <div class="mt-4 flex flex-wrap items-center gap-3">
          <label class="inline-flex items-center">
            <input
              v-model="filters.is_trial"
              @change="fetchBusinesses"
              type="checkbox"
              class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Trial Only</span>
          </label>

          <label class="inline-flex items-center">
            <input
              v-model="filters.is_suspended"
              @change="fetchBusinesses"
              type="checkbox"
              class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Suspended Only</span>
          </label>

          <label class="inline-flex items-center">
            <input
              v-model="filters.at_limit"
              @change="fetchBusinesses"
              type="checkbox"
              class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">At Employee Limit</span>
          </label>

          <!-- Export Button -->
          <button
            @click="exportData"
            class="ml-auto inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
        </div>
      </div>

      <!-- Business List -->
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-purple-200 border-t-purple-600"></div>
          <p class="mt-4 text-sm text-gray-500">Loading businesses...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="businesses.length === 0" class="p-12 text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">No businesses found</h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ searchQuery ? 'Try adjusting your search or filters' : 'Get started by creating your first business' }}
          </p>
        </div>

        <!-- Table View -->
        <div v-else class="overflow-x-auto">
          <div class="inline-block min-w-full align-middle">
            <div class="overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                      Business
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                      Subscription
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                      Employees
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                      Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                      Owner
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap sticky right-0 bg-gray-50">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="business in businesses"
                    :key="business.id"
                    class="hover:bg-gray-50 transition-colors"
                    :class="{ 'bg-red-50': business.suspended_at }"
                  >
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center min-w-max">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ getInitials(business.name) }}</span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ business.name }}
                          </div>
                          <div class="text-sm text-gray-500">
                            {{ business.email }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        <span :class="getTierBadgeClass(business.subscription_tier)">
                          {{ formatTier(business.subscription_tier) }}
                        </span>
                      </div>
                      <div class="text-sm text-gray-500 mt-1">
                        <span v-if="business.is_trial" class="text-yellow-600">
                          Trial: {{ formatDate(business.trial_end_date) }}
                        </span>
                        <span v-else>
                          Until {{ formatDate(business.subscription_end_date) }}
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center min-w-[140px]">
                        <div class="flex-1">
                          <div class="text-sm font-medium text-gray-900">
                            {{ business.current_employee_count }} / {{ business.employee_limit }}
                          </div>
                          <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div
                              class="h-2 rounded-full transition-all"
                              :class="getUsageBarClass(business.employee_usage_percentage)"
                              :style="{ width: `${Math.min(business.employee_usage_percentage || 0, 100)}%` }"
                            ></div>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="getStatusClass(business)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ getStatusText(business) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div v-if="business.admins && business.admins.length > 0" class="min-w-max">
                        {{ business.admins[0].first_name }} {{ business.admins[0].last_name }}
                      </div>
                      <div v-else class="text-gray-400">No owner</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium sticky right-0 bg-white">
                      <div class="flex items-center justify-end space-x-2">
                        <button
                          @click="viewBusiness(business)"
                          class="text-purple-600 hover:text-purple-900 p-1 hover:bg-purple-50 rounded transition-colors"
                          title="View Details"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>
                        <button
                          @click="editBusiness(business)"
                          class="text-indigo-600 hover:text-indigo-900 p-1 hover:bg-indigo-50 rounded transition-colors"
                          title="Edit"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <button
                          v-if="business.suspended_at"
                          @click="activateBusiness(business)"
                          class="text-green-600 hover:text-green-900 p-1 hover:bg-green-50 rounded transition-colors"
                          title="Activate"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </button>
                        <button
                          v-else
                          @click="suspendBusiness(business)"
                          class="text-red-600 hover:text-red-900 p-1 hover:bg-red-50 rounded transition-colors"
                          title="Suspend"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="bg-white px-4 py-4 flex items-center justify-between border-t border-gray-200 sm:px-6">
              <div class="flex-1 flex justify-between sm:hidden">
                <button
                  @click="previousPage"
                  :disabled="meta.current_page === 1 || loading"
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <button
                  @click="nextPage"
                  :disabled="meta.current_page === meta.last_page || loading"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                  <div>
                    <p class="text-sm text-gray-700">
                      Showing
                      <span class="font-medium">{{ meta.from }}</span>
                      to
                      <span class="font-medium">{{ meta.to }}</span>
                      of
                      <span class="font-medium">{{ meta.total }}</span>
                      results
                    </p>
                  </div>
                  
                  <!-- Items per page selector -->
                  <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700">Show:</span>
                    <select
                      v-model="meta.per_page"
                      @change="changePerPage"
                      class="rounded-md border-gray-300 py-1 pl-3 pr-8 text-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                      :disabled="loading"
                    >
                      <option value="10">10</option>
                      <option value="20">20</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-700">per page</span>
                  </div>
                </div>
                
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <button
                      @click="previousPage"
                      :disabled="meta.current_page === 1 || loading"
                      class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span class="sr-only">Previous</span>
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                      </svg>
                    </button>
                    
                    <template v-for="page in getPageNumbers()" :key="page">
                      <button
                        v-if="page !== '...'"
                        @click="goToPage(page)"
                        :disabled="loading"
                        :class="[
                          'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                          page === meta.current_page
                            ? 'z-10 bg-purple-50 border-purple-500 text-purple-600'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        ]"
                      >
                        {{ page }}
                      </button>
                      <span
                        v-else
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                      >
                        ...
                      </span>
                    </template>

                    <button
                      @click="nextPage"
                      :disabled="meta.current_page === meta.last_page || loading"
                      class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span class="sr-only">Next</span>
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- View Business Modal -->
    <ViewBusinessModal
      v-if="selectedBusiness"
      :business="selectedBusiness"
      @close="selectedBusiness = null"
      @edit="handleEditFromView"
    />

    <!-- Edit Business Modal -->
    <EditBusinessModal
      v-if="editingBusiness"
      :business="editingBusiness"
      @close="editingBusiness = null"
      @updated="handleBusinessUpdated"
    />

    <!-- Create Business Modal -->
    <CreateBusinessModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleBusinessCreated"
    />

    <!-- Suspend Business Modal -->
    <SuspendBusinessModal
      v-if="suspendingBusiness"
      :business="suspendingBusiness"
      @close="suspendingBusiness = null"
      @suspended="handleBusinessSuspended"
    />

    <!-- Toast Notifications -->
    <div class="fixed bottom-4 right-4 z-50 space-y-2 max-w-md">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-center p-4 rounded-lg shadow-lg transform transition-all duration-300',
            toast.type === 'success' ? 'bg-green-50 border border-green-200' :
            toast.type === 'error' ? 'bg-red-50 border border-red-200' :
            toast.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' :
            'bg-blue-50 border border-blue-200'
          ]"
        >
          <div :class="[
            'flex-shrink-0',
            toast.type === 'success' ? 'text-green-400' :
            toast.type === 'error' ? 'text-red-400' :
            toast.type === 'warning' ? 'text-yellow-400' :
            'text-blue-400'
          ]">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path v-if="toast.type === 'success'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              <path v-else-if="toast.type === 'error'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <p :class="[
              'text-sm font-medium',
              toast.type === 'success' ? 'text-green-800' :
              toast.type === 'error' ? 'text-red-800' :
              toast.type === 'warning' ? 'text-yellow-800' :
              'text-blue-800'
            ]">
              {{ toast.message }}
            </p>
          </div>
          <button
            @click="removeToast(toast.id)"
            class="ml-4 flex-shrink-0 inline-flex text-gray-400 hover:text-gray-500 focus:outline-none"
          >
            <span class="sr-only">Close</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import ViewBusinessModal from './modals/ViewBusinessModal.vue'
import EditBusinessModal from './modals/EditBusinessModal.vue'
import CreateBusinessModal from './modals/CreateBusinessModal.vue'
import SuspendBusinessModal from './modals/SuspendBusinessModal.vue'

export default {
  name: 'SuperAdminBusinessManagement',
  
  components: {
    ViewBusinessModal,
    EditBusinessModal,
    CreateBusinessModal,
    SuspendBusinessModal,
  },
  
  setup() {
    const businesses = ref([])
    const loading = ref(false)
    const searchQuery = ref('')
    const showCreateModal = ref(false)
    const selectedBusiness = ref(null)
    const editingBusiness = ref(null)
    const suspendingBusiness = ref(null)
    const toasts = ref([])

    const filters = ref({
      status: '',
      subscription_tier: '',
      is_trial: false,
      is_suspended: false,
      at_limit: false,
    })

    const stats = ref({
      total_businesses: 0,
      active_businesses: 0,
      total_employees: 0,
      suspended_businesses: 0,
    })

    const meta = ref({
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
      from: 0,
      to: 0,
    })

    const fetchBusinesses = async () => {
      loading.value = true
      try {
        // Remove null/empty values from filters
        const cleanedFilters = Object.fromEntries(
          Object.entries(filters.value).filter(([_, v]) => v !== '' && v !== false)
        )

        const params = {
          page: meta.value.current_page,
          per_page: meta.value.per_page,
          search: searchQuery.value,
          ...cleanedFilters
        }

        const response = await axios.get('/api/superadmin/businesses', { params })
        
        if (response.data.success) {
          businesses.value = response.data.data.map(business => ({
            ...business,
            employee_usage_percentage: business.employee_limit > 0 
              ? (business.current_employee_count / business.employee_limit) * 100 
              : 0
          }))
          meta.value = response.data.meta
          stats.value = response.data.stats || {}
        }
      } catch (error) {
        console.error('Error fetching businesses:', error)
        showToast('Failed to fetch businesses', 'error')
      } finally {
        loading.value = false
      }
    }

    const debouncedSearch = debounce(() => {
      meta.value.current_page = 1
      fetchBusinesses()
    }, 500)

    const changePerPage = () => {
      meta.value.current_page = 1
      fetchBusinesses()
    }

    const openCreateModal = () => {
      showCreateModal.value = true
    }

    const viewBusiness = (business) => {
      selectedBusiness.value = { ...business }
    }

    const editBusiness = (business) => {
      editingBusiness.value = { ...business }
    }

    const handleEditFromView = (business) => {
      selectedBusiness.value = null
      editingBusiness.value = { ...business }
    }

    const suspendBusiness = (business) => {
      suspendingBusiness.value = { ...business }
    }

    const activateBusiness = async (business) => {
      if (!confirm(`Are you sure you want to activate "${business.name}"?`)) {
        return
      }

      try {
        const response = await axios.post(`/api/superadmin/businesses/${business.id}/activate`)
        
        if (response.data.success) {
          showToast('Business activated successfully', 'success')
          fetchBusinesses()
        }
      } catch (error) {
        console.error('Error activating business:', error)
        showToast(error.response?.data?.message || 'Failed to activate business', 'error')
      }
    }

    const handleBusinessUpdated = () => {
      editingBusiness.value = null
      fetchBusinesses()
      showToast('Business updated successfully', 'success')
    }

    const handleBusinessCreated = () => {
      showCreateModal.value = false
      fetchBusinesses()
      showToast('Business created successfully', 'success')
    }

    const handleBusinessSuspended = () => {
      suspendingBusiness.value = null
      fetchBusinesses()
      showToast('Business suspended successfully', 'success')
    }

    const exportData = async () => {
      try {
        showToast('Preparing export...', 'info')
        
        const cleanedFilters = Object.fromEntries(
          Object.entries(filters.value).filter(([_, v]) => v !== '' && v !== false)
        )
        
        const params = {
          ...cleanedFilters,
          search: searchQuery.value,
          export: 'csv',
          per_page: 1000
        }
        
        const response = await axios.get('/api/superadmin/businesses', { params })
        
        if (response.data.success) {
          const csvData = convertToCSV(response.data.data)
          downloadCSV(csvData, 'businesses-export.csv')
          showToast('Export completed successfully', 'success')
        }
      } catch (error) {
        console.error('Error exporting data:', error)
        showToast('Failed to export data', 'error')
      }
    }

    const convertToCSV = (data) => {
      if (!data || data.length === 0) return ''
      
      const headers = ['ID', 'Name', 'Email', 'Status', 'Subscription Tier', 'Employees', 'Employee Limit', 'Created At']
      const rows = data.map(business => [
        business.id,
        business.name,
        business.email,
        business.status,
        business.subscription_tier,
        business.current_employee_count,
        business.employee_limit,
        business.created_at
      ])
      
      const csvContent = [
        headers.join(','),
        ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
      ].join('\n')
      
      return csvContent
    }

    const downloadCSV = (csvContent, filename) => {
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      const url = URL.createObjectURL(blob)
      
      link.setAttribute('href', url)
      link.setAttribute('download', filename)
      link.style.visibility = 'hidden'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }

    const previousPage = () => {
      if (meta.value.current_page > 1) {
        meta.value.current_page--
        fetchBusinesses()
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const nextPage = () => {
      if (meta.value.current_page < meta.value.last_page) {
        meta.value.current_page++
        fetchBusinesses()
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const goToPage = (page) => {
      if (page !== meta.value.current_page) {
        meta.value.current_page = page
        fetchBusinesses()
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const getPageNumbers = () => {
      const pages = []
      const currentPage = meta.value.current_page
      const lastPage = meta.value.last_page
      
      if (lastPage <= 7) {
        for (let i = 1; i <= lastPage; i++) {
          pages.push(i)
        }
      } else {
        pages.push(1)
        
        if (currentPage > 3) {
          pages.push('...')
        }
        
        const start = Math.max(2, currentPage - 1)
        const end = Math.min(lastPage - 1, currentPage + 1)
        
        for (let i = start; i <= end; i++) {
          pages.push(i)
        }
        
        if (currentPage < lastPage - 2) {
          pages.push('...')
        }
        
        if (lastPage > 1) {
          pages.push(lastPage)
        }
      }
      
      return pages
    }

    const getInitials = (name) => {
      if (!name) return 'NA'
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }

    const getTierBadgeClass = (tier) => {
      const classes = {
        basic: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
        standard: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        premium: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
        enterprise: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'
      }
      return classes[tier] || classes.basic
    }

    const formatTier = (tier) => {
      return tier ? tier.charAt(0).toUpperCase() + tier.slice(1) : 'Basic'
    }

    const getUsageBarClass = (percentage) => {
      if (percentage >= 90) return 'bg-red-500'
      if (percentage >= 70) return 'bg-yellow-500'
      return 'bg-green-500'
    }

    const getStatusClass = (business) => {
      if (business.suspended_at) {
        return 'bg-red-100 text-red-800'
      }
      if (business.status === 'active') {
        return 'bg-green-100 text-green-800'
      }
      if (business.status === 'inactive') {
        return 'bg-gray-100 text-gray-800'
      }
      return 'bg-gray-100 text-gray-800'
    }

    const getStatusText = (business) => {
      if (business.suspended_at) return 'Suspended'
      return business.status ? business.status.charAt(0).toUpperCase() + business.status.slice(1) : 'Unknown'
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      try {
        return new Date(dateString).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch (error) {
        return 'Invalid Date'
      }
    }

    let toastId = 0
    const showToast = (message, type = 'info') => {
      const id = ++toastId
      toasts.value.push({ id, message, type })
      setTimeout(() => removeToast(id), 5000)
    }

    const removeToast = (id) => {
      toasts.value = toasts.value.filter(toast => toast.id !== id)
    }

    onMounted(() => {
      fetchBusinesses()
    })

    return {
      businesses,
      loading,
      searchQuery,
      showCreateModal,
      selectedBusiness,
      editingBusiness,
      suspendingBusiness,
      toasts,
      filters,
      stats,
      meta,
      fetchBusinesses,
      debouncedSearch,
      changePerPage,
      openCreateModal,
      viewBusiness,
      editBusiness,
      handleEditFromView,
      suspendBusiness,
      activateBusiness,
      handleBusinessUpdated,
      handleBusinessCreated,
      handleBusinessSuspended,
      exportData,
      previousPage,
      nextPage,
      goToPage,
      getPageNumbers,
      getInitials,
      getTierBadgeClass,
      formatTier,
      getUsageBarClass,
      getStatusClass,
      getStatusText,
      formatDate,
      removeToast,
    }
  }
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  scrollbar-color: #9333ea #f3f4f6;
}

.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #9333ea;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #7e22ce;
}

td.sticky {
  box-shadow: -2px 0 4px rgba(0, 0, 0, 0.05);
}

table {
  table-layout: auto;
}

@media (max-width: 768px) {
  .sticky {
    position: static !important;
  }
}
</style>