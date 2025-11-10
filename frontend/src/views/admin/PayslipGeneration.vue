<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

// --- State Management ---
const loading = ref(false);
const error = ref(null);
const payslips = ref([]);
const employees = ref([]);
const selectedPayslip = ref(null);
const showGenerateModal = ref(false);
const showBulkGenerate = ref(false);
const submitting = ref(false);
const formError = ref(null);

const filters = reactive({
  pay_period: 'current',
  department: '',
  status: '',
  custom_start: '',
  custom_end: ''
});

const generateForm = reactive({
  employee_id: '',
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  basic_salary: 0,
  house_allowance: 0,
  transport_allowance: 0,
  other_allowances: 0,
  overtime_hours: 0,
  overtime_rate: 0,
  napsa: 0,
  paye: 0,
  nhima: 0,
  other_deductions: 0,
  generate_pdf: true
});

const bulkForm = reactive({
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  department: '',
  employee_ids: [],
});

// Watch for employee selection and auto-populate basic salary
const onEmployeeSelected = (employeeId) => {
  const employee = employees.value.find(emp => emp.id === parseInt(employeeId));
  if (employee && employee.base_salary) {
    generateForm.basic_salary = parseFloat(employee.base_salary);
  }
};

// --- Computed Properties ---
const availableEmployees = computed(() => {
  return employees.value.filter(emp =>
    !bulkForm.department || emp.department === bulkForm.department
  );
});

const selectedBulkEmployees = computed(() => {
  return employees.value.filter(emp =>
    bulkForm.employee_ids.includes(emp.id)
  );
});

// --- Core Calculation Functions ---
const calculateTotalEarnings = (form) => {
  const overtime = (form.overtime_hours || 0) * (form.overtime_rate || 0);
  return (form.basic_salary || 0) +
         (form.house_allowance || 0) +
         (form.transport_allowance || 0) +
         (form.other_allowances || 0) +
         overtime;
};

const calculateTotalDeductions = (form) => {
  return (form.napsa || 0) +
         (form.paye || 0) +
         (form.nhima || 0) +
         (form.other_deductions || 0);
};

const calculateNetPay = () => {
  return calculateTotalEarnings(generateForm) - calculateTotalDeductions(generateForm);
};

// --- Helper Methods for Employee Data ---
const getEmployeeName = (employee) => {
  if (employee.first_name && employee.last_name) {
    return `${employee.first_name} ${employee.last_name}`.trim()
  } else if (employee.user && employee.user.first_name) {
    return `${employee.user.first_name} ${employee.user.last_name || ''}`.trim()
  } else if (employee.name) {
    return employee.name
  } else if (employee.full_name) {
    return employee.full_name
  }
  return 'N/A'
};

// --- Lifecycle and Initial Data Fetch ---
onMounted(() => {
  initializeDates();
  fetchPayslips();
  fetchEmployees();
});

// --- Helper Methods ---
const initializeDates = () => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

  generateForm.pay_period_start = formatDateForInput(firstDay);
  generateForm.pay_period_end = formatDateForInput(lastDay);
  generateForm.payment_date = formatDateForInput(today);

  bulkForm.pay_period_start = formatDateForInput(firstDay);
  bulkForm.pay_period_end = formatDateForInput(lastDay);
  bulkForm.payment_date = formatDateForInput(today);
};

