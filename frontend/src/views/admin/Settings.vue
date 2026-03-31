<template>
  <div class="settings-page">

    <!-- ── Full-page Loading ──────────────────────── -->
    <div v-if="loading && !selectedBusinessId" class="full-loading">
      <div class="spinner"></div>
      <p>Loading configuration…</p>
    </div>

    <template v-else>

      <!-- ── Header Card ───────────────────────────── -->
      <div class="dashboard-header-card">
        <div class="header-card-accent"></div>
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                  a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                  A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                  l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                  A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                  l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                  a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                  l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                  a1.65 1.65 0 0 0-1.51 1z"/>
              </svg>
            </div>
            <div class="user-info">
              <h1 class="greeting">Business Settings</h1>
              <p class="subtitle">Manage configuration for your assigned business locations</p>
              <div class="role-meta">
                <span v-if="isSuperAdmin" class="role-badge super">👑 Super Admin</span>
                <span v-else class="role-badge">Assigned Admin</span>
                <span v-if="accessibleBusinesses.length > 0" class="count-chip">
                  {{ accessibleBusinesses.length }} business{{ accessibleBusinesses.length !== 1 ? 'es' : '' }}
                </span>
              </div>
            </div>
          </div>

          <div class="header-actions">
            <!-- Business Selector -->
            <div class="filter-group">
              <label>Active Business</label>
              <div class="business-select-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" class="select-icon">
                  <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                  <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <select
                  id="business-select"
                  v-model="selectedBusinessId"
                  @change="loadBusinessSettings"
                  :disabled="accessibleBusinesses.length === 0"
                  class="business-select"
                >
                  <option :value="null">
                    {{ accessibleBusinesses.length === 0 ? 'No businesses assigned' : '— Select a business —' }}
                  </option>
                  <option v-for="b in accessibleBusinesses" :key="b.id" :value="b.id">
                    {{ b.name }}{{ b.country ? ' (' + b.country.name + ')' : '' }}{{ b.is_primary ? ' ★' : '' }}
                  </option>
                </select>
              </div>
              <small v-if="accessibleBusinesses.length === 0" class="hint-error">
                You are not assigned to any businesses. Contact a super administrator.
              </small>
            </div>

            <button
              v-if="isSuperAdmin"
              class="btn-primary"
              @click="$router.push({ name: 'create-business' })"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Create Business
            </button>
          </div>
        </div>
      </div>

      <div class="dashboard-content">

        <!-- ── Toast Banners ──────────────────────── -->
        <transition name="toast">
          <div v-if="successMessage" class="toast-banner success">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ successMessage }}</span>
            <button class="toast-close" @click="successMessage = null">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </transition>

        <transition name="toast">
          <div v-if="error" class="toast-banner error">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ error }}</span>
            <button
              v-if="isSettingsMissing && selectedBusinessId"
              class="toast-action"
              @click="initializeSelectedBusiness"
            >Initialize Now</button>
            <button class="toast-close" @click="error = null">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </transition>

        <!-- ── Empty: No Access ──────────────────── -->
        <div v-if="accessibleBusinesses.length === 0" class="table-section">
          <div class="empty-state">
            <div class="empty-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <h3>No Business Access</h3>
            <p>You are not assigned to manage any businesses.</p>
            <div class="empty-actions">
              <button class="btn-secondary" @click="loadBusinesses">Refresh List</button>
              <button v-if="isSuperAdmin" class="btn-primary"
                @click="$router.push({ name: 'business-management' })">
                Manage Assignments
              </button>
            </div>
          </div>
        </div>

        <!-- ── Empty: Nothing Selected ──────────── -->
        <div v-else-if="!selectedBusinessId" class="table-section">
          <div class="empty-state">
            <div class="empty-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
            </div>
            <h3>Select a Business</h3>
            <p>Choose a business from the dropdown above to manage its settings.</p>
            <p class="empty-hint">
              You have access to {{ accessibleBusinesses.length }}
              business{{ accessibleBusinesses.length !== 1 ? 'es' : '' }}
            </p>
          </div>
        </div>

        <!-- ── Settings Form ─────────────────────── -->
        <template v-else-if="selectedBusinessId && (!error || isSettingsMissing)">

          <!-- Business Info Banner -->
          <div v-if="currentBusiness" class="business-banner">
            <div class="banner-flag">{{ currentBusiness.flag_emoji || '🏢' }}</div>
            <div class="banner-details">
              <div class="banner-name-row">
                <h2>{{ currentBusiness.name }}</h2>
                <span v-if="currentBusiness.is_primary" class="chip amber">★ Primary</span>
                <span v-if="!isSuperAdmin" class="chip blue">Assigned Admin</span>
                <span v-else class="chip gold">👑 Super Admin</span>
              </div>
              <div class="banner-tags">
                <span class="btag">🌍 {{ currentBusiness.country?.name || currentBusiness.country_name || 'N/A' }}</span>
                <span v-if="currentBusiness.currency_code" class="btag">💱 {{ currentBusiness.currency_code }}</span>
                <span v-if="countryInfo" class="btag">🕐 {{ countryInfo.timezone }}</span>
              </div>
            </div>
          </div>

          <!-- Settings Cards -->
          <div class="cards-grid">

            <!-- ── Business Information ──────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(99,102,241,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#6366f1" stroke-width="2">
                      <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                      <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                  </div>
                  <h3>Business Information</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Business Name <span class="required">*</span></label>
                    <input v-model="settings.company_name" type="text" class="form-control"
                      placeholder="e.g. Lusaka HQ" @input="onSettingChange" />
                  </div>
                  <div class="form-group">
                    <label>Tax / Registration ID</label>
                    <input v-model="settings.tax_id" type="text" class="form-control"
                      placeholder="e.g. TPIN-123456" @input="onSettingChange" />
                  </div>
                  <div class="form-group">
                    <label>Currency <span class="required">*</span></label>
                    <select v-model="settings.currency" class="form-control" @change="onSettingChange">
                      <option disabled value="">Select Currency</option>
                      <optgroup label="West African Currencies">
                        <option value="NGN">NGN — Nigerian Naira</option>
                        <option value="GHS">GHS — Ghanaian Cedi</option>
                        <option value="XOF">XOF — West African CFA Franc (Benin, Burkina Faso, Côte d'Ivoire, Guinea-Bissau, Mali, Niger, Senegal, Togo)</option>
                        <option value="GMD">GMD — Gambian Dalasi</option>
                        <option value="LRD">LRD — Liberian Dollar</option>
                        <option value="SLL">SLL — Sierra Leonean Leone</option>
                        <option value="MRU">MRU — Mauritanian Ouguiya</option>
                        <option value="CVE">CVE — Cape Verdean Escudo</option>
                      </optgroup>
                      <optgroup label="East African Currencies">
                        <option value="KES">KES — Kenyan Shilling</option>
                        <option value="UGX">UGX — Ugandan Shilling</option>
                        <option value="TZS">TZS — Tanzanian Shilling</option>
                        <option value="RWF">RWF — Rwandan Franc</option>
                        <option value="BIF">BIF — Burundian Franc</option>
                        <option value="ETB">ETB — Ethiopian Birr</option>
                        <option value="SOS">SOS — Somali Shilling</option>
                        <option value="DJF">DJF — Djiboutian Franc</option>
                        <option value="ERN">ERN — Eritrean Nakfa</option>
                        <option value="SCR">SCR — Seychellois Rupee</option>
                        <option value="COM">COM — Comorian Franc</option>
                        <option value="MGA">MGA — Malagasy Ariary</option>
                        <option value="MUR">MUR — Mauritian Rupee</option>
                      </optgroup>
                      <optgroup label="Southern African Currencies">
                        <option value="ZAR">ZAR — South African Rand</option>
                        <option value="ZMW">ZMW — Zambian Kwacha</option>
                        <option value="BWP">BWP — Botswana Pula</option>
                        <option value="NAD">NAD — Namibian Dollar</option>
                        <option value="SZL">SZL — Swazi Lilangeni</option>
                        <option value="LSL">LSL — Lesotho Loti</option>
                        <option value="MWK">MWK — Malawian Kwacha</option>
                        <option value="MZN">MZN — Mozambican Metical</option>
                        <option value="AOA">AOA — Angolan Kwanza</option>
                        <option value="ZWL">ZWL — Zimbabwean Gold</option>
                      </optgroup>
                      <optgroup label="Central African Currencies">
                        <option value="XAF">XAF — Central African CFA Franc (Cameroon, Central African Republic, Chad, Republic of Congo, Equatorial Guinea, Gabon)</option>
                        <option value="CDF">CDF — Congolese Franc</option>
                        <option value="STN">STN — São Tomé and Príncipe Dobra</option>
                      </optgroup>
                      <optgroup label="North African Currencies">
                        <option value="EGP">EGP — Egyptian Pound</option>
                        <option value="MAD">MAD — Moroccan Dirham</option>
                        <option value="DZD">DZD — Algerian Dinar</option>
                        <option value="TND">TND — Tunisian Dinar</option>
                        <option value="LYD">LYD — Libyan Dinar</option>
                        <option value="SDG">SDG — Sudanese Pound</option>
                        <option value="MRU">MRU — Mauritanian Ouguiya</option>
                      </optgroup>
                    </select>
                    <small class="field-hint">All African currencies are supported for transactions and reporting</small>
                  </div>
                  <div class="form-group">
                    <label>Date Format</label>
                    <select v-model="settings.date_format" class="form-control" @change="onSettingChange">
                      <option value="d/m/Y">DD/MM/YYYY</option>
                      <option value="m/d/Y">MM/DD/YYYY</option>
                      <option value="Y-m-d">YYYY-MM-DD</option>
                      <option value="d M Y">DD MMM YYYY</option>
                    </select>
                  </div>
                  <div class="form-group full-width">
                    <label>Physical Address <span class="required">*</span></label>
                    <textarea v-model="settings.company_address" rows="2" class="form-control"
                      placeholder="Street, City, Province" @input="onSettingChange"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Departments ───────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(59,130,246,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#3b82f6" stroke-width="2">
                      <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                      <circle cx="9" cy="7" r="4"/>
                      <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                  </div>
                  <h3>Departments</h3>
                </div>
                <button class="btn-text-action" @click="addDepartment">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                  Add New
                </button>
              </div>
              <div class="card-body">
                <p class="helper-text">Define the operational departments for this branch.</p>
                <div class="dept-list">
                  <div v-for="(dept, index) in settings.departments" :key="index" class="dept-row">
                    <div class="dept-num">{{ index + 1 }}</div>
                    <input v-model="dept.name" type="text" class="form-control"
                      placeholder="Department Name" @input="onSettingChange" />
                    <button
                      class="dept-delete"
                      @click="removeDepartment(index)"
                      :disabled="settings.departments.length <= 1"
                      title="Remove"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Leave Policies ────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(16,185,129,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#10b981" stroke-width="2">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                      <line x1="16" y1="2" x2="16" y2="6"/>
                      <line x1="8" y1="2" x2="8" y2="6"/>
                      <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                  </div>
                  <h3>Leave Policies</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Annual Leave</label>
                    <div class="input-suffix-wrap">
                      <input v-model.number="settings.annual_leave_days" type="number" min="0"
                        class="form-control" @input="onSettingChange" />
                      <span class="input-suffix-label">days/yr</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Sick Leave</label>
                    <div class="input-suffix-wrap">
                      <input v-model.number="settings.sick_leave_days" type="number" min="0"
                        class="form-control" @input="onSettingChange" />
                      <span class="input-suffix-label">days/yr</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Maternity Leave</label>
                    <div class="input-suffix-wrap">
                      <input v-model.number="settings.maternity_leave_days" type="number" min="0"
                        class="form-control" @input="onSettingChange" />
                      <span class="input-suffix-label">days</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Paternity Leave</label>
                    <div class="input-suffix-wrap">
                      <input v-model.number="settings.paternity_leave_days" type="number" min="0"
                        class="form-control" @input="onSettingChange" />
                      <span class="input-suffix-label">days</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Security ──────────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(239,68,68,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#ef4444" stroke-width="2">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                  </div>
                  <h3>Security</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group full-width">
                    <label>Default Password</label>
                    <input v-model="settings.default_password" type="text"
                      class="form-control" @input="onSettingChange" />
                    <small class="field-hint">Used when creating new employee accounts</small>
                  </div>
                  <div class="form-group">
                    <label>Max Login Attempts</label>
                    <input v-model.number="settings.max_login_attempts" type="number"
                      min="1" max="10" class="form-control" @input="onSettingChange" />
                  </div>
                  <div class="form-group">
                    <label>Session Timeout</label>
                    <div class="input-suffix-wrap">
                      <input v-model.number="settings.session_timeout" type="number"
                        min="1" class="form-control" @input="onSettingChange" />
                      <span class="input-suffix-label">min</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /cards-grid -->

          <!-- ── Sticky Action Bar ──────────────── -->
          <div class="action-bar">
            <div class="action-bar-inner">
              <transition name="toast">
                <span v-if="hasChanges" class="unsaved-indicator">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  You have unsaved changes
                </span>
              </transition>
              <div class="action-btns">
                <button
                  class="btn-secondary"
                  @click="loadBusinessSettings"
                  :disabled="(!hasChanges && !isSettingsMissing) || submitting"
                >Reset</button>

                <button
                  v-if="!isSettingsMissing"
                  class="btn-primary"
                  @click="saveSettings"
                  :disabled="!hasChanges || submitting"
                >
                  <svg v-if="submitting" class="spin-icon" xmlns="http://www.w3.org/2000/svg"
                    width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                  </svg>
                  {{ submitting ? 'Saving…' : 'Save Settings' }}
                </button>

                <button
                  v-else
                  class="btn-primary"
                  @click="initializeSelectedBusiness"
                  :disabled="submitting"
                >
                  <svg v-if="submitting" class="spin-icon" xmlns="http://www.w3.org/2000/svg"
                    width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                  </svg>
                  {{ submitting ? 'Initializing…' : 'Initialize Settings' }}
                </button>
              </div>
            </div>
          </div>

        </template>
      </div><!-- /dashboard-content -->
    </template>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'AdminSettings',

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      selectedBusinessId: null,
      businesses: [],
      accessibleBusinesses: [],
      currentBusiness: null,
      countryInfo: null,
      settings: {
        company_name: '', company_address: '', tax_id: '', currency: 'ZMW',
        annual_leave_days: 21, sick_leave_days: 7, maternity_leave_days: 90,
        paternity_leave_days: 14, default_password: 'Password123!',
        max_login_attempts: 5, session_timeout: 60, date_format: 'd/m/Y',
        departments: []
      },
      originalSettings: null,
      loading: false, error: null, isSettingsMissing: false,
      submitting: false, successMessage: null, userRole: null
    }
  },

  computed: {
    hasChanges() {
      if (!this.originalSettings) return false
      return JSON.stringify(this.settings) !== JSON.stringify(this.originalSettings)
    },
    isSuperAdmin() { return this.authStore.user?.role === 'super_admin' },
    isAdmin() { return this.authStore.user?.role === 'admin' || this.isSuperAdmin }
  },

  mounted() { this.initializeComponent() },

  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated) { this.error = 'Please log in to access settings.'; return }
      if (!this.isAdmin) { this.error = 'Access denied. Administrator privileges required.'; return }
      this.userRole = this.authStore.user?.role
      await this.loadBusinesses()
      if (this.authStore.user?.current_business_id && this.hasBusinessAccess(this.authStore.user.current_business_id)) {
        this.selectedBusinessId = this.authStore.user.current_business_id
        await this.loadBusinessSettings()
      }
    },

    async loadBusinesses() {
      this.loading = true
      try {
        const res = await axios.get('/api/admin/accessible-businesses')
        this.businesses = res.data.businesses || []
        this.accessibleBusinesses = this.businesses
        if (this.accessibleBusinesses.length === 1 && !this.selectedBusinessId) {
          this.selectedBusinessId = this.accessibleBusinesses[0].id
          await this.loadBusinessSettings()
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load businesses list.'
        this.accessibleBusinesses = []
      } finally { this.loading = false }
    },

    hasBusinessAccess(id) { return this.accessibleBusinesses.some(b => b.id == id) },

    async loadBusinessSettings() {
      if (!this.selectedBusinessId || !this.hasBusinessAccess(this.selectedBusinessId)) {
        this.error = 'You do not have access to this business.'; return
      }
      this.error = null; this.successMessage = null; this.isSettingsMissing = false; this.countryInfo = null
      this.loading = true
      this.currentBusiness = this.accessibleBusinesses.find(b => b.id == this.selectedBusinessId)
      try {
        const countryCode = this.currentBusiness?.country?.code || this.currentBusiness?.country_code
        const res = await axios.get('/api/admin/settings', {
          params: { business_id: this.selectedBusinessId, country_code: countryCode }
        })
        if (res.data?.settings) {
          this.settings = { ...this.settings, ...res.data.settings, business_id: this.selectedBusinessId }
          if (typeof this.settings.departments === 'string') {
            try { this.settings.departments = JSON.parse(this.settings.departments) }
            catch { this.settings.departments = [] }
          } else if (!Array.isArray(this.settings.departments)) { this.settings.departments = [] }
          if (this.settings.departments.length === 0) this.settings.departments = [{ name: 'General' }]
          this.countryInfo = res.data.country_info
          this.originalSettings = JSON.parse(JSON.stringify(this.settings))
        }
      } catch (err) {
        if (err.response?.status === 404 || err.response?.status === 422) {
          this.error = `Settings not yet configured for ${this.currentBusiness?.name}.`
          this.isSettingsMissing = true; this.prepareDefaultSettings()
        } else if (err.response?.status === 403) {
          this.error = 'You do not have permission to access settings for this business.'
          this.selectedBusinessId = null
        } else { this.handleApiError(err) }
      } finally { this.loading = false }
    },

    prepareDefaultSettings() {
      const b = this.accessibleBusinesses.find(b => b.id == this.selectedBusinessId)
      this.settings = {
        business_id: this.selectedBusinessId,
        company_name: b?.name || '', company_address: b?.address_line_1 || '',
        tax_id: b?.tax_identification_number || '', currency: b?.currency_code || 'ZMW',
        annual_leave_days: 21, sick_leave_days: 7, maternity_leave_days: 90, paternity_leave_days: 14,
        default_password: 'Password123!', max_login_attempts: 5, session_timeout: 60, date_format: 'd/m/Y',
        departments: [{ name: 'Human Resources' }, { name: 'Finance' }, { name: 'Operations' }, { name: 'IT' }]
      }
    },

    async initializeSelectedBusiness() {
      if (!this.hasBusinessAccess(this.selectedBusinessId)) { this.error = 'Access denied.'; return }
      this.submitting = true; this.error = null
      try {
        await axios.post('/api/admin/business-settings/initialize', this.settings)
        this.successMessage = `Settings initialized successfully for ${this.currentBusiness?.name}!`
        this.isSettingsMissing = false
        setTimeout(() => this.successMessage = null, 5000)
        await this.loadBusinessSettings()
      } catch (err) { this.handleApiError(err) }
      finally { this.submitting = false }
    },

    async saveSettings() {
      if (!this.hasBusinessAccess(this.selectedBusinessId)) { this.error = 'Access denied.'; return }
      this.submitting = true; this.error = null; this.successMessage = null
      try {
        const res = await axios.put('/api/admin/settings', { ...this.settings, business_id: this.selectedBusinessId })
        this.successMessage = res.data.message || 'Settings updated successfully!'
        this.originalSettings = JSON.parse(JSON.stringify(this.settings))
        setTimeout(() => this.successMessage = null, 5000)
        if (this.settings.company_name !== this.currentBusiness.name) await this.loadBusinesses()
      } catch (err) { this.handleApiError(err) }
      finally { this.submitting = false }
    },

    addDepartment() { this.settings.departments.push({ name: '' }); this.onSettingChange() },

    removeDepartment(index) {
      if (this.settings.departments.length > 1) {
        this.settings.departments.splice(index, 1); this.onSettingChange()
      }
    },

    onSettingChange() { /* triggers computed re-evaluation */ },

    handleApiError(err) {
      let msg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK') msg = 'Network error: Unable to connect to server.'
      else if (err.response?.status === 401) {
        msg = 'Session expired. Please log in again.'
        this.authStore.clearAuth(); this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        msg = 'Permission denied.'; this.selectedBusinessId = null
      } else if (err.response?.status === 422) {
        const e = err.response.data.errors
        msg = e ? Object.values(e).flat().join(', ') : err.response.data.message || 'Validation failed.'
      } else { msg = err.response?.data?.message || msg }
      this.error = msg
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.settings-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 6rem;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Full Loading ────────────────────────────────── */
.full-loading {
  min-height: 60vh;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 1rem;
  color: #64748b; font-size: 0.875rem;
}
.full-loading p { margin: 0; }

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; position: relative; overflow: hidden; flex-shrink: 0;
}
.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  
}
.user-greeting {
  display: flex; justify-content: space-between; align-items: center;
  gap: 1.5rem; flex-wrap: wrap;
}
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.25rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }

