<template>
  <div class="tax-configuration">
    <div class="page-header">
      <h1>Tax Configuration - Zambia</h1>
      <p class="page-subtitle">Configure PAYE tax rates and thresholds for Zambian private sector (2025 ZRA Guidelines)</p>
    </div>
    <div class="configuration-card">
      <div class="card-header">
        <h2>PAYE Tax Bands & Rates</h2>
        <p>Configure tax bands according to Zambian Revenue Authority (ZRA) guidelines</p>
      </div>
      <form @submit.prevent="saveTaxConfiguration" class="tax-form">
        <!-- Current Tax Year -->
        <div class="form-section">
          <h3>Tax Year Configuration</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="taxYear">Current Tax Year *</label>
              <select id="taxYear" v-model="taxConfig.taxYear" required>
                <option value="">Select Tax Year</option>
                <option v-for="year in taxYears" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
            <div class="form-group">
              <label for="currency">Currency *</label>
              <select id="currency" v-model="taxConfig.currency" required>
                <option value="ZMW">Zambian Kwacha (ZMW)</option>
                <option value="USD">US Dollar (USD)</option>
              </select>
            </div>
          </div>
        </div>
        <!-- Tax-Free Threshold -->
        <div class="form-section">
          <h3>Tax-Free Allowances</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="taxFreeThreshold">Monthly Tax-Free Threshold (ZMW) *</label>
              <input
                id="taxFreeThreshold"
                v-model.number="taxConfig.taxFreeThreshold"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="e.g., 5100.00"
              />
              <small>Monthly income exempt from PAYE tax (0% band)</small>
            </div>
            <div class="form-group">
              <label for="annualTaxFree">Annual Tax-Free Threshold (ZMW)</label>
              <input
                id="annualTaxFree"
                v-model.number="taxConfig.annualTaxFree"
                type="number"
                step="0.01"
                min="0"
                readonly
                :value="taxConfig.taxFreeThreshold * 12"
              />
              <small>Calculated automatically (Monthly × 12)</small>
            </div>
          </div>
        </div>
        <!-- PAYE Tax Bands -->
        <div class="form-section">
          <h3>PAYE Tax Bands (Progressive on Taxable Gross)</h3>
          <div class="tax-bands">
            <div v-for="(band, index) in taxConfig.taxBands" :key="index" class="tax-band">
              <div class="band-header">
                <h4>Band {{ index + 1 }}</h4>
                <button
                  v-if="taxConfig.taxBands.length > 1"
                  type="button"
                  class="btn-remove"
                  @click="removeTaxBand(index)"
                >
                  Remove
                </button>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Lower Limit (ZMW) *</label>
                  <input
                    v-model.number="band.lowerLimit"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    :placeholder="index === 0 ? '0.00' : `Above ${taxConfig.taxBands[index - 1].upperLimit}`"
                  />
                </div>
                <div class="form-group">
                  <label>Upper Limit (ZMW)</label>
                  <input
                    v-model.number="band.upperLimit"
                    type="number"
                    step="0.01"
                    min="0"
                    :placeholder="index === taxConfig.taxBands.length - 1 ? 'No upper limit' : 'Enter upper limit'"
                  />
                  <small v-if="index === taxConfig.taxBands.length - 1">Leave empty for no upper limit</small>
                </div>
                <div class="form-group">
                  <label>Tax Rate (%) *</label>
                  <input
                    v-model.number="band.rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    required
                    placeholder="e.g., 20.00"
                  />
                </div>
              </div>
            </div>
          </div>
          <button type="button" class="btn-add-band" @click="addTaxBand">
            + Add Another Tax Band
          </button>
          <small class="validation-note" v-if="hasBandValidationError">Ensure bands are continuous (next lower = prev upper + 0.01)</small>
        </div>
        <!-- NHIMA Contributions -->
        <div class="form-section">
          <h3>NHIMA Contributions (1% Employee on Gross after Bonuses)</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="nhimaEmployeeRate">Employee Contribution Rate (%) *</label>
              <input
                id="nhimaEmployeeRate"
                v-model.number="taxConfig.nhimaEmployeeRate"
                type="number"
                step="0.01"
                min="0"
                max="10"
                required
                placeholder="e.g., 1.00"
              />
              <small>Percentage of gross salary (after bonuses) deducted for NHIMA</small>
            </div>
            <div class="form-group">
              <label for="nhimaEmployerRate">Employer Contribution Rate (%) *</label>
              <input
                id="nhimaEmployerRate"
                v-model.number="taxConfig.nhimaEmployerRate"
                type="number"
                step="0.01"
                min="0"
                max="10"
                required
                placeholder="e.g., 1.00"
              />
              <small>Percentage of gross salary paid by employer</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="nhimaMaxSalary">Maximum Salary for NHIMA (ZMW)</label>
              <input
                id="nhimaMaxSalary"
                v-model.number="taxConfig.nhimaMaxSalary"
                type="number"
                step="0.01"
                min="0"
                placeholder="e.g., 50000.00"
              />
              <small>Salary cap for NHIMA contributions (optional)</small>
            </div>
          </div>
        </div>
        <!-- Other Deductions -->
        <div class="form-section">
          <h3>Other Statutory Deductions</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="napsaRate">NAPSA Employee Contribution Rate (%)</label>
              <input
                id="napsaRate"
                v-model.number="taxConfig.napsaRate"
                type="number"
                step="0.01"
                min="0"
                max="10"
                placeholder="e.g., 5.00"
              />
              <small>National Pension Scheme Authority employee contribution rate (cap at ZMW 1,708.20)</small>
            </div>
            <div class="form-group">
              <label for="napsaMaxSalary">NAPSA Maximum Assessable Salary (ZMW)</label>
              <input
                id="napsaMaxSalary"
                v-model.number="taxConfig.napsaMaxSalary"
                type="number"
                step="0.01"
                min="0"
                placeholder="e.g., 34164.00"
              />
              <small>Salary cap for NAPSA (5% yields max ZMW 1,708.20 employee contrib.)</small>
            </div>
          </div>
        </div>
        <!-- Pay Grades Configuration -->
        <div class="form-section">
          <h3>Pay Grades & Salary Bands</h3>
          <div class="pay-grades">
            <div v-for="(grade, index) in taxConfig.payGrades" :key="index" class="pay-grade">
              <div class="grade-header">
                <h4>Grade {{ grade.grade }}</h4>
                <button
                  v-if="taxConfig.payGrades.length > 1"
                  type="button"
                  class="btn-remove"
                  @click="removePayGrade(index)"
                >
                  Remove
                </button>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Grade Name *</label>
                  <input
                    v-model="grade.name"
                    type="text"
                    required
                    placeholder="e.g., Junior Staff, Management"
                  />
                </div>
                <div class="form-group">
                  <label>Minimum Salary (ZMW) *</label>
                  <input
                    v-model.number="grade.minSalary"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    placeholder="e.g., 5000.00"
                  />
                </div>
                <div class="form-group">
                  <label>Maximum Salary (ZMW) *</label>
                  <input
                    v-model.number="grade.maxSalary"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    placeholder="e.g., 10000.00"
                  />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group full-width">
                  <label>Description</label>
                  <textarea
                    v-model="grade.description"
                    rows="2"
                    placeholder="Brief description of this pay grade..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
          <button type="button" class="btn-add-grade" @click="addPayGrade">
            + Add Another Pay Grade
          </button>
        </div>
        <!-- Additional Settings -->
        <div class="form-section">
          <h3>Additional Settings</h3>
          <div class="form-row">
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="taxConfig.includeHousingAllowance" />
                Include Housing Allowance in Taxable Income
              </label>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="taxConfig.includeTransportAllowance" />
                Include Transport Allowance in Taxable Income
              </label>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="taxCalculationMethod">Tax Calculation Method *</label>
              <select id="taxCalculationMethod" v-model="taxConfig.taxCalculationMethod" required>
                <option value="cumulative">Cumulative (Progressive)</option>
                <option value="non_cumulative">Non-Cumulative</option>
              </select>
              <small>Cumulative: Tax calculated on year-to-date income</small>
            </div>
            <div class="form-group">
              <label for="roundingMethod">Rounding Method *</label>
              <select id="roundingMethod" v-model="taxConfig.roundingMethod" required>
                <option value="nearest">Nearest Kwacha</option>
                <option value="up">Round Up</option>
                <option value="down">Round Down</option>
                <option value="none">No Rounding</option>
              </select>
            </div>
          </div>
        </div>
        <!-- Action Buttons -->
        <div class="form-actions">
          <button type="button" class="btn-secondary" @click="resetToDefault">
            Reset to Default (2025 ZRA)
          </button>
          <button type="button" class="btn-secondary" @click="previewCalculation">
            Preview Calculation
          </button>
          <button type="submit" class="btn-primary" :disabled="saving || hasBandValidationError">
            {{ saving ? 'Saving...' : 'Save Tax Configuration' }}
          </button>
        </div>
      </form>
    </div>
    <!-- Preview Modal -->
    <div v-if="showPreview" class="modal-overlay" @click.self="closePreview">
      <div class="modal">
        <div class="modal-header">
          <h3>Tax Calculation Preview (Sample: Basic ZMW 7,556.80 → Gross ZMW 10,000)</h3>
          <button @click="closePreview" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <div class="preview-content">
            <h4>Allowances Breakdown</h4>
            <div class="calculation-breakdown">
              <div class="calculation-row"><span>Basic Pay:</span><span>ZMW 7,556.80</span></div>
              <div class="calculation-row"><span>Housing (25% Basic):</span><span>ZMW 1,889.20</span></div>
              <div class="calculation-row"><span>Transport:</span><span>ZMW 300.00</span></div>
              <div class="calculation-row"><span>Lunch:</span><span>ZMW 240.00</span></div>
              <div class="calculation-row total"><span>Gross Earnings:</span><span>ZMW 9,986.00</span></div>
            </div>
            <h4>Deductions Breakdown</h4>
            <div class="calculation-breakdown">
              <div class="calculation-row"><span>Taxable Income (Gross):</span><span>ZMW 9,986.00</span></div>
              <div class="calculation-row"><span>PAYE Tax:</span><span>ZMW 1,320.22</span></div>
              <div class="calculation-row"><span>NAPSA (5% Gross, capped):</span><span>ZMW 499.30</span></div>
              <div class="calculation-row"><span>NHIMA (1% Gross after bonuses):</span><span>ZMW 99.86</span></div>
              <div class="calculation-row total"><span>Net Salary:</span><span>ZMW 8,066.62</span></div>
            </div>
            <small>Full logic applied in backend (progressive PAYE, NAPSA cap ZMW 1,708.20, NHIMA on gross post-bonuses).</small>
          </div>
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
      pageName: 'Tax Configuration',
      saving: false,
      showPreview: false,
      hasBandValidationError: false,
      taxYears: [], // dynamically generated
      taxConfig: {
        taxYear: '', // current year default
        currency: 'ZMW',
        taxFreeThreshold: 0,
        annualTaxFree: 0,
        taxBands: [],
        nhimaEmployeeRate: 1,
        nhimaEmployerRate: 1,
        nhimaMaxSalary: null,
        napsaRate: 5,
        napsaMaxSalary: 34164,
        payGrades: [],
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
    }
  },
  methods: {
    getAxiosInstance() { return this.$axios || this.$http || axios; },
    async fetchTaxConfiguration() {
      try {
        const axiosInstance = this.getAxiosInstance();
        const response = await axiosInstance.get('/api/admin/tax-configuration');
        if (response.data && response.data.tax_configuration && response.data.tax_configuration.config_data) {
          this.taxConfig = { ...this.taxConfig, ...response.data.tax_configuration.config_data };
        }
      } catch (error) {
        console.error('Error fetching tax configuration:', error);
      }
    },
    validateTaxBands() {
      this.hasBandValidationError = false;
      if (!this.taxConfig.taxBands) return;
      for (let i = 1; i < this.taxConfig.taxBands.length; i++) {
        const prev = this.taxConfig.taxBands[i - 1];
        const curr = this.taxConfig.taxBands[i];
        if (prev.upperLimit && curr.lowerLimit !== prev.upperLimit + 0.01) {
          this.hasBandValidationError = true;
          return;
        }
      }
    },
    addTaxBand() {
      const lastBand = this.taxConfig.taxBands[this.taxConfig.taxBands.length - 1] || {};
      this.taxConfig.taxBands.push({
        lowerLimit: lastBand.upperLimit ? lastBand.upperLimit + 0.01 : 0,
        upperLimit: null,
        rate: null
      });
    },
    removeTaxBand(index) {
      if (this.taxConfig.taxBands.length > 1) this.taxConfig.taxBands.splice(index, 1);
    },
    addPayGrade() {
      const lastGrade = this.taxConfig.payGrades[this.taxConfig.payGrades.length - 1] || { grade: 'A', maxSalary: 0 };
      const nextGrade = String.fromCharCode((lastGrade.grade?.charCodeAt(0) || 64) + 1);
      this.taxConfig.payGrades.push({
        grade: nextGrade,
        name: '',
        minSalary: lastGrade.maxSalary + 0.01,
        maxSalary: null,
        description: ''
      });
    },
    removePayGrade(index) {
      if (this.taxConfig.payGrades.length > 1) this.taxConfig.payGrades.splice(index, 1);
    },
    async saveTaxConfiguration() {
      if (this.hasBandValidationError) return alert('Fix tax band continuity before saving.');
      this.saving = true;
      try {
        if (this.taxConfig.taxFreeThreshold != null) this.taxConfig.annualTaxFree = this.taxConfig.taxFreeThreshold * 12;
        const axiosInstance = this.getAxiosInstance();
        const response = await axiosInstance.post('/api/admin/update-tax-configuration', { taxConfig: this.taxConfig });
        if (response.data && response.data.tax_configuration && response.data.tax_configuration.config_data) {
          this.taxConfig = { ...this.taxConfig, ...response.data.tax_configuration.config_data };
        }
        alert(response.data.message || 'Tax configuration saved successfully!');
      } catch (error) {
        console.error('Error saving tax configuration:', error);
        alert(error.response?.data?.message || 'Failed to save tax configuration.');
      } finally { this.saving = false; }
    },
    async resetToDefault() {
      if (confirm('Reset to default 2025 Zambian tax rates?')) await this.fetchTaxConfiguration();
    },
    previewCalculation() { this.showPreview = true; },
    closePreview() { this.showPreview = false; }
  },
  mounted() {
    const currentYear = new Date().getFullYear();
    this.taxYears = Array.from({ length: 5 }, (_, i) => String(currentYear + i));
    this.taxConfig.taxYear = String(currentYear);
    this.fetchTaxConfiguration();
  }
};
</script>
<style scoped>
.validation-note {
  color: #e53e3e;
  font-size: 0.875rem;
  display: block;
  margin-top: 0.5rem;
}
.tax-configuration {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.page-header {
  margin-bottom: 2rem;
}
.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
}
.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin: 0;
}
.configuration-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow: hidden;
}
.card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f7fafc;
}
.card-header h2 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}
.card-header p {
  margin: 0;
  color: #718096;
}
.tax-form {
  padding: 2rem;
}
.form-section {
  margin-bottom: 2.5rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e2e8f0;
}
.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 1rem;
}
.form-section h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}
.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
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
  font-weight: 600;
  color: #4a5568;
}
.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}
.form-group small {
  margin-top: 0.5rem;
  color: #718096;
  font-size: 0.875rem;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  margin-top: 1.5rem;
}
.checkbox-label input[type="checkbox"] {
  margin: 0;
}
.tax-bands,
.pay-grades {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.tax-band,
.pay-grade {
  padding: 1.5rem;
  background: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.band-header,
.grade-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.band-header h4,
.grade-header h4 {
  margin: 0;
  color: #2d3748;
}
.btn-remove {
  background: #fed7d7;
  color: #c53030;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
}
.btn-remove:hover {
  background: #feb2b2;
}
.btn-add-band,
.btn-add-grade {
  background: #e6fffa;
  color: #234e52;
  border: 1px dashed #38b2ac;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}
.btn-add-band:hover,
.btn-add-grade:hover {
  background: #b2f5ea;
  border-style: solid;
}
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}
.btn-primary {
  background: #4299e1;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}
.btn-primary:hover:not(:disabled) {
  background: #3182ce;
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}
.btn-secondary:hover {
  background: #cbd5e0;
}
/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 {
  margin: 0;
  color: #2d3748;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}
.close-btn:hover {
  background: #f0f0f0;
}
.modal-body {
  padding: 1.5rem;
}
.preview-content h4 {
  margin: 0 0 1rem 0;
  color: #2d3748;
}
.calculation-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.calculation-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}
.calculation-row.total {
  font-weight: 700;
  color: #2d3748;
  border-bottom: none;
  border-top: 2px solid #e2e8f0;
  padding-top: 1rem;
  margin-top: 0.5rem;
}
@media (max-width: 768px) {
  .tax-configuration {
    padding: 1rem;
  }
  .tax-form {
    padding: 1rem;
  }
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  .form-actions {
    flex-direction: column;
  }
  .btn-primary,
  .btn-secondary {
    width: 100%;
  }
}
</style>