const fetchPayslips = async () => {
  loading.value = true;
  error.value = null;

  try {
    const params = new URLSearchParams();
    if (filters.pay_period === 'custom') {
      params.append('start', filters.custom_start);
      params.append('end', filters.custom_end);
    } else {
      params.append('pay_period', filters.pay_period);
    }
    if (filters.department) params.append('department', filters.department);
    if (filters.status) params.append('status', filters.status);
    
    const response = await axios.get('/api/admin/payslips', { params });
    payslips.value = response.data.data || response.data;
  } catch (err) {
    error.value = 'Failed to fetch payslips';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const fetchEmployees = async () => {
  try {
    console.log('Fetching employees...');
    const response = await axios.get('/api/admin/employees');
   
    console.log('Employees response:', response.data);
   
    // Handle different response structures
    let employeesData = [];
    if (response.data && response.data.employees) {
      employeesData = response.data.employees;
    } else if (response.data && response.data.data) {
      employeesData = response.data.data;
    } else {
      employeesData = response.data || [];
    }
   
    employees.value = employeesData;
    console.log('Processed employees:', employees.value);
  } catch (err) {
    console.error('Failed to fetch employees:', err);
  }
};

const generatePayslip = async () => {
  submitting.value = true;
  formError.value = null;

  // Validate required fields
  if (!generateForm.employee_id || !generateForm.pay_period_start || !generateForm.pay_period_end || !generateForm.payment_date || generateForm.basic_salary <= 0) {
    formError.value = 'Please fill in all required fields correctly.';
    submitting.value = false;
    return;
  }

  try {
    // Calculate derived values before sending
    const formData = {
      ...generateForm,
      employee_id: generateForm.employee_id.toString(),
      gross_salary: calculateTotalEarnings(generateForm),
      total_deductions: calculateTotalDeductions(generateForm),
      net_pay: calculateNetPay(),
      overtime_pay: (generateForm.overtime_hours || 0) * (generateForm.overtime_rate || 0)
    };
    const response = await axios.post('/api/admin/payslips', formData);
    payslips.value.unshift(response.data.data || response.data);
    closeModals();
  } catch (err) {
    formError.value = err.response?.data?.message || 'Failed to generate payslip';
    console.error(err);
  } finally {
    submitting.value = false;
  }
};
const bulkGeneratePayslips = async () => {
  submitting.value = true;
  formError.value = null;

  if (bulkForm.employee_ids.length === 0) {
    formError.value = 'Please select at least one employee.';
    submitting.value = false;
    return;
  }

  try {
    const promises = bulkForm.employee_ids.map(async (id) => {
      const emp = employees.value.find(e => e.id === id);
      if (!emp) return null;
      const formData = {
        employee_id: id.toString(),
        pay_period_start: bulkForm.pay_period_start,
        pay_period_end: bulkForm.pay_period_end,
        payment_date: bulkForm.payment_date,
        basic_salary: parseFloat(emp.base_salary) || 0,
        house_allowance: 0,
        transport_allowance: 0,
        other_allowances: 0,
        overtime_hours: 0,
        overtime_rate: 0,
        napsa: 0,
        paye: 0,
        nhima: 0,
        other_deductions: 0,
        generate_pdf: true
      };
      // Calculate derived
      formData.gross_salary = calculateTotalEarnings(formData);
      formData.total_deductions = calculateTotalDeductions(formData);
      formData.net_pay = formData.gross_salary - formData.total_deductions;
      formData.overtime_pay = 0;

      const response = await axios.post('/api/admin/payslips', formData);
      return response.data.data || response.data;
    });
    const results = await Promise.all(promises);
    const newPayslips = results.filter(Boolean);
    payslips.value.unshift(...newPayslips);
    closeModals();
  } catch (err) {
    formError.value = 'Failed to generate payslips';
    console.error(err);
  } finally {
    submitting.value = false;
  }
};

const viewPayslip = (payslip) => {
  selectedPayslip.value = payslip;
};

const downloadPayslip = async (payslip) => {
  try {
    const response = await axios.get(`/api/payslips/${payslip.id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `payslip-${payslip.id}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Failed to download payslip:', err);
    alert('Failed to download payslip');
  }
};

const sendPayslip = async (payslip) => {
  try {
    await axios.post(`/api/payslips/${payslip.id}/send`);
    const index = payslips.value.findIndex(p => p.id === payslip.id);
    if (index !== -1) {
      payslips.value[index].status = 'paid';
      payslips.value[index].is_sent = true;
    }
    alert(`Payslip sent to ${payslip.employee_name} successfully!`);
  } catch (err) {
    console.error('Failed to send payslip:', err);
    alert('Failed to send payslip');
  }
};

const removeEmployee = (employeeId) => {
  bulkForm.employee_ids = bulkForm.employee_ids.filter(id => id !== employeeId);
};

const closeModals = () => {
  showGenerateModal.value = false;
  showBulkGenerate.value = false;
  formError.value = null;
  Object.assign(generateForm, {
    employee_id: '',
    basic_salary: 0, house_allowance: 0, transport_allowance: 0,
    other_allowances: 0, overtime_hours: 0, overtime_rate: 0,
    napsa: 0, paye: 0, nhima: 0, other_deductions: 0
  });
  bulkForm.employee_ids = [];
};

// --- Formatting & Utility Functions ---
const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num || 0);
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateForInput = (date) => {
  return date.toISOString().split('T')[0];
};

const formatStatus = (status) => {
  const statusMap = {
    draft: 'Draft',
    generated: 'Generated',
    paid: 'Paid'
  };
  return statusMap[status] || status;
};

const convertToWords = (amount) => {
  if (amount === 0) return 'Zero Kwacha Only';
  const wholeAmount = Math.floor(amount);
  const cents = Math.round((amount - wholeAmount) * 100);
  let words = `${formatNumber(wholeAmount)} Kwacha`;
  if (cents > 0) {
    words += ` and ${cents} Ngwee`;
  }
  return words + ' Only';
};

// Exposing functions needed in the template
defineExpose({
  fetchPayslips,
  generatePayslip,
  bulkGeneratePayslips,
  viewPayslip,
  downloadPayslip,
  sendPayslip,
  removeEmployee,
  closeModals,
  calculateNetPay,
  calculateTotalEarnings,
  calculateTotalDeductions,
  formatNumber,
  formatDate,
  formatStatus,
  convertToWords,
  calculatePayslipEarnings: calculateTotalEarnings,
  calculatePayslipDeductions: calculateTotalDeductions,
  getEmployeeName
});
</script>

<template>
  <div class="payslip-generation">
    <header class="app-header">
      <div class="header-content">
        <h1 class="header-title">Eagle pay Zambia</h1>
        <p class="header-subtitle">Generate and manage employee payslips for castle holdings</p>
      </div>
      <div class="header-actions">
        <button @click="showBulkGenerate = true" class="btn-secondary-icon">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 11.586V3a1 1 0 112 0v8.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
          <span>Bulk Generate</span>
        </button>
        <button @click="showGenerateModal = true" class="btn-primary-icon">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          <span>Generate Payslip</span>
        </button>
      </div>
    </header>

    <div class="filter-controls-card">
      <div class="filter-group-container">
        <div class="filter-group">
          <label class="filter-label">Pay Period</label>
          <select v-model="filters.pay_period" @change="fetchPayslips" class="select-input">
            <option value="current">Current Month</option>
            <option value="last">Last Month</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        <div class="filter-group" v-if="filters.pay_period === 'custom'">
          <label class="filter-label">From</label>
          <input type="month" v-model="filters.custom_start" @change="fetchPayslips" class="date-input">
        </div>
        <div class="filter-group" v-if="filters.pay_period === 'custom'">
          <label class="filter-label">To</label>
          <input type="month" v-model="filters.custom_end" @change="fetchPayslips" class="date-input">
        </div>
        <div class="filter-group">
          <label class="filter-label">Department</label>
          <select v-model="filters.department" @change="fetchPayslips" class="select-input">
            <option value="">All Departments</option>
            <option value="IT">IT</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Sales">Sales</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Status</label>
          <select v-model="filters.status" @change="fetchPayslips" class="select-input">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="generated">Generated</option>
            <option value="paid">Paid</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="loading" class="state-indicator loading-state">
      <div class="spinner"></div>
      <p>Fetching the latest payslip records...</p>
    </div>

    <div v-else-if="error" class="state-indicator error-state">
      {{ error }}
    </div>

    <div v-else class="payslips-dashboard-grid">
      <div class="payslip-grid-container" v-if="payslips.length > 0">
        <div v-for="payslip in payslips" :key="payslip.id" class="payslip-item-card">
          <div class="card-header-status">
            <h3 class="employee-name">{{ payslip.employee_name }}</h3>
            <span :class="['status-badge', `status-${payslip.status}`]">
              {{ formatStatus(payslip.status) }}
            </span>
          </div>
          <p class="employee-meta">{{ payslip.employee_id }} | {{ payslip.department }}</p>
          <div class="payslip-summary-details">
            <div class="detail-pair">
              <span class="detail-label">Period:</span>
              <span class="detail-value">{{ formatDate(payslip.pay_period_start) }} - {{ formatDate(payslip.pay_period_end) }}</span>
            </div>
            <div class="detail-pair">
              <span class="detail-label">Basic Salary:</span>
              <span class="detail-value">K{{ formatNumber(payslip.basic_salary) }}</span>
            </div>
            <div class="detail-pair net-pay-row">
              <span class="detail-label">Net Pay:</span>
              <span class="detail-net-pay">K{{ formatNumber(payslip.net_pay) }}</span>
            </div>
            <div class="detail-pair">
              <span class="detail-label">Payment Date:</span>
              <span class="detail-value">{{ formatDate(payslip.payment_date) }}</span>
            </div>
          </div>
          <div class="card-actions-footer">
            <button @click="viewPayslip(payslip)" class="btn-link-icon">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
              <span>View</span>
            </button>
            <button @click="downloadPayslip(payslip)" class="btn-link-icon">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 11.586V3a1 1 0 112 0v8.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              <span>Download</span>
            </button>
            <button v-if="payslip.status === 'draft' || payslip.status === 'generated'" @click="sendPayslip(payslip)" class="btn-link-icon btn-send">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></svg>
              <span>Send</span>
            </button>
          </div>
        </div>
      </div>
      <div v-else class="state-indicator empty-state">
        <p class="empty-message">No payslips found for the current filters. Try adjusting your criteria or generating a new one.</p>
        <button @click="showGenerateModal = true" class="btn-primary-icon">Generate First Payslip</button>
      </div>
    </div>

    <!-- Generate Single Payslip Modal -->
    <div v-if="showGenerateModal" class="modal-overlay" @click.self="closeModals">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="modal-title">Generate Single Payslip</h2>
          <button @click="closeModals" class="close-btn" aria-label="Close modal">✕</button>
        </div>
        <form @submit.prevent="generatePayslip" class="modal-body-content">
          <div class="form-group required">
            <label class="form-label">Employee</label>
            <select v-model="generateForm.employee_id" @change="onEmployeeSelected($event.target.value)" required class="select-input">
              <option value="" disabled>Select Employee</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ getEmployeeName(employee) }} (ID: {{ employee.id }})
              </option>
            </select>
          </div>
          <div class="form-row-grid">
            <div class="form-group required">
              <label class="form-label">Pay Period Start</label>
              <input type="date" v-model="generateForm.pay_period_start" required class="text-input">
            </div>
            <div class="form-group required">
              <label class="form-label">Pay Period End</label>
              <input type="date" v-model="generateForm.pay_period_end" required class="text-input">
            </div>
            <div class="form-group required">
              <label class="form-label">Payment Date</label>
              <input type="date" v-model="generateForm.payment_date" required class="text-input">
            </div>
          </div>
          <div class="form-section-group">
            <h3 class="section-title">Earnings</h3>
            <div class="form-row-grid grid-col-2">
              <div class="form-group">
                <label class="form-label">Basic Salary (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.basic_salary" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">House Allowance (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.house_allowance" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Transport Allowance (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.transport_allowance" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Other Allowances (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.other_allowances" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Overtime Hours</label>
                <input type="number" step="0.1" v-model.number="generateForm.overtime_hours" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Overtime Rate (K/hour)</label>
                <input type="number" step="0.01" v-model.number="generateForm.overtime_rate" @input="calculateNetPay" class="text-input">
              </div>
            </div>
          </div>
          <div class="form-section-group">
            <h3 class="section-title">Deductions</h3>
            <div class="form-row-grid grid-col-2">
              <div class="form-group">
                <label class="form-label">NAPSA Contribution (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.napsa" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">PAYE Tax (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.paye" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">NHIMA Contribution (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.nhima" @input="calculateNetPay" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Other Deductions (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.other_deductions" @input="calculateNetPay" class="text-input">
              </div>
            </div>
          </div>
          <div class="summary-preview-card">
            <h4 class="summary-title">Calculation Summary</h4>
            <div class="summary-row">
              <span class="summary-label">Total Earnings:</span>
              <span class="summary-amount positive">K{{ formatNumber(calculateTotalEarnings(generateForm)) }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Total Deductions:</span>
              <span class="summary-amount negative">K{{ formatNumber(calculateTotalDeductions(generateForm)) }}</span>
            </div>
            <div class="summary-row total-row">
              <span class="summary-label">Net Pay:</span>
              <span class="summary-amount net-pay-final">K{{ formatNumber(calculateNetPay()) }}</span>
            </div>
          </div>
          <div v-if="formError" class="form-alert-error">
            {{ formError }}
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Generating...' : 'Generate Payslip' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Generate Modal -->
    <div v-if="showBulkGenerate" class="modal-overlay" @click.self="closeModals">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="modal-title">Bulk Generate Payslips</h2>
          <button @click="closeModals" class="close-btn" aria-label="Close modal">✕</button>
        </div>
        <form @submit.prevent="bulkGeneratePayslips" class="modal-body-content">
          <div class="form-row-grid grid-col-2">
            <div class="form-group required">
              <label class="form-label">Pay Period Start</label>
              <input type="date" v-model="bulkForm.pay_period_start" required class="text-input">
            </div>
            <div class="form-group required">
              <label class="form-label">Pay Period End</label>
              <input type="date" v-model="bulkForm.pay_period_end" required class="text-input">
            </div>
          </div>
          <div class="form-group required">
            <label class="form-label">Payment Date</label>
            <input type="date" v-model="bulkForm.payment_date" required class="text-input">
          </div>
          <div class="form-group">
            <label class="form-label">Filter by Department</label>
            <select v-model="bulkForm.department" class="select-input">
              <option value="">All Departments</option>
              <option value="IT">IT</option>
              <option value="HR">HR</option>
              <option value="Finance">Finance</option>
              <option value="Sales">Sales</option>
              <option value="Administration">Administration</option>
            </select>
          </div>
         
          <div class="form-section-group employee-selection-area">
              <h4 class="selection-title">Selected Employees ({{ selectedBulkEmployees.length }})</h4>
              <div class="employee-tag-list">
                  <span v-for="employee in selectedBulkEmployees" :key="employee.id" class="employee-tag-pill">
                      {{ getEmployeeName(employee) }}
                      <button type="button" @click="removeEmployee(employee.id)" class="remove-tag-btn" aria-label="Remove employee">✕</button>
                  </span>
                  <p v-if="selectedBulkEmployees.length === 0" class="selection-hint">Select employees below to include them.</p>
              </div>
              <h4 class="selection-title">Available Employees</h4>
              <div class="employees-checkbox-list">
                  <label v-for="employee in availableEmployees" :key="employee.id" class="checkbox-label-card">
                      <input type="checkbox" :value="employee.id" v-model="bulkForm.employee_ids" class="checkbox-input">
                      <div class="employee-details">
                          <span class="employee-name-label">{{ getEmployeeName(employee) }}</span>
                          <span class="employee-dept-label">({{ employee.department }})</span>
                      </div>
                  </label>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting || bulkForm.employee_ids.length === 0">
              {{ submitting ? 'Generating...' : `Generate ${bulkForm.employee_ids.length} Payslips` }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Payslip Detail View Modal -->
    <div v-if="selectedPayslip" class="modal-overlay" @click.self="selectedPayslip = null">
      <div class="modal-card large-modal">
        <div class="modal-header">
          <h2 class="modal-title">Payslip for {{ selectedPayslip.employee_name }}</h2>
          <button @click="selectedPayslip = null" class="close-btn" aria-label="Close payslip view">✕</button>
        </div>
        
        <div class="payslip-detail-view">
          <header class="payslip-detail__header">
            <div class="company-info">
              <h2>Castle Holdings Ltd</h2>
              <p>54 Seble Road, Lusaka, Zambia</p>
              <p>Phone: +260 211 123456 | Email: payroll@archangel.co.zm</p>
            </div>
            <div class="payslip-title">
              <h1>PAYSLIP</h1>
              <p>Period: {{ formatDate(selectedPayslip.pay_period_start) }} to {{ formatDate(selectedPayslip.pay_period_end) }}</p>
            </div>
          </header>

          <div class="payslip-detail__employee-info">
            <h3 class="info-section__title">Employee Details</h3>
            <div class="info-grid">
              <div class="info-item">
                <label>Name:</label>
                <span>{{ selectedPayslip.employee_name }}</span>
              </div>
              <div class="info-item">
                <label>Employee ID:</label>
                <span>{{ selectedPayslip.employee_id }}</span>
              </div>
              <div class="info-item">
                <label>Department:</label>
                <span>{{ selectedPayslip.department }}</span>
              </div>
              <div class="info-item">
                <label>Payment Date:</label>
                <span>{{ formatDate(selectedPayslip.payment_date) }}</span>
              </div>
            </div>
          </div>

          <div class="payslip-detail__earnings-deductions">
            <section class="earnings-section">
              <h3 class="section-title">Earnings</h3>
              <div class="amount-list">
                <div class="amount-item">
                  <label>Basic Salary:</label>
                  <span>K{{ formatNumber(selectedPayslip.basic_salary || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>House Allowance:</label>
                  <span>K{{ formatNumber(selectedPayslip.house_allowance || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Transport Allowance:</label>
                  <span>K{{ formatNumber(selectedPayslip.transport_allowance || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Other Allowances:</label>
                  <span>K{{ formatNumber(selectedPayslip.other_allowances || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Overtime Pay ({{ selectedPayslip.overtime_hours || 0 }} hrs):</label>
                  <span>K{{ formatNumber((selectedPayslip.overtime_hours || 0) * (selectedPayslip.overtime_rate || 0)) }}</span>
                </div>
                <div class="amount-item total">
                  <label>Gross Earnings:</label>
                  <span>K{{ formatNumber(calculatePayslipEarnings(selectedPayslip)) }}</span>
                </div>
              </div>
            </section>

            <section class="deductions-section">
              <h3 class="section-title">Deductions</h3>
              <div class="amount-list">
                <div class="amount-item">
                  <label>NAPSA (5%):</label>
                  <span>K{{ formatNumber(selectedPayslip.napsa || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>PAYE Tax:</label>
                  <span>K{{ formatNumber(selectedPayslip.paye || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>NHIMA (1%):</label>
                  <span>K{{ formatNumber(selectedPayslip.nhima || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Other Deductions:</label>
                  <span>K{{ formatNumber(selectedPayslip.other_deductions || 0) }}</span>
                </div>
                <div class="amount-item filler-item"></div>
                <div class="amount-item total">
                  <label>Total Deductions:</label>
                  <span>K{{ formatNumber(calculatePayslipDeductions(selectedPayslip)) }}</span>
                </div>
              </div>
            </section>
          </div>

          <div class="payslip-detail__net-pay-summary">
            <div class="net-pay-visual-card">
              <h2>NET PAYABLE</h2>
              <div class="net-pay-amount">K{{ formatNumber(selectedPayslip.net_pay) }}</div>
              <p class="net-pay-words">({{ convertToWords(selectedPayslip.net_pay) }})</p>
            </div>
          </div>

          <footer class="payslip-detail__footer">
            <div class="signature-block">
              <div class="signature-line"></div>
              <p>Authorized Signature</p>
            </div>
            <div class="notes-block">
              <p><strong>Notes:</strong> This is a computer-generated payslip. Please contact HR for any discrepancies.</p>
            </div>
          </footer>
        </div>

        <div class="modal-footer print-actions">
          <button @click="downloadPayslip(selectedPayslip)" class="btn-secondary-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 11.586V3a1 1 0 112 0v8.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            Download PDF
          </button>
          <button onclick="window.print()" class="btn-primary-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 4v3H3v5h14V7h-2V4h-7V3H5zm0 14h10a1 1 0 001-1V9H4v8a1 1 0 001 1z" clip-rule="evenodd" /></svg>
            Print
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.payslip-generation {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}
.app-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.header-content h1 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}
.header-subtitle {
  color: #718096;
  margin: 0;
}
.header-actions {
  display: flex;
  gap: 1rem;
}
.filter-controls-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.filter-group-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.filter-group {
  display: flex;
  flex-direction: column;
}
.filter-label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}
.select-input,
.date-input,
.text-input {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}
.payslips-dashboard-grid {
  display: grid;
  gap: 1.5rem;
}
.payslip-grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}
.payslip-item-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border-left: 4px solid #4c51bf;
}
.card-header-status {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}
.employee-name {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}
.employee-meta {
  color: #718096;
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
}
.payslip-summary-details {
  margin-bottom: 1.5rem;
}
.detail-pair {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  border-bottom: 1px solid #f0f0f0;
}
.detail-label {
  color: #4a5568;
}
.detail-value {
  font-weight: 600;
  color: #2d3748;
}
.net-pay-row .detail-net-pay {
  color: #065f46;
  font-size: 1.1rem;
  font-weight: 700;
}
.card-actions-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}
.state-indicator {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.loading-state .spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #4c51bf;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.error-state {
  color: #dc2626;
}
.empty-state .empty-message {
  color: #718096;
  margin-bottom: 1rem;
}
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}
.status-draft {
  background-color: #fef3c7;
  color: #92400e;
}
.status-generated {
  background-color: #dbeafe;
  color: #1e40af;
}
.status-paid {
  background-color: #d1fae5;
  color: #065f46;
}
.btn-primary-icon,
.btn-secondary-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}
.btn-primary-icon {
  background-color: #4c51bf;
  color: white;
}
.btn-primary-icon:hover {
  background-color: #3e44a8;
}
.btn-secondary-icon {
  background-color: white;
  color: #4c51bf;
  border: 1px solid #c3dafe;
}
.btn-secondary-icon:hover {
  background-color: #f7fafc;
}
.btn-link-icon {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4c51bf;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s;
  text-decoration: none;
}
.btn-link-icon:hover {
  background: #f7fafc;
}
.btn-send {
  color: #059669;
}
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
.modal-card {
  background: white;
  border-radius: 12px;
  max-width: 90vw;
  max-height: 90vh;
  overflow-y: auto;
  min-width: 500px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}
.large-modal {
  min-width: 800px;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-title {
  margin: 0;
  color: #2d3748;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
}
.modal-body-content {
  padding: 1.5rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group.required .form-label::after {
  content: ' *';
  color: #ef4444;
}
.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}
.form-row-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.grid-col-2 {
  grid-template-columns: repeat(2, 1fr);
}
.form-section-group {
  margin: 2rem 0;
  padding: 1.5rem;
  background: #f7fafc;
  border-radius: 8px;
}
.section-title {
  margin: 0 0 1rem 0;
  color: #2d3748;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.5rem;
}
.summary-preview-card {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 8px;
  margin: 1.5rem 0;
  border-left: 4px solid #0ea5e9;
}
.summary-title {
  margin: 0 0 1rem 0;
  color: #0369a1;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}
.summary-amount {
  font-weight: 600;
}
.positive {
  color: #059669;
}
.negative {
  color: #dc2626;
}
.total-row .net-pay-final {
  color: #065f46;
  font-size: 1.1rem;
  font-weight: 700;
}
.form-alert-error {
  background: #fef2f2;
  color: #dc2626;
  padding: 1rem;
  border-radius: 6px;
  margin: 1rem 0;
  border-left: 4px solid #ef4444;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
}
.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}
.btn-primary {
  background: #4c51bf;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background: #3e44a8;
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-secondary {
  background: #f7fafc;
  color: #4c51bf;
  border: 1px solid #cbd5e0;
}
.btn-secondary:hover {
  background: #edf2f7;
}
.employee-selection-area {
  margin-top: 1rem;
}
.selection-title {
  margin: 1rem 0 0.5rem 0;
  color: #2d3748;
}
.employee-tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.employee-tag-pill {
  background: #dbeafe;
  color: #1e40af;
  padding: 0.5rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.remove-tag-btn {
  background: none;
  border: none;
  color: inherit;
  font-weight: bold;
  cursor: pointer;
}
.selection-hint {
  color: #9ca3af;
  font-style: italic;
  margin: 0;
}
.employees-checkbox-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.5rem;
}
.checkbox-label-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s;
}
.checkbox-label-card:hover {
  background: #f7fafc;
}
.checkbox-input {
  margin: 0;
}
.employee-details {
  flex: 1;
}
.employee-name-label {
  font-weight: 600;
  color: #2d3748;
}
.employee-dept-label {
  color: #718096;
  font-size: 0.875rem;
}
.payslip-detail-view {
  padding: 2rem;
}
.payslip-detail__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #333;
}
.company-info {
  flex: 1;
}
.company-info h2 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}
.company-info p {
  margin: 0.25rem 0;
  color: #718096;
  font-size: 0.9rem;
}
.payslip-title {
  text-align: right;
}
.payslip-title h1 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 2rem;
}
.payslip-title p {
  margin: 0;
  color: #718096;
}
.payslip-detail__employee-info {
  margin-bottom: 2rem;
}
.info-section__title {
  margin: 0 0 1rem 0;
  color: #2d3748;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.5rem;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.info-item {
  display: flex;
  justify-content: space-between;
}
.info-item label {
  font-weight: 600;
  color: #4a5568;
}
.payslip-detail__earnings-deductions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}
.earnings-section,
.deductions-section {
  background: #f7fafc;
  padding: 1.5rem;
  border-radius: 8px;
}
.amount-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.amount-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}
.amount-item.total {
  border-top: 2px solid #e2e8f0;
  font-weight: 700;
  font-size: 1.1rem;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
}
.filler-item {
  visibility: hidden;
}
.payslip-detail__net-pay-summary {
  text-align: center;
  margin-bottom: 2rem;
}
.net-pay-visual-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
  max-width: 400px;
  margin: 0 auto;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}
.net-pay-visual-card h2 {
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
}
.net-pay-amount {
  font-size: 3rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}
.net-pay-words {
  font-size: 1rem;
  margin: 0;
  font-style: italic;
  opacity: 0.9;
}
.payslip-detail__footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 2px solid #e2e8f0;
}
.signature-block {
  text-align: center;
}
.signature-line {
  width: 200px;
  height: 1px;
  background: #333;
  margin: 0 auto 0.5rem;
}
.notes-block {
  max-width: 300px;
  text-align: right;
}
.notes-block p {
  margin: 0;
  color: #718096;
  font-size: 0.9rem;
}
.print-actions {
  justify-content: center;
  gap: 2rem;
}
@media (max-width: 768px) {
  .payslip-generation {
    padding: 0.5rem;
  }
  .app-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  .header-actions {
    justify-content: center;
  }
  .filter-group-container {
    grid-template-columns: 1fr;
  }
  .payslip-grid-container {
    grid-template-columns: 1fr;
  }
  .form-row-grid {
    grid-template-columns: 1fr;
  }
  .payslip-detail__earnings-deductions {
    grid-template-columns: 1fr;
  }
  .payslip-detail__header {
    flex-direction: column;
    text-align: center;
  }
  .payslip-title {
    text-align: center;
    margin-top: 1rem;
  }
  .payslip-detail__footer {
    flex-direction: column;
    gap: 2rem;
    text-align: center;
  }
  .notes-block {
    text-align: center;
  }
  .modal-card {
    min-width: auto;
    margin: 1rem;
  }
  .large-modal {
    min-width: auto;
  }
}
</style>