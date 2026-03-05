<template>
  <div class="country-management">

    <!-- ── Sticky Header ──────────────────────────────── -->
    <div class="fixed-header">
      <div class="management-header-card">
        <div class="header-inner">
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="2" y1="12" x2="22" y2="12"></line>
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Country Management</h1>
                <p class="subtitle">Manage countries and their configurations</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ countries.length }} Countries</span>
                </div>
              </div>
            </div>
          </div>

          <div class="header-controls">
            <div class="search-wrapper">
              <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input v-model="searchQuery" type="text" placeholder="Search by name or code..." @input="handleSearch" class="header-search" />
            </div>

            <div class="select-wrapper">
              <select v-model="activeFilter" @change="fetchCountries" class="header-select">
                <option value="all">All Countries</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
              </select>
              <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>

            <div class="view-toggle">
              <button type="button" :class="['toggle-btn', viewMode === 'grid' ? 'active' : '']" @click="viewMode = 'grid'" title="Grid View">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
              </button>
              <button type="button" :class="['toggle-btn', viewMode === 'table' ? 'active' : '']" @click="viewMode = 'table'" title="Table View">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
              </button>
            </div>

            <button @click="openCreateModal" class="btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add Country
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Scrollable Content ─────────────────────────── -->
    <div class="page-content">

      <!-- Stats Badge and Refresh - Moved below header, aligned right -->
      <div class="content-header">
        <div class="stats-badge">
          <div class="stats-content">
            <span class="stats-primary">{{ filteredCountries.length }}</span>
            <div class="stats-details">
              <span class="stats-label">Filtered</span>
              <span class="stats-total">of {{ countries.length }}</span>
            </div>
          </div>
        </div>

        <button @click="fetchCountries" class="btn-icon" title="Refresh Data">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
        </button>
      </div>

      <div v-if="loading" class="state-banner loading">
        <div class="spinner"></div>
        <span>Loading countries...</span>
      </div>

      <div v-else-if="error" class="state-banner error">
        {{ error }}
        <button @click="fetchCountries" class="btn-text">Retry</button>
      </div>

      <div v-else class="management-content">

        <div v-if="filteredCountries.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="2" y1="12" x2="22" y2="12"></line>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
          </div>
          <h3>No Countries Found</h3>
          <p>Start by adding your first country to the system</p>
          <button @click="openCreateModal" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Country
          </button>
        </div>

        <template v-else>

          <!-- GRID VIEW -->
          <div v-if="viewMode === 'grid'" class="countries-grid">
            <div v-for="country in filteredCountries" :key="country.id" class="country-card" :class="{ inactive: !country.is_active }">
              <div class="card-header">
                <div class="country-flag">
                  <img v-if="country.flag" :src="country.flag" :alt="`${country.name} flag`" class="flag-image" @error="handleFlagError" />
                  <span v-else class="flag-fallback">{{ country.code }}</span>
                </div>
                <div class="country-info">
                  <h3>{{ country.name }}</h3>
                  <span class="country-code">{{ country.code }}</span>
                </div>
                <span :class="['status-badge', country.is_active ? 'active' : 'inactive']">
                  {{ country.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <div class="card-body">
                <div class="info-row"><span class="label">Currency:</span><span class="value">{{ country.currency_symbol }} {{ country.currency_code }}</span></div>
                <div class="info-row"><span class="label">Phone Code:</span><span class="value">{{ country.phone_code }}</span></div>
                <div class="info-row"><span class="label">Timezone:</span><span class="value">{{ formatTimezone(country.timezone) }}</span></div>
                <div class="info-row"><span class="label">Date Format:</span><span class="value">{{ country.date_format }}</span></div>
                <div v-if="country.configuration" class="config-info">
                  <h4>Work Configuration</h4>
                  <div class="config-details">
                    <div class="config-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg><span>{{ country.configuration.work_days_per_week }} days/week</span></div>
                    <div class="config-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><span>{{ country.configuration.hours_per_day }} hrs/day</span></div>
                    <div class="config-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span>{{ country.configuration.annual_leave_days }} annual</span></div>
                    <div class="config-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8 10a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.574 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><span>{{ country.configuration.sick_leave_days }} sick</span></div>
                  </div>
                </div>
              </div>

              <div class="card-actions">
                <button @click="viewStatistics(country)" class="btn-action btn-stats"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg><span class="btn-text">Stats</span></button>
                <button @click="editCountry(country)" class="btn-action btn-edit"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"></path><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"></polygon></svg><span class="btn-text">Edit</span></button>
                <button @click="toggleStatus(country)" :class="['btn-action', country.is_active ? 'btn-deactivate' : 'btn-activate']" :disabled="togglingStatus === country.id">
                  <svg v-if="togglingStatus === country.id" class="spinner-small" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32"><animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/></circle></svg>
                  <template v-else><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2" ry="2"></rect><circle cx="12" cy="12" r="2" fill="currentColor"/></svg><span class="btn-text">{{ country.is_active ? 'Active' : 'Inactive' }}</span></template>
                </button>
                <button @click="deleteCountry(country)" class="btn-action btn-delete" :disabled="deleting === country.id">
                  <svg v-if="deleting === country.id" class="spinner-small" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32"><animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/></circle></svg>
                  <template v-else><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0h10"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg><span class="btn-text">Delete</span></template>
                </button>
              </div>
            </div>
          </div>

          <!-- TABLE VIEW -->
          <div v-else class="table-wrapper">
            <table class="countries-table">
              <thead>
                <tr><th>Country</th><th>Code</th><th>Currency</th><th>Phone</th><th>Timezone</th><th>Work Config</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <tr v-for="country in filteredCountries" :key="country.id" :class="{ 'row-inactive': !country.is_active }">
                  <td><div class="table-country-cell"><div class="table-flag"><img v-if="country.flag" :src="country.flag" :alt="`${country.name} flag`" class="flag-image" @error="handleFlagError" /><span v-else class="flag-fallback-sm">{{ country.code }}</span></div><span class="table-country-name">{{ country.name }}</span></div></td>
                  <td><span class="code-chip">{{ country.code }}</span></td>
                  <td class="col-currency">{{ country.currency_symbol }} {{ country.currency_code }}</td>
                  <td>{{ country.phone_code }}</td>
                  <td>{{ formatTimezone(country.timezone) }}</td>
                  <td><span v-if="country.configuration" class="config-pills"><span class="cpill">{{ country.configuration.work_days_per_week }}d/wk</span><span class="cpill">{{ country.configuration.hours_per_day }}h/day</span><span class="cpill">{{ country.configuration.annual_leave_days }} AL</span></span><span v-else class="text-muted">—</span></td>
                  <td><span :class="['status-badge', country.is_active ? 'active' : 'inactive']">{{ country.is_active ? 'Active' : 'Inactive' }}</span></td>
                  <td>
                    <div class="table-actions">
                      <button @click="viewStatistics(country)" class="tbl-btn tbl-stats" title="Statistics"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></button>
                      <button @click="editCountry(country)" class="tbl-btn tbl-edit" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"></path><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"></polygon></svg></button>
                      <button @click="toggleStatus(country)" :class="['tbl-btn', country.is_active ? 'tbl-deactivate' : 'tbl-activate']" :disabled="togglingStatus === country.id" :title="country.is_active ? 'Deactivate' : 'Activate'">
                        <svg v-if="togglingStatus === country.id" class="spinner-small" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32"><animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/></circle></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2" ry="2"></rect><circle cx="12" cy="12" r="2" fill="currentColor"/></svg>
                      </button>
                      <button @click="deleteCountry(country)" class="tbl-btn tbl-delete" :disabled="deleting === country.id" title="Delete">
                        <svg v-if="deleting === country.id" class="spinner-small" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32"><animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/></circle></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0h10"></path></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </template>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Country' : 'Add New Country' }}</h2>
          <button @click="closeModal" class="close-btn"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <form @submit.prevent="saveCountry" class="modal-body">
          <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-grid">
              <div class="form-group"><label>Country Name *</label><input v-model="formData.name" type="text" placeholder="e.g., Zambia" required :class="{ 'error': errors.name }" /><span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span></div>
              <div class="form-group"><label>Country Code (ISO 2) *</label><input v-model="formData.code" type="text" placeholder="e.g., ZM" maxlength="2" required @input="generateFlagUrl" style="text-transform: uppercase;" :class="{ 'error': errors.code }" /><span v-if="errors.code" class="error-text">{{ errors.code[0] }}</span></div>
              <div class="form-group full-width"><label>Flag URL *</label><div class="flag-input-wrapper"><input v-model="formData.flag" type="text" placeholder="e.g., https://flagcdn.com/zm.svg" required class="flag-input" :class="{ 'error': errors.flag }" /><div v-if="formData.flag" class="flag-preview"><img :src="formData.flag" :alt="`${formData.name} flag`" @error="handleFormFlagError" class="preview-flag" /></div></div><span v-if="errors.flag" class="error-text">{{ errors.flag[0] }}</span><small class="help-text">Auto-generated from country code. You can also use <a href="https://flagcdn.com" target="_blank">FlagCDN</a></small></div>
              <div class="form-group"><label>Currency Code *</label><input v-model="formData.currency_code" type="text" placeholder="e.g., ZMW" maxlength="3" required style="text-transform: uppercase;" :class="{ 'error': errors.currency_code }" /><span v-if="errors.currency_code" class="error-text">{{ errors.currency_code[0] }}</span></div>
              <div class="form-group"><label>Currency Symbol *</label><input v-model="formData.currency_symbol" type="text" placeholder="e.g., K" required :class="{ 'error': errors.currency_symbol }" /><span v-if="errors.currency_symbol" class="error-text">{{ errors.currency_symbol[0] }}</span></div>
              <div class="form-group"><label>Phone Code *</label><input v-model="formData.phone_code" type="text" placeholder="e.g., +260" required :class="{ 'error': errors.phone_code }" /><span v-if="errors.phone_code" class="error-text">{{ errors.phone_code[0] }}</span></div>
              <div class="form-group"><label>Timezone *</label><select v-model="formData.timezone" required :class="{ 'error': errors.timezone }"><option value="">Select Timezone</option><option v-for="tz in timezones" :key="tz" :value="tz">{{ tz.replace('_', ' ') }}</option></select><span v-if="errors.timezone" class="error-text">{{ errors.timezone[0] }}</span></div>
              <div class="form-group"><label>Date Format *</label><select v-model="formData.date_format" required :class="{ 'error': errors.date_format }"><option value="Y-m-d">YYYY-MM-DD</option><option value="d/m/Y">DD/MM/YYYY</option><option value="m/d/Y">MM/DD/YYYY</option><option value="d-M-Y">DD-MMM-YYYY</option></select><span v-if="errors.date_format" class="error-text">{{ errors.date_format[0] }}</span></div>
              <div class="form-group"><label>Time Format *</label><select v-model="formData.time_format" required :class="{ 'error': errors.time_format }"><option value="H:i">24 Hour (HH:MM)</option><option value="h:i A">12 Hour (hh:mm AM/PM)</option></select><span v-if="errors.time_format" class="error-text">{{ errors.time_format[0] }}</span></div>
            </div>
            <div class="checkbox-group"><label class="checkbox-label"><input v-model="formData.is_active" type="checkbox" /><span>Active Country</span></label></div>
          </div>
          <div class="form-section">
            <h3>Work Configuration</h3>
            <div class="form-grid">
              <div class="form-group"><label>Work Days per Week</label><input v-model.number="formData.work_days_per_week" type="number" min="1" max="7" placeholder="e.g., 5" /></div>
              <div class="form-group"><label>Hours per Day</label><input v-model.number="formData.hours_per_day" type="number" min="1" max="24" step="0.5" placeholder="e.g., 8" /></div>
              <div class="form-group"><label>Overtime Multiplier</label><input v-model.number="formData.overtime_multiplier" type="number" min="1" step="0.1" placeholder="e.g., 1.5" /></div>
              <div class="form-group"><label>Annual Leave Days</label><input v-model.number="formData.annual_leave_days" type="number" min="0" placeholder="e.g., 20" /></div>
              <div class="form-group"><label>Sick Leave Days</label><input v-model.number="formData.sick_leave_days" type="number" min="0" placeholder="e.g., 10" /></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <svg v-if="saving" class="spinner-small" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32"><animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/></circle></svg>
              <span v-else>{{ isEditMode ? 'Update Country' : 'Add Country' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStatsModal" class="modal-overlay" @click.self="closeStatsModal">
      <div class="modal-content stats-modal">
        <div class="modal-header">
          <h2><img v-if="selectedCountry?.flag" :src="selectedCountry.flag" :alt="`${selectedCountry.name} flag`" class="stats-flag" @error="(e) => e.target.style.display = 'none'" />{{ selectedCountry?.name }} - Statistics</h2>
          <button @click="closeStatsModal" class="close-btn"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div v-if="loadingStats" class="modal-body"><div class="loading-state"><div class="spinner"></div><p>Loading statistics...</p></div></div>
        <div v-else class="modal-body">
          <div class="stats-grid">
            <div class="stat-card" style="--accent:#3b82f6;"><div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div><div class="stat-info"><span class="stat-label">Total Employees</span><div class="stat-number">{{ statistics?.total_employees || 0 }}</div></div></div>
            <div class="stat-card" style="--accent:#10b981;"><div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div><div class="stat-info"><span class="stat-label">Active Employees</span><div class="stat-number">{{ statistics?.active_employees || 0 }}</div></div></div>
            <div class="stat-card" style="--accent:#f59e0b;"><div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div><div class="stat-info"><span class="stat-label">Total Attendances</span><div class="stat-number">{{ statistics?.total_attendances || 0 }}</div></div></div>
            <div class="stat-card" style="--accent:#8b5cf6;"><div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M4 4v16h16"></path><polyline points="20 10 12 18 8 14"></polyline></svg></div><div class="stat-info"><span class="stat-label">Total Leaves</span><div class="stat-number">{{ statistics?.total_leaves || 0 }}</div></div></div>
            <div class="stat-card" style="--accent:#ec4899;"><div class="stat-icon-wrap" style="background:rgba(236,72,153,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ec4899" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div><div class="stat-info"><span class="stat-label">Total Payrolls</span><div class="stat-number">{{ statistics?.total_payrolls || 0 }}</div></div></div>
            <div class="stat-card" style="--accent:#f97316;"><div class="stat-icon-wrap" style="background:rgba(249,115,22,0.1);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div><div class="stat-info"><span class="stat-label">Total Payslips</span><div class="stat-number">{{ statistics?.total_payslips || 0 }}</div></div></div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CountryManagement',
  data() {
    return {
      countries: [],
      loading: false,
      error: null,
      searchQuery: '',
      activeFilter: 'all',
      viewMode: 'grid',
      showModal: false,
      showStatsModal: false,
      isEditMode: false,
      saving: false,
      loadingStats: false,
      togglingStatus: null,
      deleting: null,
      selectedCountry: null,
      statistics: null,
      errors: {},
      formData: {
        name: '', code: '', flag: '', currency_code: '', currency_symbol: '',
        phone_code: '', timezone: '', date_format: 'Y-m-d', time_format: 'H:i',
        is_active: true, work_days_per_week: 5, hours_per_day: 8,
        overtime_multiplier: 1.5, annual_leave_days: 20, sick_leave_days: 10,
      },
      timezones: [
        'Africa/Lusaka', 'Africa/Johannesburg', 'Africa/Nairobi', 'Africa/Lagos',
        'Africa/Cairo', 'Africa/Casablanca', 'Africa/Algiers', 'Africa/Tunis',
        'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Dar_es_Salaam', 'Africa/Kampala',
        'Africa/Harare', 'Africa/Gaborone', 'Africa/Maputo', 'Africa/Windhoek',
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Rome',
        'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Shanghai',
        'Asia/Tokyo', 'Australia/Sydney', 'Australia/Melbourne', 'Pacific/Auckland'
      ],
      searchTimeout: null,
    };
  },
  computed: {
    filteredCountries() {
      let filtered = this.countries;
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(c =>
          c.name.toLowerCase().includes(query) || c.code.toLowerCase().includes(query)
        );
      }
      return filtered;
    },
  },
  mounted() { this.fetchCountries(); },
  methods: {
    async fetchCountries() {
      this.loading = true; this.error = null;
      try {
        await this.ensureCsrfToken();
        const params = {};
        if (this.activeFilter !== 'all') params.is_active = this.activeFilter === 'active';
        const response = await axios.get('/api/admin/countries', { params });
        this.countries = response.data.data || response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load countries';
      } finally { this.loading = false; }
    },
    async ensureCsrfToken() {
      try { await axios.get('/sanctum/csrf-cookie', { baseURL: window.location.origin }); } catch {}
    },
    handleSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {}, 300);
    },
    openCreateModal() { this.isEditMode = false; this.errors = {}; this.resetForm(); this.showModal = true; },
    editCountry(country) {
      this.isEditMode = true; this.selectedCountry = country; this.errors = {};
      this.formData = {
        name: country.name, code: country.code, flag: country.flag || '',
        currency_code: country.currency_code, currency_symbol: country.currency_symbol,
        phone_code: country.phone_code, timezone: country.timezone,
        date_format: country.date_format, time_format: country.time_format,
        is_active: country.is_active,
        work_days_per_week: country.configuration?.work_days_per_week || 5,
        hours_per_day: country.configuration?.hours_per_day || 8,
        overtime_multiplier: country.configuration?.overtime_multiplier || 1.5,
        annual_leave_days: country.configuration?.annual_leave_days || 20,
        sick_leave_days: country.configuration?.sick_leave_days || 10,
      };
      this.showModal = true;
    },
    generateFlagUrl() {
      if (this.formData.code?.length === 2)
        this.formData.flag = `https://flagcdn.com/${this.formData.code.toLowerCase()}.svg`;
    },
    handleFlagError(e) { e.target.style.display = 'none'; },
    handleFormFlagError(e) { e.target.style.display = 'none'; },
    formatTimezone(tz) { return tz ? tz.replace('_', ' ').split('/').pop() : '—'; },
    async saveCountry() {
      this.saving = true; this.errors = {};
      try {
        await this.ensureCsrfToken();
        if (this.isEditMode) {
          await axios.put(`/api/admin/countries/${this.selectedCountry.id}`, this.formData);
          this.$toast?.success('Country updated successfully');
        } else {
          await axios.post('/api/admin/countries', this.formData);
          this.$toast?.success('Country created successfully');
        }
        this.closeModal(); this.fetchCountries();
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
          const msgs = Object.values(this.errors).flat();
          if (msgs.length > 0) this.$toast?.error(msgs[0]);
        } else {
          this.$toast?.error(error.response?.data?.message || 'Failed to save country');
        }
      } finally { this.saving = false; }
    },
    async toggleStatus(country) {
      if (this.togglingStatus === country.id) return;
      if (!confirm(`Are you sure you want to ${country.is_active ? 'deactivate' : 'activate'} ${country.name}?`)) return;
      this.togglingStatus = country.id;
      try {
        await this.ensureCsrfToken();
        const response = await axios.post(`/api/admin/countries/${country.id}/toggle-status`);
        this.$toast?.success(response.data.message);
        const idx = this.countries.findIndex(c => c.id === country.id);
        if (idx !== -1) this.countries[idx].is_active = !country.is_active;
      } catch (error) {
        this.$toast?.error(error.response?.data?.message || 'Failed to update status');
      } finally { this.togglingStatus = null; }
    },
    async deleteCountry(country) {
      if (this.deleting === country.id) return;
      if (!confirm(`Are you sure you want to delete ${country.name}? This action cannot be undone.`)) return;
      this.deleting = country.id;
      try {
        await this.ensureCsrfToken();
        await axios.delete(`/api/admin/countries/${country.id}`);
        this.$toast?.success('Country deleted successfully');
        this.fetchCountries();
      } catch (error) {
        this.$toast?.error(error.response?.data?.message || 'Failed to delete country');
      } finally { this.deleting = null; }
    },
    async viewStatistics(country) {
      this.selectedCountry = country; this.showStatsModal = true;
      this.loadingStats = true; this.statistics = null;
      try {
        await this.ensureCsrfToken();
        const response = await axios.get(`/api/admin/countries/${country.id}/statistics`);
        this.statistics = response.data.data?.statistics || response.data.statistics || response.data;
      } catch (error) {
        this.$toast?.error('Failed to load statistics');
      } finally { this.loadingStats = false; }
    },
    closeModal() { this.showModal = false; this.errors = {}; this.resetForm(); },
    closeStatsModal() { this.showStatsModal = false; this.statistics = null; this.selectedCountry = null; },
    resetForm() {
      this.formData = {
        name: '', code: '', flag: '', currency_code: '', currency_symbol: '',
        phone_code: '', timezone: '', date_format: 'Y-m-d', time_format: 'H:i',
        is_active: true, work_days_per_week: 5, hours_per_day: 8,
        overtime_multiplier: 1.5, annual_leave_days: 20, sick_leave_days: 10,
      };
      this.selectedCountry = null;
    },
  },
};
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.country-management {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Sticky Header ────────────────────────────────────── */
.fixed-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(248, 250, 252, 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  padding: 1rem 2rem 0;
  box-sizing: border-box;
  box-shadow: 0 1px 0 rgba(0,0,0,0.04);
}

.management-header-card {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1rem;
  position: relative;
  overflow: hidden;
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px; right: -20px;
  width: 140px; height: 140px;
  background: radial-gradient(circle, rgba(59,130,246,0.05) 0%, transparent 70%);
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
  gap: 1rem; 
  flex: 1; 
  flex-wrap: wrap; 
}

.avatar-section { 
  display: flex; 
  align-items: center; 
  gap: 0.75rem; 
}

.avatar { 
  width: 44px; 
  height: 44px; 
  background: linear-gradient(135deg, #3b82f6, #2563eb); 
  border-radius: 10px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: white; 
  box-shadow: 0 4px 10px rgba(59,130,246,0.25); 
  flex-shrink: 0; 
}

.user-info { 
  display: flex; 
  flex-direction: column; 
  gap: 0.1rem; 
}

.greeting { 
  margin: 0; 
  font-size: 1.25rem; 
  font-weight: 700; 
  color: #1e293b; 
  line-height: 1.2; 
}

.subtitle { 
  margin: 0; 
  color: #64748b; 
  font-size: 0.8rem; 
}

.role-meta { 
  display: flex; 
  align-items: center; 
  gap: 0.4rem; 
  margin-top: 0.125rem; 
}

.role-badge { 
  background: #eff6ff; 
  border: 1px solid #bfdbfe; 
  padding: 0.1rem 0.5rem; 
  border-radius: 6px; 
  font-size: 0.65rem; 
  font-weight: 600; 
  color: #2563eb; 
}

.month-badge { 
  background: #dbeafe; 
  border: 1px solid #93c5fd; 
  padding: 0.1rem 0.5rem; 
  border-radius: 6px; 
  font-size: 0.65rem; 
  font-weight: 600; 
  color: #1d4ed8; 
}

.header-controls { 
  display: flex; 
  align-items: center; 
  gap: 0.6rem; 
  flex-wrap: wrap; 
}

.search-wrapper { 
  position: relative; 
}

.search-icon { 
  position: absolute; 
  left: 10px; 
  top: 50%; 
  transform: translateY(-50%); 
  color: #94a3b8; 
  pointer-events: none; 
}

.header-search { 
  background: #f8fafc; 
  border: 1px solid #e2e8f0; 
  color: #475569; 
  padding: 0.4rem 0.75rem 0.4rem 2rem; 
  border-radius: 6px; 
  font-family: inherit; 
  font-size: 0.8rem; 
  min-width: 200px; 
  transition: all 0.2s; 
}

.header-search:focus { 
  outline: none; 
  border-color: #3b82f6; 
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
}

.header-search::placeholder { 
  color: #94a3b8; 
}

.select-wrapper { 
  position: relative; 
}

.header-select { 
  appearance: none; 
  background: #f8fafc; 
  border: 1px solid #e2e8f0; 
  color: #475569; 
  padding: 0.4rem 2rem 0.4rem 0.75rem; 
  border-radius: 6px; 
  font-family: inherit; 
  font-size: 0.8rem; 
  cursor: pointer; 
  min-width: 130px; 
  transition: all 0.2s; 
}

.header-select:focus { 
  outline: none; 
  border-color: #3b82f6; 
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
}

.select-chevron { 
  position: absolute; 
  right: 8px; 
  top: 50%; 
  transform: translateY(-50%); 
  color: #94a3b8; 
  pointer-events: none; 
}

.view-toggle { 
  display: flex; 
  border: 1px solid #e2e8f0; 
  border-radius: 6px; 
  overflow: hidden; 
  flex-shrink: 0; 
}

.toggle-btn { 
  width: 32px; 
  height: 32px; 
  border: none; 
  background: white; 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.15s; 
  font-family: inherit; 
}

.toggle-btn:first-child { 
  border-right: 1px solid #e2e8f0; 
}

.toggle-btn.active { 
  background: #3b82f6; 
  color: white; 
}

.toggle-btn:not(.active):hover { 
  background: #f8fafc; 
  color: #1e293b; 
}

.btn-primary { 
  background: linear-gradient(135deg, #3b82f6, #2563eb); 
  color: white; 
  border: none; 
  padding: 0.45rem 1rem; 
  border-radius: 6px; 
  font-size: 0.8rem; 
  font-weight: 600; 
  cursor: pointer; 
  display: flex; 
  align-items: center; 
  gap: 0.4rem; 
  transition: all 0.2s; 
  box-shadow: 0 4px 10px rgba(59,130,246,0.3); 
  white-space: nowrap; 
}

.btn-primary:hover:not(:disabled) { 
  transform: translateY(-1px); 
  box-shadow: 0 6px 14px rgba(59,130,246,0.4); 
}

.btn-primary:disabled { 
  opacity: 0.6; 
  cursor: not-allowed; 
}

/* ── Page Content ─────────────────────────────────────── */
.page-content {
  flex: 1;
  padding: 0 2rem 2rem;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
}

.content-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.stats-badge { 
  background: #f8fafc; 
  border: 1px solid #e2e8f0; 
  border-radius: 8px; 
  padding: 0.5rem 0.875rem; 
  flex-shrink: 0; 
}

.stats-content { 
  display: flex; 
  align-items: center; 
  gap: 0.6rem; 
}

.stats-primary { 
  font-size: 1.25rem; 
  font-weight: 700; 
  color: #3b82f6; 
  line-height: 1; 
}

.stats-details { 
  display: flex; 
  flex-direction: column; 
}

.stats-label { 
  font-size: 0.7rem; 
  font-weight: 600; 
  color: #64748b; 
  text-transform: uppercase; 
  letter-spacing: 0.02em; 
}

.stats-total { 
  font-size: 0.65rem; 
  color: #94a3b8; 
}

.btn-icon { 
  width: 36px; 
  height: 36px; 
  background: #f8fafc; 
  border: 1px solid #e2e8f0; 
  border-radius: 6px; 
  color: #475569; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.2s; 
  flex-shrink: 0; 
}

.btn-icon:hover { 
  background: #f1f5f9; 
  border-color: #cbd5e1; 
  color: #1e293b; 
}

/* ── State Banners ────────────────────────────────────── */
.state-banner { 
  padding: 1.25rem; 
  border-radius: 10px; 
  text-align: center; 
  margin-bottom: 1.5rem; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 0.75rem; 
  font-size: 0.9rem;
}

.state-banner.error { 
  background: #fef2f2; 
  color: #dc2626; 
  border: 1px solid #fecaca; 
}

.state-banner.loading { 
  background: #f0f9ff; 
  color: #0369a1; 
  border: 1px solid #bae6fd; 
}

.spinner { 
  width: 24px; 
  height: 24px; 
  border: 3px solid #bae6fd; 
  border-top-color: #0284c7; 
  border-radius: 50%; 
  animation: spin 1s linear infinite; 
}

.spinner-small { 
  width: 14px; 
  height: 14px; 
  animation: spin 1s linear infinite; 
}

@keyframes spin { 
  to { transform: rotate(360deg); } 
}

.btn-text { 
  background: none; 
  border: none; 
  color: #3b82f6; 
  font-weight: 600; 
  cursor: pointer; 
  margin-left: 0.75rem; 
  font-size: 0.9rem;
}

.btn-text:hover { 
  text-decoration: underline; 
}

/* ── Management Content ───────────────────────────────── */
.management-content { 
  display: flex; 
  flex-direction: column; 
  gap: 1.5rem; 
}

/* ── Countries Grid ───────────────────────────────────── */
.countries-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
  gap: 1.25rem; 
}

.country-card { 
  background: white; 
  border-radius: 12px; 
  border: 1px solid #e2e8f0; 
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); 
  overflow: hidden; 
  transition: transform 0.2s, box-shadow 0.2s; 
}

.country-card:hover { 
  transform: translateY(-3px); 
  box-shadow: 0 10px 20px -6px rgba(0,0,0,0.1); 
}

.country-card.inactive { 
  opacity: 0.85; 
  background: #fafbfc; 
}

.card-header { 
  display: flex; 
  align-items: center; 
  gap: 0.875rem; 
  padding: 1rem 1.25rem; 
  background: #fcfcfc; 
  border-bottom: 1px solid #f1f5f9; 
}

.country-flag { 
  position: relative; 
  width: 42px; 
  height: 42px; 
  border-radius: 8px; 
  background: #f1f5f9; 
  overflow: hidden; 
  flex-shrink: 0; 
  border: 1px solid #e2e8f0; 
}

.flag-image { 
  position: absolute; 
  inset: 0; 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
}

.flag-fallback { 
  position: absolute; 
  inset: 0; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-weight: 700; 
  font-size: 1rem; 
  color: #64748b; 
}

.country-info { 
  flex: 1; 
  min-width: 0; 
}

.country-info h3 { 
  margin: 0; 
  font-size: 1rem; 
  font-weight: 700; 
  color: #1e293b; 
  line-height: 1.3; 
}

.country-code { 
  font-size: 0.75rem; 
  color: #64748b; 
  font-weight: 500; 
}

.status-badge { 
  padding: 0.2rem 0.6rem; 
  border-radius: 20px; 
  font-size: 0.65rem; 
  font-weight: 600; 
  text-transform: uppercase; 
  letter-spacing: 0.02em; 
  white-space: nowrap; 
}

.status-badge.active { 
  background: #dcfce7; 
  color: #166534; 
}

.status-badge.inactive { 
  background: #fee2e2; 
  color: #991b1b; 
}

.card-body { 
  padding: 1rem 1.25rem; 
}

.info-row { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  padding: 0.4rem 0; 
  border-bottom: 1px dashed #f1f5f9; 
  font-size: 0.85rem; 
}

.info-row:last-child { 
  border-bottom: none; 
}

.info-row .label { 
  color: #64748b; 
  font-weight: 500; 
}

.info-row .value { 
  color: #1e293b; 
  font-weight: 600; 
}

.config-info { 
  margin-top: 0.875rem; 
  padding-top: 0.875rem; 
  border-top: 2px solid #f1f5f9; 
}

.config-info h4 { 
  margin: 0 0 0.6rem; 
  font-size: 0.85rem; 
  font-weight: 700; 
  color: #334155; 
}

.config-details { 
  display: grid; 
  grid-template-columns: repeat(2, 1fr); 
  gap: 0.5rem; 
}

.config-item { 
  display: flex; 
  align-items: center; 
  gap: 0.4rem; 
  font-size: 0.75rem; 
  color: #475569; 
}

.config-item svg { 
  color: #3b82f6; 
  flex-shrink: 0; 
}

.card-actions { 
  display: flex; 
  gap: 0.4rem; 
  padding: 0.875rem 1.25rem 1rem; 
  background: #f8fafc; 
  border-top: 1px solid #e2e8f0; 
}

.btn-action { 
  flex: 1; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 0.25rem; 
  padding: 0.45rem 0.2rem; 
  background: white; 
  border: 1px solid #e2e8f0; 
  border-radius: 6px; 
  font-size: 0.7rem; 
  font-weight: 600; 
  color: #475569; 
  cursor: pointer; 
  transition: all 0.2s; 
  white-space: nowrap; 
}

.btn-action:hover:not(:disabled) { 
  transform: translateY(-1px); 
  box-shadow: 0 2px 6px rgba(0,0,0,0.05); 
}

.btn-action:disabled { 
  opacity: 0.5; 
  cursor: not-allowed; 
}

.btn-stats { 
  border-color: #3b82f6; 
  color: #3b82f6; 
} 

.btn-stats:hover:not(:disabled) { 
  background: #eff6ff; 
}

.btn-edit { 
  border-color: #f59e0b; 
  color: #f59e0b; 
} 

.btn-edit:hover:not(:disabled) { 
  background: #fffbeb; 
}

.btn-activate { 
  border-color: #10b981; 
  color: #10b981; 
} 

.btn-activate:hover:not(:disabled) { 
  background: #ecfdf5; 
}

.btn-deactivate { 
  border-color: #ef4444; 
  color: #ef4444; 
} 

.btn-deactivate:hover:not(:disabled) { 
  background: #fef2f2; 
}

.btn-delete { 
  border-color: #ef4444; 
  color: #ef4444; 
} 

.btn-delete:hover:not(:disabled) { 
  background: #fef2f2; 
}

/* ── Table View ───────────────────────────────────────── */
.table-wrapper { 
  overflow-x: auto; 
  border-radius: 10px; 
  border: 1px solid #e2e8f0; 
  background: white; 
}

.countries-table { 
  width: 100%; 
  border-collapse: collapse; 
  font-size: 0.8rem; 
}

.countries-table thead tr { 
  background: #f8fafc; 
  border-bottom: 1px solid #e2e8f0; 
}

.countries-table th { 
  padding: 0.7rem 0.875rem; 
  text-align: left; 
  font-size: 0.65rem; 
  font-weight: 700; 
  color: #64748b; 
  text-transform: uppercase; 
  letter-spacing: 0.04em; 
  white-space: nowrap; 
}

.countries-table tbody tr { 
  border-bottom: 1px solid #f1f5f9; 
  transition: background 0.12s; 
}

.countries-table tbody tr:last-child { 
  border-bottom: none; 
}

.countries-table tbody tr:hover { 
  background: #fafafa; 
}

.countries-table tbody tr.row-inactive { 
  opacity: 0.7; 
}

.countries-table td { 
  padding: 0.75rem 0.875rem; 
  vertical-align: middle; 
}

.table-country-cell { 
  display: flex; 
  align-items: center; 
  gap: 0.6rem; 
}

.table-flag { 
  width: 32px; 
  height: 24px; 
  border-radius: 5px; 
  background: #f1f5f9; 
  overflow: hidden; 
  flex-shrink: 0; 
  border: 1px solid #e2e8f0; 
  position: relative; 
}

.table-flag .flag-image { 
  position: absolute; 
  inset: 0; 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
}

.flag-fallback-sm { 
  font-size: 0.6rem; 
  font-weight: 700; 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  height: 100%; 
}

.table-country-name { 
  font-weight: 600; 
  color: #1e293b; 
  white-space: nowrap; 
}

.code-chip { 
  background: #f1f5f9; 
  border: 1px solid #e2e8f0; 
  padding: 0.1rem 0.45rem; 
  border-radius: 5px; 
  font-size: 0.7rem; 
  font-weight: 700; 
  color: #475569; 
  font-family: monospace; 
}

.col-currency { 
  font-weight: 600; 
  color: #334155; 
}

.config-pills { 
  display: flex; 
  flex-wrap: wrap; 
  gap: 0.25rem; 
}

.cpill { 
  background: #eff6ff; 
  border: 1px solid #bfdbfe; 
  padding: 0.1rem 0.4rem; 
  border-radius: 9999px; 
  font-size: 0.65rem; 
  font-weight: 600; 
  color: #2563eb; 
  white-space: nowrap; 
}

.text-muted { 
  color: #94a3b8; 
}

.table-actions { 
  display: flex; 
  gap: 0.3rem; 
  justify-content: flex-end; 
}

.tbl-btn { 
  width: 28px; 
  height: 28px; 
  border-radius: 5px; 
  border: 1px solid #e2e8f0; 
  background: white; 
  color: #64748b; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.15s; 
  flex-shrink: 0; 
}

.tbl-btn:disabled { 
  opacity: 0.4; 
  cursor: not-allowed; 
}

.tbl-stats:hover:not(:disabled) { 
  background: #eff6ff; 
  color: #3b82f6; 
  border-color: #bfdbfe; 
}

.tbl-edit:hover:not(:disabled) { 
  background: #fffbeb; 
  color: #d97706; 
  border-color: #fde68a; 
}

.tbl-activate:hover:not(:disabled) { 
  background: #ecfdf5; 
  color: #059669; 
  border-color: #a7f3d0; 
}

.tbl-deactivate:hover:not(:disabled) { 
  background: #fef2f2; 
  color: #dc2626; 
  border-color: #fca5a5; 
}

.tbl-delete:hover:not(:disabled) { 
  background: #fef2f2; 
  color: #dc2626; 
  border-color: #fca5a5; 
}

/* ── Empty State ──────────────────────────────────────── */
.empty-state { 
  text-align: center; 
  padding: 3rem 2rem; 
  background: white; 
  border-radius: 12px; 
  border: 1px solid #e2e8f0; 
}

.empty-icon { 
  width: 70px; 
  height: 70px; 
  margin: 0 auto 1.25rem; 
  background: #f8fafc; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  border: 2px dashed #e2e8f0; 
}

.empty-state h3 { 
  margin: 0 0 0.4rem; 
  font-size: 1.1rem; 
  color: #1e293b; 
}

.empty-state p { 
  margin: 0 0 1.25rem; 
  color: #64748b; 
  font-size: 0.9rem;
}

/* ── Modal ────────────────────────────────────────────── */
.modal-overlay { 
  position: fixed; 
  inset: 0; 
  background: rgba(0,0,0,0.5); 
  backdrop-filter: blur(4px); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 1000; 
  padding: 1rem; 
}

.modal-content { 
  background: white; 
  border-radius: 14px; 
  width: 100%; 
  max-width: 850px; 
  max-height: 90vh; 
  overflow: hidden; 
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
  animation: modalSlideUp 0.2s ease; 
}

@keyframes modalSlideUp { 
  from { opacity: 0; transform: translateY(20px); } 
  to { opacity: 1; transform: translateY(0); } 
}

.modal-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  padding: 1rem 1.5rem; 
  border-bottom: 1px solid #e2e8f0; 
  background: #fcfcfc; 
}