.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #1d4ed8;
}
.role-badge.super { background: #fef3c7; border-color: #fde68a; color: #92400e; }
.count-chip {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #64748b;
}
.header-actions {
  display: flex; gap: 0.75rem; flex-shrink: 0;
  align-items: flex-end; flex-wrap: wrap;
}

/* Business Selector */
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label {
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.business-select-wrap { position: relative; display: flex; align-items: center; }
.select-icon { position: absolute; left: 0.65rem; color: #94a3b8; pointer-events: none; z-index: 1; }
.business-select {
  padding: 0.5rem 2rem 0.5rem 2.1rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.65rem center;
  transition: all 0.2s; font-family: inherit; min-width: 260px;
}
.business-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.business-select:disabled { opacity: 0.6; cursor: not-allowed; }
.hint-error { font-size: 0.72rem; color: #ef4444; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s; font-family: inherit; white-space: nowrap;
}
.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-text-action {
  display: inline-flex; align-items: center; gap: 0.3rem;
  background: none; border: none; color: #6366f1;
  font-size: 0.78rem; font-weight: 600; cursor: pointer;
  font-family: inherit; padding: 0.2rem 0.5rem;
  border-radius: 6px; transition: background 0.15s;
}
.btn-text-action:hover { background: #eff6ff; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Toast Banners ───────────────────────────────── */
.toast-banner {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.75rem 1rem; border-radius: 10px;
  font-size: 0.875rem; font-weight: 600;
}
.toast-banner.success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.toast-banner.error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.toast-banner span { flex: 1; }
.toast-close {
  background: none; border: none; color: inherit;
  cursor: pointer; opacity: 0.7; padding: 0; line-height: 1;
}
.toast-close:hover { opacity: 1; }
.toast-action {
  background: rgba(255,255,255,0.5); border: 1px solid currentColor;
  border-radius: 6px; padding: 0.2rem 0.65rem;
  font-size: 0.75rem; font-weight: 700; cursor: pointer;
  color: inherit; font-family: inherit; white-space: nowrap;
  transition: background 0.15s;
}
.toast-action:hover { background: rgba(255,255,255,0.8); }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── Empty States ────────────────────────────────── */
.table-section {
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
}
.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center;
  justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.empty-state p  { margin: 0; font-size: 0.875rem; color: #64748b; }
.empty-hint { font-size: 0.8rem !important; color: #94a3b8 !important; }
.empty-actions { display: flex; gap: 0.75rem; margin-top: 0.25rem; }

/* ── Business Banner ────────────────────────────── */
.business-banner {
  display: flex; align-items: center; gap: 1.25rem;
  background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
  border: 1px solid #bfdbfe; border-radius: 16px; padding: 1.25rem 1.5rem;
}
.banner-flag {
  width: 56px; height: 56px; border-radius: 14px; background: white;
  display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07); border: 1px solid #e2e8f0; flex-shrink: 0;
}
.banner-details { display: flex; flex-direction: column; gap: 0.5rem; min-width: 0; }
.banner-name-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.banner-name-row h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e40af; }
.chip { display: inline-flex; padding: 0.15rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
.chip.amber { background: #fef3c7; color: #92400e; }
.chip.blue  { background: #dbeafe; color: #1e40af; }
.chip.gold  { background: linear-gradient(135deg, #fef3c7, #fed7aa); color: #92400e; }
.banner-tags { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.btag {
  background: white; border: 1px solid #dbeafe; padding: 0.2rem 0.65rem;
  border-radius: 9999px; font-size: 0.75rem; font-weight: 500; color: #1e40af;
}

/* ── Cards Grid ──────────────────────────────────── */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
  gap: 1.25rem;
}
.settings-card {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; display: flex; flex-direction: column;
}
.card-header {
  padding: 1.125rem 1.375rem; border-bottom: 1px solid #f1f5f9;
  display: flex; justify-content: space-between; align-items: center;
}
.card-header-left { display: flex; align-items: center; gap: 0.75rem; }
.card-icon {
  width: 34px; height: 34px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #334155; }
.card-body { padding: 1.375rem; flex: 1; }

/* ── Forms ───────────────────────────────────────── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.125rem; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group label {
  display: block; font-size: 0.72rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.4rem;
}
.required { color: #ef4444; }
.form-control {
  width: 100%; padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.875rem; color: #1e293b; background: #f8fafc;
  transition: all 0.2s; font-family: inherit; box-sizing: border-box;
}
.form-control:focus {
  outline: none; border-color: #6366f1; background: white;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
textarea.form-control { resize: vertical; }
select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center; padding-right: 2rem;
}
.field-hint { font-size: 0.72rem; color: #94a3b8; margin-top: 0.3rem; display: block; }
.helper-text { font-size: 0.82rem; color: #64748b; margin: 0 0 1rem; }

/* Input with unit suffix */
.input-suffix-wrap { position: relative; display: flex; align-items: center; }
.input-suffix-wrap .form-control { padding-right: 4.5rem; }
.input-suffix-label {
  position: absolute; right: 0.75rem; color: #94a3b8;
  font-size: 0.72rem; font-weight: 600; pointer-events: none; white-space: nowrap;
}

/* Departments */
.dept-list  { display: flex; flex-direction: column; gap: 0.625rem; }
.dept-row   { display: flex; align-items: center; gap: 0.5rem; }
.dept-num {
  width: 24px; height: 24px; border-radius: 6px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.68rem; font-weight: 700; color: #94a3b8; flex-shrink: 0;
}
.dept-delete {
  width: 34px; height: 34px; border-radius: 7px; border: 1px solid #e2e8f0;
  background: white; color: #94a3b8;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; flex-shrink: 0;
}
.dept-delete:hover:not(:disabled) { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.dept-delete:disabled { opacity: 0.35; cursor: not-allowed; }

/* ── Sticky Action Bar ───────────────────────────── */
.action-bar {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 50;
  background: white; border-top: 1px solid #e2e8f0;
  box-shadow: 0 -4px 16px -4px rgba(0,0,0,0.06);
  padding: 0.875rem 2rem;
}
.action-bar-inner {
  max-width: 1200px; margin: 0 auto;
  display: flex; justify-content: space-between; align-items: center; gap: 1rem;
}
.unsaved-indicator {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-size: 0.82rem; font-weight: 600; color: #d97706;
}
.action-btns { display: flex; gap: 0.625rem; }

/* ── Spinner ─────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite;
}
.spin-icon { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .settings-page        { padding: 1rem 1rem 5rem; }
  .user-greeting        { flex-direction: column; align-items: flex-start; }
  .cards-grid           { grid-template-columns: 1fr; }
  .form-grid            { grid-template-columns: 1fr; }
  .form-group.full-width{ grid-column: 1; }
  .action-bar           { padding: 0.75rem 1rem; }
  .action-bar-inner     { flex-direction: row; }
  .action-btns          { flex: 1; justify-content: flex-end; }
  .business-select      { min-width: 200px; }
  .banner-flag          { display: none; }
}
</style>