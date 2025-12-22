<template>
  <div class="country-management">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Country Management</h1>
        <p class="page-subtitle">Manage countries and their configurations</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Country
      </button>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by name or code..."
          @input="handleSearch"
        />
      </div>
      <div class="filter-buttons">
        <button
          :class="['filter-btn', { active: activeFilter === 'all' }]"
          @click="setFilter('all')"
        >
          All Countries
        </button>
        <button
          :class="['filter-btn', { active: activeFilter === 'active' }]"
          @click="setFilter('active')"
        >
          Active
        </button>
        <button
          :class="['filter-btn', { active: activeFilter === 'inactive' }]"
          @click="setFilter('inactive')"
        >
          Inactive
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading countries...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Failed to Load Countries</h3>
      <p>{{ error }}</p>
      <button @click="fetchCountries" class="btn btn-primary">
        <i class="fas fa-redo"></i> Try Again
      </button>
    </div>

    <!-- Countries Grid -->
    <div v-else-if="countries.length > 0" class="countries-grid">
      <div
        v-for="country in filteredCountries"
        :key="country.id"
        class="country-card"
        :class="{ inactive: !country.is_active }"
      >
        <div class="card-header">
          <div class="country-flag">
            <img
              v-if="country.flag"
              :src="country.flag"
              :alt="`${country.name} flag`"
              class="flag-image"
              @error="handleFlagError"
            />
            <span class="flag-fallback">{{ country.code }}</span>
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
          <div class="info-row">
            <span class="label">Currency:</span>
            <span class="value">{{ country.currency_symbol }} {{ country.currency_code }}</span>
          </div>
          <div class="info-row">
            <span class="label">Phone Code:</span>
            <span class="value">{{ country.phone_code }}</span>
          </div>
          <div class="info-row">
            <span class="label">Timezone:</span>
            <span class="value">{{ country.timezone }}</span>
          </div>
          <div class="info-row">
            <span class="label">Date Format:</span>
            <span class="value">{{ country.date_format }}</span>
          </div>

          <!-- Configuration Info -->
          <div v-if="country.configuration" class="config-info">
            <h4>Work Configuration</h4>
            <div class="config-details">
              <div class="config-item">
                <i class="fas fa-calendar-week"></i>
                <span>{{ country.configuration.work_days_per_week }} days/week</span>
              </div>
              <div class="config-item">
                <i class="fas fa-clock"></i>
                <span>{{ country.configuration.hours_per_day }} hrs/day</span>
              </div>
              <div class="config-item">
                <i class="fas fa-umbrella-beach"></i>
                <span>{{ country.configuration.annual_leave_days }} annual leave</span>
              </div>
              <div class="config-item">
                <i class="fas fa-notes-medical"></i>
                <span>{{ country.configuration.sick_leave_days }} sick leave</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card-actions">
          <button
            @click="viewStatistics(country)"
            class="btn-action btn-stats"
            title="Statistics"
          >
            <i class="fas fa-chart-bar"></i>
            <span class="btn-text">Stats</span>
          </button>
          <button
            @click="editCountry(country)"
            class="btn-action btn-edit"
            title="Edit"
          >
            <i class="fas fa-edit"></i>
            <span class="btn-text">Edit</span>
          </button>
          <button
            @click="toggleStatus(country)"
            :class="['btn-action', country.is_active ? 'btn-deactivate' : 'btn-activate']"
            :title="country.is_active ? 'Deactivate' : 'Activate'"
            :disabled="togglingStatus === country.id"
          >
            <i v-if="togglingStatus === country.id" class="fas fa-spinner fa-spin"></i>
            <template v-else>
              <i :class="['fas', country.is_active ? 'fa-toggle-on' : 'fa-toggle-off']"></i>
              <span class="btn-text">{{ country.is_active ? 'Active' : 'Inactive' }}</span>
            </template>
          </button>
          <button
            @click="deleteCountry(country)"
            class="btn-action btn-delete"
            title="Delete"
            :disabled="deleting === country.id"
          >
            <i v-if="deleting === country.id" class="fas fa-spinner fa-spin"></i>
            <template v-else>
              <i class="fas fa-trash"></i>
              <span class="btn-text">Delete</span>
            </template>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <i class="fas fa-globe"></i>
      <h3>No Countries Found</h3>
      <p>Start by adding your first country to the system</p>
      <button @click="openCreateModal" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Country
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Country' : 'Add New Country' }}</h2>
          <button @click="closeModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form @submit.prevent="saveCountry" class="modal-body">
          <!-- Basic Information -->
          <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Country Name *</label>
                <input 
                  v-model="formData.name" 
                  type="text" 
                  placeholder="e.g., Zambia" 
                  required 
                  :class="{ 'error': errors.name }"
                />
                <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
              </div>
              <div class="form-group">
                <label>Country Code (ISO 2) *</label>
                <input
                  v-model="formData.code"
                  type="text"
                  placeholder="e.g., ZM"
                  maxlength="2"
                  required
                  @input="generateFlagUrl"
                  style="text-transform: uppercase;"
                  :class="{ 'error': errors.code }"
                />
                <span v-if="errors.code" class="error-text">{{ errors.code[0] }}</span>
              </div>

              <div class="form-group full-width">
                <label>Flag URL *</label>
                <div class="flag-input-wrapper">
                  <input
                    v-model="formData.flag"
                    type="text"
                    placeholder="e.g., https://flagcdn.com/zm.svg"
                    required
                    class="flag-input"
                    :class="{ 'error': errors.flag }"
                  />
                  <div v-if="formData.flag" class="flag-preview">
                    <img
                      :src="formData.flag"
                      :alt="`${formData.name} flag`"
                      @error="handleFormFlagError"
                      class="preview-flag"
                    />
                  </div>
                </div>
                <span v-if="errors.flag" class="error-text">{{ errors.flag[0] }}</span>
                <small class="help-text">
                  Auto-generated from country code. You can also use
                  <a href="https://flagcdn.com" target="_blank">FlagCDN</a> or
                  <a href="https://www.countryflags.io" target="_blank">CountryFlags.io</a>
                </small>
              </div>

              <div class="form-group">
                <label>Currency Code *</label>
                <input
                  v-model="formData.currency_code"
                  type="text"
                  placeholder="e.g., ZMW"
                  maxlength="3"
                  required
                  style="text-transform: uppercase;"
                  :class="{ 'error': errors.currency_code }"
                />
                <span v-if="errors.currency_code" class="error-text">{{ errors.currency_code[0] }}</span>
              </div>
              <div class="form-group">
                <label>Currency Symbol *</label>
                <input 
                  v-model="formData.currency_symbol" 
                  type="text" 
                  placeholder="e.g., K" 
                  required 
                  :class="{ 'error': errors.currency_symbol }"
                />
                <span v-if="errors.currency_symbol" class="error-text">{{ errors.currency_symbol[0] }}</span>
              </div>
              <div class="form-group">
                <label>Phone Code *</label>
                <input 
                  v-model="formData.phone_code" 
                  type="text" 
                  placeholder="e.g., +260" 
                  required 
                  :class="{ 'error': errors.phone_code }"
                />
                <span v-if="errors.phone_code" class="error-text">{{ errors.phone_code[0] }}</span>
              </div>
              <div class="form-group">
                <label>Timezone *</label>
                <select v-model="formData.timezone" required :class="{ 'error': errors.timezone }">
                  <option value="">Select Timezone</option>
                  <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                </select>
                <span v-if="errors.timezone" class="error-text">{{ errors.timezone[0] }}</span>
              </div>
              <div class="form-group">
                <label>Date Format *</label>
                <select v-model="formData.date_format" required :class="{ 'error': errors.date_format }">
                  <option value="Y-m-d">YYYY-MM-DD</option>
                  <option value="d/m/Y">DD/MM/YYYY</option>
                  <option value="m/d/Y">MM/DD/YYYY</option>
                  <option value="d-M-Y">DD-MMM-YYYY</option>
                </select>
                <span v-if="errors.date_format" class="error-text">{{ errors.date_format[0] }}</span>
              </div>
              <div class="form-group">
                <label>Time Format *</label>
                <select v-model="formData.time_format" required :class="{ 'error': errors.time_format }">
                  <option value="H:i">24 Hour (HH:MM)</option>
                  <option value="h:i A">12 Hour (hh:mm AM/PM)</option>
                </select>
                <span v-if="errors.time_format" class="error-text">{{ errors.time_format[0] }}</span>
              </div>
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input v-model="formData.is_active" type="checkbox" />
                <span>Active Country</span>
              </label>
            </div>
          </div>

          <!-- Work Configuration -->
          <div class="form-section">
            <h3>Work Configuration</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Work Days per Week</label>
                <input v-model.number="formData.work_days_per_week" type="number" min="1" max="7" placeholder="e.g., 5" />
              </div>
              <div class="form-group">
                <label>Hours per Day</label>
                <input v-model.number="formData.hours_per_day" type="number" min="1" max="24" step="0.5" placeholder="e.g., 8" />
              </div>
              <div class="form-group">
                <label>Overtime Multiplier</label>
                <input v-model.number="formData.overtime_multiplier" type="number" min="1" step="0.1" placeholder="e.g., 1.5" />
              </div>
              <div class="form-group">
                <label>Annual Leave Days</label>
                <input v-model.number="formData.annual_leave_days" type="number" min="0" placeholder="e.g., 20" />
              </div>
              <div class="form-group">
                <label>Sick Leave Days</label>
                <input v-model.number="formData.sick_leave_days" type="number" min="0" placeholder="e.g., 10" />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <i v-if="saving" class="fas fa-spinner fa-spin"></i>
              {{ saving ? 'Saving...' : isEditMode ? 'Update Country' : 'Add Country' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStatsModal" class="modal-overlay" @click.self="closeStatsModal">
      <div class="modal-content stats-modal">
        <div class="modal-header">
          <h2>
            <img
              v-if="selectedCountry?.flag"
              :src="selectedCountry.flag"
              :alt="`${selectedCountry.name} flag`"
              class="stats-flag"
              @error="(e) => e.target.style.display = 'none'"
            />
            {{ selectedCountry?.name }} - Statistics
          </h2>
          <button @click="closeStatsModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-if="loadingStats" class="modal-body">
          <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading statistics...</p>
          </div>
        </div>

        <div v-else class="modal-body">
          <div class="stats-grid">
            <div class="stat-card">
              <i class="fas fa-users"></i>
              <div class="stat-content">
                <h4>Total Employees</h4>
                <p class="stat-value">{{ statistics?.total_employees || 0 }}</p>
              </div>
            </div>
            <div class="stat-card">
              <i class="fas fa-user-check"></i>
              <div class="stat-content">
                <h4>Active Employees</h4>
                <p class="stat-value">{{ statistics?.active_employees || 0 }}</p>
              </div>
            </div>
            <div class="stat-card">
              <i class="fas fa-clock"></i>
              <div class="stat-content">
                <h4>Total Attendances</h4>
                <p class="stat-value">{{ statistics?.total_attendances || 0 }}</p>
              </div>
            </div>
            <div class="stat-card">
              <i class="fas fa-umbrella-beach"></i>
              <div class="stat-content">
                <h4>Total Leaves</h4>
                <p class="stat-value">{{ statistics?.total_leaves || 0 }}</p>
              </div>
            </div>
            <div class="stat-card">
              <i class="fas fa-dollar-sign"></i>
              <div class="stat-content">
                <h4>Total Payrolls</h4>
                <p class="stat-value">{{ statistics?.total_payrolls || 0 }}</p>
              </div>
            </div>
            <div class="stat-card">
              <i class="fas fa-file-invoice-dollar"></i>
              <div class="stat-content">
                <h4>Total Payslips</h4>
                <p class="stat-value">{{ statistics?.total_payslips || 0 }}</p>
              </div>
            </div>
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
        name: '',
        code: '',
        flag: '',
        currency_code: '',
        currency_symbol: '',
        phone_code: '',
        timezone: '',
        date_format: 'Y-m-d',
        time_format: 'H:i',
        is_active: true,
        work_days_per_week: 5,
        hours_per_day: 8,
        overtime_multiplier: 1.5,
        annual_leave_days: 20,
        sick_leave_days: 10,
      },
      timezones: [
        // African Timezones (Grouped by UTC offset)
        'Africa/Abidjan',        // UTC+0 (Côte d'Ivoire)
        'Africa/Accra',          // UTC+0 (Ghana)
        'Africa/Algiers',        // UTC+1 (Algeria)
        'Africa/Bamako',         // UTC+0 (Mali)
        'Africa/Bangui',         // UTC+1 (Central African Republic)
        'Africa/Banjul',         // UTC+0 (Gambia)
        'Africa/Bissau',         // UTC+0 (Guinea-Bissau)
        'Africa/Blantyre',       // UTC+2 (Malawi)
        'Africa/Brazzaville',    // UTC+1 (Republic of Congo)
        'Africa/Bujumbura',      // UTC+2 (Burundi)
        'Africa/Cairo',          // UTC+2 (Egypt)
        'Africa/Casablanca',     // UTC+1 (Morocco)
        'Africa/Ceuta',          // UTC+1 (Spain - North Africa)
        'Africa/Conakry',        // UTC+0 (Guinea)
        'Africa/Dakar',          // UTC+0 (Senegal)
        'Africa/Dar_es_Salaam',  // UTC+3 (Tanzania)
        'Africa/Djibouti',       // UTC+3 (Djibouti)
        'Africa/Douala',         // UTC+1 (Cameroon)
        'Africa/El_Aaiun',       // UTC+1 (Western Sahara)
        'Africa/Freetown',       // UTC+0 (Sierra Leone)
        'Africa/Gaborone',       // UTC+2 (Botswana)
        'Africa/Harare',         // UTC+2 (Zimbabwe)
        'Africa/Johannesburg',   // UTC+2 (South Africa)
        'Africa/Juba',           // UTC+2 (South Sudan)
        'Africa/Kampala',        // UTC+3 (Uganda)
        'Africa/Khartoum',       // UTC+2 (Sudan)
        'Africa/Kigali',         // UTC+2 (Rwanda)
        'Africa/Kinshasa',       // UTC+1 (DR Congo - West)
        'Africa/Lagos',          // UTC+1 (Nigeria)
        'Africa/Libreville',     // UTC+1 (Gabon)
        'Africa/Lome',           // UTC+0 (Togo)
        'Africa/Luanda',         // UTC+1 (Angola)
        'Africa/Lubumbashi',     // UTC+2 (DR Congo - East)
        'Africa/Lusaka',         // UTC+2 (Zambia)
        'Africa/Malabo',         // UTC+1 (Equatorial Guinea)
        'Africa/Maputo',         // UTC+2 (Mozambique)
        'Africa/Maseru',         // UTC+2 (Lesotho)
        'Africa/Mbabane',        // UTC+2 (Eswatini)
        'Africa/Mogadishu',      // UTC+3 (Somalia)
        'Africa/Monrovia',       // UTC+0 (Liberia)
        'Africa/Nairobi',        // UTC+3 (Kenya)
        'Africa/Ndjamena',       // UTC+1 (Chad)
        'Africa/Niamey',         // UTC+1 (Niger)
        'Africa/Nouakchott',     // UTC+0 (Mauritania)
        'Africa/Ouagadougou',    // UTC+0 (Burkina Faso)
        'Africa/Porto-Novo',     // UTC+1 (Benin)
        'Africa/Sao_Tome',       // UTC+0 (São Tomé and Príncipe)
        'Africa/Tripoli',        // UTC+2 (Libya)
        'Africa/Tunis',          // UTC+1 (Tunisia)
        'Africa/Windhoek',       // UTC+2 (Namibia)
        
        // Other Major Timezones
        'America/New_York',      // UTC-5/-4 (USA East)
        'America/Chicago',       // UTC-6/-5 (USA Central)
        'America/Los_Angeles',   // UTC-8/-7 (USA West)
        'Europe/London',         // UTC+0/+1 (UK)
        'Europe/Paris',          // UTC+1/+2 (France)
        'Europe/Berlin',         // UTC+1/+2 (Germany)
        'Asia/Dubai',            // UTC+4 (UAE)
        'Asia/Kolkata',          // UTC+5:30 (India)
        'Asia/Shanghai',         // UTC+8 (China)
        'Asia/Tokyo',            // UTC+9 (Japan)
        'Australia/Sydney',      // UTC+10/+11 (Australia)
      ],
      searchTimeout: null,
    };
  },
  computed: {
    filteredCountries() {
      let filtered = this.countries;
      
      // Apply search filter locally if we have countries
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(country => 
          country.name.toLowerCase().includes(query) ||
          country.code.toLowerCase().includes(query)
        );
      }
      
      // Apply status filter
      if (this.activeFilter === 'active') {
        filtered = filtered.filter((c) => c.is_active);
      } else if (this.activeFilter === 'inactive') {
        filtered = filtered.filter((c) => !c.is_active);
      }
      
      return filtered;
    },
  },
  mounted() {
    this.fetchCountries();
  },
  methods: {
    async fetchCountries() {
      this.loading = true;
      this.error = null;
      try {
        // Ensure we have CSRF cookie
        await this.ensureCsrfToken();
        
        const response = await axios.get('/api/admin/countries', {
          params: {
            search: this.searchQuery || undefined,
            is_active: this.activeFilter === 'all' ? undefined : (this.activeFilter === 'active')
          }
        });
        
        this.countries = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching countries:', error);
        this.error = error.response?.data?.message || 'Failed to load countries. Please check your connection.';
        this.$toast?.error(this.error);
      } finally {
        this.loading = false;
      }
    },
    
    async ensureCsrfToken() {
      try {
        // Get CSRF cookie from Laravel Sanctum
        await axios.get('/sanctum/csrf-cookie', {
          baseURL: window.location.origin
        });
      } catch (error) {
        console.warn('CSRF cookie fetch failed, continuing anyway:', error);
      }
    },
    
    handleSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        // Use local filtering instead of API call for better performance
        // this.fetchCountries();
      }, 300);
    },
    
    setFilter(filter) {
      this.activeFilter = filter;
      this.fetchCountries();
    },
    
    openCreateModal() {
      this.isEditMode = false;
      this.errors = {};
      this.resetForm();
      this.showModal = true;
    },
    
    editCountry(country) {
      this.isEditMode = true;
      this.selectedCountry = country;
      this.errors = {};
      this.formData = {
        name: country.name,
        code: country.code,
        flag: country.flag || '',
        currency_code: country.currency_code,
        currency_symbol: country.currency_symbol,
        phone_code: country.phone_code,
        timezone: country.timezone,
        date_format: country.date_format,
        time_format: country.time_format,
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
      if (this.formData.code && this.formData.code.length === 2) {
        const countryCode = this.formData.code.toLowerCase();
        this.formData.flag = `https://flagcdn.com/${countryCode}.svg`;
      }
    },
    
    handleFlagError(e) {
      e.target.style.display = 'none';
    },
    
    handleFormFlagError(e) {
      e.target.style.display = 'none';
    },
    
    async saveCountry() {
  this.saving = true;
  this.errors = {};
  
  try {
    await this.ensureCsrfToken();
    
    if (this.isEditMode) {
      await axios.put(`/api/admin/countries/${this.selectedCountry.id}`, this.formData);
      this.$toast?.success('Country updated successfully');
    } else {
      await axios.post('/api/admin/countries', this.formData);
      this.$toast?.success('Country created successfully');
    }
    
    this.closeModal();
    this.fetchCountries();
  } catch (error) {
    console.error('Error saving country:', error);
    
    if (error.response?.status === 422) {
      this.errors = error.response.data.errors || {};
      
      // Show specific error messages
      const errorMessages = Object.values(this.errors).flat();
      if (errorMessages.length > 0) {
        this.$toast?.error(errorMessages[0]); // Show first error
      } else {
        this.$toast?.error('Please check the form for errors');
      }
      
      // Log all errors for debugging
      console.error('Validation errors:', this.errors);
    } else {
      const message = error.response?.data?.message || 'Failed to save country';
      this.$toast?.error(message);
    }
  } finally {
    this.saving = false;
  }
},
    
    async toggleStatus(country) {
      if (this.togglingStatus === country.id) return;
      
      const action = country.is_active ? 'deactivate' : 'activate';
      if (!confirm(`Are you sure you want to ${action} ${country.name}?`)) return;

      this.togglingStatus = country.id;
      try {
        await this.ensureCsrfToken();
        
        const response = await axios.post(`/api/admin/countries/${country.id}/toggle-status`);
        this.$toast?.success(response.data.message);

        // Update local state
        const idx = this.countries.findIndex(c => c.id === country.id);
        if (idx !== -1) this.countries[idx].is_active = !country.is_active;
      } catch (error) {
        console.error('Error toggling status:', error);
        this.$toast?.error(error.response?.data?.message || 'Failed to update status');
      } finally {
        this.togglingStatus = null;
      }
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
        console.error('Error deleting country:', error);
        this.$toast?.error(error.response?.data?.message || 'Failed to delete country');
      } finally {
        this.deleting = null;
      }
    },
    
    async viewStatistics(country) {
      this.selectedCountry = country;
      this.showStatsModal = true;
      this.loadingStats = true;
      this.statistics = null;
      
      try {
        await this.ensureCsrfToken();
        
        const response = await axios.get(`/api/admin/countries/${country.id}/statistics`);
        this.statistics = response.data.data?.statistics || response.data.statistics || response.data;
      } catch (error) {
        console.error('Error fetching statistics:', error);
        this.$toast?.error('Failed to load statistics');
      } finally {
        this.loadingStats = false;
      }
    },
    
    closeModal() {
      this.showModal = false;
      this.errors = {};
      this.resetForm();
    },
    
    closeStatsModal() {
      this.showStatsModal = false;
      this.statistics = null;
      this.selectedCountry = null;
    },
    
    resetForm() {
      this.formData = {
        name: '',
        code: '',
        flag: '',
        currency_code: '',
        currency_symbol: '',
        phone_code: '',
        timezone: '',
        date_format: 'Y-m-d',
        time_format: 'H:i',
        is_active: true,
        work_days_per_week: 5,
        hours_per_day: 8,
        overtime_multiplier: 1.5,
        annual_leave_days: 20,
        sick_leave_days: 10,
      };
      this.selectedCountry = null;
    },
  },
};
</script>

