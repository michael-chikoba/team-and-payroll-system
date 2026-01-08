<template>
  <div class="tax-page-container">
    <!-- Page Header -->
    <header class="page-header">
      <div class="header-content">
        <h1 class="page-title">Tax Configuration</h1>
        <p class="page-subtitle">Manage PAYE tax rates, thresholds, and statutory deductions.</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-ghost" @click="showHelpModal = true">
          <i class="fas fa-book-open"></i> Documentation
        </button>
        <button class="btn btn-primary" @click="showAddCountryModal = true" v-if="!selectedCountry">
          <i class="fas fa-plus"></i> Add Country
        </button>
      </div>
    </header>

    <!-- Scope Selection -->
    <section class="config-section scope-section">
      <div class="card scope-card">
        <div class="card-icon-header">
          <div class="icon-box blue">
            <i class="fas fa-globe-americas"></i>
          </div>
          <div class="header-text">
            <h2>Configuration Scope</h2>
            <p>Select the jurisdiction to configure.</p>
          </div>
        </div>
        
        <div class="scope-controls">
          <div class="control-group">
            <label class="input-label">Select Jurisdiction</label>
            <div class="select-wrapper">
              <select 
                class="modern-select"
                v-model="selectedCountry" 
                @change="loadCountryConfig"
                :disabled="loading"
              >
                <option value="">Select Scope...</option>
                <option value="global">🌍 Global (Fallback)</option>
                <option 
                  v-for="country in availableCountries" 
                  :key="country.code" 
                  :value="country.code"
                >
                  {{ country.flag_emoji }} {{ country.name }}
                </option>
              </select>
              <button 
                class="btn-icon" 
                @click="reloadCountryData" 
                v-if="selectedCountry && selectedCountry !== 'global'"
                title="Refresh Data"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
              </button>
            </div>
          </div>

          <!-- Quick Stats / Info -->
          <div class="scope-meta" v-if="selectedCountry && selectedCountry !== 'global'">
             <div class="meta-badge" :class="selectedCountryConfig?.has_config ? 'success' : 'warning'">
                <i :class="selectedCountryConfig?.has_config ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                <span>{{ selectedCountryConfig?.has_config ? 'Active Config' : 'Not Configured' }}</span>
             </div>
             <div class="meta-badge neutral" v-if="selectedCountryConfig?.currency">
                <span class="currency-code">{{ selectedCountryConfig.currency }}</span>
             </div>
          </div>

          <div class="business-toggle" v-if="currentBusinessId && selectedCountry && selectedCountry !== 'global'">
            <label class="toggle-switch">
              <input type="checkbox" v-model="applyToBusiness" :disabled="loading">
              <span class="slider"></span>
            </label>
            <span class="toggle-label">Apply to My Business Only</span>
          </div>
        </div>

        <!-- Scope Alert Banner -->
        <div class="scope-banner" v-if="!loading && selectedCountry" :class="getScopeInfoClass()">
          <i :class="getScopeInfoIcon()"></i>
          <div class="banner-content">
            <strong>{{ getScopeInfoTitle() }}</strong>
            <p>{{ getScopeInfoDescription() }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div class="loading-overlay" v-if="loading">
      <div class="spinner"></div>
      <p>Loading Data...</p>
    </div>

    <!-- Empty State -->
    <div class="empty-state-card" v-if="!selectedCountry && !loading">
      <div class="empty-illustration">
        <i class="fas fa-arrow-up"></i>
      </div>
      <h3>Start Configuration</h3>
      <p>Select a scope above to begin editing tax rules.</p>
    </div>

    <!-- No Config State -->
    <div class="empty-state-card" v-if="selectedCountry && selectedCountry !== 'global' && !loading && selectedCountryConfig && !selectedCountryConfig.has_config">
      <div class="empty-illustration warning">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <h3>No Configuration Found</h3>
      <p>No tax rules exist for {{ getCountryDisplayName(selectedCountry) }} yet.</p>
      <div class="empty-actions">
        <button class="btn btn-primary" @click="createNewConfig">Create Configuration</button>
        <button class="btn btn-outline" @click="loadDefaultConfig">Use Template</button>
      </div>
    </div>

    <!-- Main Configuration Form -->
    <transition name="fade">
      <div class="config-workspace" v-if="selectedCountry && selectedCountryConfig?.has_config && !loading">
        
        <!-- Header Actions -->
        <div class="workspace-header">
          <div class="workspace-title"></div>
          <div class="workspace-actions">
            <button class="btn btn-danger-ghost" @click="deleteCountryConfig">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>

        <form @submit.prevent="saveTaxConfiguration" class="modern-form">
          
          <!-- General Settings -->
          <div class="card form-card">
            <h3 class="card-title">General Settings</h3>
            <div class="grid-3">
              <div class="form-group">
                <label>Tax Year</label>
                <select class="modern-input" v-model="taxConfig.taxYear" required>
                  <option v-for="year in taxYears" :key="year">{{ year }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Currency</label>
                <div class="input-icon-wrapper">
                  <i class="fas fa-coins"></i>
                  <select class="modern-input with-icon" v-model="taxConfig.currency">
                    <!-- Favorites -->
                    <optgroup label="Popular">
                        <option value="ZMW">ZMW - Zambian Kwacha</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="ZAR">ZAR - South African Rand</option>
                    </optgroup>
                    
                    <!-- African Currencies -->
                    <optgroup label="African Currencies">
                        <option v-for="curr in africanCurrencies" :key="curr.code" :value="curr.code">
                            {{ curr.code }} - {{ curr.name }}
                        </option>
                    </optgroup>

                    <!-- World Currencies -->
                    <optgroup label="Other Major World">
                        <option v-for="curr in majorCurrencies" :key="curr.code" :value="curr.code">
                            {{ curr.code }} - {{ curr.name }}
                        </option>
                    </optgroup>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label>Effective Date</label>
                <input type="date" class="modern-input" v-model="taxConfig.effective_date" required>
              </div>
            </div>
          </div>

          <!-- Tax Free Threshold -->
          <div class="card form-card">
            <h3 class="card-title">Tax Exemptions</h3>
            <div class="grid-2">
              <div class="form-group">
                <label>Monthly Tax-Free Amount</label>
                <div class="input-group">
                  <span class="prefix">{{ taxConfig.currency }}</span>
                  <input 
                    type="number" 
                    class="modern-input" 
                    v-model.number="taxConfig.taxFreeThreshold" 
                    min="0" step="0.01"
                    @change="taxConfig.annualTaxFree = taxConfig.taxFreeThreshold * 12"
                  >
                </div>
              </div>
              <div class="form-group">
                <label>Annual Equivalent (Auto)</label>
                <div class="input-group readonly">
                  <span class="prefix">{{ taxConfig.currency }}</span>
                  <input type="number" class="modern-input" v-model.number="taxConfig.annualTaxFree" readonly>
                </div>
              </div>
            </div>
          </div>

          <!-- PAYE Bands -->
          <div class="card form-card">
            <div class="card-header-flex">
              <h3 class="card-title">PAYE Tax Bands</h3>
              <div class="validation-badge error" v-if="hasBandValidationError">
                <i class="fas fa-exclamation-triangle"></i> Gaps detected in bands
              </div>
            </div>
            
            <div class="tax-bands-container">
              <div v-for="(band, index) in taxConfig.taxBands" :key="index" class="band-row">
                <div class="band-indicator">{{ index + 1 }}</div>
                
                <div class="band-inputs">
                  <div class="form-group">
                    <label class="sub-label">Lower Limit</label>
                    <div class="input-group sm">
                      <span class="prefix">{{ taxConfig.currency }}</span>
                      <input type="number" class="modern-input" v-model.number="band.lowerLimit" step="0.01" @change="validateTaxBands">
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label class="sub-label">Upper Limit</label>
                    <div class="input-group sm">
                      <span class="prefix">{{ taxConfig.currency }}</span>
                      <input 
                        type="number" 
                        class="modern-input" 
                        v-model.number="band.upperLimit" 
                        step="0.01" 
                        @change="validateTaxBands"
                        :placeholder="index === taxConfig.taxBands.length - 1 ? 'Infinity' : ''"
                      >
                    </div>
                  </div>
                  
                  <div class="form-group rate-group">
                    <label class="sub-label">Rate</label>
                    <div class="input-group sm">
                      <input type="number" class="modern-input" v-model.number="band.rate" step="0.1">
                      <span class="suffix">%</span>
                    </div>
                  </div>
                </div>

                <button 
                  type="button" 
                  class="btn-icon danger" 
                  @click="removeTaxBand(index)" 
                  v-if="taxConfig.taxBands.length > 1"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <button type="button" class="btn btn-dashed" @click="addTaxBand">
                <i class="fas fa-plus"></i> Add Tax Band
              </button>
            </div>
          </div>

          <!-- Statutory Deductions (Dynamic) -->
          <div class="card form-card">
            <div class="card-header-flex">
              <h3 class="card-title">Statutory Deductions</h3>
              <button 
                type="button" 
                class="btn btn-ghost" 
                style="font-size: 0.85rem;"
                @click="loadCountryPresets(selectedCountry)"
                v-if="['ZM', 'KE', 'ZA'].includes(selectedCountry)"
              >
                <i class="fas fa-magic"></i> Load {{ selectedCountry }} Defaults
              </button>
            </div>

            <div class="deductions-container">
               <!-- Deduction Item -->
               <div v-for="(deduction, index) in taxConfig.statutory_deductions" :key="index" class="card-sub-panel">
                 <div class="deduction-header">
                    <input 
                      type="text" 
                      class="title-input" 
                      v-model="deduction.name" 
                      placeholder="Deduction Name (e.g. NHIMA)"
                    >
                    <button type="button" class="btn-icon danger sm" @click="removeDeduction(index)">
                       <i class="fas fa-times"></i>
                    </button>
                 </div>

                 <div class="grid-3">
                    <div class="form-group">
                       <label>Type</label>
                       <select class="modern-input" v-model="deduction.type">
                          <option value="pension">Pension</option>
                          <option value="health">Health Insurance</option>
                          <option value="levy">Statutory Levy</option>
                          <option value="other">Other</option>
                       </select>
                    </div>
                    
                    <div class="form-group">
                       <label>Calculation Base</label>
                       <select class="modern-input" v-model="deduction.base">
                          <option value="gross">Gross Salary</option>
                          <option value="basic">Basic Salary</option>
                       </select>
                    </div>

                    <div class="form-group">
                       <label>Ceiling Amount</label>
                       <div class="input-group">
                          <span class="prefix">{{ taxConfig.currency }}</span>
                          <input 
                            type="number" 
                            class="modern-input" 
                            v-model.number="deduction.ceiling" 
                            placeholder="No Limit"
                          >
                       </div>
                    </div>

                    <div class="form-group">
                       <label>Employee Rate</label>
                       <div class="input-group">
                          <input type="number" class="modern-input" v-model.number="deduction.employee_rate" step="0.01">
                          <span class="suffix">%</span>
                       </div>
                    </div>

                    <div class="form-group">
                       <label>Employer Rate</label>
                       <div class="input-group">
                          <input type="number" class="modern-input" v-model.number="deduction.employer_rate" step="0.01">
                          <span class="suffix">%</span>
                       </div>
                    </div>
                 </div>
               </div>

               <button type="button" class="btn btn-dashed" @click="addDeduction">
                  <i class="fas fa-plus"></i> Add Statutory Deduction
               </button>
            </div>
          </div>

          <!-- Settings & Calculations -->
          <div class="card form-card">
            <h3 class="card-title">Advanced Settings</h3>
            
            <div class="toggle-grid">
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

            <div class="grid-2 mt-4">
              <div class="form-group">
                <label>Calculation Logic</label>
                <select class="modern-input" v-model="taxConfig.taxCalculationMethod">
                  <option value="cumulative">Cumulative (Progressive)</option>
                  <option value="non_cumulative">Non-Cumulative</option>
                </select>
              </div>
              <div class="form-group">
                <label>Rounding Strategy</label>
                <select class="modern-input" v-model="taxConfig.roundingMethod">
                  <option value="nearest">Nearest Integer</option>
                  <option value="up">Round Up</option>
                  <option value="down">Round Down</option>
                  <option value="none">Exact (No Rounding)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="sticky-footer">
            <div class="footer-left">
              <button type="button" class="btn btn-ghost" @click="resetToDefault">Reset Defaults</button>
            </div>
            <div class="footer-right">
              <button type="button" class="btn btn-outline" @click="previewCalculation">
                <i class="fas fa-calculator"></i> Preview
              </button>
              <button 
                type="submit" 
                class="btn btn-primary" 
                :disabled="saving || hasBandValidationError"
              >
                <span v-if="saving"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                <span v-else><i class="fas fa-save"></i> Save Changes</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </transition>

    <!-- Add Country Modal -->
    <div class="modal-backdrop" v-if="showAddCountryModal" @click.self="showAddCountryModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Add Country</h3>
          <button class="close-btn" @click="showAddCountryModal = false">✕</button>
        </div>
        
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input 
            type="text" 
            v-model="countrySearch"
            placeholder="Search countries by name or code..."
            @input="filterCountries"
            autofocus
          />
        </div>
        
        <div class="country-list">
          <div 
            v-for="country in filteredCountries" 
            :key="country.code"
            class="country-list-item"
            :class="{ 'is-configured': country.has_config }"
            @click="selectCountryForAdd(country)"
          >
            <span class="flag">{{ country.flag_emoji }}</span>
            <div class="country-details">
              <h4>{{ country.name }}</h4>
              <small>{{ country.currency }} • {{ country.timezone }}</small>
            </div>
            <div class="status-pill" :class="country.has_config ? 'green' : 'gray'">
              {{ country.has_config ? 'Configured' : 'Available' }}
            </div>
          </div>
          
          <div class="empty-search" v-if="filteredCountries.length === 0">
            <p>No countries found matching "{{ countrySearch }}"</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Help Modal -->
    <div class="modal-backdrop" v-if="showHelpModal" @click.self="showHelpModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Help & Documentation</h3>
          <button class="close-btn" @click="showHelpModal = false">✕</button>
        </div>
        <div class="modal-body prose">
          <h4>Configuration Scope</h4>
          <p>
            <strong>Global:</strong> Fallback settings for any country not specifically configured.<br>
            <strong>Country:</strong> Specific tax laws for a nation.<br>
            <strong>Business Override:</strong> Applies only to your specific entity.
          </p>
          <h4>Statutory Deductions</h4>
          <p>Add rules for pensions (NAPSA/NSSF), health insurance (NHIMA/NHIF), or other levies. Define ceiling amounts if contributions satisfy a maximum limit.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary full-width" @click="showHelpModal = false">Got it</button>
        </div>
      </div>
    </div>

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
      
      // Extended Currency Lists
      africanCurrencies: [
        { code: 'DZD', name: 'Algerian Dinar' },
        { code: 'AOA', name: 'Angolan Kwanza' },
        { code: 'BWP', name: 'Botswana Pula' },
        { code: 'BIF', name: 'Burundian Franc' },
        { code: 'CVE', name: 'Cape Verdean Escudo' },
        { code: 'XAF', name: 'Central African CFA Franc' },
        { code: 'KMF', name: 'Comorian Franc' },
        { code: 'CDF', name: 'Congolese Franc' },
        { code: 'DJF', name: 'Djiboutian Franc' },
        { code: 'EGP', name: 'Egyptian Pound' },
        { code: 'ERN', name: 'Eritrean Nakfa' },
        { code: 'ETB', name: 'Ethiopian Birr' },
        { code: 'GMD', name: 'Gambian Dalasi' },
        { code: 'GHS', name: 'Ghanaian Cedi' },
        { code: 'GNF', name: 'Guinean Franc' },
        { code: 'KES', name: 'Kenyan Shilling' },
        { code: 'LSL', name: 'Lesotho Loti' },
        { code: 'LRD', name: 'Liberian Dollar' },
        { code: 'LYD', name: 'Libyan Dinar' },
        { code: 'MGA', name: 'Malagasy Ariary' },
        { code: 'MWK', name: 'Malawian Kwacha' },
        { code: 'MRU', name: 'Mauritanian Ouguiya' },
        { code: 'MUR', name: 'Mauritian Rupee' },
        { code: 'MAD', name: 'Moroccan Dirham' },
        { code: 'MZN', name: 'Mozambican Metical' },
        { code: 'NAD', name: 'Namibian Dollar' },
        { code: 'NGN', name: 'Nigerian Naira' },
        { code: 'RWF', name: 'Rwandan Franc' },
        { code: 'STN', name: 'São Tomé and Príncipe Dobra' },
        { code: 'SCR', name: 'Seychellois Rupee' },
        { code: 'SLE', name: 'Sierra Leonean Leone' },
        { code: 'SOS', name: 'Somali Shilling' },
        { code: 'ZAR', name: 'South African Rand' },
        { code: 'SSP', name: 'South Sudanese Pound' },
        { code: 'SDG', name: 'Sudanese Pound' },
        { code: 'SZL', name: 'Swazi Lilangeni' },
        { code: 'TZS', name: 'Tanzanian Shilling' },
        { code: 'TND', name: 'Tunisian Dinar' },
        { code: 'UGX', name: 'Ugandan Shilling' },
        { code: 'XOF', name: 'West African CFA Franc' },
        { code: 'ZMW', name: 'Zambian Kwacha' },
        { code: 'ZWG', name: 'Zimbabwean ZiG' }
      ],
      majorCurrencies: [
        { code: 'AUD', name: 'Australian Dollar' },
        { code: 'CAD', name: 'Canadian Dollar' },
        { code: 'CNY', name: 'Chinese Yuan' },
        { code: 'JPY', name: 'Japanese Yen' },
        { code: 'CHF', name: 'Swiss Franc' },
        { code: 'AED', name: 'UAE Dirham' }
      ],
      
      taxConfig: {
        taxYear: new Date().getFullYear().toString(),
        currency: 'ZMW',
        taxFreeThreshold: 0,
        annualTaxFree: 0,
        effective_date: new Date().toISOString().split('T')[0],
        taxBands: [],
        
        // Replaced flat fields with dynamic array
        statutory_deductions: [], 
        
        includeHousingAllowance: false,
        includeTransportAllowance: false,
        taxCalculationMethod: 'cumulative',
        roundingMethod: 'nearest'
      }
    };
  },
  
  watch: {
    'taxConfig.taxBands': {
      handler() { this.validateTaxBands(); },
      deep: true
    },
    selectedCountry(newVal) {
      if (newVal) {
        this.loadCountryConfig();
      } else {
        this.selectedCountryConfig = null;
        this.currentConfigId = null;
      }
    }
  },
  
  methods: {
    // --- Statutory Deductions Logic ---
    addDeduction() {
        this.taxConfig.statutory_deductions.push({
            name: '',
            type: 'pension',
            base: 'gross',
            employee_rate: 0,
            employer_rate: 0,
            ceiling: null
        });
    },

    removeDeduction(index) {
        this.taxConfig.statutory_deductions.splice(index, 1);
    },

    loadCountryPresets(countryCode) {
        if(!confirm(`Overwrite current deduction rules with ${countryCode} defaults?`)) return;

        let presets = [];
        
        if (countryCode === 'ZM') { // Zambia
            presets = [
                { name: 'NAPSA', type: 'pension', base: 'gross', employee_rate: 5, employer_rate: 5, ceiling: 34164 },
                { name: 'NHIMA', type: 'health', base: 'basic', employee_rate: 1, employer_rate: 1, ceiling: null }
            ];
        } else if (countryCode === 'KE') { // Kenya
            presets = [
                { name: 'NSSF (Tier I)', type: 'pension', base: 'gross', employee_rate: 6, employer_rate: 6, ceiling: 7000 },
                { name: 'Housing Levy', type: 'levy', base: 'gross', employee_rate: 1.5, employer_rate: 1.5, ceiling: null }
            ];
        } else if (countryCode === 'ZA') { // South Africa
            presets = [
                { name: 'UIF', type: 'pension', base: 'gross', employee_rate: 1, employer_rate: 1, ceiling: 17712 }
            ];
        }

        this.taxConfig.statutory_deductions = presets;
    },

    // --- Data Loading ---
    async fetchCountries() {
      this.loading = true;
      try {
        const axiosInstance = this.getAxiosInstance();
        const response = await axiosInstance.get('/api/admin/available-countries');
        if (response.data.countries) {
          this.availableCountries = response.data.countries;
          this.allCountries = response.data.countries;
          this.filteredCountries = response.data.countries;
        }
        await this.fetchTaxConfigurations();
      } catch (error) {
        console.error('Error fetching countries:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async fetchTaxConfigurations() {
      try {
        const axiosInstance = this.getAxiosInstance();
        const response = await axiosInstance.get('/api/admin/tax-configurations');
        if (response.data.tax_configurations) {
          this.availableCountries = this.availableCountries.map(country => {
            const hasConfig = response.data.tax_configurations.some(
              config => config.country_code === country.code && !config.business_id
            );
            return {
              ...country,
              has_config: hasConfig
            };
          });
        }
      } catch (error) {
        console.error('Error fetching tax configurations:', error);
      }
    },

    async loadCountryConfig() {
      if (!this.selectedCountry) return;
      this.loading = true;
      this.selectedCountryConfig = null;
      this.currentConfigId = null;
      
      try {
        const axiosInstance = this.getAxiosInstance();
        const params = this.selectedCountry === 'global' ? { country_code: null } : { country_code: this.selectedCountry };
        const response = await axiosInstance.get('/api/admin/tax-configuration', { params });
          
        if (response.data?.tax_configuration) {
          this.taxConfig = { 
            ...this.taxConfig, 
            ...response.data.tax_configuration.config_data 
          };
          // Ensure array exists if loading old data format
          if (!this.taxConfig.statutory_deductions) {
             this.taxConfig.statutory_deductions = [];
          }

          this.currentConfigId = response.data.tax_configuration.id;
          this.selectedCountryConfig = {
            has_config: true,
            currency: response.data.tax_configuration.config_data.currency
          };
        } else {
          this.selectedCountryConfig = { has_config: false };
          this.resetTaxConfigToCountryDefaults();
        }
        this.applyToBusiness = false;
      } catch (error) {
        if (error.response?.status === 404) {
          this.selectedCountryConfig = { has_config: false };
          this.resetTaxConfigToCountryDefaults();
        }
      } finally {
        this.loading = false;
      }
    },
    
    resetTaxConfigToCountryDefaults() {
      const country = this.allCountries.find(c => c.code === this.selectedCountry);
      if (country) {
        this.taxConfig.currency = country.currency || 'ZMW';
        this.setDefaultTaxBands(country.currency);
        this.taxConfig.statutory_deductions = [];
      }
    },
    
    setDefaultTaxBands(currency) {
      const defaults = {
        'USD': [{ lowerLimit: 0, upperLimit: 1000, rate: 0 }, { lowerLimit: 1000.01, upperLimit: null, rate: 15 }],
        'ZMW': [{ lowerLimit: 0, upperLimit: 4500, rate: 0 }, { lowerLimit: 4500.01, upperLimit: 9000, rate: 25 }, { lowerLimit: 9000.01, upperLimit: null, rate: 30 }],
        'EUR': [{ lowerLimit: 0, upperLimit: 10000, rate: 0 }, { lowerLimit: 10000.01, upperLimit: null, rate: 20 }]
      };
      this.taxConfig.taxBands = defaults[currency] || defaults['USD'];
    },
    
    reloadCountryData() {
      if (this.selectedCountry) {
        this.fetchTaxConfigurations();
        this.loadCountryConfig();
      }
    },
    
    // --- Helper Methods ---
    getCountryDisplayName(countryCode) {
      if (countryCode === 'global') return 'Global Configuration';
      const country = this.allCountries.find(c => c.code === countryCode);
      return country ? `${country.name}` : countryCode;
    },
    
    getScopeInfoClass() {
      if (this.selectedCountry === 'global') return 'banner-global';
      if (this.applyToBusiness) return 'banner-business';
      return 'banner-country';
    },
    
    getScopeInfoIcon() {
      if (this.selectedCountry === 'global') return 'fas fa-globe';
      if (this.applyToBusiness) return 'fas fa-building';
      return 'fas fa-flag';
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
      const term = this.countrySearch.toLowerCase();
      this.filteredCountries = this.allCountries.filter(c => 
        c.name.toLowerCase().includes(term) || c.code.toLowerCase().includes(term)
      );
    },
    
    selectCountryForAdd(country) {
      this.selectedCountry = country.code;
      this.showAddCountryModal = false;
    },
    
    createNewConfig() {
      if (!this.selectedCountry) return;
      const country = this.allCountries.find(c => c.code === this.selectedCountry);
      if (country) {
        this.taxConfig.currency = country.currency || 'ZMW';
        this.setDefaultTaxBands(country.currency);
        this.selectedCountryConfig = { has_config: true, created_now: true };
      }
    },
    
    async deleteCountryConfig() {
      if (!this.currentConfigId || !confirm('Are you sure? This cannot be undone.')) return;
      this.loading = true;
      try {
        await this.getAxiosInstance().delete(`/api/admin/tax-configuration/${this.currentConfigId}`);
        this.selectedCountryConfig = { has_config: false };
        this.currentConfigId = null;
        this.resetTaxConfigToCountryDefaults();
        await this.fetchTaxConfigurations();
      } catch (error) {
        console.error('Delete failed', error);
      } finally {
        this.loading = false;
      }
    },
    
    loadDefaultConfig() {
        this.resetTaxConfigToCountryDefaults();
        this.selectedCountryConfig = { has_config: true, created_now: true };
    },
    
    validateTaxBands() {
      this.hasBandValidationError = false;
      if (!this.taxConfig.taxBands) return;
      for (let i = 1; i < this.taxConfig.taxBands.length; i++) {
        const prev = this.taxConfig.taxBands[i - 1];
        const curr = this.taxConfig.taxBands[i];
        if (prev.upperLimit && Math.abs(curr.lowerLimit - prev.upperLimit) > 0.02) {
          this.hasBandValidationError = true;
          break;
        }
      }
    },
    
    addTaxBand() {
      const lastBand = this.taxConfig.taxBands[this.taxConfig.taxBands.length - 1];
      this.taxConfig.taxBands.push({
        lowerLimit: lastBand?.upperLimit ? lastBand.upperLimit + 0.01 : 0,
        upperLimit: null,
        rate: 0
      });
    },
    
    removeTaxBand(index) {
      if (this.taxConfig.taxBands.length > 1) this.taxConfig.taxBands.splice(index, 1);
    },
    
    async saveTaxConfiguration() {
      if (this.hasBandValidationError || !this.selectedCountry) return;
      this.saving = true;
      try {
        if (this.taxConfig.taxFreeThreshold != null) {
          this.taxConfig.annualTaxFree = this.taxConfig.taxFreeThreshold * 12;
        }
        const payload = {
          taxConfig: this.taxConfig,
          apply_to_business: this.applyToBusiness && this.selectedCountry !== 'global',
          country_code: this.selectedCountry !== 'global' ? this.selectedCountry : null
        };
        const response = await this.getAxiosInstance().post('/api/admin/update-tax-configuration', payload);
        this.currentConfigId = response.data.tax_configuration.id;
        this.selectedCountryConfig = { has_config: true, last_updated: new Date().toISOString(), currency: this.taxConfig.currency };
        await this.fetchTaxConfigurations();
        alert('Configuration saved successfully');
      } catch (error) {
        console.error('Save failed', error);
        alert('Failed to save configuration');
      } finally {
        this.saving = false;
      }
    },
    
    resetToDefault() {
      if (confirm('Reset form to defaults?')) this.loadDefaultConfig();
    },
    
    previewCalculation() { alert('Calculation preview logic would go here.'); },
    
    getAxiosInstance() { return this.$axios || this.$http || axios; }
  },
  
  async mounted() {
    const currentYear = new Date().getFullYear();
    this.taxYears = Array.from({ length: 6 }, (_, i) => String(currentYear + i));
    this.taxConfig.taxYear = String(currentYear);
    try {
      const userResponse = await this.getAxiosInstance().get('/api/user');
      this.currentBusinessId = userResponse.data.current_business_id;
    } catch (e) { /* ignore */ }
    await this.fetchCountries();
  }
};
</script>

<style scoped>
/* --- VARIABLES --- */
:root {
  --primary: #4f46e5;
  --primary-dark: #4338ca;
  --primary-light: #eef2ff;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --text-main: #1f2937;
  --text-light: #6b7280;
  --bg-page: #f3f4f6;
  --bg-card: #ffffff;
  --border: #e5e7eb;
  --radius: 12px;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* --- RESET & LAYOUT --- */
* { box-sizing: border-box; }

.tax-page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem 1rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  color: #1f2937;
  background-color: #f8fafc;
  min-height: 100vh;
}

.grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }

