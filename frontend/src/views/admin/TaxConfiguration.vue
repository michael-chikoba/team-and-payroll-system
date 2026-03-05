<template>
  <div class="tax-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Tax Configuration</h1>
            <p class="subtitle">Manage PAYE tax rates, thresholds, and statutory deductions</p>
            <div class="role-meta" v-if="selectedCountry">
              <span class="role-badge" :class="selectedCountryConfig?.has_config ? 'success-badge' : 'warn-badge'">
                {{ selectedCountryConfig?.has_config ? '● Active Config' : '○ Not Configured' }}
              </span>
              <span class="count-chip" v-if="selectedCountryConfig?.currency">{{ selectedCountryConfig.currency }}</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button class="btn-outline" @click="showHelpModal = true">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Documentation
          </button>
          <button class="btn-primary" @click="showAddCountryModal = true" v-if="!selectedCountry">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Country
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Scope Selector Card ─────────────────────── -->
      <div class="table-section scope-section">
        <div class="scope-header">
          <div class="scope-title-row">
            <div class="scope-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                fill="none" stroke="#6366f1" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
              </svg>
            </div>
            <div>
              <h2 style="margin:0;font-size:1rem;font-weight:700;color:#334155;">Configuration Scope</h2>
              <p style="margin:0;font-size:0.8rem;color:#64748b;">Select the jurisdiction to configure</p>
            </div>
          </div>
        </div>

        <div class="scope-controls">
          <!-- Jurisdiction Selector -->
          <div class="filter-group flex-1">
            <label>Jurisdiction</label>
            <div class="select-with-btn">
              <div class="business-select-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" class="select-icon">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="2" y1="12" x2="22" y2="12"/>
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/>
                </svg>
                <select
                  v-model="selectedCountry"
                  @change="loadCountryConfig"
                  :disabled="loading"
                  class="business-select"
                >
                  <option value="">Select Scope…</option>
                  <option value="global">🌍 Global (Fallback)</option>
                  <option v-for="country in availableCountries" :key="country.code" :value="country.code">
                    {{ country.flag_emoji }} {{ country.name }}
                  </option>
                </select>
              </div>
              <button
                v-if="selectedCountry && selectedCountry !== 'global'"
                class="btn-icon-sq"
                @click="reloadCountryData"
                title="Refresh Data"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" :class="{ 'spin-icon': loading }">
                  <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Business-only toggle -->
          <div class="toggle-item" v-if="currentBusinessId && selectedCountry && selectedCountry !== 'global'">
            <label class="toggle-switch">
              <input type="checkbox" v-model="applyToBusiness" :disabled="loading">
              <span class="slider"></span>
            </label>
            <div class="toggle-text">
              <span>Apply to My Business Only</span>
              <small>Override for this entity only</small>
            </div>
          </div>
        </div>

        <!-- Scope Banner -->
        <div class="scope-banner" v-if="!loading && selectedCountry" :class="getScopeInfoClass()">
          <div class="scope-banner-icon">
            <svg v-if="selectedCountry === 'global'" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <svg v-else-if="applyToBusiness" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
          </div>
          <div class="scope-banner-body">
            <strong>{{ getScopeInfoTitle() }}</strong>
            <p>{{ getScopeInfoDescription() }}</p>
          </div>
        </div>
      </div>

      <!-- ── Loading ─────────────────────────────────── -->
      <div v-if="loading" class="table-section">
        <div class="empty-state">
          <div class="spinner"></div>
          <p>Loading data…</p>
        </div>
      </div>

      <!-- ── Empty: Nothing Selected ─────────────────── -->
      <div v-if="!selectedCountry && !loading" class="table-section">
        <div class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="2" y1="12" x2="22" y2="12"/>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
          </div>
          <h3>Start Configuration</h3>
          <p>Select a scope above to begin editing tax rules.</p>
        </div>
      </div>

      <!-- ── Empty: No Config ─────────────────────────── -->
      <div v-if="selectedCountry && selectedCountry !== 'global' && !loading && selectedCountryConfig && !selectedCountryConfig.has_config"
        class="table-section">
        <div class="empty-state">
          <div class="empty-icon-wrap warn-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="12" y1="11" x2="12" y2="15"/>
              <line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>
          </div>
          <h3>No Configuration Found</h3>
          <p>No tax rules exist for {{ getCountryDisplayName(selectedCountry) }} yet.</p>
          <div class="empty-actions">
            <button class="btn-primary" @click="createNewConfig">Create Configuration</button>
            <button class="btn-secondary" @click="loadDefaultConfig">Use Template</button>
          </div>
        </div>
      </div>

      <!-- ── Main Configuration Form ──────────────────── -->
      <transition name="fade">
        <div v-if="selectedCountry && selectedCountryConfig?.has_config && !loading">

          <!-- Workspace Header -->
          <div class="workspace-bar">
            <span class="workspace-label" v-if="selectedCountry !== 'global'">
              {{ getCountryDisplayName(selectedCountry) }}
            </span>
            <span class="workspace-label" v-else>Global Fallback</span>
            <button class="btn-danger-sm" @click="deleteCountryConfig">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
              </svg>
              Delete Config
            </button>
          </div>

          <form @submit.prevent="saveTaxConfiguration">

            <!-- ── General Settings ─────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(99,102,241,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#6366f1" stroke-width="2">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                      <line x1="16" y1="2" x2="16" y2="6"/>
                      <line x1="8" y1="2" x2="8" y2="6"/>
                      <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                  </div>
                  <h3>General Settings</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid-3">
                  <div class="form-group">
                    <label>Tax Year <span class="required">*</span></label>
                    <select class="form-control" v-model="taxConfig.taxYear" required>
                      <option v-for="year in taxYears" :key="year">{{ year }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Currency</label>
                    <select class="form-control" v-model="taxConfig.currency">
                      <optgroup label="Popular">
                        <option value="ZMW">ZMW — Zambian Kwacha</option>
                        <option value="USD">USD — US Dollar</option>
                        <option value="EUR">EUR — Euro</option>
                        <option value="GBP">GBP — British Pound</option>
                        <option value="ZAR">ZAR — South African Rand</option>
                      </optgroup>
                      <optgroup label="African Currencies">
                        <option v-for="curr in africanCurrencies" :key="curr.code" :value="curr.code">
                          {{ curr.code }} — {{ curr.name }}
                        </option>
                      </optgroup>
                      <optgroup label="Other Major World">
                        <option v-for="curr in majorCurrencies" :key="curr.code" :value="curr.code">
                          {{ curr.code }} — {{ curr.name }}
                        </option>
                      </optgroup>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Effective Date <span class="required">*</span></label>
                    <input type="date" class="form-control" v-model="taxConfig.effective_date" required />
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Tax Exemptions ───────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(16,185,129,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#10b981" stroke-width="2">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                  </div>
                  <h3>Tax Exemptions</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid-2">
                  <div class="form-group">
                    <label>Monthly Tax-Free Amount</label>
                    <div class="input-affix-wrap">
                      <span class="input-prefix">{{ taxConfig.currency }}</span>
                      <input type="number" class="form-control with-prefix" v-model.number="taxConfig.taxFreeThreshold"
                        min="0" step="0.01"
                        @change="taxConfig.annualTaxFree = taxConfig.taxFreeThreshold * 12" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Annual Equivalent <span class="computed-label">(auto-calculated)</span></label>
                    <div class="input-affix-wrap">
                      <span class="input-prefix">{{ taxConfig.currency }}</span>
                      <input type="number" class="form-control with-prefix readonly-input"
                        v-model.number="taxConfig.annualTaxFree" readonly />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── PAYE Tax Bands ───────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(245,158,11,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#f59e0b" stroke-width="2">
                      <line x1="12" y1="1" x2="12" y2="23"/>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                  </div>
                  <h3>PAYE Tax Bands</h3>
                </div>
                <div class="validation-chip" v-if="hasBandValidationError">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                  Gaps detected in bands
                </div>
              </div>
              <div class="card-body">
                <div class="bands-list">
                  <div v-for="(band, index) in taxConfig.taxBands" :key="index" class="band-row">
                    <div class="band-num">{{ index + 1 }}</div>
                    <div class="band-fields">
                      <div class="form-group">
                        <label class="sub-label">Lower Limit</label>
                        <div class="input-affix-wrap">
                          <span class="input-prefix sm">{{ taxConfig.currency }}</span>
                          <input type="number" class="form-control with-prefix" v-model.number="band.lowerLimit"
                            step="0.01" @change="validateTaxBands" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="sub-label">Upper Limit</label>
                        <div class="input-affix-wrap">
                          <span class="input-prefix sm">{{ taxConfig.currency }}</span>
                          <input type="number" class="form-control with-prefix" v-model.number="band.upperLimit"
                            step="0.01" @change="validateTaxBands"
                            :placeholder="index === taxConfig.taxBands.length - 1 ? '∞' : ''" />
                        </div>
                      </div>
                      <div class="form-group rate-col">
                        <label class="sub-label">Rate</label>
                        <div class="input-affix-wrap">
                          <input type="number" class="form-control with-suffix" v-model.number="band.rate" step="0.1" />
                          <span class="input-suffix">%</span>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="band-delete" @click="removeTaxBand(index)"
                      v-if="taxConfig.taxBands.length > 1" title="Remove band">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <button type="button" class="btn-dashed" @click="addTaxBand">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                  Add Tax Band
                </button>
              </div>
            </div>

            <!-- ── Statutory Deductions ─────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(59,130,246,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#3b82f6" stroke-width="2">
                      <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                      <line x1="8" y1="21" x2="16" y2="21"/>
                      <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                  </div>
                  <h3>Statutory Deductions</h3>
                </div>
                <button
                  type="button"
                  class="btn-outline-sm"
                  @click="loadCountryPresets(selectedCountry)"
                  v-if="['ZM', 'KE', 'ZA'].includes(selectedCountry)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                  </svg>
                  Load {{ selectedCountry }} Defaults
                </button>
              </div>
              <div class="card-body">
                <div class="deductions-list">
                  <div v-for="(ded, index) in taxConfig.statutory_deductions" :key="index" class="deduction-panel">
                    <div class="deduction-panel-header">
                      <div class="deduction-name-row">
                        <div class="ded-num">{{ index + 1 }}</div>
                        <input type="text" class="ded-name-input" v-model="ded.name"
                          placeholder="Deduction Name (e.g. NHIMA)" />
                      </div>
                      <button type="button" class="band-delete" @click="removeDeduction(index)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                          fill="none" stroke="currentColor" stroke-width="2">
                          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                      </button>
                    </div>
                    <div class="form-grid-3">
                      <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" v-model="ded.type">
                          <option value="pension">Pension</option>
                          <option value="health">Health Insurance</option>
                          <option value="levy">Statutory Levy</option>
                          <option value="other">Other</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Calculation Base</label>
                        <select class="form-control" v-model="ded.base">
                          <option value="gross">Gross Salary</option>
                          <option value="basic">Basic Salary</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Ceiling Amount</label>
                        <div class="input-affix-wrap">
                          <span class="input-prefix">{{ taxConfig.currency }}</span>
                          <input type="number" class="form-control with-prefix" v-model.number="ded.ceiling"
                            placeholder="No Limit" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label>Employee Rate</label>
                        <div class="input-affix-wrap">
                          <input type="number" class="form-control with-suffix" v-model.number="ded.employee_rate" step="0.01" />
                          <span class="input-suffix">%</span>
                        </div>
                      </div>
                      <div class="form-group">
                        <label>Employer Rate</label>
                        <div class="input-affix-wrap">
                          <input type="number" class="form-control with-suffix" v-model.number="ded.employer_rate" step="0.01" />
                          <span class="input-suffix">%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn-dashed" @click="addDeduction">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                  Add Statutory Deduction
                </button>
              </div>
            </div>

            <!-- ── Advanced Settings ────────────────── -->
            <div class="settings-card">
              <div class="card-header">
                <div class="card-header-left">
                  <div class="card-icon" style="background:rgba(239,68,68,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                      fill="none" stroke="#ef4444" stroke-width="2">
                      <circle cx="12" cy="12" r="3"/>
                      <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                  </div>
                  <h3>Advanced Settings</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="toggles-grid">
                  <div class="toggle-item">
                    <label class="toggle-switch">
                      <input type="checkbox" v-model="taxConfig.includeHousingAllowance">
                      <span class="slider"></span>
                    </label>
                    <div class="toggle-text">
                      <span>Tax Housing Allowance</span>
                      <small>Include in gross taxable income</small>
                    </div>
                  </div>
                  <div class="toggle-item">
                    <label class="toggle-switch">
                      <input type="checkbox" v-model="taxConfig.includeTransportAllowance">
                      <span class="slider"></span>
                    </label>
                    <div class="toggle-text">
                      <span>Tax Transport Allowance</span>
                      <small>Include in gross taxable income</small>
                    </div>
                  </div>
                </div>
                <div class="form-grid-2" style="margin-top:1.25rem;">
                  <div class="form-group">
                    <label>Calculation Logic</label>
                    <select class="form-control" v-model="taxConfig.taxCalculationMethod">
                      <option value="cumulative">Cumulative (Progressive)</option>
                      <option value="non_cumulative">Non-Cumulative</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Rounding Strategy</label>
                    <select class="form-control" v-model="taxConfig.roundingMethod">
                      <option value="nearest">Nearest Integer</option>
                      <option value="up">Round Up</option>
                      <option value="down">Round Down</option>
                      <option value="none">Exact (No Rounding)</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Sticky Footer ────────────────────── -->
            <div class="action-bar">
              <div class="action-bar-inner">
                <button type="button" class="btn-outline" @click="resetToDefault">Reset Defaults</button>
                <div class="action-btns">
                  <button type="button" class="btn-secondary" @click="previewCalculation">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                      <line x1="8" y1="21" x2="16" y2="21"/>
                      <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    Preview
                  </button>
                  <button type="submit" class="btn-primary" :disabled="saving || hasBandValidationError">
                    <svg v-if="saving" class="spin-icon" xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                      <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                      <polyline points="17 21 17 13 7 13 7 21"/>
                      <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    {{ saving ? 'Saving…' : 'Save Changes' }}
                  </button>
                </div>
              </div>
            </div>

          </form>
        </div>
      </transition>

    </div><!-- /dashboard-content -->

    <!-- ── Add Country Modal ────────────────────────── -->
    <transition name="fade">
      <div v-if="showAddCountryModal" class="modal-overlay" @click.self="showAddCountryModal = false">
        <transition name="modal-slide">
          <div v-if="showAddCountryModal" class="modal-panel" @click.stop>
            <div class="modal-header">
              <h2>Add Country</h2>
              <button class="btn-icon-sq" @click="showAddCountryModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-search">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" class="search-icon">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input type="text" v-model="countrySearch" @input="filterCountries"
                placeholder="Search countries by name or code…" class="modal-search-input" autofocus />
            </div>
            <div class="country-list">
              <div v-for="country in filteredCountries" :key="country.code"
                class="country-row" :class="{ configured: country.has_config }"
                @click="selectCountryForAdd(country)">
                <span class="country-flag">{{ country.flag_emoji }}</span>
                <div class="country-info">
                  <span class="country-name">{{ country.name }}</span>
                  <span class="country-meta">{{ country.currency }} · {{ country.timezone }}</span>
                </div>
                <span class="status-pill" :class="country.has_config ? 'pill-green' : 'pill-gray'">
                  {{ country.has_config ? 'Configured' : 'Available' }}
                </span>
              </div>
              <div v-if="filteredCountries.length === 0" class="modal-empty">
                <p>No countries found matching "{{ countrySearch }}"</p>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>

    <!-- ── Help Modal ───────────────────────────────── -->
    <transition name="fade">
      <div v-if="showHelpModal" class="modal-overlay" @click.self="showHelpModal = false">
        <transition name="modal-slide">
          <div v-if="showHelpModal" class="modal-panel" @click.stop>
            <div class="modal-header">
              <h2>Help &amp; Documentation</h2>
              <button class="btn-icon-sq" @click="showHelpModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="help-section">
                <h4>Configuration Scope</h4>
                <p><strong>Global:</strong> Fallback settings for any country not specifically configured.</p>
                <p><strong>Country:</strong> Specific tax laws for a nation.</p>
                <p><strong>Business Override:</strong> Applies only to your specific entity.</p>
              </div>
              <div class="help-section">
                <h4>Statutory Deductions</h4>
                <p>Add rules for pensions (NAPSA/NSSF), health insurance (NHIMA/NHIF), or other levies. Define ceiling amounts if contributions satisfy a maximum limit.</p>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-primary" style="width:100%;justify-content:center;" @click="showHelpModal = false">Got it</button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'TaxConfiguration',
  data() {
    return {
      loading: false,
      saving: false,
      showHelpModal: false,
      showAddCountryModal: false,
      hasBandValidationError: false,
      taxYears: [],
      availableCountries: [],
      allCountries: [],
      filteredCountries: [],
      countrySearch: '',
      selectedCountry: null,
      selectedCountryConfig: null,
      applyToBusiness: false,
      currentBusinessId: null,
      currentConfigId: null,

      africanCurrencies: [
        { code: 'DZD', name: 'Algerian Dinar' }, { code: 'AOA', name: 'Angolan Kwanza' },
        { code: 'BWP', name: 'Botswana Pula' }, { code: 'BIF', name: 'Burundian Franc' },
        { code: 'XAF', name: 'Central African CFA Franc' }, { code: 'EGP', name: 'Egyptian Pound' },
        { code: 'ETB', name: 'Ethiopian Birr' }, { code: 'GHS', name: 'Ghanaian Cedi' },
        { code: 'KES', name: 'Kenyan Shilling' }, { code: 'MWK', name: 'Malawian Kwacha' },
        { code: 'MAD', name: 'Moroccan Dirham' }, { code: 'MZN', name: 'Mozambican Metical' },
        { code: 'NGN', name: 'Nigerian Naira' }, { code: 'RWF', name: 'Rwandan Franc' },
        { code: 'ZAR', name: 'South African Rand' }, { code: 'TZS', name: 'Tanzanian Shilling' },
        { code: 'UGX', name: 'Ugandan Shilling' }, { code: 'XOF', name: 'West African CFA Franc' },
        { code: 'ZMW', name: 'Zambian Kwacha' }, { code: 'ZWG', name: 'Zimbabwean ZiG' }
      ],
      majorCurrencies: [
        { code: 'AUD', name: 'Australian Dollar' }, { code: 'CAD', name: 'Canadian Dollar' },
        { code: 'CNY', name: 'Chinese Yuan' }, { code: 'JPY', name: 'Japanese Yen' },
        { code: 'CHF', name: 'Swiss Franc' }, { code: 'AED', name: 'UAE Dirham' }
      ],

      taxConfig: {
        taxYear: new Date().getFullYear().toString(),
        currency: 'ZMW',
        taxFreeThreshold: 0,
        annualTaxFree: 0,
        effective_date: new Date().toISOString().split('T')[0],
        taxBands: [],
        statutory_deductions: [],
        includeHousingAllowance: false,
        includeTransportAllowance: false,
        taxCalculationMethod: 'cumulative',
        roundingMethod: 'nearest'
      }
    };
  },

  watch: {
    'taxConfig.taxBands': { handler() { this.validateTaxBands(); }, deep: true },
    selectedCountry(v) { if (v) { this.loadCountryConfig(); } else { this.selectedCountryConfig = null; this.currentConfigId = null; } }
  },

  methods: {
    addDeduction() {
      this.taxConfig.statutory_deductions.push({ name: '', type: 'pension', base: 'gross', employee_rate: 0, employer_rate: 0, ceiling: null });
    },
    removeDeduction(i) { this.taxConfig.statutory_deductions.splice(i, 1); },

    loadCountryPresets(code) {
      if (!confirm(`Overwrite current deduction rules with ${code} defaults?`)) return;
      const presets = {
        ZM: [{ name: 'NAPSA', type: 'pension', base: 'gross', employee_rate: 5, employer_rate: 5, ceiling: 34164 }, { name: 'NHIMA', type: 'health', base: 'basic', employee_rate: 1, employer_rate: 1, ceiling: null }],
        KE: [{ name: 'NSSF (Tier I)', type: 'pension', base: 'gross', employee_rate: 6, employer_rate: 6, ceiling: 7000 }, { name: 'Housing Levy', type: 'levy', base: 'gross', employee_rate: 1.5, employer_rate: 1.5, ceiling: null }],
        ZA: [{ name: 'UIF', type: 'pension', base: 'gross', employee_rate: 1, employer_rate: 1, ceiling: 17712 }]
      };
      this.taxConfig.statutory_deductions = presets[code] || [];
    },

    async fetchCountries() {
      this.loading = true;
      try {
        const res = await this.getAxiosInstance().get('/api/admin/available-countries');
        if (res.data.countries) { this.availableCountries = res.data.countries; this.allCountries = res.data.countries; this.filteredCountries = res.data.countries; }
        await this.fetchTaxConfigurations();
      } catch (e) { console.error(e); } finally { this.loading = false; }
    },

    async fetchTaxConfigurations() {
      try {
        const res = await this.getAxiosInstance().get('/api/admin/tax-configurations');
        if (res.data.tax_configurations) {
          this.availableCountries = this.availableCountries.map(c => ({
            ...c, has_config: res.data.tax_configurations.some(t => t.country_code === c.code && !t.business_id)
          }));
        }
      } catch (e) { console.error(e); }
    },

    async loadCountryConfig() {
      if (!this.selectedCountry) return;
      this.loading = true; this.selectedCountryConfig = null; this.currentConfigId = null;
      try {
        const params = this.selectedCountry === 'global' ? { country_code: null } : { country_code: this.selectedCountry };
        const res = await this.getAxiosInstance().get('/api/admin/tax-configuration', { params });
        if (res.data?.tax_configuration) {
          this.taxConfig = { ...this.taxConfig, ...res.data.tax_configuration.config_data };
          if (!this.taxConfig.statutory_deductions) this.taxConfig.statutory_deductions = [];
          this.currentConfigId = res.data.tax_configuration.id;
          this.selectedCountryConfig = { has_config: true, currency: res.data.tax_configuration.config_data.currency };
        } else {
          this.selectedCountryConfig = { has_config: false }; this.resetTaxConfigToCountryDefaults();
        }
        this.applyToBusiness = false;
      } catch (e) {
        if (e.response?.status === 404) { this.selectedCountryConfig = { has_config: false }; this.resetTaxConfigToCountryDefaults(); }
      } finally { this.loading = false; }
    },

    resetTaxConfigToCountryDefaults() {
      const c = this.allCountries.find(c => c.code === this.selectedCountry);
      if (c) { this.taxConfig.currency = c.currency || 'ZMW'; this.setDefaultTaxBands(c.currency); this.taxConfig.statutory_deductions = []; }
    },

    setDefaultTaxBands(currency) {
      const defaults = {
        USD: [{ lowerLimit: 0, upperLimit: 1000, rate: 0 }, { lowerLimit: 1000.01, upperLimit: null, rate: 15 }],
        ZMW: [{ lowerLimit: 0, upperLimit: 4500, rate: 0 }, { lowerLimit: 4500.01, upperLimit: 9000, rate: 25 }, { lowerLimit: 9000.01, upperLimit: null, rate: 30 }],
        EUR: [{ lowerLimit: 0, upperLimit: 10000, rate: 0 }, { lowerLimit: 10000.01, upperLimit: null, rate: 20 }]
      };
      this.taxConfig.taxBands = defaults[currency] || defaults.USD;
    },

    reloadCountryData() { if (this.selectedCountry) { this.fetchTaxConfigurations(); this.loadCountryConfig(); } },

    getCountryDisplayName(code) {
      if (code === 'global') return 'Global Configuration';
      return this.allCountries.find(c => c.code === code)?.name || code;
    },

    getScopeInfoClass() {
      if (this.selectedCountry === 'global') return 'scope-global';
      if (this.applyToBusiness) return 'scope-business';
      return 'scope-country';
    },

    getScopeInfoTitle() {
      if (this.selectedCountry === 'global') return 'Global Fallback';
      if (this.applyToBusiness) return 'Business Override';
      return 'National Default';
    },

    getScopeInfoDescription() {
      if (this.selectedCountry === 'global') return 'Settings apply to all countries without specific configs.';
      if (this.applyToBusiness) return 'These settings apply ONLY to your business entity.';
      return `Standard settings for all businesses in ${this.getCountryDisplayName(this.selectedCountry)}.`;
    },

    filterCountries() {
      const t = this.countrySearch.toLowerCase();
      this.filteredCountries = this.allCountries.filter(c => c.name.toLowerCase().includes(t) || c.code.toLowerCase().includes(t));
    },

    selectCountryForAdd(c) { this.selectedCountry = c.code; this.showAddCountryModal = false; },

    createNewConfig() {
      const c = this.allCountries.find(c => c.code === this.selectedCountry);
      if (c) { this.taxConfig.currency = c.currency || 'ZMW'; this.setDefaultTaxBands(c.currency); this.selectedCountryConfig = { has_config: true, created_now: true }; }
    },

    async deleteCountryConfig() {
      if (!this.currentConfigId || !confirm('Are you sure? This cannot be undone.')) return;
      this.loading = true;
      try {
        await this.getAxiosInstance().delete(`/api/admin/tax-configuration/${this.currentConfigId}`);
        this.selectedCountryConfig = { has_config: false }; this.currentConfigId = null;
        this.resetTaxConfigToCountryDefaults(); await this.fetchTaxConfigurations();
      } catch (e) { console.error(e); } finally { this.loading = false; }
    },

    loadDefaultConfig() { this.resetTaxConfigToCountryDefaults(); this.selectedCountryConfig = { has_config: true, created_now: true }; },

    validateTaxBands() {
      this.hasBandValidationError = false;
      if (!this.taxConfig.taxBands) return;
      for (let i = 1; i < this.taxConfig.taxBands.length; i++) {
        const prev = this.taxConfig.taxBands[i - 1], curr = this.taxConfig.taxBands[i];
        if (prev.upperLimit && Math.abs(curr.lowerLimit - prev.upperLimit) > 0.02) { this.hasBandValidationError = true; break; }
      }
    },

    addTaxBand() {
      const last = this.taxConfig.taxBands[this.taxConfig.taxBands.length - 1];
      this.taxConfig.taxBands.push({ lowerLimit: last?.upperLimit ? last.upperLimit + 0.01 : 0, upperLimit: null, rate: 0 });
    },

    removeTaxBand(i) { if (this.taxConfig.taxBands.length > 1) this.taxConfig.taxBands.splice(i, 1); },

    async saveTaxConfiguration() {
      if (this.hasBandValidationError || !this.selectedCountry) return;
      this.saving = true;
      try {
        if (this.taxConfig.taxFreeThreshold != null) this.taxConfig.annualTaxFree = this.taxConfig.taxFreeThreshold * 12;
        const res = await this.getAxiosInstance().post('/api/admin/update-tax-configuration', {
          taxConfig: this.taxConfig,
          apply_to_business: this.applyToBusiness && this.selectedCountry !== 'global',
          country_code: this.selectedCountry !== 'global' ? this.selectedCountry : null
        });
        this.currentConfigId = res.data.tax_configuration.id;
        this.selectedCountryConfig = { has_config: true, currency: this.taxConfig.currency };
        await this.fetchTaxConfigurations();
        alert('Configuration saved successfully');
      } catch (e) { console.error(e); alert('Failed to save configuration'); }
      finally { this.saving = false; }
    },

    resetToDefault() { if (confirm('Reset form to defaults?')) this.loadDefaultConfig(); },
    previewCalculation() { alert('Calculation preview logic would go here.'); },
    getAxiosInstance() { return this.$axios || this.$http || axios; }
  },

  async mounted() {
    const y = new Date().getFullYear();
    this.taxYears = Array.from({ length: 6 }, (_, i) => String(y + i));
    this.taxConfig.taxYear = String(y);
    try { const r = await this.getAxiosInstance().get('/api/user'); this.currentBusinessId = r.data.current_business_id; } catch {}
    await this.fetchCountries();
  }
};
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.tax-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 5rem;
  max-width: 1060px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; position: relative; overflow: hidden; flex-shrink: 0;
}
.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  
}
.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.25rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }

.role-badge {
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600;
}
.role-badge.success-badge { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.role-badge.warn-badge    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.count-chip {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 700; color: #64748b;
}
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s; font-family: inherit; white-space: nowrap;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }

.btn-outline-sm {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.3rem 0.75rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 7px; font-size: 0.75rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-outline-sm:hover { background: #eff6ff; border-color: #a5b4fc; color: #4f46e5; }

.btn-icon-sq {
  width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: white; color: #475569; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; flex-shrink: 0;
}
.btn-icon-sq:hover { background: #eff6ff; border-color: #a5b4fc; color: #4f46e5; }

.btn-dashed {
  width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  background: transparent; border: 2px dashed #e2e8f0; color: #94a3b8;
  border-radius: 10px; padding: 0.75rem; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; font-family: inherit; margin-top: 0.875rem;
}
.btn-dashed:hover { border-color: #6366f1; color: #6366f1; background: #eff6ff; }

.btn-danger-sm {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; background: white; border: 1px solid #fca5a5;
  color: #dc2626; border-radius: 7px; font-size: 0.75rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.btn-danger-sm:hover { background: #fee2e2; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Shared Section / Card ───────────────────────── */
.table-section {
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
}

/* ── Scope Section ───────────────────────────────── */
.scope-section { display: flex; flex-direction: column; gap: 1.25rem; }
.scope-header  { display: flex; align-items: center; justify-content: space-between; }
.scope-title-row { display: flex; align-items: center; gap: 0.75rem; }
.scope-icon-wrap {
  width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.1);
  display: flex; align-items: center; justify-content: center;
}
.scope-controls { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
.filter-group   { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group.flex-1 { flex: 1; min-width: 220px; }
.filter-group label {
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.04em;
}

.select-with-btn { display: flex; gap: 0.4rem; }
.business-select-wrap { position: relative; display: flex; align-items: center; flex: 1; }
.select-icon { position: absolute; left: 0.65rem; color: #94a3b8; pointer-events: none; z-index: 1; }
.business-select {
  flex: 1; padding: 0.5rem 2rem 0.5rem 2.1rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.65rem center;
  transition: all 0.2s; font-family: inherit;
}
.business-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.business-select:disabled { opacity: 0.6; cursor: not-allowed; }

/* Scope Banner */
.scope-banner {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.875rem 1rem; border-radius: 10px; font-size: 0.82rem;
}
.scope-banner strong { display: block; font-weight: 700; margin-bottom: 0.15rem; }
.scope-banner p { margin: 0; opacity: 0.85; }
.scope-banner-icon { flex-shrink: 0; margin-top: 0.1rem; }
.scope-global   { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.scope-business { background: #fdf4ff; border: 1px solid #e9d5ff; color: #7e22ce; }
.scope-country  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

/* ── Empty State ─────────────────────────────────── */
.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-icon-wrap.warn-icon { background: #fef3c7; border-color: #fde68a; color: #d97706; }
.empty-state h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
.empty-state p  { margin: 0; font-size: 0.875rem; color: #64748b; max-width: 320px; }
.empty-actions  { display: flex; gap: 0.75rem; margin-top: 0.25rem; }

/* ── Spinner ─────────────────────────────────────── */
.spinner {
  width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
.spin-icon { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Workspace Bar ───────────────────────────────── */
.workspace-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.875rem 1.25rem; background: white; border-radius: 12px;
  border: 1px solid #e2e8f0; margin-bottom: 0;
}
.workspace-label { font-size: 0.875rem; font-weight: 700; color: #334155; }

/* ── Settings Cards ──────────────────────────────── */
.settings-card {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; display: flex; flex-direction: column; margin-top: 1.25rem;
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
.card-body { padding: 1.375rem; }

/* ── Forms ───────────────────────────────────────── */
.form-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.125rem; }
.form-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.125rem; }
.form-group  { display: flex; flex-direction: column; gap: 0.35rem; }
.form-group label {
  font-size: 0.72rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.sub-label { font-size: 0.65rem !important; }
.required { color: #ef4444; }
.computed-label { font-size: 0.65rem; color: #94a3b8; font-weight: 400; text-transform: none; letter-spacing: 0; }

.form-control {
  width: 100%; padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.875rem; color: #1e293b; background: #f8fafc;
  transition: all 0.2s; font-family: inherit; box-sizing: border-box;
}
.form-control:focus { outline: none; border-color: #6366f1; background: white; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-control.readonly-input { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center; padding-right: 2rem;
}

/* Input affixes */
.input-affix-wrap { display: flex; align-items: stretch; }
.input-prefix {
  display: flex; align-items: center; padding: 0 0.6rem;
  background: #f1f5f9; border: 1px solid #e2e8f0; border-right: none;
  border-radius: 8px 0 0 8px; font-size: 0.72rem; font-weight: 700; color: #64748b;
  white-space: nowrap;
}
.input-prefix.sm { font-size: 0.65rem; }
.input-suffix {
  display: flex; align-items: center; padding: 0 0.6rem;
  background: #f1f5f9; border: 1px solid #e2e8f0; border-left: none;
  border-radius: 0 8px 8px 0; font-size: 0.72rem; font-weight: 700; color: #64748b;
}
.form-control.with-prefix { border-radius: 0 8px 8px 0; }
.form-control.with-suffix { border-radius: 8px 0 0 8px; }

/* Validation chip */
.validation-chip {
  display: inline-flex; align-items: center; gap: 0.35rem;
  background: #fee2e2; color: #991b1b; font-size: 0.72rem; font-weight: 700;
  padding: 0.25rem 0.6rem; border-radius: 9999px; border: 1px solid #fca5a5;
}

/* ── Tax Bands ───────────────────────────────────── */
.bands-list { display: flex; flex-direction: column; gap: 0.75rem; }
.band-row {
  display: flex; align-items: flex-end; gap: 0.875rem;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem 1rem;
}
.band-num {
  width: 24px; height: 24px; border-radius: 6px; background: rgba(99,102,241,0.1);
  color: #6366f1; display: flex; align-items: center; justify-content: center;
  font-size: 0.68rem; font-weight: 800; flex-shrink: 0; margin-bottom: 0.15rem;
}
.band-fields { display: flex; gap: 1rem; flex: 1; flex-wrap: wrap; }
.band-fields .form-group { flex: 1; min-width: 110px; margin: 0; }
.rate-col { max-width: 110px; }

.band-delete {
  width: 30px; height: 30px; border-radius: 7px; border: 1px solid #e2e8f0;
  background: white; color: #94a3b8; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; flex-shrink: 0; margin-bottom: 0.15rem;
}
.band-delete:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }

/* ── Deductions ──────────────────────────────────── */
.deductions-list { display: flex; flex-direction: column; gap: 1rem; }
.deduction-panel {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.125rem;
}
.deduction-panel-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;
}
.deduction-name-row { display: flex; align-items: center; gap: 0.625rem; flex: 1; min-width: 0; }
.ded-num {
  width: 22px; height: 22px; border-radius: 5px; background: rgba(59,130,246,0.1);
  color: #3b82f6; display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 800; flex-shrink: 0;
}
.ded-name-input {
  flex: 1; border: none; border-bottom: 2px solid #e2e8f0; background: transparent;
  padding: 0.2rem 0; font-size: 0.9rem; font-weight: 700; color: #1e293b;
  outline: none; transition: border-color 0.15s; font-family: inherit;
}
.ded-name-input:focus { border-color: #6366f1; }
.ded-name-input::placeholder { color: #94a3b8; font-weight: 400; }

/* ── Toggles ─────────────────────────────────────── */
.toggles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.875rem; }
.toggle-item {
  display: flex; align-items: flex-start; gap: 0.75rem;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.875rem;
}
.toggle-text { display: flex; flex-direction: column; gap: 0.1rem; }
.toggle-text span { font-size: 0.82rem; font-weight: 600; color: #334155; }
.toggle-text small { font-size: 0.72rem; color: #94a3b8; }

/* Toggle switch */
.toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; margin-top: 1px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider {
  position: absolute; cursor: pointer; inset: 0;
  background: #e2e8f0; transition: 0.3s; border-radius: 22px;
}
.slider::before {
  content: ''; position: absolute; height: 16px; width: 16px;
  left: 3px; bottom: 3px; background: white;
  transition: 0.3s; border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
input:checked + .slider { background: #6366f1; }
input:checked + .slider::before { transform: translateX(18px); }

/* ── Sticky Action Bar ───────────────────────────── */
.action-bar {
  position: sticky; bottom: 0; z-index: 40;
  background: white; border-top: 1px solid #e2e8f0;
  box-shadow: 0 -4px 16px -4px rgba(0,0,0,0.06);
  padding: 0.875rem 1.375rem; border-radius: 0 0 16px 16px;
  margin-top: 1.25rem;
}
.action-bar-inner { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.action-btns     { display: flex; gap: 0.625rem; }

/* ── Modals ──────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.45); z-index: 50;
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-panel {
  background: white; border-radius: 16px; max-width: 500px; width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
  display: flex; flex-direction: column; max-height: 80vh;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.modal-header h2 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }

.modal-search { position: relative; padding: 0.875rem 1.25rem; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.search-icon { position: absolute; left: 2rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.modal-search-input {
  width: 100%; padding: 0.55rem 0.75rem 0.55rem 2.25rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.875rem; font-family: inherit; background: #f8fafc;
  transition: all 0.2s; box-sizing: border-box;
}
.modal-search-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.country-list { overflow-y: auto; flex: 1; }
.country-row {
  display: flex; align-items: center; gap: 0.875rem;
  padding: 0.75rem 1.5rem; cursor: pointer; transition: background 0.12s;
}
.country-row:hover { background: #f8fafc; }
.country-row.configured { border-left: 3px solid #6ee7b7; }
.country-flag { font-size: 1.375rem; flex-shrink: 0; }
.country-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.country-name { font-size: 0.875rem; font-weight: 600; color: #1e293b; }
.country-meta { font-size: 0.72rem; color: #94a3b8; }
.status-pill { font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 9999px; white-space: nowrap; }
.pill-green { background: #d1fae5; color: #065f46; }
.pill-gray  { background: #f1f5f9; color: #64748b; }

.modal-empty { text-align: center; padding: 2.5rem 1.5rem; color: #94a3b8; font-size: 0.875rem; }

.modal-body { padding: 1.375rem 1.5rem; overflow-y: auto; }
.help-section { margin-bottom: 1.25rem; }
.help-section h4 { margin: 0 0 0.5rem; font-size: 0.875rem; font-weight: 700; color: #334155; }
.help-section p { margin: 0 0 0.35rem; font-size: 0.82rem; color: #64748b; line-height: 1.6; }

.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; background: #f8fafc; border-radius: 0 0 16px 16px; }

/* ── Transitions ─────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-slide-enter-active, .modal-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-slide-enter-from, .modal-slide-leave-to { opacity: 0; transform: scale(0.96) translateY(8px); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .tax-page      { padding: 1rem 1rem 4rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; }
  .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
  .band-row      { flex-direction: column; align-items: stretch; }
  .band-fields   { flex-direction: column; }
  .rate-col      { max-width: none; }
  .action-bar-inner { flex-direction: row; }
  .action-btns { flex: 1; justify-content: flex-end; }
}
</style>