<style scoped>
/* Add error styles */
.error {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.error-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #ef4444;
}

.error-state i {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.7;
}

.error-state h3 {
  margin: 1rem 0 0.75rem;
  font-size: 1.5rem;
  color: #1e293b;
}

.error-state p {
  color: #64748b;
  margin-bottom: 2rem;
}

/* Rest of your existing styles remain the same */
.country-management {
  padding: 2rem;
  max-width: 1500px;
  margin: 0 auto;
  background: #f9fafb;
  min-height: 100vh;
}

.country-management {
  padding: 2rem;
  max-width: 1500px;
  margin: 0 auto;
  background: #f9fafb;
  min-height: 100vh;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.page-subtitle {
  color: #64748b;
  margin-top: 0.25rem;
  font-size: 0.95rem;
}

/* Filters */
.filters-section {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  align-items: center;
}
.search-box {
  flex: 1;
  min-width: 280px;
  position: relative;
}
.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.search-box input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  font-size: 1rem;
  transition: all 0.2s;
}
.search-box input:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
}
.filter-buttons {
  display: flex;
  gap: 0.75rem;
}
.filter-btn {
  padding: 0.75rem 1.75rem;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-btn.active {
  background: #4299e1;
  color: white;
  border-color: #4299e1;
}

/* Grid */
.countries-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1.75rem;
}

