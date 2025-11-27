<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import jsPDF from 'jspdf';

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
  custom_month: ''
});

const generateForm = reactive({
  employee_id: '',
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  basic_salary: 0,
  transport_allowance: 0,
  lunch_allowance: 0,
  overtime_hours: 0,
  overtime_rate: 0,
  generate_pdf: true
});

const bulkForm = reactive({
  pay_period_start: '',
  pay_period_end: '',
  payment_date: '',
  department: '',
  employee_ids: [],
});

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

// Date utilities
const getCurrentMonthDates = () => {
  const now = new Date();
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return {
    start: formatDateForInput(firstDay),
    end: formatDateForInput(lastDay)
  };
};

const getLastMonthDates = () => {
  const now = new Date();
  const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
  const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
  return {
    start: formatDateForInput(firstDay),
    end: formatDateForInput(lastDay)
  };
};

const getMonthDates = (yearMonth) => {
  if (!yearMonth) return getCurrentMonthDates();
  
  const [year, month] = yearMonth.split('-').map(Number);
  const firstDay = new Date(year, month - 1, 1);
  const lastDay = new Date(year, month, 0);
  return {
    start: formatDateForInput(firstDay),
    end: formatDateForInput(lastDay)
  };
};

// Watch for filter changes
watch(() => filters.pay_period, (newValue) => {
  if (newValue === 'custom' && !filters.custom_month) {
    // Set default to current month when switching to custom
    filters.custom_month = new Date().toISOString().slice(0, 7);
  }
  fetchPayslips();
});

watch(() => filters.custom_month, () => {
  if (filters.pay_period === 'custom') {
    fetchPayslips();
  }
});