.modal-header h2 { 
  margin: 0; 
  font-size: 1.1rem; 
  font-weight: 700; 
  color: #1e293b; 
  display: flex; 
  align-items: center; 
  gap: 0.6rem; 
}

.stats-flag { 
  width: 28px; 
  height: 28px; 
  border-radius: 5px; 
  object-fit: cover; 
  border: 1px solid #e2e8f0; 
}

.close-btn { 
  background: none; 
  border: none; 
  width: 34px; 
  height: 34px; 
  border-radius: 6px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: #64748b; 
  cursor: pointer; 
  transition: all 0.2s; 
}

.close-btn:hover { 
  background: #fee2e2; 
  color: #ef4444; 
}

.modal-body { 
  padding: 1.5rem; 
  overflow-y: auto; 
  max-height: calc(90vh - 130px); 
}

.form-section { 
  margin-bottom: 1.75rem; 
}

.form-section h3 { 
  margin: 0 0 1rem; 
  padding-bottom: 0.4rem; 
  border-bottom: 2px solid #f1f5f9; 
  font-size: 1rem; 
  font-weight: 700; 
  color: #334155; 
}

.form-grid { 
  display: grid; 
  grid-template-columns: repeat(2, 1fr); 
  gap: 1rem; 
}

.form-group { 
  display: flex; 
  flex-direction: column; 
}

