<template>
  <div class="superadmin-management">
    <!-- ── Main (header card lives inside here) ───────── -->
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="management-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                  <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">SuperAdmin Dashboard</h1>
                <p class="subtitle">System-wide business management & control</p>
                <div class="role-meta">
                  <span class="role-badge">SuperAdmin View</span>
                  <span class="month-badge">{{ stats.total_businesses || 0 }} Businesses</span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">
              <button @click="openCreateModal" class="btn-accent">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create Business
              </button>
            </div>
          </div>

          <!-- System Stats Badge -->
          <div class="view-badge">
            <div class="view-content">
              <span class="view-primary">{{ meta.total || 0 }}</span>
              <div class="view-details">
                <span class="view-label">Total Records</span>
                <span class="view-total">{{ meta.from }}-{{ meta.to }} shown</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- System Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card" style="--accent:#8b5cf6;">
          <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Total Businesses</span>
            <div class="stat-number">{{ stats.total_businesses || 0 }}</div>
            <div class="stat-trend">System-wide</div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#10b981;">
          <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Active Businesses</span>
            <div class="stat-number">{{ stats.active_businesses || 0 }}</div>
            <div class="stat-trend">
              <span class="trend-up">Operational</span>
            </div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#3b82f6;">
          <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Total Employees</span>
            <div class="stat-number">{{ stats.total_employees || 0 }}</div>
            <div class="stat-trend">Across all businesses</div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#ef4444;">
          <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Suspended</span>
            <div class="stat-number">{{ stats.suspended_businesses || 0 }}</div>
            <div class="stat-trend">
              <span class="trend-down">Requires attention</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Card -->
      <div class="filters-card">
        <div class="filters-header">
          <h3>Filter Businesses</h3>
          <span class="filter-count">{{ meta.total || 0 }} businesses</span>
        </div>
        
        <div class="filters-grid">
          <!-- Search -->
          <div class="search-wrapper full-width">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="search"
              placeholder="Search by name, email, ID, registration..."
              class="filter-search"
            />
          </div>

          <div class="filter-row">
            <!-- Status Filter -->
            <div class="filter-item">
              <label class="filter-label">Status</label>
              <div class="select-wrapper">
                <select v-model="filters.status" @change="fetchBusinesses" class="filter-select">
                  <option value="">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="suspended">Suspended</option>
                  <option value="inactive">Inactive</option>
                </select>
                <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
            </div>

            <!-- Subscription Tier Filter -->
            <div class="filter-item">
              <label class="filter-label">Subscription Tier</label>
              <div class="select-wrapper">
                <select v-model="filters.subscription_tier" @change="fetchBusinesses" class="filter-select">
                  <option value="">All Tiers</option>
                  <option value="basic">Basic</option>
                  <option value="standard">Standard</option>
                  <option value="premium">Premium</option>
                  <option value="enterprise">Enterprise</option>
                </select>
                <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
            </div>
          </div>

          <!-- Additional Filters -->
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input
                v-model="filters.is_trial"
                @change="fetchBusinesses"
                type="checkbox"
              />
              <span>Trial Only</span>
            </label>

            <label class="checkbox-label">
              <input
                v-model="filters.is_suspended"
                @change="fetchBusinesses"
                type="checkbox"
              />
              <span>Suspended Only</span>
            </label>

            <label class="checkbox-label">
              <input
                v-model="filters.at_limit"
                @change="fetchBusinesses"
                type="checkbox"
              />
              <span>At Employee Limit</span>
            </label>

            <!-- Export Button -->
            <button
              @click="exportData"
              class="btn-secondary export-btn"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              Export CSV
            </button>
          </div>
        </div>
      </div>

      <!-- Business List -->
      <div class="content-card">
        <div class="card-header">
          <h3>Business Management</h3>
          <span class="count-badge">{{ businesses.length }} shown</span>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading businesses...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="businesses.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
          </div>
          <h3>No Businesses Found</h3>
          <p>
            {{ searchQuery || filters.status || filters.subscription_tier ? 'Try adjusting your filters' : 'Get started by creating your first business' }}
          </p>
        </div>

        <!-- Table View -->
        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Business</th>
                <th>Subscription</th>
                <th>Employees</th>
                <th>Status</th>
                <th>Owner</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="business in businesses"
                :key="business.id"
                :class="{ 'row-warning': business.suspended_at }"
              >
                <td>
                  <div class="business-info">
                    <div class="business-avatar">{{ getInitials(business.name) }}</div>
                    <div class="business-details">
                      <span class="business-name">{{ business.name }}</span>
                      <span class="business-email">{{ business.email }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="subscription-info">
                    <span :class="['tier-badge', business.subscription_tier]">
                      {{ formatTier(business.subscription_tier) }}
                    </span>
                    <span v-if="business.is_trial" class="trial-badge">
                      Trial ends: {{ formatDate(business.trial_end_date) }}
                    </span>
                    <span v-else class="subscription-date">
                      Until {{ formatDate(business.subscription_end_date) }}
                    </span>
                  </div>
                </td>
                <td>
                  <div class="employee-usage">
                    <div class="usage-header">
                      <span class="usage-count">{{ business.current_employee_count }} / {{ business.employee_limit }}</span>
                    </div>
                    <div class="progress-bar">
                      <div
                        class="progress-fill"
                        :class="getUsageBarClass(business.employee_usage_percentage)"
                        :style="{ width: `${Math.min(business.employee_usage_percentage || 0, 100)}%` }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span :class="['status-badge', getStatusClass(business)]">
                    {{ getStatusText(business) }}
                  </span>
                </td>
                <td>
                  <div class="owner-info">
                    <span v-if="business.admins && business.admins.length > 0">
                      {{ business.admins[0].first_name }} {{ business.admins[0].last_name }}
                    </span>
                    <span v-else class="text-muted">No owner</span>
                  </div>
                </td>
                <td class="text-right">
                  <div class="action-buttons">
                    <button
                      @click="viewBusiness(business)"
                      class="btn-icon-small"
                      title="View Details"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"></path>
                      </svg>
                    </button>
                    <button
                      @click="editBusiness(business)"
                      class="btn-icon-small"
                      title="Edit"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"></path>
                        <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"></polygon>
                      </svg>
                    </button>
                    <button
                      v-if="business.suspended_at"
                      @click="activateBusiness(business)"
                      class="btn-icon-small success"
                      title="Activate"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                    </button>
                    <button
                      v-else
                      @click="suspendBusiness(business)"
                      class="btn-icon-small warning"
                      title="Suspend"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="pagination-bar">
          <div class="pagination-info">
            <span>Showing {{ meta.from }} to {{ meta.to }} of {{ meta.total }} results</span>
            
            <!-- Items per page selector -->
            <div class="per-page-selector">
              <span>Show:</span>
              <div class="select-wrapper small">
                <select
                  v-model="meta.per_page"
                  @change="changePerPage"
                  class="per-page-select"
                  :disabled="loading"
                >
                  <option value="10">10</option>
                  <option value="20">20</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
              <span>per page</span>
            </div>
          </div>

          <div class="pagination-controls">
            <button
              @click="previousPage"
              :disabled="meta.current_page === 1 || loading"
              class="btn-icon-small"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            
            <template v-for="page in getPageNumbers()" :key="page">
              <button
                v-if="page !== '...'"
                @click="goToPage(page)"
                :disabled="loading"
                :class="['page-number', { active: page === meta.current_page }]"
              >
                {{ page }}
              </button>
              <span v-else class="page-ellipsis">...</span>
            </template>

            <button
              @click="nextPage"
              :disabled="meta.current_page === meta.last_page || loading"
              class="btn-icon-small"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

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
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', toast.type]"
        >
          <div class="toast-icon">
            <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <svg v-else-if="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="toast-message">{{ toast.message }}</div>
          <button @click="removeToast(toast.id)" class="toast-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
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

    const formatTier = (tier) => {
      return tier ? tier.charAt(0).toUpperCase() + tier.slice(1) : 'Basic'
    }

    const getUsageBarClass = (percentage) => {
      if (percentage >= 90) return 'danger'
      if (percentage >= 70) return 'warning'
      return 'success'
    }

    const getStatusClass = (business) => {
      if (business.suspended_at) return 'cancelled'
      if (business.status === 'active') return 'approved'
      if (business.status === 'inactive') return 'pending'
      return 'neutral'
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
/* ── Base ─────────────────────────────────────────────── */
.superadmin-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #001f5b 0%, #002a7a 50%, #0040c1 100%);
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.user-greeting {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
  flex-wrap: wrap;
}

/* Avatar */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
  flex-wrap: wrap;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #7c3aed;
  display: inline-flex;
  align-items: center;
}

/* Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-accent {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
  white-space: nowrap;
}

.btn-accent:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
}

.btn-secondary {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* View badge */
.view-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 130px;
  flex-shrink: 0;
}

