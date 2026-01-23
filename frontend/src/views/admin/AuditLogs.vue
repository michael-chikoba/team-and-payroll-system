<template>
  <div class="audit-logs-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <div class="header-actions">
        <!-- Refresh Button -->
        <button @click="refreshData" class="refresh-btn" :disabled="refreshing || loading || loadingLogins">
          <span v-if="refreshing" class="spinner-small"></span>
          🔄 Refresh
        </button>
        
        <button @click="clearLogs" class="clear-btn" :disabled="clearing">Clear All Logs</button>
        <button @click="exportLogs" class="export-btn">📥 Export CSV</button>
      </div>
    </header>

    <!-- Auto-refresh indicator -->
    <div v-if="autoRefreshEnabled" class="auto-refresh-indicator">
      <span class="refresh-timer">Auto-refresh in: {{ countdown }}s</span>
      <button @click="toggleAutoRefresh" class="toggle-refresh-btn">
        {{ autoRefreshEnabled ? '⏸️ Pause' : '▶️ Resume' }}
      </button>
    </div>

    <!-- Tab Navigation - Swapped order -->
    <div class="tabs">
      <button 
        :class="['tab-btn', { active: activeTab === 'logins' }]"
        @click="switchToLoginTab"
      >
        🔐 Login Audits
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'activity' }]"
        @click="activeTab = 'activity'"
      >
        📋 Activity Logs
      </button>
    </div>

    <!-- Login Audits Tab - Now first/default -->
    <div v-show="activeTab === 'logins'">
      <div class="filters">
        <div class="filter-group">
          <label>Search:</label>
          <input
            v-model="loginSearchQuery"
            @input="filterLoginAudits"
            placeholder="Search by email, IP, location..."
            class="search-input"
          />
        </div>

        <div class="filter-group">
          <label>Date Range:</label>
          <input v-model="loginDateFrom" type="date" @change="filterLoginAudits" />
          <input v-model="loginDateTo" type="date" @change="filterLoginAudits" />
        </div>

        <div class="filter-group">
          <label>Status:</label>
          <select v-model="statusFilter" @change="filterLoginAudits">
            <option value="">All Status</option>
            <option value="success">Success</option>
            <option value="failed">Failed</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Device:</label>
          <select v-model="deviceFilter" @change="filterLoginAudits">
            <option value="">All Devices</option>
            <option value="desktop">Desktop</option>
            <option value="mobile">Mobile</option>
            <option value="tablet">Tablet</option>
          </select>
        </div>
      </div>

      <div class="summary-cards">
        <div class="card">
          <h3>Total Logins</h3>
          <p class="value">{{ loginStats.total_logins || 0 }}</p>
        </div>
        <div class="card">
          <h3>Failed Attempts</h3>
          <p class="value text-danger">{{ loginStats.failed_logins || 0 }}</p>
        </div>
        <div class="card">
          <h3>Unique Users</h3>
          <p class="value">{{ loginStats.unique_users || 0 }}</p>
        </div>
        <div class="card">
          <h3>Suspicious Logins</h3>
          <p class="value text-warning">{{ loginStats.suspicious_logins || 0 }}</p>
        </div>
      </div>

      <!-- Login Charts -->
      <div class="charts-row">
        <div class="chart-card">
          <h3>Logins by Country</h3>
          <div class="country-list">
            <div 
              v-for="country in loginStats.logins_by_country" 
              :key="country.country_code"
              class="country-item"
            >
              <span class="country-name">{{ country.country }}</span>
              <span class="country-count">{{ country.count }}</span>
            </div>
            <div v-if="!loginStats.logins_by_country?.length" class="empty-chart">
              No data available
            </div>
          </div>
        </div>

        <div class="chart-card">
          <h3>Logins by Device</h3>
          <div class="device-list">
            <div 
              v-for="device in loginStats.logins_by_device" 
              :key="device.device_type"
              class="device-item"
            >
              <span class="device-icon">{{ getDeviceIcon(device.device_type) }}</span>
              <span class="device-name">{{ device.device_type || 'Unknown' }}</span>
              <span class="device-count">{{ device.count }}</span>
            </div>
            <div v-if="!loginStats.logins_by_device?.length" class="empty-chart">
              No data available
            </div>
          </div>
        </div>
      </div>

      <div class="content">
        <div v-if="loginAudits.length === 0 && !loadingLogins" class="empty-state">
          <p>No login audits yet.</p>
        </div>

        <table v-else class="audit-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Status</th>
              <th>Login Time</th>
              <th>IP Address</th>
              <th>Location</th>
              <th>Device</th>
              <th>Browser</th>
              <th>Session</th>
              <th>Flags</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="audit in filteredLoginAudits" 
              :key="audit.id"
              :class="{ 'suspicious-row': audit.is_suspicious }"
            >
              <td>
                <div class="user-cell">
                  <strong>{{ audit.user?.name || 'Unknown' }}</strong>
                  <small>{{ audit.email }}</small>
                </div>
              </td>
              <td>
                <span :class="['status-badge', audit.status]">
                  {{ audit.status === 'success' ? '✓' : '✗' }} {{ audit.status }}
                </span>
                <div v-if="audit.failure_reason" class="failure-reason">
                  {{ audit.failure_reason }}
                </div>
              </td>
              <td>
                <div class="time-cell">
                  <div>{{ formatDate(audit.login_at) }}</div>
                  <small v-if="audit.logout_at">
                    Logout: {{ formatDate(audit.logout_at) }}
                  </small>
                </div>
              </td>
              <td>
                <code class="ip-address">{{ audit.ip_address }}</code>
              </td>
              <td>
                <div class="location-cell">
                  <div>{{ audit.location || 'Unknown' }}</div>
                  <small v-if="audit.city">{{ audit.city }}, {{ audit.country }}</small>
                </div>
              </td>
              <td>
                <span class="device-badge">
                  {{ getDeviceIcon(audit.device_type) }} {{ audit.device_type || 'Unknown' }}
                </span>
              </td>
              <td>{{ audit.browser || 'Unknown' }}</td>
              <td>
                <span v-if="audit.session_duration_minutes" class="session-duration">
                  {{ audit.session_duration_minutes }} min
                </span>
                <span v-else class="session-active">Active</span>
              </td>
              <td>
                <div class="flags">
                  <span v-if="audit.is_suspicious" class="flag suspicious" title="Suspicious login">
                    ⚠️
                  </span>
                  <span v-if="audit.status === 'failed'" class="flag failed" title="Failed attempt">
                    ❌
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="loginPagination.total > loginPagination.per_page" class="pagination">
          <button 
            @click="changeLoginPage(loginPagination.current_page - 1)"
            :disabled="loginPagination.current_page === 1"
            class="page-btn"
          >
            Previous
          </button>
          <span class="page-info">
            Page {{ loginPagination.current_page }} of {{ loginPagination.last_page }}
          </span>
          <button 
            @click="changeLoginPage(loginPagination.current_page + 1)"
            :disabled="loginPagination.current_page === loginPagination.last_page"
            class="page-btn"
          >
            Next
          </button>
        </div>

        <div v-if="loadingLogins" class="loading-overlay">
          <div class="spinner"></div>
          <p>Loading login audits...</p>
        </div>
      </div>
    </div>

    <!-- Activity Logs Tab - Now second -->
    <div v-show="activeTab === 'activity'">
      <div class="filters">
        <div class="filter-group">
          <label>Search:</label>
          <input
            v-model="searchQuery"
            @input="filterLogs"
            placeholder="Search by user, action, or details..."
            class="search-input"
          />
        </div>

        <div class="filter-group">
          <label>Date Range:</label>
          <input v-model="dateFrom" type="date" @change="filterLogs" />
          <input v-model="dateTo" type="date" @change="filterLogs" />
        </div>

        <div class="filter-group">
          <label>Action:</label>
          <select v-model="actionFilter" @change="filterLogs">
            <option value="">All Actions</option>
            <option value="create">Create</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
            <option value="login">Login</option>
          </select>
        </div>
      </div>

      <div class="summary-cards">
        <div class="card">
          <h3>Total Logs</h3>
          <p class="value">{{ filteredLogs.length }}</p>
        </div>
        <div class="card">
          <h3>Today</h3>
          <p class="value">{{ todayLogs }}</p>
        </div>
        <div class="card">
          <h3>This Week</h3>
          <p class="value">{{ weekLogs }}</p>
        </div>
      </div>

      <div class="content">
        <div v-if="logs.length === 0 && !loading" class="empty-state">
          <p>No audit logs yet.</p>
        </div>

        <table v-else class="audit-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Action</th>
              <th>Timestamp</th>
              <th>Details</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in filteredLogs" :key="log.id">
              <td>{{ log.id }}</td>
              <td>{{ log.user_name || 'System' }}</td>
              <td>
                <span :class="['action-badge', log.action.toLowerCase()]">
                  {{ formatAction(log.action) }}
                </span>
              </td>
              <td>{{ formatDate(log.timestamp) }}</td>
              <td class="details-cell">{{ log.details }}</td>
              <td>
                <button @click="viewDetails(log)" class="action-btn view">View</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
          <p>Loading audit logs...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'AuditLogs',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'Audit Logs Dashboard',
      activeTab: 'logins', // Changed default to 'logins'
      
      // Auto-refresh settings
      autoRefreshEnabled: true,
      refreshInterval: null,
      countdown: 60,
      countdownInterval: null,
      refreshing: false,
      
      // Activity logs
      logs: [],
      filteredLogs: [],
      searchQuery: '',
      dateFrom: '',
      dateTo: '',
      actionFilter: '',
      loading: false,
      clearing: false,
      
      // Login audits
      loginAudits: [],
      filteredLoginAudits: [],
      loginSearchQuery: '',
      loginDateFrom: '',
      loginDateTo: '',
      statusFilter: '',
      deviceFilter: '',
      loadingLogins: false,
      loginStats: {},
      loginPagination: {
        current_page: 1,
        per_page: 50,
        total: 0,
        last_page: 1
      },
      
      error: null,
      retryCount: 0
    }
  },
  computed: {
    todayLogs() {
      const today = new Date().toISOString().split('T')[0]
      return this.filteredLogs.filter(log =>
        log.timestamp && log.timestamp.startsWith(today)
      ).length
    },
    weekLogs() {
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)
      const weekStart = weekAgo.toISOString().split('T')[0]
      return this.filteredLogs.filter(log =>
        log.timestamp && log.timestamp >= weekStart
      ).length
    }
  },
  mounted() {
    this.initializeComponent()
    this.startAutoRefresh()
  },
  beforeUnmount() {
    this.stopAutoRefresh()
  },
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access audit logs.'
        return
      }
      if (!this.authStore.isAdmin) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      this.fetchLoginAudits() // Start with login audits
      this.fetchLoginStats()
    },

    // Auto-refresh functionality
    startAutoRefresh() {
      this.stopAutoRefresh()
      
      if (this.autoRefreshEnabled) {
        // Start countdown timer
        this.countdownInterval = setInterval(() => {
          this.countdown--
          if (this.countdown <= 0) {
            this.refreshData()
            this.countdown = 60
          }
        }, 1000)
        
        // Start auto-refresh interval
        this.refreshInterval = setInterval(() => {
          this.refreshData()
        }, 60000) // 60 seconds
      }
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval)
        this.refreshInterval = null
      }
      if (this.countdownInterval) {
        clearInterval(this.countdownInterval)
        this.countdownInterval = null
      }
    },

    toggleAutoRefresh() {
      this.autoRefreshEnabled = !this.autoRefreshEnabled
      if (this.autoRefreshEnabled) {
        this.startAutoRefresh()
        this.$notify({
          type: 'success',
          title: 'Auto-refresh',
          text: 'Auto-refresh enabled (60 seconds)'
        })
      } else {
        this.stopAutoRefresh()
        this.$notify({
          type: 'info',
          title: 'Auto-refresh',
          text: 'Auto-refresh paused'
        })
      }
    },

    async refreshData() {
      if (this.refreshing) return
      
      this.refreshing = true
      this.countdown = 60
      
      try {
        if (this.activeTab === 'logins') {
          await Promise.all([
            this.fetchLoginAudits(this.loginPagination.current_page),
            this.fetchLoginStats()
          ])
        } else {
          await this.fetchLogs()
        }
        
        this.$notify({
          type: 'success',
          title: 'Refreshed',
          text: 'Data refreshed successfully!'
        })
      } catch (error) {
        console.error('Refresh error:', error)
      } finally {
        this.refreshing = false
      }
    },

    async switchToLoginTab() {
      this.activeTab = 'logins'
      if (this.loginAudits.length === 0) {
        await Promise.all([
          this.fetchLoginAudits(),
          this.fetchLoginStats()
        ])
      }
    },

    async fetchLoginAudits(page = 1) {
      this.loadingLogins = true
      this.error = null
      try {
        const params = {
          page,
          per_page: this.loginPagination.per_page,
          search: this.loginSearchQuery || undefined,
          status: this.statusFilter || undefined,
          device_type: this.deviceFilter || undefined,
          start_date: this.loginDateFrom || undefined,
          end_date: this.loginDateTo || undefined
        }

        const response = await axios.get('/api/admin/audit/logins', { params })
        
        this.loginAudits = response.data.data || []
        this.filteredLoginAudits = [...this.loginAudits]
        
        if (response.data.pagination) {
          this.loginPagination = response.data.pagination
        }
      } catch (err) {
        console.error('Login audit fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loadingLogins = false
      }
    },

    async fetchLoginStats() {
      try {
        const params = {
          start_date: this.loginDateFrom || undefined,
          end_date: this.loginDateTo || undefined
        }
        const response = await axios.get('/api/admin/audit/login-stats', { params })
        this.loginStats = response.data.stats || {}
      } catch (err) {
        console.error('Login stats fetch error:', err)
      }
    },

    filterLoginAudits() {
      this.fetchLoginAudits(1)
      this.fetchLoginStats()
    },

    changeLoginPage(page) {
      if (page >= 1 && page <= this.loginPagination.last_page) {
        this.fetchLoginAudits(page)
      }
    },

    getDeviceIcon(deviceType) {
      const icons = {
        mobile: '📱',
        tablet: '📱',
        desktop: '💻',
        unknown: '🖥️'
      }
      return icons[deviceType?.toLowerCase()] || icons.unknown
    },

    async fetchLogs(retry = false) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/admin/audit-logs')
        this.logs = response.data.data || response.data || []
        this.filteredLogs = [...this.logs]
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },

    filterLogs() {
      let filtered = [...this.logs]
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(log =>
          log.user_name?.toLowerCase().includes(query) ||
          log.action.toLowerCase().includes(query) ||
          log.details.toLowerCase().includes(query)
        )
      }
      if (this.dateFrom) {
        filtered = filtered.filter(log => log.timestamp && log.timestamp >= this.dateFrom)
      }
      if (this.dateTo) {
        filtered = filtered.filter(log => log.timestamp && log.timestamp <= this.dateTo)
      }
      if (this.actionFilter) {
        filtered = filtered.filter(log => log.action.toLowerCase() === this.actionFilter)
      }
      this.filteredLogs = filtered
    },

    async clearLogs() {
      if (!confirm('Are you sure you want to clear all audit logs? This action cannot be undone.')) return
      this.clearing = true
      try {
        await axios.delete('/api/admin/audit-logs')
        this.logs = []
        this.filteredLogs = []
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Audit logs cleared successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.clearing = false
      }
    },

    async exportLogs() {
      try {
        const endpoint = this.activeTab === 'logins' 
          ? '/api/admin/audit/logins/export' 
          : '/api/admin/audit-logs/export'
        
        const response = await axios.get(endpoint, { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        const filename = `${this.activeTab}-logs-${new Date().toISOString().split('T')[0]}.csv`
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Logs exported successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      }
    },

    viewDetails(log) {
      this.$notify({
        type: 'info',
        title: 'Log Details',
        text: `User: ${log.user_name || 'System'}\nAction: ${log.action}\nTimestamp: ${log.timestamp}\nDetails: ${log.details}`
      })
    },

    formatAction(action) {
      const map = {
        create: 'Create',
        update: 'Update',
        delete: 'Delete',
        login: 'Login'
      }
      return map[action.toLowerCase()] || action
    },

    formatDate(timestamp) {
      if (!timestamp) return 'N/A'
      return new Date(timestamp).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network/CORS error: Ensure backend allows this origin.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
      this.$notify({ type: 'error', title: 'Error', text: errorMsg })
    }
  }
}
</script>