.form-group.full-width { 
  grid-column: 1 / -1; 
}

.form-group label { 
  margin-bottom: 0.4rem; 
  font-weight: 600; 
  font-size: 0.8rem; 
  color: #475569; 
}

.form-group input, 
.form-group select { 
  padding: 0.55rem 0.875rem; 
  border: 2px solid #e2e8f0; 
  border-radius: 6px; 
  background: white; 
  font-size: 0.875rem; 
  transition: all 0.2s; 
  color: #1e293b; 
}

.form-group input:focus, 
.form-group select:focus { 
  outline: none; 
  border-color: #3b82f6; 
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
}

.form-group input::placeholder { 
  color: #94a3b8; 
}

.form-group input.error, 
.form-group select.error { 
  border-color: #ef4444; 
}

.error-text { 
  color: #ef4444; 
  font-size: 0.75rem; 
  margin-top: 0.2rem; 
  font-weight: 500; 
}

.help-text { 
  margin-top: 0.4rem; 
  font-size: 0.75rem; 
  color: #64748b; 
}

.help-text a { 
  color: #3b82f6; 
  text-decoration: none; 
  font-weight: 600; 
}

.help-text a:hover { 
  text-decoration: underline; 
}

.flag-input-wrapper { 
  display: flex; 
  gap: 0.6rem; 
  align-items: flex-start; 
}