/* Country Card */
.country-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
}
.country-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}
.country-card.inactive {
  opacity: 0.78;
  background: #fafbfc;
}

/* Card Header */
.card-header {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}
.country-flag {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.18);
  overflow: hidden;
  flex-shrink: 0;
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
  font-size: 1.4rem;
  background: rgba(255, 255, 255, 0.25);
  color: white;
}
.country-info h3 {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 600;
}
.country-code {
  font-size: 0.9rem;
  opacity: 0.9;
}
.status-badge {
  padding: 0.35rem 1rem;
  border-radius: 30px;
  font-size: 0.85rem;
  font-weight: 600;
}
.status-badge.active { background: #10b981; }
.status-badge.inactive { background: #ef4444; }

/* Card Body */
.card-body {
  padding: 1.75rem;
}
.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.95rem;
}
.info-row:last-child { border-bottom: none; }
.info-row .label {
  color: #64748b;
  font-weight: 500;
}
.info-row .value {
  color: #1e293b;
  font-weight: 600;
}
.config-info {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid #f1f5f9;
}
.config-info h4 {
  margin: 0 0 1rem;
  color: #1e293b;
  font-size: 1.05rem;
  font-weight: 600;
}
.config-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.config-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.92rem;
  color: #475569;
}
.config-item i {
  color: #764ba2;
  font-size: 1.1rem;
}