/* --- HEADER --- */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title { font-size: 1.875rem; font-weight: 700; color: #111827; margin: 0; }
.page-subtitle { color: #6b7280; margin: 0.25rem 0 0; font-size: 0.95rem; }

/* --- CARDS --- */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border: 1px solid #f3f4f6;
  transition: box-shadow 0.2s;
}
.card:hover { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}

/* --- STATUTORY DEDUCTIONS STYLES (NEW) --- */
.deductions-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.card-sub-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
    border-radius: 8px;
    position: relative;
}
.deduction-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    align-items: center;
}
.title-input {
    font-weight: 600;
    font-size: 1em;
    border: none;
    border-bottom: 2px solid #cbd5e1;
    border-radius: 0;
    padding: 0.25rem 0;
    background: transparent;
    width: 60%;
    color: #374151;
    outline: none;
}
.title-input:focus {
    border-color: #4f46e5;
}

/* --- SCOPE SELECTOR --- */
.card-icon-header { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
.icon-box {
  width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
}
.icon-box.blue { background: #e0e7ff; color: #4f46e5; }

.scope-controls { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1.5rem; }
.control-group { flex: 1; min-width: 250px; }
.input-label { display: block; font-size: 0.85rem; font-weight: 500; color: #4b5563; margin-bottom: 0.5rem; }

.select-wrapper { display: flex; gap: 0.5rem; }
.modern-select {
  flex: 1;
  padding: 0.625rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.95rem;
  color: #1f2937;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.modern-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

.btn-icon {
  width: 42px; height: 42px; border: 1px solid #d1d5db; background: white; border-radius: 8px;
  color: #6b7280; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;
}
.btn-icon:hover { border-color: #4f46e5; color: #4f46e5; }
.btn-icon.danger:hover { border-color: #ef4444; color: #ef4444; }
.btn-icon.sm { width: 32px; height: 32px; font-size: 0.8rem; }

.scope-meta { display: flex; gap: 0.75rem; align-items: center; }
.meta-badge {
  display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.8rem; border-radius: 20px;
  font-size: 0.75rem; font-weight: 600;
}
.meta-badge.success { background: #d1fae5; color: #065f46; }
.meta-badge.warning { background: #fee2e2; color: #991b1b; }
.meta-badge.neutral { background: #f3f4f6; color: #374151; }

.scope-banner {
  margin-top: 1.5rem; padding: 1rem; border-radius: 8px; display: flex; gap: 1rem; align-items: flex-start;
  font-size: 0.9rem;
}
.banner-global { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
.banner-business { background: #fdf2f8; color: #9d174d; border: 1px solid #fce7f3; }
.banner-country { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

/* --- FORMS --- */
.modern-input {
  width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 8px;
  font-size: 0.95rem; transition: all 0.2s; outline: none;
}
.modern-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
.modern-input[readonly] { background: #f9fafb; color: #6b7280; }

.input-group { position: relative; display: flex; align-items: center; }
.input-group .prefix, .input-group .suffix {
  padding: 0 0.75rem; color: #6b7280; font-size: 0.9rem; background: #f9fafb;
  border: 1px solid #d1d5db; height: 42px; display: flex; align-items: center;
}
.input-group .prefix { border-right: none; border-radius: 8px 0 0 8px; }
.input-group .suffix { border-left: none; border-radius: 0 8px 8px 0; }
.input-group input { border-radius: 0; flex: 1; height: 42px; }
.input-group .prefix + input { border-radius: 0 8px 8px 0; }
.input-group input:not(:last-child) { border-radius: 8px 0 0 8px; }

.input-icon-wrapper { position: relative; }
.input-icon-wrapper i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
.modern-input.with-icon { padding-left: 2.5rem; }

.form-group label { display: block; font-size: 0.85rem; font-weight: 500; color: #374151; margin-bottom: 0.4rem; }

/* --- TOGGLE SWITCH --- */
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider {
  position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc;
  transition: .4s; border-radius: 34px;
}
.slider:before {
  position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white;
  transition: .4s; border-radius: 50%;
}
input:checked + .slider { background-color: #4f46e5; }
input:checked + .slider:before { transform: translateX(20px); }
.business-toggle { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
.toggle-label { font-size: 0.9rem; color: #4b5563; font-weight: 500; }

/* --- TAX BANDS --- */
.tax-bands-container { display: flex; flex-direction: column; gap: 1rem; }
.band-row {
  display: flex; gap: 1rem; align-items: center; background: #f9fafb; padding: 1rem; border-radius: 8px;
  border: 1px solid #e5e7eb;
}
.band-indicator {
  width: 28px; height: 28px; background: #e0e7ff; color: #4f46e5; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;
}
.band-inputs { display: flex; gap: 1rem; flex: 1; flex-wrap: wrap; }
.band-inputs .form-group { flex: 1; margin-bottom: 0; }
.sub-label { font-size: 0.75rem !important; color: #6b7280 !important; text-transform: uppercase; letter-spacing: 0.05em; }

.card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.75rem; }
.card-header-flex .card-title { margin: 0; border: none; padding: 0; }
.validation-badge { font-size: 0.8rem; color: #dc2626; background: #fef2f2; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #fecaca; }

/* --- BUTTONS --- */
.btn {
  padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer;
  transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 0.5rem;
}
.btn-primary { background: #4f46e5; color: white; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
.btn-primary:disabled { background: #a5b4fc; cursor: not-allowed; transform: none; }

.btn-ghost { background: transparent; color: #6b7280; }
.btn-ghost:hover { background: #f3f4f6; color: #1f2937; }

.btn-outline { background: white; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { border-color: #9ca3af; background: #f9fafb; }

.btn-dashed {
  width: 100%; background: transparent; border: 2px dashed #e5e7eb; color: #6b7280; justify-content: center;
}
.btn-dashed:hover { border-color: #4f46e5; color: #4f46e5; background: #eef2ff; }

.btn-danger-ghost { background: transparent; color: #ef4444; border: 1px solid transparent; }
.btn-danger-ghost:hover { background: #fef2f2; border-color: #fecaca; }

/* --- UTILS --- */
.empty-state-card { text-align: center; padding: 3rem; background: white; border-radius: 12px; border: 1px solid #e5e7eb; }
.empty-illustration {
  font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; width: 80px; height: 80px;
  background: #f9fafb; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
}
.empty-illustration.warning { color: #fbbf24; background: #fffbeb; }

.sticky-footer {
  position: sticky; bottom: 0; background: white; padding: 1rem; border-top: 1px solid #e5e7eb;
  display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.05);
  margin-top: 2rem; border-radius: 0 0 12px 12px;
}
.footer-right { display: flex; gap: 0.75rem; }

.toggle-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem; }
.toggle-item { display: flex; gap: 0.75rem; align-items: flex-start; padding: 0.75rem; background: #f9fafb; border-radius: 8px; }
.toggle-text { display: flex; flex-direction: column; }
.toggle-text span { font-size: 0.9rem; font-weight: 500; color: #374151; }
.toggle-text small { color: #6b7280; font-size: 0.8rem; }

/* --- MODAL --- */
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center; z-index: 50; backdrop-filter: blur(2px);
}
.modal-card {
  background: white; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  overflow: hidden; animation: slideUp 0.3s ease-out;
}
.modal-header { padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.close-btn { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #9ca3af; }

.search-bar { padding: 1rem; position: relative; border-bottom: 1px solid #f3f4f6; }
.search-bar i { position: absolute; left: 1.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
.search-bar input { width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 1px solid #e5e7eb; border-radius: 8px; outline: none; }
.search-bar input:focus { border-color: #4f46e5; }

.country-list { max-height: 400px; overflow-y: auto; }
.country-list-item {
  display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1.5rem; cursor: pointer; transition: background 0.2s;
}
.country-list-item:hover { background: #f9fafb; }
.country-list-item .flag { font-size: 1.5rem; }
.country-details { flex: 1; }
.country-details h4 { margin: 0; font-size: 0.95rem; color: #1f2937; }
.country-details small { color: #6b7280; }
.status-pill { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-transform: uppercase; }
.status-pill.green { background: #d1fae5; color: #065f46; }
.status-pill.gray { background: #f3f4f6; color: #6b7280; }

.modal-body { padding: 1.5rem; }
.modal-footer { padding: 1rem 1.5rem; background: #f9fafb; }
.full-width { width: 100%; justify-content: center; }

@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@media (max-width: 640px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; display: flex; gap: 0.5rem; }
  .header-actions .btn { flex: 1; justify-content: center; }
  .band-row { flex-direction: column; align-items: stretch; }
  .band-inputs { flex-direction: column; }
}
</style>