.view-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: #8b5cf6;
  line-height: 1;
}

.view-details {
  display: flex;
  flex-direction: column;
}

.view-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.view-total {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* ── Stats Grid ───────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.4rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.1rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08);
  border-color: var(--accent);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--accent);
}

.stat-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.stat-trend {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.trend-up {
  color: #10b981;
  font-weight: 700;
}

.trend-down {
  color: #ef4444;
  font-weight: 700;
}

/* ── Filters Card ─────────────────────────────────────── */
.filters-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.75rem;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.filters-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
}

.filter-count {
  font-size: 0.8rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
}

.filters-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.select-wrapper {
  position: relative;
  width: 100%;
}

.select-wrapper.small {
  width: 70px;
  display: inline-block;
}

.filter-select {
  width: 100%;
  padding: 0.625rem 2rem 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
  appearance: none;
}

.filter-select:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.per-page-select {
  width: 70px;
  padding: 0.4rem 1.5rem 0.4rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.8rem;
  background: white;
  cursor: pointer;
  appearance: none;
}

.select-chevron {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.select-wrapper.small .select-chevron {
  right: 5px;
}

/* Search */
.search-wrapper {
  position: relative;
}

.search-wrapper.full-width {
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.filter-search {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.filter-search:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

/* Checkbox Group */
.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1.5rem;
  margin-top: 0.5rem;
}

.checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #475569;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #8b5cf6;
}

.export-btn {
  margin-left: auto;
}

/* ── Content Cards ────────────────────────────────────── */
.content-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  overflow: hidden;
  margin-bottom: 1.75rem;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fcfcfc;
}

