<template>
  <div class="audit-management">
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
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="3" y1="9" x2="21" y2="9"></line>
                  <line x1="3" y1="15" x2="21" y2="15"></line>
                  <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Audit & Security Logs</h1>
                <p class="subtitle">Monitor system access, security events, and user activities</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ activeTab === 'logins' ? 'Login Audits' : 'Activity Logs' }}</span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">
              <!-- Auto Refresh Indicator -->
              <div v-if="autoRefreshEnabled" class="refresh-indicator">
                <span class="pulse-dot"></span>
                <span class="refresh-text">Refreshing in {{ countdown }}s</span>
              </div>

              <button @click="toggleAutoRefresh" class="btn-icon" :title="autoRefreshEnabled ? 'Pause Auto-Refresh' : 'Enable Auto-Refresh'">
                <svg v-if="autoRefreshEnabled" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="6" y="4" width="4" height="16"></rect>
                  <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </button>

              <button @click="refreshData" class="btn-icon" title="Refresh Data" :disabled="refreshing">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spinning': refreshing }">
                  <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
              </button>

              <button @click="exportLogs" class="btn-accent">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export CSV
              </button>

              <button @click="clearLogs" class="btn-icon btn-danger" title="Clear Logs" :disabled="clearing">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- View badge -->
          <div class="view-badge">
            <div class="view-content">
              <span class="view-primary">{{ activeTab === 'logins' ? '🔐' : '📋' }}</span>
              <div class="view-details">
                <span class="view-label">Current View</span>
                <span class="view-total">{{ activeTab === 'logins' ? 'Login Audits' : 'Activity Logs' }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="tabs-container">
        <button 
          :class="['tab-button', { active: activeTab === 'logins' }]"
          @click="switchToLoginTab"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          Login Audits
        </button>
        <button 
          :class="['tab-button', { active: activeTab === 'activity' }]"
          @click="activeTab = 'activity'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          Activity Logs
        </button>
      </div>

      <!-- =============================================
           TAB 1: LOGIN AUDITS
           ============================================= -->
      <div v-show="activeTab === 'logins'" class="fade-in">
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total Logins</span>
              <div class="stat-number">{{ loginStats.total_logins || 0 }}</div>
              <div class="stat-trend">All time</div>
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
              <span class="stat-label">Failed Attempts</span>
              <div class="stat-number">{{ loginStats.failed_logins || 0 }}</div>
              <div class="stat-trend">
                <span class="trend-down">{{ ((loginStats.failed_logins / loginStats.total_logins) * 100 || 0).toFixed(1) }}%</span> failure rate
              </div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Unique Users</span>
              <div class="stat-number">{{ loginStats.unique_users || 0 }}</div>
              <div class="stat-trend">Active accounts</div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#f59e0b;">
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Suspicious Events</span>
              <div class="stat-number">{{ loginStats.suspicious_logins || 0 }}</div>
              <div class="stat-trend">
                <span class="trend-warn">Requires attention</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters Bar -->
        <div class="filters-card">
          <div class="filters-header">
            <h3>Filter Login Audits</h3>
            <span class="filter-count">{{ filteredLoginAudits.length }} records</span>
          </div>
          <div class="filters-grid">
            <!-- Search -->
            <div class="search-wrapper full-width">
              <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                v-model="loginSearchQuery"
                @input="debounceFilterLogin"
                placeholder="Search by email, IP address, or location..."
                class="filter-search"
              />
            </div>

            <div class="filter-row">
              <!-- Date From -->
              <div class="filter-item">
                <label class="filter-label">From Date</label>
                <input v-model="loginDateFrom" type="date" class="filter-input" @change="filterLoginAudits" />
              </div>

              <!-- Date To -->
              <div class="filter-item">
                <label class="filter-label">To Date</label>
                <input v-model="loginDateTo" type="date" class="filter-input" @change="filterLoginAudits" />
              </div>

              <!-- Status Filter -->
              <div class="filter-item">
                <label class="filter-label">Status</label>
                <div class="select-wrapper">
                  <select v-model="statusFilter" class="filter-select" @change="filterLoginAudits">
                    <option value="">All Statuses</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                  </select>
                  <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>

              <!-- Device Filter -->
              <div class="filter-item">
                <label class="filter-label">Device</label>
                <div class="select-wrapper">
                  <select v-model="deviceFilter" class="filter-select" @change="filterLoginAudits">
                    <option value="">All Devices</option>
                    <option value="desktop">Desktop</option>
                    <option value="mobile">Mobile</option>
                    <option value="tablet">Tablet</option>
                  </select>
                  <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Analysis Charts Row -->
        <div class="charts-grid">
          <!-- Country Distribution -->
          <div class="content-card">
            <div class="card-header">
              <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="2" y1="12" x2="22" y2="12"></line>
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                Logins by Country
              </h3>
            </div>
            <div class="list-container">
              <div v-for="country in loginStats.logins_by_country" :key="country.country_code" class="list-item">
                <span class="item-label">{{ country.country }}</span>
                <span class="item-value">{{ country.count }}</span>
              </div>
              <div v-if="!loginStats.logins_by_country?.length" class="empty-text">No data available</div>
            </div>
          </div>

          <!-- Device Distribution -->
          <div class="content-card">
            <div class="card-header">
              <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                  <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                  <line x1="8" y1="21" x2="16" y2="21"></line>
                  <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                Logins by Device
              </h3>
            </div>
            <div class="list-container">
              <div v-for="device in loginStats.logins_by_device" :key="device.device_type" class="list-item">
                <span class="item-label">
                  <span class="device-icon">{{ getDeviceIcon(device.device_type) }}</span>
                  <span class="capitalize">{{ device.device_type || 'Unknown' }}</span>
                </span>
                <span class="item-value">{{ device.count }}</span>
              </div>
              <div v-if="!loginStats.logins_by_device?.length" class="empty-text">No data available</div>
            </div>
          </div>
        </div>

        <!-- Login Data Table -->
        <div class="content-card">
          <div class="card-header">
            <h3>Login Audit Records</h3>
            <span class="count-badge">{{ filteredLoginAudits.length }}</span>
          </div>
          
          <!-- Loading State -->
          <div v-if="loadingLogins" class="loading-state">
            <div class="spinner"></div>
            <p>Loading audit records...</p>
          </div>

          <!-- Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>User Details</th>
                  <th>Status</th>
                  <th>Time</th>
                  <th>Network & Location</th>
                  <th>Device Info</th>
                  <th>Security Flags</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="audit in filteredLoginAudits" 
                  :key="audit.id"
                  :class="{ 'row-warning': audit.is_suspicious }"
                >
                  <td>
                    <div class="user-info-cell">
                      <div class="user-avatar">{{ audit.user?.name?.charAt(0) || '?' }}</div>
                      <div class="user-details">
                        <span class="user-name">{{ audit.user?.name || 'Unknown User' }}</span>
                        <span class="user-email">{{ audit.email }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="['status-badge', audit.status === 'success' ? 'approved' : 'rejected']">
                      {{ audit.status === 'success' ? 'Success' : 'Failed' }}
                    </span>
                    <div v-if="audit.failure_reason" class="failure-reason">
                      {{ audit.failure_reason }}
                    </div>
                  </td>
                  <td>
                    <div class="time-cell">
                      <span class="date-time">{{ formatDate(audit.login_at) }}</span>
                      <span v-if="audit.logout_at" class="logout-time">Out: {{ formatTime(audit.logout_at) }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="location-cell">
                      <code class="ip-badge">{{ audit.ip_address }}</code>
                      <span class="location">{{ audit.location || audit.city || 'Unknown Location' }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="device-cell">
                      <span class="device-type">
                        <span class="device-icon">{{ getDeviceIcon(audit.device_type) }}</span>
                        <span class="capitalize">{{ audit.device_type || 'Unknown' }}</span>
                      </span>
                      <span class="browser">{{ audit.browser }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="security-flags">
                      <span v-if="audit.is_suspicious" class="badge warning" title="Suspicious activity detected">
                        ⚠️ Suspicious
                      </span>
                      <span v-if="!audit.logout_at && audit.status === 'success'" class="badge success pulse">
                        ● Online
                      </span>
                      <span v-else-if="audit.session_duration_minutes" class="session-duration">
                        {{ audit.session_duration_minutes }} min
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="filteredLoginAudits.length === 0 && !loadingLogins" class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No Login Records Found</h3>
            <p>Try adjusting your filters or date range.</p>
          </div>

          <!-- Pagination -->
          <div v-if="loginPagination.total > loginPagination.per_page" class="pagination-bar">
            <span class="pagination-info">
              Page {{ loginPagination.current_page }} of {{ loginPagination.last_page }}
            </span>
            <div class="pagination-controls">
              <button 
                @click="changeLoginPage(loginPagination.current_page - 1)"
                :disabled="loginPagination.current_page === 1"
                class="btn-icon-small"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              <button 
                @click="changeLoginPage(loginPagination.current_page + 1)"
                :disabled="loginPagination.current_page === loginPagination.last_page"
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

      <!-- =============================================
           TAB 2: ACTIVITY LOGS
           ============================================= -->
      <div v-show="activeTab === 'activity'" class="fade-in">
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total Logs</span>
              <div class="stat-number">{{ filteredLogs.length }}</div>
              <div class="stat-trend">Activity records</div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Today's Activity</span>
              <div class="stat-number">{{ todayLogs }}</div>
              <div class="stat-trend">Last 24 hours</div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#10b981;">
            <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">This Week</span>
              <div class="stat-number">{{ weekLogs }}</div>
              <div class="stat-trend">Last 7 days</div>
            </div>
          </div>
        </div>

        <!-- Filters Bar -->
        <div class="filters-card">
          <div class="filters-header">
            <h3>Filter Activity Logs</h3>
            <span class="filter-count">{{ filteredLogs.length }} records</span>
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
                @input="filterLogs"
                placeholder="Search by user, action type, or details..."
                class="filter-search"
              />
            </div>

            <div class="filter-row">
              <!-- Date From -->
              <div class="filter-item">
                <label class="filter-label">From Date</label>
                <input v-model="dateFrom" type="date" class="filter-input" @change="filterLogs" />
              </div>

              <!-- Date To -->
              <div class="filter-item">
                <label class="filter-label">To Date</label>
                <input v-model="dateTo" type="date" class="filter-input" @change="filterLogs" />
              </div>

              <!-- Action Filter -->
              <div class="filter-item">
                <label class="filter-label">Action Type</label>
                <div class="select-wrapper">
                  <select v-model="actionFilter" class="filter-select" @change="filterLogs">
                    <option value="">All Actions</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                    <option value="login">Login</option>
                  </select>
                  <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Table -->
        <div class="content-card">
          <div class="card-header">
            <h3>Activity Log Records</h3>
            <span class="count-badge">{{ filteredLogs.length }}</span>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading activity logs...</p>
          </div>

          <!-- Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Action</th>
                  <th>Timestamp</th>
                  <th>Details</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in filteredLogs" :key="log.id">
                  <td>
                    <div class="user-info-cell">
                      <div class="user-avatar small">{{ log.user_name?.charAt(0) || 'S' }}</div>
                      <span class="user-name">{{ log.user_name || 'System' }}</span>
                    </div>
                  </td>
                  <td>
                    <span :class="['action-badge', getActionBadgeClass(log.action)]">
                      {{ formatAction(log.action) }}
                    </span>
                  </td>
                  <td>
                    <span class="date-time">{{ formatDate(log.timestamp) }}</span>
                  </td>
                  <td>
                    <div class="details-cell" :title="log.details">
                      {{ truncateText(log.details, 50) }}
                    </div>
                  </td>
                  <td class="text-right">
                    <button @click="viewDetails(log)" class="btn-icon-small" title="View Details">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"></path>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="filteredLogs.length === 0 && !loading" class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No Activity Logs</h3>
            <p>There are no activity records matching your filters.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import _ from 'lodash'

export default {
  name: 'AuditLogs',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      activeTab: 'logins',
      
      // Auto-refresh settings
      autoRefreshEnabled: true,
      refreshInterval: null,
      countdown: 60,
      countdownInterval: null,
      refreshing: false,
      
      // Activity logs (Client-side filtering)
      logs: [],
      filteredLogs: [],
      searchQuery: '',
      dateFrom: '',
      dateTo: '',
      actionFilter: '',
      loading: false,
      clearing: false,
      
      // Login audits (Server-side pagination)
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
      
      error: null
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
  created() {
    // Debounce search to prevent API spam
    this.debounceFilterLogin = _.debounce(this.filterLoginAudits, 500)
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
      this.fetchLoginAudits()
      this.fetchLoginStats()
    },

    // --- Auto Refresh Logic ---
    startAutoRefresh() {
      this.stopAutoRefresh()
      if (this.autoRefreshEnabled) {
        this.countdownInterval = setInterval(() => {
          this.countdown--
          if (this.countdown <= 0) {
            this.refreshData()
            this.countdown = 60
          }
        }, 1000)
        
        this.refreshInterval = setInterval(() => {
          this.refreshData()
        }, 60000)
      }
    },

    stopAutoRefresh() {
      if (this.refreshInterval) clearInterval(this.refreshInterval)
      if (this.countdownInterval) clearInterval(this.countdownInterval)
      this.refreshInterval = null
      this.countdownInterval = null
    },

    toggleAutoRefresh() {
      this.autoRefreshEnabled = !this.autoRefreshEnabled
      if (this.autoRefreshEnabled) {
        this.startAutoRefresh()
        this.$notify?.({ type: 'success', text: 'Auto-refresh enabled' })
      } else {
        this.stopAutoRefresh()
        this.$notify?.({ type: 'info', text: 'Auto-refresh paused' })
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
        this.$notify?.({ type: 'success', text: 'Data refreshed successfully' })
      } catch (error) {
        console.error('Refresh error:', error)
      } finally {
        this.refreshing = false
      }
    },

    // --- Tab Logic ---
    async switchToLoginTab() {
      this.activeTab = 'logins'
      if (this.loginAudits.length === 0) {
        await Promise.all([
          this.fetchLoginAudits(),
          this.fetchLoginStats()
        ])
      }
    },

    // --- Login Audits (Server-side) ---
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
        console.error('Login stats error:', err)
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

    // --- Activity Logs (Client-side) ---
    async fetchLogs() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/admin/audit-logs')
        this.logs = response.data.data || response.data || []
        this.filteredLogs = [...this.logs]
      } catch (err) {
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
          (log.user_name && log.user_name.toLowerCase().includes(query)) ||
          (log.action && log.action.toLowerCase().includes(query)) ||
          (log.details && log.details.toLowerCase().includes(query))
        )
      }
      
      if (this.dateFrom) {
        filtered = filtered.filter(log => log.timestamp && log.timestamp >= this.dateFrom)
      }
      
      if (this.dateTo) {
        filtered = filtered.filter(log => log.timestamp && log.timestamp <= this.dateTo)
      }
      
      if (this.actionFilter) {
        filtered = filtered.filter(log => log.action && log.action.toLowerCase() === this.actionFilter)
      }
      
      this.filteredLogs = filtered
    },

    // --- Actions ---
    async clearLogs() {
      if (!confirm('Are you sure you want to clear ALL audit logs? This action cannot be undone.')) return
      this.clearing = true
      try {
        await axios.delete('/api/admin/audit-logs')
        this.logs = []
        this.filteredLogs = []
        this.$notify?.({ type: 'success', text: 'Logs cleared successfully' })
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
        link.setAttribute('download', `${this.activeTab}-logs-${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        this.$notify?.({ type: 'success', text: 'Export started successfully' })
      } catch (err) {
        this.handleApiError(err)
      }
    },

    viewDetails(log) {
      alert(`Log Details:\n\nUser: ${log.user_name || 'System'}\nAction: ${log.action}\nTimestamp: ${log.timestamp}\n\n${log.details}`)
    },

    // --- Helpers ---
    getDeviceIcon(deviceType) {
      const icons = { 
        mobile: '📱', 
        tablet: '📱', 
        desktop: '💻', 
        unknown: '🖥️' 
      }
      return icons[deviceType?.toLowerCase()] || icons.unknown
    },

    formatAction(action) {
      if (!action) return 'Unknown'
      return action.charAt(0).toUpperCase() + action.slice(1)
    },
    
    getActionBadgeClass(action) {
      const map = {
        create: 'success',
        update: 'warning',
        delete: 'danger',
        login: 'info'
      }
      return map[action?.toLowerCase()] || 'neutral'
    },

    formatDate(timestamp) {
      if (!timestamp) return 'N/A'
      return new Date(timestamp).toLocaleDateString('en-US', {
        month: 'short', 
        day: 'numeric', 
        year: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit'
      })
    },

    formatTime(timestamp) {
      if (!timestamp) return ''
      return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit', 
        minute: '2-digit'
      })
    },

    truncateText(text, length) {
      if (!text) return '—'
      return text.length > length ? text.substring(0, length) + '...' : text
    },

    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.response?.status === 401) {
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
        errorMsg = 'Session expired.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.$notify?.({ type: 'error', text: errorMsg })
    }
  }
}
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.audit-management {
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
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
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
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
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
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #1d4ed8;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #dbeafe;
  border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #2563eb;
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

.refresh-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 20px;
  font-size: 0.75rem;
  color: #1d4ed8;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: #2563eb;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.2); }
}

.refresh-text {
  font-weight: 600;
}

/* Buttons */
.btn-icon {
  width: 28px;
  height: 28px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-icon:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon.btn-danger:hover:not(:disabled) {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}

.btn-accent {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  border: none;
  padding: 0.375rem 0.75rem;
  border-radius: 7px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  white-space: nowrap;
}

.btn-accent:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-icon-small {
  width: 32px;
  height: 32px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-small:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-icon-small:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
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
  color: #3b82f6;
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

/* Tabs */
.tabs-container {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 2px;
}

.tab-button {
  background: none;
  border: none;
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  border-radius: 8px 8px 0 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-button:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.tab-button.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
  background: white;
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

.stat-card::before { display: none; }

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

.trend-warn {
  color: #f59e0b;
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
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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

.filter-input {
  padding: 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Select */
.select-wrapper {
  position: relative;
  width: 100%;
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
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select-chevron {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

/* ── Charts Grid ──────────────────────────────────────── */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.75rem;
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
  display: flex;
  align-items: center;
}

.count-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

/* Lists */
.list-container {
  padding: 0.5rem;
  max-height: 250px;
  overflow-y: auto;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.list-item:last-child {
  border-bottom: none;
}

.item-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #1e293b;
}

.item-value {
  background: #f1f5f9;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
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

/* User Info Cell */
.user-info-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1rem;
  flex-shrink: 0;
}

.user-avatar.small {
  width: 28px;
  height: 28px;
  font-size: 0.8rem;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: #1e293b;
}

.user-email {
  font-size: 0.75rem;
  color: #64748b;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.status-badge.approved,
.status-badge.success {
  background: #ecfdf5;
  color: #10b981;
  border: 1px solid #d1fae5;
}

.status-badge.rejected,
.status-badge.danger {
  background: #fef2f2;
  color: #ef4444;
  border: 1px solid #fee2e2;
}

.status-badge.warning {
  background: #fffbeb;
  color: #f59e0b;
  border: 1px solid #fef3c7;
}

.status-badge.info {
  background: #eff6ff;
  color: #3b82f6;
  border: 1px solid #dbeafe;
}

.status-badge.neutral {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

/* Action Badges */
.action-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.action-badge.success {
  background: #ecfdf5;
  color: #10b981;
}

.action-badge.warning {
  background: #fffbeb;
  color: #f59e0b;
}

.action-badge.danger {
  background: #fef2f2;
  color: #ef4444;
}

.action-badge.info {
  background: #eff6ff;
  color: #3b82f6;
}

.action-badge.neutral {
  background: #f1f5f9;
  color: #64748b;
}

/* Failure Reason */
.failure-reason {
  font-size: 0.7rem;
  color: #ef4444;
  margin-top: 0.25rem;
  max-width: 150px;
  white-space: normal;
}

/* Time Cell */
.time-cell {
  display: flex;
  flex-direction: column;
}

.date-time {
  font-family: 'Inter', monospace;
  font-size: 0.85rem;
  color: #1e293b;
}

.logout-time {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Location Cell */
.location-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.ip-badge {
  background: #f1f5f9;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-family: 'Inter', monospace;
  border: 1px solid #e2e8f0;
  width: fit-content;
}

.location {
  font-size: 0.75rem;
  color: #64748b;
}

/* Device Cell */
.device-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.device-type {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.85rem;
}

.device-icon {
  font-size: 1rem;
}

.browser {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Security Flags */
.security-flags {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  align-items: center;
}

.badge {
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge.warning {
  background: #fffbeb;
  color: #f59e0b;
  border: 1px solid #fef3c7;
}

.badge.success {
  background: #ecfdf5;
  color: #10b981;
  border: 1px solid #d1fae5;
}

.badge.pulse {
  animation: pulseOpacity 2s infinite;
}

@keyframes pulseOpacity {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.session-duration {
  font-size: 0.7rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

/* Details Cell */
.details-cell {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #64748b;
  font-size: 0.85rem;
}

/* Text Utilities */
.text-right {
  text-align: right;
}

.capitalize {
  text-transform: capitalize;
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
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #64748b;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
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

.empty-text {
  color: #94a3b8;
  font-style: italic;
  padding: 1rem;
  text-align: center;
}

/* Pagination */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
}

/* Fade Animation */
.fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
  
  .charts-grid {
    grid-template-columns: 1fr;
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
    flex-wrap: wrap;
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
  
  .filter-row {
    grid-template-columns: 1fr;
  }
  
  .tabs-container {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .tab-button {
    width: 100%;
    justify-content: center;
  }
  
  .data-table thead th,
  .data-table tbody td {
    padding: 0.75rem;
    font-size: 0.85rem;
  }
  
  .user-avatar {
    width: 32px;
    height: 32px;
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
  
  .btn-icon,
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
  
  .pagination-bar {
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
  }
  
  .security-flags {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>