// --- Core Calculation Functions (for display only - backend does actual calculations) ---
const calculatePreviewEarnings = (basicSalary, transportAllowance = 0, lunchAllowance = 0, overtimeHours = 0, overtimeRate = 0) => {
  const housingAllowance = basicSalary * 0.25; // 25% of basic (unchanged)
  const overtimePay = overtimeHours * overtimeRate;
  return {
    basic_salary: basicSalary,
    housing_allowance: housingAllowance,
    transport_allowance: transportAllowance,
    lunch_allowance: lunchAllowance,
    overtime_pay: overtimePay,
    total_earnings: basicSalary + housingAllowance + transportAllowance + lunchAllowance + overtimePay
  };
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
    
    // Handle different period types
    if (filters.pay_period === 'custom' && filters.custom_month) {
      const { start, end } = getMonthDates(filters.custom_month);
      params.append('start', start);
      params.append('end', end);
    } else if (filters.pay_period === 'last') {
      const { start, end } = getLastMonthDates();
      params.append('start', start);
      params.append('end', end);
    } else {
      // Default to current month
      const { start, end } = getCurrentMonthDates();
      params.append('start', start);
      params.append('end', end);
    }
    
    if (filters.department) params.append('department', filters.department);
    if (filters.status) params.append('status', filters.status);

    const response = await axios.get('/api/admin/payslips', { params });
    payslips.value = response.data.data || response.data;

    // Log PDF availability for debugging
    payslips.value.forEach(payslip => {
      console.log(`Payslip ${payslip.id}: PDF available: ${payslip.pdf_available}, PDF path: ${payslip.pdf_path}`);
    });

  } catch (err) {
    error.value = 'Failed to fetch payslips';
    console.error('Fetch payslips error:', err);

    // Retry once after 2 seconds
    setTimeout(async () => {
      try {
        const retryResponse = await axios.get('/api/admin/payslips');
        payslips.value = retryResponse.data.data || retryResponse.data;
        error.value = null;
      } catch (retryErr) {
        console.error('Retry failed:', retryErr);
      }
    }, 2000);
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

// Watch for employee selection and auto-populate basic salary and allowances
const onEmployeeSelected = (employeeId) => {
  const employee = employees.value.find(emp => emp.id === parseInt(employeeId));
  if (employee) {
    generateForm.basic_salary = parseFloat(employee.base_salary) || 0;
    generateForm.transport_allowance = parseFloat(employee.transport_allowance) || 0;
    generateForm.lunch_allowance = parseFloat(employee.lunch_allowance) || 0;
  }
};

// Generate PDF function
const generatePdf = async (payslipId) => {
  try {
    const response = await axios.post(`/api/admin/payslips/${payslipId}/generate-pdf`);
    return response.data;
  } catch (err) {
    console.error('Failed to generate PDF:', err);
    throw err;
  }
};

// Client-side PDF generation fallback
const generateClientPdf = (payslip) => {
  const doc = new jsPDF();
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  
  // Header
  doc.setFontSize(20);
  doc.text('PAYSLIP', pageWidth / 2, 20, { align: 'center' });
  
  // Company info (left side)
  doc.setFontSize(10);
  doc.text('Castle Holdings Ltd', 20, 40);
  doc.text('54 Seble Road, Lusaka, Zambia', 20, 48);
  doc.text('Phone: +260 211 123456 | Email: payroll@castleholdings.co.zm', 20, 56);
  
  // Period (right side)
  doc.text(`Period: ${formatDate(payslip.pay_period_start)} to ${formatDate(payslip.pay_period_end)}`, pageWidth / 2, 40, { align: 'center' });
  
  // Employee details
  let y = 80;
  doc.setFontSize(12);
  doc.text('Employee Details', 20, y);
  y += 10;
  doc.setFontSize(10);
  doc.text(`Name: ${payslip.employee_name || 'N/A'}`, 20, y); y += 8;
  doc.text(`Employee ID: ${payslip.employee_id || 'N/A'}`, 20, y); y += 8;
  doc.text(`Department: ${payslip.department || 'N/A'}`, 20, y); y += 8;
  doc.text(`Payment Date: ${formatDate(payslip.payment_date)}`, 20, y); y += 10;
  
  // Earnings section
  doc.setFontSize(12);
  doc.text('Earnings', 20, y);
  y += 10;
  doc.setFontSize(10);
  doc.text(`Basic Salary: K${formatNumber(payslip.basic_salary || 0)}`, 20, y); y += 8;
  doc.text(`House Allowance (25%): K${formatNumber(payslip.house_allowance || 0)}`, 20, y); y += 8;
  doc.text(`Transport Allowance: K${formatNumber(payslip.transport_allowance || 0)}`, 20, y); y += 8;
  doc.text(`Lunch Allowance: K${formatNumber(payslip.lunch_allowance || 0)}`, 20, y); y += 8;
  doc.text(`Overtime Pay (${payslip.overtime_hours || 0} hrs): K${formatNumber(payslip.overtime_pay || 0)}`, 20, y); y += 8;
  doc.setFontSize(12);
  doc.text(`Gross Earnings: K${formatNumber(payslip.gross_salary || 0)}`, 20, y);
  y += 15;
  
  // Deductions section (right column)
  const deductY = 110; // Start deductions earlier on right
  doc.setFontSize(12);
  doc.text('Deductions', 105, deductY);
  let deductYPos = deductY + 10;
  doc.setFontSize(10);
  doc.text(`PAYE Tax: K${formatNumber(payslip.paye || 0)}`, 105, deductYPos); deductYPos += 8;
  doc.text(`NAPSA (10% of gross): K${formatNumber(payslip.napsa || 0)}`, 105, deductYPos); deductYPos += 8;
  doc.text(`NHIMA (1% of gross): K${formatNumber(payslip.nhima || 0)}`, 105, deductYPos); deductYPos += 8;
  doc.setFontSize(12);
  doc.text(`Total Deductions: K${formatNumber(payslip.total_deductions || 0)}`, 105, deductYPos);
  
  // Net Pay (centered below)
  y = Math.max(y, deductYPos + 15);
  doc.setFontSize(16);
  doc.text('NET PAYABLE', pageWidth / 2, y, { align: 'center' });
  y += 15;
  doc.setFontSize(24);
  doc.text(`K${formatNumber(payslip.net_pay || 0)}`, pageWidth / 2, y, { align: 'center' });
  y += 20;
  doc.setFontSize(10);
  doc.text(`(${convertToWords(payslip.net_pay || 0)})`, pageWidth / 2, y, { align: 'center' });
  y += 20;
  
  // Footer
  doc.setLineWidth(0.5);
  doc.line(20, y, pageWidth - 20, y);
  y += 10;
  doc.text('Authorized Signature', pageWidth / 2, y, { align: 'center' });
  y += 10;
  doc.setFontSize(8);
  doc.text('Notes: This is a computer-generated payslip. Please contact HR for any discrepancies.', 20, y, { maxWidth: pageWidth - 40 });
  
  // Generate filename
  const period = payslip.pay_period_start
    ? new Date(payslip.pay_period_start).toISOString().slice(0, 7)
    : new Date().toISOString().slice(0, 7);
  const filename = `payslip-${payslip.employee_id || payslip.employee_name || 'unknown'}-${period}-client.pdf`;
  
  // Save the PDF
  doc.save(filename);
};

// Generate single payslip
const generatePayslip = async () => {
  submitting.value = true;
  formError.value = null;
  
  // Validate required fields - only basic salary is needed for calculations
  if (!generateForm.employee_id || !generateForm.pay_period_start ||
      !generateForm.pay_period_end || !generateForm.payment_date ||
      generateForm.basic_salary <= 0) {
    formError.value = 'Please fill in all required fields correctly.';
    submitting.value = false;
    return;
  }
  
  try {
    // Send only basic data - backend will calculate everything using tax configuration
    const formData = {
      employee_id: generateForm.employee_id.toString(),
      pay_period_start: generateForm.pay_period_start,
      pay_period_end: generateForm.pay_period_end,
      payment_date: generateForm.payment_date,
      basic_salary: generateForm.basic_salary,
      overtime_hours: generateForm.overtime_hours || 0,
      overtime_rate: generateForm.overtime_rate || 0,
      generate_pdf: true
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

// Bulk generate payslips
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
        overtime_hours: 0,
        overtime_rate: 0,
        generate_pdf: true
      };
      
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

const viewPayslip = async (payslip) => {
  // Check if PDF is available before showing details
  if (!payslip.pdf_available && !payslip.pdf_path) {
    try {
      alert('Generating PDF for this payslip...');
      await generatePdf(payslip.id);
    
      // Refresh the payslip data
      const response = await axios.get(`/api/admin/payslips/${payslip.id}`);
      const updatedPayslip = response.data.data || response.data;
    
      selectedPayslip.value = updatedPayslip;
    } catch (err) {
      console.error('Failed to generate PDF:', err);
      // Still show the payslip even if PDF generation fails
      selectedPayslip.value = payslip;
    }
  } else {
    selectedPayslip.value = payslip;
  }
};

const downloadPayslip = async (payslip) => {
  try {
    console.log('Attempting to download payslip:', payslip.id);

    // Check if PDF is available first
    if (!payslip.pdf_available && !payslip.pdf_path) {
      // Try to generate PDF first
      try {
        console.log('PDF not available, generating PDF first...');
        await generatePdf(payslip.id);
     
        // Wait a moment for PDF generation
        await new Promise(resolve => setTimeout(resolve, 1000));
     
        // Refresh payslip data
        const refreshedResponse = await axios.get(`/api/admin/payslips/${payslip.id}`);
        const refreshedPayslip = refreshedResponse.data.data || refreshedResponse.data;
     
        if (!refreshedPayslip.pdf_available && !refreshedPayslip.pdf_path) {
          throw new Error('PDF generation failed');
        }
     
        // Update the payslip in our local state
        const index = payslips.value.findIndex(p => p.id === payslip.id);
        if (index !== -1) {
          payslips.value[index] = { ...payslips.value[index], ...refreshedPayslip };
        }
        payslip = refreshedPayslip;
      } catch (genError) {
        console.error('PDF generation failed:', genError);
        alert('PDF is not available for this payslip. Please try generating it first.');
        return;
      }
    }
    
    // Try different download endpoints
    let downloadUrl = '';
    let response;

    // Try admin endpoint first
    try {
      downloadUrl = `/api/admin/payslips/${payslip.id}/download`;
      console.log('Trying admin endpoint:', downloadUrl);
      response = await axios.get(downloadUrl, {
        responseType: 'blob',
        timeout: 30000 // 30 second timeout
      });
    } catch (adminError) {
      console.log('Admin endpoint failed, trying regular endpoint...');
    
      // Try regular endpoint
      try {
        downloadUrl = `/api/payslips/${payslip.id}/download`;
        console.log('Trying regular endpoint:', downloadUrl);
        response = await axios.get(downloadUrl, {
          responseType: 'blob',
          timeout: 30000
        });
      } catch (regularError) {
        console.log('Regular endpoint failed, trying alternative endpoint...');
     
        // Try alternative endpoint pattern
        try {
          downloadUrl = `/api/payslips/${payslip.id}/pdf`;
          console.log('Trying alternative endpoint:', downloadUrl);
          response = await axios.get(downloadUrl, {
            responseType: 'blob',
            timeout: 30000
          });
        } catch (altError) {
          console.error('All download endpoints failed:', {
            adminError: adminError.response?.status,
            regularError: regularError.response?.status,
            altError: altError.response?.status
          });
       
          // Check if it's an authentication issue
          if (adminError.response?.status === 401 || regularError.response?.status === 401) {
            alert('Authentication required. Please log in again.');
            return;
          }
       
          // Check if it's a server error
          if (adminError.response?.status === 500 || regularError.response?.status === 500) {
            alert('Server error occurred while generating PDF. Please try again later.');
            return;
          }
       
          // Fallback to client-side PDF generation to solve 404 error
          console.log('All endpoints failed, generating PDF client-side...');
          generateClientPdf(payslip);
          return;
        }
      }
    }
    
    // If we get here, we have a successful response
    console.log('Download successful, creating blob...');

    // Create blob URL and download
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    // Create filename
    const period = payslip.pay_period_start
      ? new Date(payslip.pay_period_start).toISOString().slice(0, 7)
      : new Date().toISOString().slice(0, 7);

    const filename = `payslip-${payslip.employee_id || payslip.employee_name}-${period}.pdf`;

    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();

    // Clean up URL
    setTimeout(() => window.URL.revokeObjectURL(url), 100);

    console.log('Download completed successfully');

  } catch (err) {
    console.error('Failed to download payslip:', err);

    // Provide more specific error messages
    if (err.response) {
      switch (err.response.status) {
        case 401:
          alert('Authentication required. Please log in again.');
          break;
        case 403:
          alert('You do not have permission to download this payslip.');
          break;
        case 404:
          // Fallback to client-side for 404
          console.log('404 detected, generating client-side PDF...');
          generateClientPdf(payslip);
          break;
        case 500:
          alert('Server error occurred. Please try again later.');
          break;
        default:
          alert(`Failed to download payslip: ${err.response.status} ${err.response.statusText}`);
      }
    } else if (err.request) {
      alert('Network error. Please check your connection and try again.');
    } else {
      alert('Failed to download payslip. Please try again.');
    }
  }
};

const sendPayslip = async (payslip) => {
  try {
    await axios.post(`/api/admin/payslips/${payslip.id}/send`);
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
    basic_salary: 0,
    transport_allowance: 0,
    lunch_allowance: 0,
    overtime_hours: 0,
    overtime_rate: 0
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

// Get display text for current filter
const getFilterDisplayText = computed(() => {
  if (filters.pay_period === 'current') {
    const { start } = getCurrentMonthDates();
    const month = new Date(start).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    return `Current Month (${month})`;
  } else if (filters.pay_period === 'last') {
    const { start } = getLastMonthDates();
    const month = new Date(start).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    return `Last Month (${month})`;
  } else if (filters.pay_period === 'custom' && filters.custom_month) {
    const [year, month] = filters.custom_month.split('-');
    const date = new Date(year, month - 1);
    const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    return `Selected Month (${monthName})`;
  }
  return 'All Periods';
});

// Utility function to check backend endpoints (for debugging)
const checkEndpoints = async () => {
  const endpoints = [
    '/api/admin/payslips/1/download',
    '/api/payslips/1/download',
    '/api/payslips/1/pdf',
    '/api/admin/payslips/1/generate-pdf'
  ];
  
  for (const endpoint of endpoints) {
    try {
      const response = await axios.head(endpoint);
      console.log(`Endpoint ${endpoint}: ${response.status}`);
    } catch (err) {
      console.log(`Endpoint ${endpoint}: ${err.response?.status || 'Failed'}`);
    }
  }
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
  formatNumber,
  formatDate,
  formatStatus,
  convertToWords,
  getEmployeeName
});
</script>

<template>
  <div class="payslip-generation">
    <header class="app-header">
      <div class="header-content">
        <h1 class="header-title">Castle Holding Zambia</h1>
        <p class="header-subtitle">Generate and manage employee payslips for Castle Holdings</p>
        <div class="filter-indicator" v-if="getFilterDisplayText">
          <span class="filter-label">Showing:</span>
          <span class="filter-value">{{ getFilterDisplayText }}</span>
        </div>
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
          <select v-model="filters.pay_period" class="select-input">
            <option value="current">Current Month</option>
            <option value="last">Last Month</option>
            <option value="custom">Choose Month</option>
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
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Status</label>
          <select v-model="filters.status" class="select-input">
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
              <div class="form-group required">
                <label class="form-label">Basic Salary (K)</label>
                <input type="number" step="0.01" v-model.number="generateForm.basic_salary" required class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Overtime Hours</label>
                <input type="number" step="0.1" v-model.number="generateForm.overtime_hours" class="text-input">
              </div>
              <div class="form-group">
                <label class="form-label">Overtime Rate (K/hour)</label>
                <input type="number" step="0.01" v-model.number="generateForm.overtime_rate" class="text-input">
              </div>
            </div>
         
            <!-- Preview of calculated allowances -->
            <div class="allowance-preview" v-if="generateForm.basic_salary > 0">
              <h4 class="preview-title">Allowances (Automatically Calculated)</h4>
              <div class="preview-grid">
                <div class="preview-item">
                  <span class="preview-label">Housing Allowance (25%):</span>
                  <span class="preview-value">K{{ formatNumber(generateForm.basic_salary * 0.25) }}</span>
                </div>
                <div class="preview-item">
                  <span class="preview-label">Transport Allowance:</span>
                  <span class="preview-value">K{{ formatNumber(generateForm.transport_allowance) }}</span>
                </div>
                <div class="preview-item">
                  <span class="preview-label">Lunch Allowance:</span>
                  <span class="preview-value">K{{ formatNumber(generateForm.lunch_allowance) }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-section-group">
            <h3 class="section-title">Deductions Preview</h3>
            <div class="deductions-preview">
              <p class="preview-note">
                <strong>Note:</strong> Deductions are automatically calculated by the system based on Zambian tax regulations:
              </p>
              <ul class="preview-list">
                <li>PAYE Tax: Calculated from gross-salary salary using progressive tax bands</li>
                <li>NAPSA: </li>
                <li>NHIMA: </li>
              </ul>
            </div>
          </div>
          
          <div class="summary-preview-card">
            <h4 class="summary-title">Estimated Calculation Summary</h4>
            <div class="summary-row">
              <span class="summary-label">Total Earnings:</span>
              <span class="summary-amount positive">K{{ formatNumber(calculatePreviewEarnings(generateForm.basic_salary, generateForm.transport_allowance, generateForm.lunch_allowance, generateForm.overtime_hours, generateForm.overtime_rate).total_earnings) }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Deductions:</span>
              <span class="summary-amount negative">Calculated by system</span>
            </div>
            <div class="summary-row total-row">
              <span class="summary-label">Net Pay:</span>
              <span class="summary-amount net-pay-final">Calculated by system</span>
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
                {{ getEmployeeName(employee) }} - K{{ formatNumber(employee.base_salary || 0) }}
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
                  <span class="employee-dept-label">({{ employee.department }}) - K{{ formatNumber(employee.base_salary || 0) }}</span>
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
              <p>Phone: +260 211 123456 | Email: payroll@castleholdings.co.zm</p>
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
                  <label>House Allowance (25%):</label>
                  <span>K{{ formatNumber(selectedPayslip.house_allowance || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Transport Allowance:</label>
                  <span>K{{ formatNumber(selectedPayslip.transport_allowance || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Lunch Allowance:</label>
                  <span>K{{ formatNumber(selectedPayslip.lunch_allowance || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>Overtime Pay ({{ selectedPayslip.overtime_hours || 0 }} hrs):</label>
                  <span>K{{ formatNumber(selectedPayslip.overtime_pay || 0) }}</span>
                </div>
                <div class="amount-item total">
                  <label>Gross Earnings:</label>
                  <span>K{{ formatNumber(selectedPayslip.gross_salary || 0) }}</span>
                </div>
              </div>
            </section>
         
            <section class="deductions-section">
              <h3 class="section-title">Deductions</h3>
              <div class="amount-list">
                <div class="amount-item">
                  <label>PAYE Tax:</label>
                  <span>K{{ formatNumber(selectedPayslip.paye || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>NAPSA :</label>
                  <span>K{{ formatNumber(selectedPayslip.napsa || 0) }}</span>
                </div>
                <div class="amount-item">
                  <label>NHIMA :</label>
                  <span>K{{ formatNumber(selectedPayslip.nhima || 0) }}</span>
                </div>
                <div class="amount-item filler-item"></div>
                <div class="amount-item total">
                  <label>Total Deductions:</label>
                  <span>K{{ formatNumber(selectedPayslip.total_deductions || 0) }}</span>
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
  align-items: flex-start;
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
  margin: 0 0 0.5rem 0;
}

.filter-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f0f9ff;
  border-radius: 6px;
  border-left: 4px solid #0ea5e9;
}

.filter-label {
  font-weight: 600;
  color: #0369a1;
  font-size: 0.875rem;
}

.filter-value {
  color: #0c4a6e;
  font-weight: 500;
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

.date-input {
  cursor: pointer;
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

.allowance-preview {
  margin-top: 1rem;
  padding: 1rem;
  background: #f0f9ff;
  border-radius: 6px;
  border-left: 4px solid #0ea5e9;
}

.preview-title {
  margin: 0 0 0.75rem 0;
  color: #0369a1;
  font-size: 0.9rem;
  font-weight: 600;
}

.preview-grid {
  display: grid;
  gap: 0.5rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.preview-label {
  color: #4a5568;
}

.preview-value {
  font-weight: 600;
  color: #059669;
}

.deductions-preview {
  padding: 1rem;
  background: #fff7ed;
  border-radius: 6px;
  border-left: 4px solid #ea580c;
}

.preview-note {
  margin: 0 0 0.75rem 0;
  color: #9a3412;
  font-size: 0.875rem;
}

.preview-list {
  margin: 0;
  padding-left: 1.25rem;
  color: #7c2d12;
  font-size: 0.875rem;
}

.preview-list li {
  margin-bottom: 0.25rem;
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