<style scoped>
.audit-logs-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.title {
  margin: 0;
  font-size: 2.5rem;
  font-weight: 300;
  color: #2c3e50;
  letter-spacing: -0.5px;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.auto-refresh-indicator {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.refresh-timer {
  font-weight: 500;
  font-size: 0.9rem;
}

.toggle-refresh-btn {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.toggle-refresh-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.refresh-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
  color: white;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.refresh-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.refresh-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  background: white;
  padding: 0.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.tab-btn {
  flex: 1;
  padding: 1rem;
  border: none;
  background: transparent;
  color: #7f8c8d;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.tab-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.tab-btn:hover:not(.active) {
  background: #f8f9fa;
}

.clear-btn, .export-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clear-btn {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}

.clear-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.export-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
}

.clear-btn:hover:not(:disabled), .export-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  background: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.search-input, .filter-group input, .filter-group select {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.95rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card .value {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.card .value.text-danger {
  color: #dc3545;
}

.card .value.text-warning {
  color: #ffc107;
}

.charts-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.chart-card h3 {
  margin: 0 0 1rem;
  font-size: 1.1rem;
  color: #2c3e50;
}

.country-list, .device-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.country-item, .device-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.device-icon {
  font-size: 1.2rem;
  margin-right: 0.5rem;
}

.country-count, .device-count {
  font-weight: 600;
  color: #667eea;
}

.empty-chart {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  position: relative;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
  font-size: 1.1rem;
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.audit-table th,
.audit-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.audit-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

.audit-table tr:hover {
  background: #f8f9fa;
}

.suspicious-row {
  background: #fff3cd !important;
  border-left: 4px solid #ffc107;
}

.user-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.user-cell small {
  color: #7f8c8d;
  font-size: 0.8rem;
}

.time-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.time-cell small {
  color: #7f8c8d;
  font-size: 0.75rem;
}

.location-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.location-cell small {
  color: #7f8c8d;
  font-size: 0.75rem;
}

.ip-address {
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.85rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.success {
  background: #d4edda;
  color: #155724;
}

.status-badge.failed {
  background: #f8d7da;
  color: #721c24;
}

.failure-reason {
  font-size: 0.75rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.details-cell {
  max-width: 300px;
  word-wrap: break-word;
}

.action-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.action-badge.create {
  background: #d4edda;
  color: #155724;
}

.action-badge.update {
  background: #fff3cd;
  color: #856404;
}

.action-badge.delete {
  background: #f8d7da;
  color: #721c24;
}

.action-badge.login {
  background: #d1ecf1;
  color: #0c5460;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s ease;
  color: white;
  margin-right: 0.5rem;
}

.action-btn.view {
  background: #007bff;
}

.action-btn.view:hover {
  background: #0056b3;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
}

.page-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.page-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.page-info {
  font-weight: 500;
  color: #2c3e50;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .header-actions {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .filters {
    flex-direction: column;
  }
  
  .audit-table {
    font-size: 0.85rem;
  }
  
  .audit-table th,
  .audit-table td {
    padding: 0.5rem;
  }
}
</style>