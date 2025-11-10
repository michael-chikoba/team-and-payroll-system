<template>
  <div class="audit-logs-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <div class="header-actions">
        <button @click="clearLogs" class="clear-btn" :disabled="clearing">Clear All Logs</button>
        <button @click="exportLogs" class="export-btn">📥 Export CSV</button>
      </div>
    </header>

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
      logs: [],
      filteredLogs: [],
      searchQuery: '',
      dateFrom: '',
      dateTo: '',
      actionFilter: '',
      loading: false,
      clearing: false,
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
      this.fetchLogs()
    },
    async fetchLogs(retry = false) {
      this.loading = true
      this.error = null
      try {
        console.log('Fetching audit logs... (retry:', retry, ')')
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
        const response = await axios.get('/api/admin/audit-logs/export', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `audit-logs-${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Audit logs exported successfully!'
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
      return new Date(timestamp).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchLogs(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
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
  margin-bottom: 2rem;
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
  font-size: 0.95rem;
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
  font-size: 0.85rem;
}

.audit-table tr:hover {
  background: #f8f9fa;
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

@media (max-width: 768px) {
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