/* Card Actions */
.card-actions {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem 1.75rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}
.btn-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.25s ease;
}
.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}
.btn-stats { border-color: #4299e1; color: #4299e1; }
.btn-stats:hover { background: #4299e1; color: white; }

.btn-edit { border-color: #f59e0b; color: #f59e0b; }
.btn-edit:hover { background: #f59e0b; color: white; }

.btn-activate { border-color: #10b981; color: #10b981; }
.btn-activate:hover { background: #10b981; color: white; }

.btn-deactivate { border-color: #ef4444; color: #ef4444; }
.btn-deactivate:hover { background: #ef4444; color: white; }

.btn-delete { border-color: #ef4444; color: #ef4444; }
.btn-delete:hover { background: #ef4444; color: white; }

/* Empty & Loading */
.loading-state,
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
  color: #64748b;
}
.loading-state i,
.empty-state i {
  font-size: 4.5rem;
  margin-bottom: 1.5rem;
  opacity: 0.6;
}
.empty-state h3 {
  margin: 1rem 0 0.75rem;
  font-size: 1.5rem;
  color: #1e293b;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.75rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.modal-header h2 {
  margin: 0;
  font-size: 1.6rem;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.stats-flag {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.75rem;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
}
.close-btn:hover {
  background: #fee2e2;
  color: #ef4444;
}
.modal-body {
  padding: 2rem;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

/* Form */
.form-section {
  margin-bottom: 2.5rem;
}
.form-section h3 {
  margin: 0 0 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  color: #1e293b;
  font-size: 1.25rem;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
.form-group {
  display: flex;
  flex-direction: column;
}
.form-group.full-width {
  grid-column: 1 / -1;
}
.form-group label {
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 600;
  font-size: 0.95rem;
}
.form-group input,
.form-group select {
  padding: 0.875rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  background: white;
  font-size: 1rem;
  transition: all 0.2s;
}
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
}
.flag-input-wrapper {
  display: flex;
  gap: 1rem;
  align-items: end;
}
.flag-input {
  flex: 1;
}
.flag-preview {
  width: 110px;
  height: 80px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-flag {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.help-text {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: #64748b;
}
.help-text a {
  color: #4299e1;
  text-decoration: none;
}
.help-text a:hover {
  text-decoration: underline;
}
.checkbox-group {
  flex-direction: row;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.95rem;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.75rem 2rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}
.btn {
  padding: 0.875rem 2rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  transition: all 0.2s;
}
.btn-primary {
  background: #4299e1;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background: #3182ce;
  transform: translateY(-1px);
}
.btn-secondary {
  background: #e2e8f0;
  color: #4b5563;
}
.btn-secondary:hover {
  background: #cbd5e0;
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Statistics Modal */
.stats-modal .modal-content {
  max-width: 1000px;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}
.stat-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 8px 25px rgba(118, 75, 162, 0.25);
}
.stat-card i {
  font-size: 3rem;
  opacity: 0.9;
}
.stat-content h4 {
  margin: 0;
  font-size: 1rem;
  opacity: 0.9;
  font-weight: 500;
}
.stat-value {
  margin: 0.75rem 0 0;
  font-size: 2.5rem;
  font-weight: 700;
}

/* Responsive */
@media (max-width: 992px) {
  .countries-grid {
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  }
}
@media (max-width: 768px) {
  .countries-grid {
    grid-template-columns: 1fr;
  }
  .form-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-buttons {
    justify-content: center;
  }
  .card-actions {
    flex-wrap: wrap;
  }
  .btn-action .btn-text {
    display: none;
  }
}
</style>