.flag-input { 
  flex: 1; 
}

.flag-preview { 
  width: 50px; 
  height: 34px; 
  border: 2px solid #e2e8f0; 
  border-radius: 5px; 
  overflow: hidden; 
  background: #f8fafc; 
  flex-shrink: 0; 
}

.preview-flag { 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
}

.checkbox-group { 
  margin-top: 0.875rem; 
}

.checkbox-label { 
  display: inline-flex; 
  align-items: center; 
  gap: 0.5rem; 
  cursor: pointer; 
  font-weight: 500; 
  color: #475569; 
  font-size: 0.875rem;
}

.checkbox-label input[type="checkbox"] { 
  width: 16px; 
  height: 16px; 
  cursor: pointer; 
  accent-color: #3b82f6; 
}

.modal-footer { 
  display: flex; 
  justify-content: flex-end; 
  gap: 0.75rem; 
  padding: 1.125rem 1.5rem; 
  border-top: 1px solid #e2e8f0; 
  background: #f8fafc; 
}

.btn-secondary { 
  padding: 0.55rem 1.25rem; 
  background: white; 
  border: 1px solid #e2e8f0; 
  border-radius: 6px; 
  font-weight: 600; 
  color: #475569; 
  cursor: pointer; 
  transition: all 0.2s; 
  font-size: 0.875rem;
}