.card-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
}

.count-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #8b5cf6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #64748b;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: #f8fafc;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed #e2e8f0;
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #1e293b;
}

.empty-state p {
  margin: 0;
  color: #94a3b8;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  scrollbar-color: #8b5cf6 #f1f5f9;
}

.table-responsive::-webkit-scrollbar {
  height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
  background: #8b5cf6;
  border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
  background: #7c3aed;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table thead th {
  background: #f8fafc;
  padding: 1rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.data-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr.row-warning {
  background: #fffbeb;
}

.data-table tbody td {
  padding: 1rem;
  color: #1e293b;
  vertical-align: middle;
}

/* Business Info */
.business-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 200px;
}

.business-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1rem;
  flex-shrink: 0;
}

.business-details {
  display: flex;
  flex-direction: column;
}

.business-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.business-email {
  font-size: 0.75rem;
  color: #64748b;
}

/* Subscription Info */
.subscription-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 140px;
}

.tier-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  width: fit-content;
}

.tier-badge.basic {
  background: #f1f5f9;
  color: #475569;
}

.tier-badge.standard {
  background: #dbeafe;
  color: #3b82f6;
}

.tier-badge.premium {
  background: #ede9fe;
  color: #8b5cf6;
}

.tier-badge.enterprise {
  background: #fef3c7;
  color: #f59e0b;
}

.trial-badge {
  font-size: 0.7rem;
  color: #f59e0b;
  background: #fffbeb;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.subscription-date {
  font-size: 0.7rem;
  color: #64748b;
}

/* Employee Usage */
.employee-usage {
  min-width: 120px;
}

.usage-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.usage-count {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-fill.success {
  background: #10b981;
}

.progress-fill.warning {
  background: #f59e0b;
}

.progress-fill.danger {
  background: #ef4444;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.status-badge.approved {
  background: #ecfdf5;
  color: #10b981;
  border: 1px solid #d1fae5;
}

.status-badge.pending {
  background: #fffbeb;
  color: #f59e0b;
  border: 1px solid #fef3c7;
}

.status-badge.cancelled {
  background: #fef2f2;
  color: #ef4444;
  border: 1px solid #fee2e2;
}

.status-badge.neutral {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

/* Owner Info */
.owner-info {
  font-size: 0.85rem;
  color: #1e293b;
}

.text-muted {
  color: #94a3b8;
  font-style: italic;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: flex-end;
}

.btn-icon-small {
  width: 32px;
  height: 32px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #475569;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-small:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-icon-small.success:hover {
  background: #ecfdf5;
  border-color: #10b981;
  color: #10b981;
}

.btn-icon-small.warning:hover {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}

.text-right {
  text-align: right;
}

/* Pagination */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.85rem;
  color: #64748b;
  flex-wrap: wrap;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-number {
  min-width: 32px;
  height: 32px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.page-number:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.page-number.active {
  background: #8b5cf6;
  border-color: #8b5cf6;
  color: white;
}

.page-number:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-ellipsis {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

/* Toast Notifications */
.toast-container {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: 400px;
}

.toast {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  border-left: 4px solid;
  animation: slideIn 0.3s ease;
}

.toast.success {
  border-left-color: #10b981;
}

.toast.success .toast-icon {
  color: #10b981;
}

.toast.error {
  border-left-color: #ef4444;
}

.toast.error .toast-icon {
  color: #ef4444;
}

.toast.info {
  border-left-color: #3b82f6;
}

.toast.info .toast-icon {
  color: #3b82f6;
}

.toast-icon {
  flex-shrink: 0;
}

.toast-message {
  flex: 1;
  font-size: 0.9rem;
  color: #1e293b;
}

.toast-close {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.toast-close:hover {
  background: #f1f5f9;
  color: #475569;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

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

/* Responsive */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .management-main {
    padding: 1rem;
  }
  
  .header-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-controls {
    width: 100%;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .view-badge {
    align-self: flex-start;
  }
  
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .checkbox-group {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .export-btn {
    margin-left: 0;
    width: 100%;
    justify-content: center;
  }
  
  .pagination-bar {
    flex-direction: column;
    align-items: center;
  }
  
  .pagination-info {
    flex-direction: column;
    align-items: center;
  }
  
  .per-page-selector {
    width: 100%;
    justify-content: center;
  }
  
  .data-table thead th,
  .data-table tbody td {
    padding: 0.75rem;
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .management-main {
    padding: 0.75rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .header-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-accent {
    width: 100%;
    justify-content: center;
  }
  
  .role-meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .view-badge {
    width: 100%;
  }
  
  .view-content {
    justify-content: center;
  }
  
  .filter-row {
    grid-template-columns: 1fr;
  }
  
  .pagination-controls {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>