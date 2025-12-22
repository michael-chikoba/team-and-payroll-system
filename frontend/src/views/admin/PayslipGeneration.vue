<template>
  <div class="payslip-generation">
    <!-- Header -->
    <header class="app-header">
      <div class="header-content">
        <h1 class="header-title">Payslip Management</h1>
        <p class="header-subtitle">Generate, view, and distribute employee payslips.</p>
        <div class="filter-indicator" v-if="getFilterDisplayText">
          <span class="filter-label">Current View:</span>
          <span class="filter-value">{{ getFilterDisplayText }}</span>
        </div>
      </div>
      <div class="header-actions">
        <button @click="showBulkGenerate = true" class="btn-secondary-icon">
          <span class="icon">📑</span>
          <span>Bulk Generate</span>
        </button>
        <button @click="showGenerateModal = true" class="btn-primary-icon">
          <span class="icon">➕</span>
          <span>Generate Single</span>
        </button>
      </div>
    </header>

    <!-- Filters -->
    <div class="filter-controls-card">
      <div class="filter-group-container">
        
        <!-- Business Filter -->
        <div class="filter-group">
          <label class="filter-label">Business</label>
          <select v-model="filters.business_id" class="select-input" @change="handleBusinessChange">
            <option value="">All Businesses</option>
            <option v-for="business in businesses" :key="business.id" :value="business.id">
              {{ business.name }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label class="filter-label">Pay Period</label>
          <select v-model="filters.pay_period" class="select-input">
            <option value="current">Current Month</option>
            <option value="last">Last Month</option>
            <option value="custom">Select Specific Month</option>
          </select>
        </div>
        
        <div class="filter-group" v-if="filters.pay_period === 'custom'">
          <label class="filter-label">Select Month</label>
          <input type="month" v-model="filters.custom_month" class="date-input">
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Department</label>
          <select v-model="filters.department" class="select-input">
            <option value="">All Departments</option>
            <option value="IT">IT</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Sales">Sales</option>
            <option value="Operations">Operations</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Status</label>
          <select v-model="filters.status" class="select-input">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="generated">Generated</option>
            <option value="paid">Paid</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-if="loading" class="state-indicator loading-state">
      <div class="spinner"></div>
      <p>Loading payslip records...</p>
    </div>
    
    <div v-else-if="error" class="state-indicator error-state">
      <span class="error-icon">⚠️</span>
      <p>{{ error }}</p>
      <button @click="fetchPayslips" class="btn-text">Try Again</button>
    </div>
    
    <div v-else class="payslips-dashboard-grid">
      <div class="payslip-grid-container" v-if="payslips.length > 0">
        <!-- Payslip Card -->
        <div v-for="payslip in payslips" :key="payslip.id" class="payslip-item-card">
          <div class="card-header-status">
            <div>
              <h3 class="employee-name">{{ getEmployeeName(payslip) }}</h3>
              <p class="employee-meta">{{ payslip.employee_id || '#' }} • {{ payslip.department || 'General' }}</p>
              <!-- Show Business Name if All Businesses selected -->
              <span v-if="!filters.business_id && payslip.business_name" class="business-tag">
                🏢 {{ payslip.business_name }}
              </span>
            </div>
            <span :class="['status-badge', `status-${payslip.status}`]">
              {{ formatStatus(payslip.status) }}
            </span>
          </div>
          
          <div class="payslip-summary-details">
            <div class="detail-pair">
              <span class="detail-label">Period</span>
              <span class="detail-value">{{ formatDate(payslip.pay_period_start) }} - {{ formatDate(payslip.pay_period_end) }}</span>
            </div>
            <div class="detail-pair">
              <span class="detail-label">Gross Pay</span>
              <span class="detail-value">K{{ formatNumber(payslip.gross_salary) }}</span>
            </div>
            <div class="detail-pair net-pay-row">
              <span class="detail-label">Net Pay</span>
              <span class="detail-net-pay">K{{ formatNumber(payslip.net_pay) }}</span>
            </div>
          </div>

          <div class="card-actions-footer">
            <button @click="viewPayslip(payslip)" class="btn-action view" title="View Details">
              👁️ View
            </button>
            <button @click="downloadPayslip(payslip)" class="btn-action download" title="Download PDF">
              ⬇️ PDF
            </button>
            <button v-if="!payslip.is_sent" @click="sendPayslip(payslip)" class="btn-action send" title="Email to Employee">
              📧 Send
            </button>
          </div>
        </div>
      </div>
      
      <div v-else class="state-indicator empty-state">
        <div class="empty-icon">📂</div>
        <h3>No Payslips Found</h3>
        <p>No records match your current filters.</p>
        <button @click="showGenerateModal = true" class="btn-primary-icon mt-3">Generate New Payslip</button>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: View Payslip Detail (Dynamic)           -->
    <!-- ============================================== -->
    <div v-if="selectedPayslip" class="modal-overlay" @click.self="selectedPayslip = null">
      <div class="modal-card large-modal">
        <div class="modal-header">
          <h2 class="modal-title">Payslip Details</h2>
          <button @click="selectedPayslip = null" class="close-btn">×</button>
        </div>
     
        <div class="modal-body payslip-detail-view">
          <!-- Header -->
          <div class="payslip-branding">
            <div class="company-details">
              <h2>{{ selectedPayslip.business_name || 'Castle Holdings Ltd' }}</h2>
              <p>54 Seble Road, Lusaka, Zambia</p>
            </div>
            <div class="payslip-period-badge">
              <span>{{ formatDate(selectedPayslip.pay_period_end, 'month') }}</span>
            </div>
          </div>

          <!-- Employee Info -->
          <div class="info-grid-box">
            <div class="info-cell">
              <label>Employee Name</label>
              <span>{{ getEmployeeName(selectedPayslip) }}</span>
            </div>
            <div class="info-cell">
              <label>Employee ID</label>
              <span>{{ selectedPayslip.employee_id || 'N/A' }}</span>
            </div>
            <div class="info-cell">
              <label>Department</label>
              <span>{{ selectedPayslip.department || 'N/A' }}</span>
            </div>
            <div class="info-cell">
              <label>Pay Date</label>
              <span>{{ formatDate(selectedPayslip.payment_date) }}</span>
            </div>
          </div>
       
          <div class="earnings-deductions-grid">
            <!-- Dynamic Earnings -->
            <section class="detail-section">
              <h3 class="section-title earnings">Earnings</h3>
              <div class="amount-list">
                <div class="amount-item">
                  <label>Basic Salary</label>
                  <span>K{{ formatNumber(selectedPayslip.basic_salary) }}</span>
                </div>
                <div class="amount-item" v-if="selectedPayslip.house_allowance > 0">
                  <label>Housing Allowance</label>
                  <span>K{{ formatNumber(selectedPayslip.house_allowance) }}</span>
                </div>
                <div class="amount-item" v-if="selectedPayslip.transport_allowance > 0">
                  <label>Transport Allowance</label>
                  <span>K{{ formatNumber(selectedPayslip.transport_allowance) }}</span>
                </div>
                <div class="amount-item" v-if="selectedPayslip.lunch_allowance > 0">
                  <label>Lunch/Other Allowance</label>
                  <span>K{{ formatNumber(selectedPayslip.lunch_allowance) }}</span>
                </div>
                <div class="amount-item" v-if="selectedPayslip.overtime_pay > 0">
                  <label>Overtime Pay</label>
                  <span>K{{ formatNumber(selectedPayslip.overtime_pay) }}</span>
                </div>
                <div class="amount-item" v-if="selectedPayslip.bonuses > 0">
                  <label>Bonuses</label>
                  <span>K{{ formatNumber(selectedPayslip.bonuses) }}</span>
                </div>
                <div class="amount-item total">
                  <label>Gross Earnings</label>
                  <span>K{{ formatNumber(selectedPayslip.gross_salary) }}</span>
                </div>
              </div>
            </section>
         
            <!-- Dynamic Deductions -->
            <section class="detail-section">
              <h3 class="section-title deductions">Deductions</h3>
              <div class="amount-list">
                <!-- Iterate through dynamically computed deductions list -->
                <div v-for="(item, idx) in getDynamicDeductions(selectedPayslip)" :key="idx" class="amount-item">
                  <label>{{ item.name }}</label>
                  <span>K{{ formatNumber(item.amount) }}</span>
                </div>
                
                <div class="amount-item total">
                  <label>Total Deductions</label>
                  <span>K{{ formatNumber(selectedPayslip.total_deductions) }}</span>
                </div>
              </div>
            </section>
          </div>
       
          <!-- Net Pay -->
          <div class="net-pay-banner">
            <div class="label">NET PAYABLE</div>
            <div class="amount">K{{ formatNumber(selectedPayslip.net_pay) }}</div>
            <div class="words">{{ convertToWords(selectedPayslip.net_pay) }}</div>
          </div>
        </div>
     
        <div class="modal-footer">
          <button @click="downloadPayslip(selectedPayslip)" class="btn-primary-icon">
            Download PDF
          </button>
          <button @click="selectedPayslip = null" class="btn-secondary">
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Generate Single Modal -->
    <div v-if="showGenerateModal" class="modal-overlay" @click.self="closeModals">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="modal-title">Generate Single Payslip</h2>
          <button @click="closeModals" class="close-btn">×</button>
        </div>
        <form @submit.prevent="generatePayslip" class="modal-body-content">
          <!-- Only show employees for the selected business -->
          <div class="form-group">
            <label class="form-label">Employee</label>
            <select v-model="generateForm.employee_id" @change="onEmployeeSelected($event.target.value)" required class="select-input">
              <option value="" disabled>Select Employee</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                <!-- UPDATED: Use helper directly instead of constructing object -->
                {{ getEmployeeName(emp) }}
              </option>
            </select>
            <small v-if="employees.length === 0" class="text-muted">No employees found for selected business.</small>
          </div>
          
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label">Start Date</label>
              <input type="date" v-model="generateForm.pay_period_start" required class="text-input">
            </div>
            <div class="form-group">
              <label class="form-label">End Date</label>
              <input type="date" v-model="generateForm.pay_period_end" required class="text-input">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Basic Salary</label>
            <input type="number" v-model.number="generateForm.basic_salary" class="text-input" step="0.01">
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Generating...' : 'Generate' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Generate Modal -->
    <div v-if="showBulkGenerate" class="modal-overlay" @click.self="closeModals">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="modal-title">Bulk Generate</h2>
          <button @click="closeModals" class="close-btn">×</button>
        </div>
        <form @submit.prevent="bulkGeneratePayslips" class="modal-body-content">
          <div class="form-row-grid">
            <div class="form-group">
              <label class="form-label">Period Start</label>
              <input type="date" v-model="bulkForm.pay_period_start" required class="text-input">
            </div>
            <div class="form-group">
              <label class="form-label">Period End</label>
              <input type="date" v-model="bulkForm.pay_period_end" required class="text-input">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select v-model="bulkForm.department" class="select-input">
              <option value="">All Departments</option>
              <option value="IT">IT</option>
              <option value="HR">HR</option>
              <option value="Finance">Finance</option>
            </select>
          </div>
          
          <div class="selection-summary">
            <p>
              Will generate payslips for <strong>{{ availableEmployees.length }}</strong> employees 
              <span v-if="filters.business_id">in selected business</span>.
            </p>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="submitting || availableEmployees.length === 0">
              {{ submitting ? 'Processing...' : 'Generate All' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import jsPDF from 'jspdf';

// --- State ---
const loading = ref(false);
const error = ref(null);
const payslips = ref([]);
const employees = ref([]);
const businesses = ref([]); // Store list of businesses
const selectedPayslip = ref(null);
const showGenerateModal = ref(false);
const showBulkGenerate = ref(false);
const submitting = ref(false);

const filters = reactive({
  pay_period: 'current',
  department: '',
  status: '',
  custom_month: '',
  business_id: '' // Filter for business
});

const generateForm = reactive({
  employee_id: '',
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  basic_salary: 0,
  overtime_hours: 0,
  overtime_rate: 0
});

const bulkForm = reactive({
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  department: '',
  employee_ids: []
});

// --- Lifecycle ---
onMounted(() => {
  initializeDates();
  fetchBusinesses(); // Load businesses on mount
  fetchEmployees();
  fetchPayslips();
});

// --- Computed ---
const getFilterDisplayText = computed(() => {
  let text = [];
  
  if (filters.business_id) {
    const b = businesses.value.find(b => b.id === filters.business_id);
    if(b) text.push(b.name);
  }

  if (filters.pay_period === 'current') text.push('Current Month');
  else if (filters.pay_period === 'last') text.push('Last Month');
  else if (filters.custom_month) text.push(`Month: ${filters.custom_month}`);
  
  return text.length ? text.join(' • ') : 'All Records';
});

// Available employees logic now respects the Business Filter
const availableEmployees = computed(() => {
  return employees.value.filter(emp => 
    !bulkForm.department || emp.department === bulkForm.department
  );
});

// --- Methods: Data Fetching ---
const initializeDates = () => {
  const now = new Date();
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
  const today = now.toISOString().split('T')[0];

  generateForm.pay_period_start = firstDay;
  generateForm.pay_period_end = lastDay;
  generateForm.payment_date = today;

  bulkForm.pay_period_start = firstDay;
  bulkForm.pay_period_end = lastDay;
  bulkForm.payment_date = today;
};

// 1. Fetch Businesses List
const fetchBusinesses = async () => {
  try {
    const res = await axios.get('/api/admin/businesses');
    businesses.value = res.data.data || res.data.businesses || res.data || [];
  } catch (err) {
    console.error('Failed to load businesses', err);
  }
};

// 2. Fetch Employees (Filtered by Business)
const fetchEmployees = async () => {
  try {
    const params = {};
    if (filters.business_id) {
      params.business_id = filters.business_id;
    }
    
    const res = await axios.get('/api/admin/employees', { params });
    employees.value = res.data.data || res.data.employees || [];
  } catch (err) {
    console.error('Failed to load employees', err);
  }
};

// 3. Fetch Payslips (Filtered by Business)
const fetchPayslips = async () => {
  loading.value = true;
  error.value = null;
  try {
    const params = {
      department: filters.department || undefined,
      status: filters.status || undefined,
      business_id: filters.business_id || undefined 
    };

    if (filters.pay_period === 'current') {
      params.pay_period = 'current';
    } else if (filters.pay_period === 'last') {
      params.pay_period = 'last';
    } else if (filters.pay_period === 'custom' && filters.custom_month) {
      const [y, m] = filters.custom_month.split('-');
      params.start = `${y}-${m}-01`;
      params.end = `${y}-${m}-31`; 
    }

    const res = await axios.get('/api/admin/payslips', { params });
    payslips.value = res.data.data || res.data;
  } catch (err) {
    error.value = "Failed to load payslips.";
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const handleBusinessChange = () => {
  // When business filter changes, reload everything
  fetchEmployees();
  fetchPayslips();
};

// --- Methods: Logic ---

// UPDATED: Robust Employee Name Getter
const getEmployeeName = (emp) => {
  if (!emp) return 'Unknown Employee';
  
  // 1. Check for nested User relationship (Standard Employee model)
  if (emp.user && emp.user.first_name) {
    return `${emp.user.first_name} ${emp.user.last_name || ''}`.trim();
  }
  
  // 2. Check for flat properties (Payslip or summary object)
  if (emp.employee_name) return emp.employee_name;
  if (emp.first_name) {
    return `${emp.first_name} ${emp.last_name || ''}`.trim();
  }
  if (emp.name) return emp.name; // Fallback to 'name' field if it exists directly

  return 'Unknown';
};

const getDynamicDeductions = (payslip) => {
  let list = [];
  
  // 1. Always add PAYE if exists
  if (payslip.paye > 0) list.push({ name: 'PAYE Tax', amount: payslip.paye });

  // 2. Try to get dynamic breakdown from JSON
  if (payslip.breakdown?.deductions_breakdown?.statutory_breakdown) {
    payslip.breakdown.deductions_breakdown.statutory_breakdown.forEach(d => {
      list.push({ name: d.name, amount: d.amount });
    });
  } 
  // 3. Fallback to legacy fields if breakdown is missing
  else {
    if (payslip.napsa > 0) list.push({ name: 'NAPSA', amount: payslip.napsa });
    if (payslip.nhima > 0) list.push({ name: 'NHIMA', amount: payslip.nhima });
    if (payslip.pension > 0) list.push({ name: 'Pension', amount: payslip.pension });
  }

  // 4. Other deductions
  if (payslip.other_deductions > 0) list.push({ name: 'Other Deductions', amount: payslip.other_deductions });

  return list;
};

const onEmployeeSelected = (id) => {
  const emp = employees.value.find(e => e.id == id);
  if (emp) {
    generateForm.basic_salary = emp.base_salary;
  }
};

const viewPayslip = (payslip) => {
  selectedPayslip.value = payslip;
};

const generatePayslip = async () => {
  submitting.value = true;
  try {
    const payload = { ...generateForm, generate_pdf: true };
    await axios.post('/api/admin/payslips', payload);
    await fetchPayslips();
    closeModals();
    alert('Payslip Generated!');
  } catch (err) {
    alert(err.response?.data?.message || 'Generation failed');
  } finally {
    submitting.value = false;
  }
};

const bulkGeneratePayslips = async () => {
  submitting.value = true;
  try {
    const ids = availableEmployees.value.map(e => e.id);
    
    if (ids.length === 0) {
      alert('No employees found to generate payslips for.');
      submitting.value = false;
      return;
    }

    for (const id of ids) {
      const emp = employees.value.find(e => e.id == id);
      await axios.post('/api/admin/payslips', {
        employee_id: id,
        pay_period_start: bulkForm.pay_period_start,
        pay_period_end: bulkForm.pay_period_end,
        payment_date: bulkForm.payment_date,
        basic_salary: emp.base_salary,
        generate_pdf: true
      });
    }
    await fetchPayslips();
    closeModals();
    alert('Bulk Generation Complete');
  } catch (err) {
    console.error(err);
    alert('Some payslips might have failed.');
  } finally {
    submitting.value = false;
  }
};

const downloadPayslip = async (payslip) => {
  try {
    if (!payslip.pdf_available && !payslip.pdf_path) {
      generateClientPdf(payslip);
      return;
    }
    
    const response = await axios.get(`/api/admin/payslips/${payslip.id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `payslip_${payslip.employee_id}.pdf`);
    document.body.appendChild(link);
    link.click();
  } catch (err) {
    console.error('Download failed, trying client generation', err);
    generateClientPdf(payslip);
  }
};

const generateClientPdf = (payslip) => {
  const doc = new jsPDF();
  const companyName = payslip.business_name || 'Castle Holdings Ltd';
  
  doc.setFontSize(18);
  doc.text('PAYSLIP', 105, 20, { align: 'center' });
  
  doc.setFontSize(12);
  doc.text(companyName, 20, 40);
  doc.setFontSize(10);
  doc.text(`Period: ${formatDate(payslip.pay_period_start)} to ${formatDate(payslip.pay_period_end)}`, 20, 50);
  doc.text(`Employee: ${getEmployeeName(payslip)}`, 20, 60);
  
  let y = 80;
  doc.text('Earnings:', 20, y);
  doc.text(`Basic: K${formatNumber(payslip.basic_salary)}`, 120, y, { align: 'right' });
  
  y += 10;
  doc.text('Deductions:', 20, y);
  
  const deductions = getDynamicDeductions(payslip);
  deductions.forEach(d => {
    y += 7;
    doc.text(`${d.name}`, 30, y);
    doc.text(`K${formatNumber(d.amount)}`, 120, y, { align: 'right' });
  });
  
  y += 15;
  doc.setFontSize(14);
  doc.text(`Net Pay: K${formatNumber(payslip.net_pay)}`, 120, y, { align: 'right' });
  
  doc.save(`payslip_client_${payslip.id}.pdf`);
};

const sendPayslip = async (payslip) => {
  if(!confirm('Send email notification?')) return;
  try {
    await axios.post(`/api/admin/payslips/${payslip.id}/send`);
    alert('Email sent.');
  } catch (e) { alert('Failed to send email'); }
};

// --- Helpers ---
const closeModals = () => { showGenerateModal.value = false; showBulkGenerate.value = false; };
const formatNumber = (n) => new Intl.NumberFormat('en-ZM', { minimumFractionDigits: 2 }).format(n || 0);
const formatDate = (d, type='full') => {
  if(!d) return '-';
  const date = new Date(d);
  if(type === 'month') return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  return date.toLocaleDateString('en-GB'); 
};
const formatStatus = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown';
const convertToWords = (amount) => {
  return `${formatNumber(amount)} Kwacha`; 
};

// Watch filters
watch(() => filters.pay_period, fetchPayslips);
watch(() => filters.department, fetchPayslips);
watch(() => filters.status, fetchPayslips);
</script>

<style scoped>
/* Core Layout */
.payslip-generation { max-width: 1200px; margin: 0 auto; padding: 2rem; font-family: 'Inter', sans-serif; color: #1e293b; }

/* Header */
.app-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.header-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.header-subtitle { color: #64748b; margin: 0.25rem 0 0; font-size: 0.9rem; }
.filter-indicator { margin-top: 0.5rem; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; background: #f1f5f9; padding: 0.25rem 0.75rem; border-radius: 20px; color: #475569; }
.header-actions { display: flex; gap: 0.75rem; }

/* Controls */
.filter-controls-card { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
.filter-group-container { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.filter-group { flex: 1; min-width: 200px; }
.filter-label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.05em; }
.select-input, .date-input { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; transition: border 0.2s; }
.select-input:focus, .date-input:focus { border-color: #6366f1; }

/* Grid */
.payslip-grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.5rem; }
.payslip-item-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; transition: transform 0.2s, box-shadow 0.2s; }
.payslip-item-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

/* Card Content */
.card-header-status { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.employee-name { font-weight: 600; color: #0f172a; margin: 0; font-size: 1rem; }
.employee-meta { font-size: 0.8rem; color: #64748b; margin: 0.1rem 0 0; }
.business-tag { font-size: 0.75rem; background: #f0f9ff; color: #0284c7; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; }
.status-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; text-transform: uppercase; }
.status-paid { background: #dcfce7; color: #166534; }
.status-generated { background: #e0f2fe; color: #0369a1; }
.status-draft { background: #fef9c3; color: #854d0e; }

.payslip-summary-details { margin: 1rem 0; padding: 0.75rem; background: #f8fafc; border-radius: 8px; }
.detail-pair { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem; }
.detail-label { color: #64748b; }
.detail-value { font-weight: 500; color: #334155; }
.net-pay-row { border-top: 1px dashed #cbd5e1; margin-top: 0.5rem; padding-top: 0.5rem; font-weight: 700; font-size: 0.95rem; }
.detail-net-pay { color: #059669; }

.card-actions-footer { display: flex; gap: 0.5rem; justify-content: flex-end; }
.btn-action { padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0; background: white; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
.btn-action:hover { background: #f1f5f9; border-color: #cbd5e1; }
.btn-action.download { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
.btn-action.view { color: #475569; }

/* Buttons */
.btn-primary-icon { background: #4f46e5; color: white; padding: 0.6rem 1.2rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
.btn-primary-icon:hover { background: #4338ca; }
.btn-secondary-icon { background: white; border: 1px solid #cbd5e1; color: #475569; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
.btn-secondary-icon:hover { background: #f8fafc; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal-card { background: white; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
.large-modal { max-width: 800px; }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
.modal-title { margin: 0; font-size: 1.1rem; color: #0f172a; }
.close-btn { background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
.modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
.modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.75rem; background: #f8fafc; }

/* Payslip View Styling */
.payslip-branding { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #0f172a; padding-bottom: 1rem; }
.company-details h2 { margin: 0; color: #0f172a; }
.payslip-period-badge { margin-top: 0.5rem; display: inline-block; background: #f1f5f9; padding: 0.25rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem; }

.info-grid-box { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #f8fafc; padding: 1rem; border-radius: 8px; }
.info-cell label { display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 0.25rem; }
.info-cell span { font-weight: 600; color: #0f172a; font-size: 1rem; }

.earnings-deductions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
.section-title { font-size: 0.9rem; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem; }
.section-title.earnings { color: #0369a1; border-color: #bae6fd; }
.section-title.deductions { color: #be123c; border-color: #fecdd3; }

.amount-item { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.35rem 0; border-bottom: 1px dashed #f1f5f9; }
.amount-item label { color: #475569; }
.amount-item span { font-family: monospace; font-weight: 600; font-size: 0.95rem; }
.amount-item.total { border-top: 2px solid #e2e8f0; margin-top: 0.5rem; padding-top: 0.5rem; font-weight: 700; font-size: 1rem; color: #0f172a; border-bottom: none; }

.net-pay-banner { background: #0f172a; color: white; padding: 1.5rem; text-align: center; border-radius: 12px; }
.net-pay-banner .label { font-size: 0.8rem; letter-spacing: 0.1em; opacity: 0.8; }
.net-pay-banner .amount { font-size: 2.5rem; font-weight: 700; margin: 0.25rem 0; }
.net-pay-banner .words { font-style: italic; opacity: 0.7; font-size: 0.9rem; }

/* Responsive */
@media (max-width: 768px) {
  .earnings-deductions-grid { grid-template-columns: 1fr; }
  .header-actions { flex-direction: column; }
  .filter-group-container { flex-direction: column; }
}
</style>