.btn-secondary:hover { 
  background: #f1f5f9; 
  border-color: #cbd5e1; 
}

/* ── Stats Modal ──────────────────────────────────────── */
.stats-modal .modal-content { 
  max-width: 900px; 
}

.stats-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
  gap: 1rem; 
}

.stat-card { 
  background: white; 
  border-radius: 10px; 
  padding: 1rem; 
  display: flex; 
  align-items: center; 
  gap: 0.875rem; 
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); 
  border: 1px solid #e2e8f0; 
  position: relative; 
  overflow: hidden; 
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
  width: 42px; 
  height: 42px; 
  border-radius: 10px; 
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
  font-size: 0.7rem; 
  color: #64748b; 
  font-weight: 600; 
  text-transform: uppercase; 
  letter-spacing: 0.04em; 
}

.stat-number { 
  font-size: 1.5rem; 
  font-weight: 800; 
  color: #0f172a; 
  line-height: 1.1; 
  margin-top: 0.1rem; 
}

.loading-state { 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  gap: 0.875rem; 
  padding: 2rem; 
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) {
  .fixed-header { padding: 1rem 1.5rem 0; }
  .page-content { padding: 0 1.5rem 2rem; }
  .countries-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
}

@media (max-width: 768px) {
  .fixed-header { padding: 0.75rem 1rem 0; }
  .page-content { padding: 0 1rem 2rem; }
  
  .header-inner { 
    flex-direction: column; 
    align-items: flex-start; 
  }
  
  .user-greeting { 
    flex-direction: column; 
    align-items: flex-start; 
    gap: 0.875rem; 
  }
  
  .header-controls { 
    width: 100%; 
    flex-wrap: wrap; 
  }
  
  .search-wrapper { 
    width: 100%; 
  }
  
  .header-search { 
    width: 100%; 
    min-width: 0; 
  }
  
  .header-select { 
    min-width: 120px; 
  }
  
  .countries-grid { 
    grid-template-columns: 1fr; 
  }
  
  .form-grid { 
    grid-template-columns: 1fr; 
  }
}

@media (max-width: 480px) {
  .header-controls { 
    flex-direction: column; 
  }
  
  .select-wrapper, 
  .header-select, 
  .btn-primary { 
    width: 100%; 
  }
  
  .btn-primary { 
    justify-content: center; 
  }
  
  .content-header {
    flex-direction: column;
    align-items: flex-end;
  }
  
  .card-actions { 
    flex-wrap: wrap; 
  }
  
  .btn-action { 
    min-width: calc(50% - 0.2rem); 
  }
  
  .stats-grid { 
    grid-template-columns: 1fr; 
  }
  
  .flag-input-wrapper { 
    flex-direction: column; 
  }
  
  .flag-preview { 
    width: 100%; 
    height: 50px; 
  }
}
</style>