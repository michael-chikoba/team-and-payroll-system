<template>
  <div class="payslip-view">

    <!-- ── Sticky Action Bar ───────────────────────── -->
    <transition name="sticky-fade">
      <div v-if="showStickyBar" class="sticky-action-bar">
        <div class="sticky-inner">
          <div class="sticky-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Payslip Management
          </div>
          <div class="sticky-actions">
            <button @click="showBulkGenerate = true" class="btn-outline">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              Bulk Generate
            </button>
            <button @click="showGenerateModal = true" class="btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Generate Single
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card" ref="headerCardRef">
      <div class="header-accent"></div>
      <div class="header-inner">
        <div class="header-left">
          <div class="header-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          </div>
          <div class="header-text">
            <h1>Payslip Management</h1>
            <p>Generate, view and distribute employee payslips</p>
            <div class="header-badges">
              <span class="badge-role">Admin View</span>
              <span v-if="currentBusiness" class="currency-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="12" y1="1" x2="12" y2="23"/>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                {{ getCurrencySymbol(currentCurrency) }} {{ currentCurrency }}
              </span>
              <span v-if="getFilterDisplayText !== 'All Records'" class="badge-filter">{{ getFilterDisplayText }}</span>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <div class="layout-toggle">
            <button :class="['layout-btn', { active: viewMode === 'grid' }]"  @click="viewMode = 'grid'"  title="Grid view">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </button>
            <button :class="['layout-btn', { active: viewMode === 'table' }]" @click="viewMode = 'table'" title="Table view">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
          </div>
          <button @click="showBulkGenerate = true" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Bulk Generate
          </button>
          <button @click="showGenerateModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Generate Single
          </button>
        </div>
      </div>
    </div>

    <!-- ── Filter + Content Card ───────────────────── -->
    <div class="section-card">

      <!-- Controls Bar -->
      <div class="controls-bar">
        <div class="filters-row">
          <div class="filter-group">
            <label>Business</label>
            <select v-model="filters.business_id" @change="handleBusinessChange" class="filter-select">
              <option value="">All Businesses</option>
              <option v-for="b in businesses" :key="b.id" :value="b.id">
                {{ b.name }} ({{ getCurrencySymbol(b.currency_code) }}{{ b.currency_code }})
              </option>
            </select>
          </div>
          <div class="filter-group">
            <label>Pay Period</label>
            <select v-model="filters.pay_period" @change="handlePeriodChange" class="filter-select">
              <option value="current">Current Month</option>
              <option value="last">Last Month</option>
              <option value="custom">Select Month</option>
              <option value="date_range">Date Range</option>
            </select>
          </div>
          <div class="filter-group" v-if="filters.pay_period === 'custom'">
            <label>Month</label>
            <input type="month" v-model="filters.custom_month" @change="fetchPayslips" class="filter-select" />
          </div>
          <div class="filter-group" v-if="filters.pay_period === 'date_range'">
            <label>Start Date</label>
            <input type="date" v-model="filters.start_date" @change="fetchPayslips" class="filter-select" />
          </div>
          <div class="filter-group" v-if="filters.pay_period === 'date_range'">
            <label>End Date</label>
            <input type="date" v-model="filters.end_date" @change="fetchPayslips" class="filter-select" />
          </div>
          <div class="filter-group">
            <label>Department</label>
            <select v-model="filters.department" class="filter-select">
              <option value="">All Departments</option>
              <option value="IT">IT</option>
              <option value="HR">HR</option>
              <option value="Finance">Finance</option>
              <option value="Sales">Sales</option>
              <option value="Operations">Operations</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select v-model="filters.status" class="filter-select">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="generated">Generated</option>
              <option value="paid">Paid</option>
            </select>
          </div>
        </div>
        <span class="records-badge" v-if="!loading">{{ payslips.length }} record{{ payslips.length !== 1 ? 's' : '' }}</span>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="state-empty">
        <div class="spinner"></div>
        <p>Loading payslip records…</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="state-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="color:#ef4444;">{{ error }}</p>
        <button @click="fetchPayslips" class="btn-primary">Try Again</button>
      </div>

      <!-- ── Grid View ────────────────────────────── -->
      <div v-else-if="viewMode === 'grid' && payslips.length > 0" class="payslip-grid">
        <div v-for="payslip in payslips" :key="payslip.id" class="payslip-card">

          <div class="card-head">
            <div class="card-avatar" :style="{ background: getAvatarColor(getEmployeeName(payslip)) }">
              {{ getInitials(getEmployeeName(payslip)) }}
            </div>
            <div class="card-meta">
              <div class="card-name">{{ getEmployeeName(payslip) }}</div>
              <div class="card-sub">
                <span class="mono-id">{{ payslip.employee_id || '#—' }}</span>
                <span class="dept-chip">{{ payslip.department || 'General' }}</span>
              </div>
              <span v-if="!filters.business_id && payslip.business_name" class="biz-chip">🏢 {{ payslip.business_name }}</span>
            </div>
            <span :class="['status-badge', statusClass(payslip.status)]">
              <span class="dot"></span>{{ formatStatus(payslip.status) }}
            </span>
          </div>

          <div class="amount-strip">
            <div class="amount-cell">
              <span class="amt-lbl">Period</span>
              <span class="amt-val small-val">{{ formatDate(payslip.pay_period_start) }} → {{ formatDate(payslip.pay_period_end) }}</span>
            </div>
            <div class="amount-cell">
              <span class="amt-lbl">Gross</span>
              <span class="amt-val">{{ formatCurrency(payslip.gross_salary) }}</span>
            </div>
            <div class="amount-cell">
              <span class="amt-lbl">Net Pay</span>
              <span class="amt-val net-green">{{ formatCurrency(payslip.net_pay) }}</span>
            </div>
          </div>

          <div class="card-actions">
            <button @click="viewPayslip(payslip)" class="card-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>View
            </button>
            <button @click="downloadPayslip(payslip)" class="card-btn download" :disabled="downloadingId === payslip.id">
              <svg v-if="downloadingId !== payslip.id" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span v-if="downloadingId === payslip.id" class="btn-spinner"></span>
              {{ downloadingId === payslip.id ? '…' : 'PDF' }}
            </button>
            <button v-if="!payslip.is_sent" @click="sendPayslip(payslip)" class="card-btn send">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>Send
            </button>
          </div>

        </div>
      </div>

      <!-- ── Table View ───────────────────────────── -->
      <div v-else-if="viewMode === 'table' && payslips.length > 0" class="table-wrap">
        <div :class="['list-header', 'ps-grid', { 'with-biz': !filters.business_id }]">
          <div>Employee</div>
          <div v-if="!filters.business_id">Business</div>
          <div>Department</div>
          <div>Period</div>
          <div class="text-right">Gross</div>
          <div class="text-right">Net Pay</div>
          <div>Status</div>
          <div class="text-right">Actions</div>
        </div>

        <div
          v-for="payslip in payslips"
          :key="payslip.id"
          :class="['list-row', 'ps-grid', { 'with-biz': !filters.business_id }]"
          @click="viewPayslip(payslip)"
        >
          <div class="emp-cell">
            <div class="emp-av" :style="{ background: getAvatarColor(getEmployeeName(payslip)) }">{{ getInitials(getEmployeeName(payslip)) }}</div>
            <div>
              <div class="emp-name">{{ getEmployeeName(payslip) }}</div>
              <div class="mono-id">{{ payslip.employee_id || '#—' }}</div>
            </div>
          </div>
          <div v-if="!filters.business_id"><span class="biz-chip-sm">{{ payslip.business_name || '—' }}</span></div>
          <div><span class="dept-tag">{{ payslip.department || 'General' }}</span></div>
          <div class="period-cell">
            <span>{{ formatDate(payslip.pay_period_start) }}</span>
            <span class="period-sep">→</span>
            <span>{{ formatDate(payslip.pay_period_end) }}</span>
          </div>
          <div class="text-right mono text-muted">{{ formatCurrency(payslip.gross_salary) }}</div>
          <div class="text-right mono text-success fw-700">{{ formatCurrency(payslip.net_pay) }}</div>
          <div><span :class="['status-badge', statusClass(payslip.status)]"><span class="dot"></span>{{ formatStatus(payslip.status) }}</span></div>
          <div class="row-actions" @click.stop>
            <button @click="viewPayslip(payslip)" class="icon-btn" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button @click="downloadPayslip(payslip)" class="icon-btn dl" :disabled="downloadingId === payslip.id" title="Download">
              <span v-if="downloadingId === payslip.id" class="btn-spinner sm"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
            <button v-if="!payslip.is_sent" @click="sendPayslip(payslip)" class="icon-btn send" title="Send">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="!loading" class="state-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>No payslips found for the current filters.</p>
        <button @click="showGenerateModal = true" class="btn-primary">Generate New Payslip</button>
      </div>

    </div><!-- /section-card -->


    <!-- ═══════════════════════════════════════════ -->
    <!--  MODAL — View Payslip Detail               -->
    <!-- ═══════════════════════════════════════════ -->
    <transition name="modal-fade">
      <div v-if="selectedPayslip" class="modal-overlay" @click.self="selectedPayslip = null">
        <div class="modal-box large">
          <div class="modal-header">
            <div class="mh-left">
              <div class="mh-avatar">{{ getInitials(getEmployeeName(selectedPayslip)) }}</div>
              <div>
                <h3 class="mh-name">{{ getEmployeeName(selectedPayslip) }}</h3>
                <p class="mh-sub">{{ selectedPayslip.department || 'General' }} · {{ selectedPayslip.employee_id }}</p>
              </div>
            </div>
            <button @click="selectedPayslip = null" class="mh-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <div class="modal-body">
            <!-- Company strip -->
            <div class="company-strip">
              <div class="co-name">{{ selectedPayslip.business_name || 'Castle Holdings Ltd' }}</div>
              <div class="co-addr">54 Seble Road, Lusaka, Zambia</div>
              <span class="period-pill">{{ formatDate(selectedPayslip.pay_period_end, 'month') }}</span>
            </div>

            <!-- Quick stats -->
            <div class="modal-stats">
              <div class="mstat"><small>Employee ID</small><strong class="mono-id">{{ selectedPayslip.employee_id || 'N/A' }}</strong></div>
              <div class="mstat"><small>Department</small><strong>{{ selectedPayslip.department || 'N/A' }}</strong></div>
              <div class="mstat"><small>Pay Date</small><strong>{{ formatDate(selectedPayslip.payment_date) }}</strong></div>
              <div class="mstat">
                <small>Status</small>
                <span :class="['status-badge', statusClass(selectedPayslip.status)]"><span class="dot"></span>{{ formatStatus(selectedPayslip.status) }}</span>
              </div>
            </div>

            <!-- Earnings / Deductions -->
            <div class="detail-split">
              <div class="detail-col">
                <h5 class="detail-heading"><span class="col-dot green"></span>Earnings</h5>
                <div class="line-items">
                  <div class="line-item"><span>Basic Salary</span><span>{{ formatCurrency(selectedPayslip.basic_salary) }}</span></div>
                  <div v-if="selectedPayslip.house_allowance > 0"     class="line-item"><span>Housing Allowance</span><span>{{ formatCurrency(selectedPayslip.house_allowance) }}</span></div>
                  <div v-if="selectedPayslip.transport_allowance > 0" class="line-item"><span>Transport Allowance</span><span>{{ formatCurrency(selectedPayslip.transport_allowance) }}</span></div>
                  <div v-if="selectedPayslip.lunch_allowance > 0"     class="line-item"><span>Lunch/Other Allowance</span><span>{{ formatCurrency(selectedPayslip.lunch_allowance) }}</span></div>
                  <div v-if="selectedPayslip.overtime_pay > 0"        class="line-item"><span>Overtime Pay</span><span>{{ formatCurrency(selectedPayslip.overtime_pay) }}</span></div>
                  <div v-if="selectedPayslip.bonuses > 0"             class="line-item"><span>Bonuses</span><span>{{ formatCurrency(selectedPayslip.bonuses) }}</span></div>
                </div>
                <div class="line-total"><span>Gross Earnings</span><span class="text-success">{{ formatCurrency(selectedPayslip.gross_salary) }}</span></div>
              </div>
              <div class="detail-col">
                <h5 class="detail-heading"><span class="col-dot red"></span>Deductions</h5>
                <div class="line-items">
                  <div v-for="(item, i) in getDynamicDeductions(selectedPayslip)" :key="i" class="line-item">
                    <span>{{ item.name }}</span><span>{{ formatCurrency(item.amount) }}</span>
                  </div>
                </div>
                <div class="line-total"><span>Total Deductions</span><span class="text-danger">{{ formatCurrency(selectedPayslip.total_deductions) }}</span></div>
              </div>
            </div>

            <!-- Net Pay -->
            <div class="net-pay-card">
              <div>
                <div class="np-label">NET PAYABLE</div>
                <div class="np-amount">{{ formatCurrency(selectedPayslip.net_pay) }}</div>
                <div class="np-words">{{ convertToWords(selectedPayslip.net_pay) }}</div>
              </div>
              <div class="np-bg">{{ getCurrencySymbol(currentCurrency) }}{{ currentCurrency }}</div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="selectedPayslip = null" class="btn-secondary">Close</button>
            <button @click="downloadPayslip(selectedPayslip)" class="btn-primary" :disabled="downloadingId === selectedPayslip?.id">
              <span v-if="downloadingId === selectedPayslip?.id" class="btn-spinner"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              {{ downloadingId === selectedPayslip?.id ? 'Generating…' : 'Download PDF' }}
            </button>
          </div>
        </div>
      </div>
    </transition>


    <!-- ═══════════════════════════════════════════ -->
    <!--  MODAL — Generate Single                   -->
    <!-- ═══════════════════════════════════════════ -->
    <transition name="modal-fade">
      <div v-if="showGenerateModal" class="modal-overlay" @click.self="closeModals">
        <div class="modal-box">
          <div class="modal-header">
            <div class="mh-left">
              <div class="mh-avatar icon-av">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </div>
              <h3 class="mh-name">Generate Single Payslip</h3>
            </div>
            <button @click="closeModals" class="mh-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="modal-body form-body">
            <div class="form-group">
              <label>Employee</label>
              <select v-model="generateForm.employee_id" @change="onEmployeeSelected($event.target.value)" required class="filter-select full-w">
                <option value="" disabled>Select Employee</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ getEmployeeName(emp) }}</option>
              </select>
              <small v-if="employees.length === 0" class="hint">No employees found for selected business.</small>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Start Date</label>
                <input type="date" v-model="generateForm.pay_period_start" required class="filter-select full-w" />
              </div>
              <div class="form-group">
                <label>End Date</label>
                <input type="date" v-model="generateForm.pay_period_end" required class="filter-select full-w" />
              </div>
            </div>
            <div class="form-group">
              <label>Basic Salary</label>
              <input type="number" v-model.number="generateForm.basic_salary" class="filter-select full-w" step="0.01" />
            </div>
            <div class="info-notice" v-if="currentBusiness">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              Payslip will be generated in <strong>{{ getCurrencySymbol(currentCurrency) }}{{ currentCurrency }}</strong> currency
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button @click="generatePayslip" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Generating…' : 'Generate' }}
            </button>
          </div>
        </div>
      </div>
    </transition>


    <!-- ═══════════════════════════════════════════ -->
    <!--  MODAL — Bulk Generate                     -->
    <!-- ═══════════════════════════════════════════ -->
    <transition name="modal-fade">
      <div v-if="showBulkGenerate" class="modal-overlay" @click.self="closeModals">
        <div class="modal-box">
          <div class="modal-header">
            <div class="mh-left">
              <div class="mh-avatar icon-av">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              </div>
              <h3 class="mh-name">Bulk Generate Payslips</h3>
            </div>
            <button @click="closeModals" class="mh-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="modal-body form-body">
            <div class="form-row">
              <div class="form-group">
                <label>Period Start</label>
                <input type="date" v-model="bulkForm.pay_period_start" required class="filter-select full-w" />
              </div>
              <div class="form-group">
                <label>Period End</label>
                <input type="date" v-model="bulkForm.pay_period_end" required class="filter-select full-w" />
              </div>
            </div>
            <div class="form-group">
              <label>Department</label>
              <select v-model="bulkForm.department" class="filter-select full-w">
                <option value="">All Departments</option>
                <option value="IT">IT</option>
                <option value="HR">HR</option>
                <option value="Finance">Finance</option>
                <option value="Sales">Sales</option>
                <option value="Operations">Operations</option>
              </select>
            </div>
            <div class="info-notice">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              Will generate payslips for <strong>{{ availableEmployees.length }}</strong> employees
              <span v-if="filters.business_id">in selected business</span>.
              <span v-if="currentBusiness"> Currency: <strong>{{ getCurrencySymbol(currentCurrency) }}{{ currentCurrency }}</strong></span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button @click="bulkGeneratePayslips" class="btn-primary" :disabled="submitting || availableEmployees.length === 0">
              {{ submitting ? 'Processing…' : 'Generate All' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { useBusinessStore } from '@/stores/business';

const businessStore = useBusinessStore();
const loading           = ref(false);
const error             = ref(null);
const payslips          = ref([]);
const employees         = ref([]);
const businesses        = ref([]);
const selectedPayslip   = ref(null);
const showGenerateModal = ref(false);
const showBulkGenerate  = ref(false);
const submitting        = ref(false);
const viewMode          = ref('grid');
const headerCardRef     = ref(null);
const showStickyBar     = ref(false);

// ─── NEW: Track which payslip ID is currently downloading ─────────────────────
const downloadingId     = ref(null);

// Currency symbols for African currencies
const currencySymbols = {
  'NGN': '₦', 'GHS': '₵', 'XOF': 'CFA', 'GMD': 'D', 'LRD': 'L$',
  'SLL': 'Le', 'MRU': 'UM', 'CVE': '$',
  'KES': 'KSh', 'UGX': 'USh', 'TZS': 'TSh', 'RWF': 'FRw',
  'BIF': 'FBu', 'ETB': 'Br', 'SOS': 'S', 'DJF': 'Fdj',
  'ERN': 'Nfk', 'SCR': '₨', 'COM': 'CF', 'MGA': 'Ar', 'MUR': '₨',
  'ZAR': 'R', 'ZMW': 'K', 'BWP': 'P', 'NAD': 'N$',
  'SZL': 'L', 'LSL': 'L', 'MWK': 'MK', 'MZN': 'MT',
  'AOA': 'Kz', 'ZWL': 'ZiG',
  'XAF': 'FCFA', 'CDF': 'FC', 'STN': 'Db',
  'EGP': 'E£', 'MAD': 'DH', 'DZD': 'DA', 'TND': 'DT',
  'LYD': 'LD', 'SDG': '£S'
};

const filters = reactive({
  pay_period: 'current', department: '', status: '',
  custom_month: '', business_id: '', start_date: '', end_date: ''
});
const generateForm = reactive({
  employee_id: '', pay_period_start: '', pay_period_end: '',
  payment_date: '', basic_salary: 0, overtime_hours: 0, overtime_rate: 0
});
const bulkForm = reactive({
  pay_period_start: '', pay_period_end: '', payment_date: '', department: '', employee_ids: []
});

// ── Computed ───────────────────────────────────────────
const currentBusiness = computed(() => businessStore.currentBusiness);
const currentCurrency = computed(() => {
  if (filters.business_id) {
    const selectedBiz = businesses.value.find(b => b.id === filters.business_id);
    if (selectedBiz) return selectedBiz.currency_code || 'ZMW';
  }
  return currentBusiness.value?.currency_code || 'ZMW';
});

const getFilterDisplayText = computed(() => {
  const parts = [];
  if (filters.business_id) { const b = businesses.value.find(b => b.id === filters.business_id); if (b) parts.push(b.name); }
  if      (filters.pay_period === 'current') parts.push('Current Month');
  else if (filters.pay_period === 'last')    parts.push('Last Month');
  else if (filters.custom_month)             parts.push(`Month: ${filters.custom_month}`);
  else if (filters.start_date && filters.end_date) parts.push(`${filters.start_date} → ${filters.end_date}`);
  return parts.length ? parts.join(' · ') : 'All Records';
});
const availableEmployees = computed(() =>
  employees.value.filter(e => !bulkForm.department || e.department === bulkForm.department)
);

// ── Currency Helper ───────────────────────────────────
const getCurrencySymbol = (currencyCode) => {
  return currencySymbols[currencyCode] || currencyCode || '$';
};

const formatCurrency = (amount) => {
  if (amount === null || amount === undefined) return '—';
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: currentCurrency.value,
    minimumFractionDigits: 2
  }).format(amount || 0).replace(/[A-Z]{3}/g, getCurrencySymbol(currentCurrency.value));
};

// ── Lifecycle ─────────────────────────────────────────
const handleScroll = () => {
  if (!headerCardRef.value) return;
  const rect = headerCardRef.value.getBoundingClientRect();
  showStickyBar.value = rect.bottom < 0;
};

onMounted(() => {
  initializeDates();
  fetchBusinesses();
  fetchEmployees();
  fetchPayslips();
  window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

// ── Data Fetching ─────────────────────────────────────
const initializeDates = () => {
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  const last  = new Date(now.getFullYear(), now.getMonth()+1, 0).toISOString().split('T')[0];
  const today = now.toISOString().split('T')[0];
  Object.assign(generateForm, { pay_period_start: first, pay_period_end: last, payment_date: today });
  Object.assign(bulkForm,     { pay_period_start: first, pay_period_end: last, payment_date: today });
};

const fetchBusinesses = async () => {
  try {
    const r = await axios.get('/api/admin/businesses');
    businesses.value = r.data.data || r.data.businesses || r.data || [];
  } catch {}
};

const fetchEmployees = async () => {
  try {
    const params = filters.business_id ? { business_id: filters.business_id } : {};
    const r = await axios.get('/api/admin/employees', { params });
    employees.value = r.data.data || r.data.employees || [];
  } catch {}
};

const fetchPayslips = async () => {
  loading.value = true; error.value = null;
  try {
    const params = { department: filters.department||undefined, status: filters.status||undefined, business_id: filters.business_id||undefined };
    if      (filters.pay_period === 'current') { params.pay_period = 'current'; }
    else if (filters.pay_period === 'last')    { params.pay_period = 'last'; }
    else if (filters.pay_period === 'custom' && filters.custom_month) {
      const [y,m] = filters.custom_month.split('-');
      params.start = new Date(y,m-1,1).toISOString().split('T')[0];
      params.end   = new Date(y,m,0).toISOString().split('T')[0];
    } else if (filters.pay_period === 'date_range') {
      if (filters.start_date) params.start = filters.start_date;
      if (filters.end_date)   params.end   = filters.end_date;
    }
    const r = await axios.get('/api/admin/payslips', { params });
    payslips.value = r.data.data || r.data;
  } catch { error.value = 'Failed to load payslips.'; }
  finally { loading.value = false; }
};

const handleBusinessChange = () => { fetchEmployees(); fetchPayslips(); };
const handlePeriodChange   = () => {
  if (filters.pay_period !== 'custom') filters.custom_month = '';
  if (filters.pay_period !== 'date_range') { filters.start_date = ''; filters.end_date = ''; }
  fetchPayslips();
};

// ── Helper Functions ──────────────────────────────────
const getEmployeeName = (emp) => {
  if (!emp) return 'Unknown';
  if (emp.user?.first_name) return `${emp.user.first_name} ${emp.user.last_name||''}`.trim();
  if (emp.employee_name)    return emp.employee_name;
  if (emp.first_name)       return `${emp.first_name} ${emp.last_name||''}`.trim();
  return emp.name || 'Unknown';
};

const getDynamicDeductions = (p) => {
  const list = [];
  if (p.paye > 0) list.push({ name:'PAYE Tax', amount:p.paye });
  if (p.breakdown?.deductions_breakdown?.statutory_breakdown) {
    p.breakdown.deductions_breakdown.statutory_breakdown.forEach(d => list.push({ name:d.name, amount:d.amount }));
  } else {
    if (p.napsa   > 0) list.push({ name:'NAPSA',   amount:p.napsa });
    if (p.nhima   > 0) list.push({ name:'NHIMA',   amount:p.nhima });
    if (p.pension > 0) list.push({ name:'Pension', amount:p.pension });
  }
  if (p.other_deductions > 0) list.push({ name:'Other Deductions', amount:p.other_deductions });
  return list;
};

const onEmployeeSelected = (id) => {
  const emp = employees.value.find(e => e.id == id);
  if (emp) generateForm.basic_salary = emp.base_salary;
};

// ── Actions ───────────────────────────────────────────
const viewPayslip = (p) => { selectedPayslip.value = p; };

const generatePayslip = async () => {
  submitting.value = true;
  try {
    await axios.post('/api/admin/payslips', { ...generateForm, generate_pdf: true, currency: currentCurrency.value });
    await fetchPayslips();
    closeModals();
  }
  catch (err) { alert(err.response?.data?.message || 'Generation failed'); }
  finally { submitting.value = false; }
};

const bulkGeneratePayslips = async () => {
  submitting.value = true;
  try {
    const ids = availableEmployees.value.map(e => e.id);
    if (!ids.length) { alert('No employees found.'); submitting.value = false; return; }
    for (const id of ids) {
      const emp = employees.value.find(e => e.id == id);
      await axios.post('/api/admin/payslips', {
        employee_id: id,
        pay_period_start: bulkForm.pay_period_start,
        pay_period_end: bulkForm.pay_period_end,
        payment_date: bulkForm.payment_date,
        basic_salary: emp.base_salary,
        generate_pdf: true,
        currency: currentCurrency.value
      });
    }
    await fetchPayslips();
    closeModals();
  } catch { alert('Some payslips may have failed.'); }
  finally { submitting.value = false; }
};

// ─────────────────────────────────────────────────────────────────────────────
// FIXED: downloadPayslip
//
// ROOT CAUSE OF THE OLD BUG:
//   The old version had two failure points that both silently fell back to the
//   client-side jsPDF skeleton:
//   1. `if (!p.pdf_available && !p.pdf_path) { generateClientPdf(p); return; }`
//      → skipped the server entirely if the DB flag wasn't set
//   2. `catch { generateClientPdf(p); }`
//      → swallowed any server error and produced the skeleton PDF
//
// THE FIX:
//   - Always call the server endpoint — PayslipController::download() always
//     regenerates the PDF before serving, so the DB flag is irrelevant here.
//   - Check the response Content-Type to detect JSON error responses that axios
//     receives as 200 blobs (some proxy setups).
//   - Never fall back to generateClientPdf() automatically — fail loudly instead
//     so the real server error is surfaced to the user.
// ─────────────────────────────────────────────────────────────────────────────
const downloadPayslip = async (p) => {
  // Prevent double-clicks
  if (downloadingId.value === p.id) return;
  downloadingId.value = p.id;

  try {
    // ALWAYS call the server — it regenerates the PDF fresh every time.
    // Do NOT check p.pdf_available first; the server handles missing PDFs.
    const response = await axios.get(`/api/admin/payslips/${p.id}/download`, {
      responseType: 'blob',
    });

    // Guard: some proxy/server configs return JSON errors with a 200 status
    // and blob response type — we need to detect and surface that.
    const contentType = response.headers['content-type'] || '';
    if (!contentType.includes('application/pdf')) {
      // Try to read the error message from the blob
      const errorText = await response.data.text();
      let errorMsg = 'PDF generation failed on the server.';
      try {
        const parsed = JSON.parse(errorText);
        errorMsg = parsed.message || errorMsg;
      } catch {
        // Not JSON — use raw text if it's short
        if (errorText.length < 200) errorMsg = errorText;
      }
      alert(`Download failed: ${errorMsg}`);
      return;
    }

    // Build a clean filename
    const empName = getEmployeeName(p).replace(/\s+/g, '_');
    const period  = p.pay_period_start
      ? new Date(p.pay_period_start).toLocaleDateString('en-GB', { year: 'numeric', month: '2-digit' }).replace(/\//g, '-')
      : 'payslip';
    const filename = `Payslip_${empName}_${period}.pdf`;

    // Trigger browser download
    const blobUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link    = document.createElement('a');
    link.href     = blobUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(blobUrl), 1500);

  } catch (err) {
    // Surface the real error — never silently fall back to skeleton PDF
    console.error('[PayslipView] Download failed for payslip', p.id, err);

    const status  = err.response?.status;
    const message = err.response?.data?.message; // may be undefined for blob responses

    if (status === 401) {
      alert('Session expired. Please refresh the page and log in again.');
    } else if (status === 403) {
      alert('You do not have permission to download this payslip.');
    } else if (status === 404) {
      alert('Payslip PDF could not be found. Please try regenerating it.');
    } else if (status === 500) {
      alert(`Server error while generating PDF. Please contact support.\n\n${message || ''}`);
    } else if (message) {
      alert(`Download failed: ${message}`);
    } else {
      alert('Download failed. Please check the browser console for details and try again.');
    }
    // ← NO generateClientPdf() fallback here intentionally.
    //   The skeleton jsPDF output has caused confusion; we surface the real error instead.
  } finally {
    downloadingId.value = null;
  }
};

const sendPayslip = async (p) => {
  if (!confirm('Send email notification?')) return;
  try { await axios.post(`/api/admin/payslips/${p.id}/send`); }
  catch { alert('Failed to send email.'); }
};

const closeModals    = () => { showGenerateModal.value = false; showBulkGenerate.value = false; };
const formatNumber   = (n) => new Intl.NumberFormat('en-ZM',{minimumFractionDigits:2}).format(n||0);
const formatDate     = (d, type='full') => {
  if (!d) return '—';
  const dt = new Date(d);
  return type==='month' ? dt.toLocaleDateString('en-US',{month:'long',year:'numeric'}) : dt.toLocaleDateString('en-GB');
};
const formatStatus   = (s) => s ? s.charAt(0).toUpperCase()+s.slice(1) : 'Unknown';
const convertToWords = (n) => `${formatNumber(n)} ${currentCurrency.value}`;
const statusClass    = (s) => ({paid:'status-success',generated:'status-info',draft:'status-warning'}[s]||'status-neutral');
const getInitials    = (name) => { const p=(name||'').split(' ').filter(Boolean); return ((p[0]?.[0]||'')+(p.length>1?p[p.length-1][0]:'')).toUpperCase(); };
const getAvatarColor = () => '#3b82f6';

// ── Watchers ──────────────────────────────────────────
watch(()=>filters.pay_period,  handlePeriodChange);
watch(()=>filters.department,  fetchPayslips);
watch(()=>filters.status,      fetchPayslips);
watch(()=>filters.start_date,  ()=>{ if(filters.pay_period==='date_range'&&filters.start_date) fetchPayslips(); });
watch(()=>filters.end_date,    ()=>{ if(filters.pay_period==='date_range'&&filters.end_date)   fetchPayslips(); });
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box;}

.payslip-view{
  min-height:100vh;
  background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%);
  font-family:'Inter',system-ui,-apple-system,sans-serif;
  color:#1e293b;
  padding:1.5rem 2rem 3rem;
  max-width:1300px;
  margin:0 auto;
}

/* ── Sticky Action Bar ───────────────────────────── */
.sticky-action-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 90;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.1);
}
.sticky-inner {
  max-width: 1300px;
  margin: 0 auto;
  padding: .65rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.sticky-title {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .875rem;
  font-weight: 700;
  color: #334155;
}
.sticky-title svg { color: #64748b; flex-shrink: 0; }
.sticky-actions { display: flex; align-items: center; gap: .5rem; }

.sticky-fade-enter-active { transition: opacity .2s ease, transform .2s ease; }
.sticky-fade-leave-active { transition: opacity .15s ease, transform .15s ease; }
.sticky-fade-enter-from,.sticky-fade-leave-to { opacity: 0; transform: translateY(-100%); }

/* ── Header ──────────────────────────────────────── */
.dashboard-header-card{
  position:relative;overflow:hidden;background:white;border-radius:16px;
  padding:1.5rem 1.75rem;margin-bottom:1.25rem;
  box-shadow:0 2px 4px -1px rgba(0,0,0,.05);border:1px solid #e2e8f0;
}
.header-accent{position:absolute;top:0;left:0;right:0;height:3px;background:#e2e8f0;}
.header-inner{display:flex;justify-content:space-between;align-items:center;gap:1.5rem;flex-wrap:wrap;}
.header-left{display:flex;align-items:center;gap:1rem;}
.header-avatar{width:52px;height:52px;border-radius:14px;flex-shrink:0;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;}
.header-text h1{margin:0;font-size:1.375rem;font-weight:700;color:#1e293b;line-height:1.2;}
.header-text p{margin:0;color:#64748b;font-size:.875rem;}
.header-badges{display:flex;align-items:center;gap:.4rem;margin-top:.2rem;flex-wrap:wrap;}
.badge-role{background:#f1f5f9;border:1px solid #e2e8f0;padding:.1rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;color:#475569;}
.currency-badge{background:#fef3c7;border:1px solid #fde68a;padding:.1rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;color:#92400e;display:inline-flex;align-items:center;gap:.25rem;}
.badge-filter{background:#f1f5f9;border:1px solid #e2e8f0;padding:.1rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;color:#475569;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.header-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;}

/* Layout toggle */
.layout-toggle{display:flex;background:#f1f5f9;border-radius:8px;padding:2px;gap:2px;}
.layout-btn{width:32px;height:32px;border:none;background:transparent;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;cursor:pointer;transition:all .15s;}
.layout-btn:hover{background:#e2e8f0;color:#475569;}
.layout-btn.active{background:white;color:#334155;box-shadow:0 1px 3px rgba(0,0,0,.1);}

/* Buttons */
.btn-primary{display:inline-flex;align-items:center;gap:.4rem;background:#334155;color:white;border:none;padding:.5rem 1.2rem;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;}
.btn-primary:hover:not(:disabled){background:#1e293b;transform:translateY(-1px);}
.btn-primary:disabled{opacity:.55;cursor:not-allowed;transform:none;}
.btn-outline{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;background:white;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;}
.btn-outline:hover{background:#f8fafc;border-color:#cbd5e1;}
.btn-secondary{padding:.5rem 1.2rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;}
.btn-secondary:hover{background:#e2e8f0;}

/* Loading spinner for buttons */
.btn-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin 0.7s linear infinite;flex-shrink:0;}
.btn-spinner.sm{width:11px;height:11px;border-color:rgba(71,85,105,.3);border-top-color:#475569;}

/* Section card */
.section-card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 2px 4px -1px rgba(0,0,0,.05);border:1px solid #e2e8f0;}

/* Controls bar */
.controls-bar{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.filters-row{display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;}
.filter-group{display:flex;flex-direction:column;gap:.25rem;}
.filter-group label{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;}
.filter-select{padding:.45rem .875rem;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#334155;font-size:.875rem;font-weight:500;cursor:pointer;transition:all .2s;font-family:inherit;min-width:148px;appearance:none;}
.filter-select.full-w{width:100%;min-width:0;appearance:auto;}
input.filter-select{min-width:138px;appearance:auto;}
.filter-select:focus{outline:none;border-color:#94a3b8;box-shadow:0 0 0 3px rgba(148,163,184,.15);}
.records-badge{font-size:.75rem;font-weight:700;color:#64748b;background:#f1f5f9;padding:.2rem .7rem;border-radius:9999px;white-space:nowrap;align-self:flex-end;}

/* Grid */
.payslip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(315px,1fr));gap:1.125rem;}
.payslip-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.125rem;display:flex;flex-direction:column;gap:.875rem;transition:transform .18s,box-shadow .18s;}
.payslip-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px -6px rgba(0,0,0,.1);border-color:#cbd5e1;}
.card-head{display:flex;align-items:flex-start;gap:.75rem;}
.card-avatar{width:42px;height:42px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.82rem;background:#3b82f6 !important;}
.card-meta{flex:1;min-width:0;display:flex;flex-direction:column;gap:.15rem;}
.card-name{font-size:.9rem;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.card-sub{display:flex;align-items:center;gap:.4rem;}
.dept-chip{font-size:.68rem;background:#f1f5f9;color:#64748b;padding:.05rem .4rem;border-radius:4px;font-weight:600;}
.biz-chip{display:inline-block;margin-top:.15rem;font-size:.68rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:.1rem .4rem;border-radius:4px;font-weight:600;}
.amount-strip{display:flex;background:white;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;}
.amount-cell{flex:1;padding:.5rem .65rem;display:flex;flex-direction:column;gap:.1rem;border-right:1px solid #e2e8f0;}
.amount-cell:last-child{border-right:none;}
.amt-lbl{font-size:.58rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.amt-val{font-size:.78rem;font-weight:700;color:#334155;font-family:'SFMono-Regular',Consolas,monospace;}
.amt-val.net-green{color:#10b981;font-size:.85rem;}
.small-val{font-size:.65rem;font-family:inherit;}
.card-actions{display:flex;gap:.4rem;}
.card-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:.3rem;padding:.4rem .5rem;border:1px solid #e2e8f0;background:white;color:#64748b;border-radius:7px;font-size:.7rem;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;}
.card-btn:hover:not(:disabled){background:#f1f5f9;color:#334155;border-color:#cbd5e1;}
.card-btn:disabled{opacity:.6;cursor:not-allowed;}
.card-btn.send:hover:not(:disabled){background:#f0fdf4;color:#059669;border-color:#bbf7d0;}

/* Table */
.table-wrap{border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;}
.ps-grid{display:grid;grid-template-columns:2fr 1fr 1.4fr 1fr 1fr .9fr .65fr;padding:.75rem 1rem;align-items:center;gap:.75rem;}
.ps-grid.with-biz{grid-template-columns:1.8fr 1fr 1fr 1.4fr 1fr 1fr .9fr .65fr;}
.list-header{background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.67rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.055em;}
.list-row{background:white;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;}
.list-row:last-child{border-bottom:none;}
.list-row:hover{background:#f8fafc;}
.emp-cell{display:flex;align-items:center;gap:.65rem;}
.emp-av{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.7rem;flex-shrink:0;background:#3b82f6 !important;}
.emp-name{font-size:.83rem;font-weight:700;color:#1e293b;}
.mono-id{font-family:'SFMono-Regular',Consolas,monospace;font-size:.7rem;color:#94a3b8;}
.biz-chip-sm{font-size:.7rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:.1rem .4rem;border-radius:4px;font-weight:600;}
.dept-tag{display:inline-block;padding:.18rem .5rem;background:#f1f5f9;color:#475569;border-radius:5px;font-size:.7rem;font-weight:600;border:1px solid #e2e8f0;}
.period-cell{display:flex;align-items:center;gap:.3rem;font-size:.75rem;color:#475569;flex-wrap:wrap;}
.period-sep{color:#94a3b8;font-size:.65rem;}
.text-right{text-align:right;}
.text-muted{color:#94a3b8;}
.text-success{color:#10b981;}
.text-danger{color:#ef4444;}
.fw-700{font-weight:700;}
.mono{font-family:'SFMono-Regular',Consolas,monospace;font-size:.78rem;}
.row-actions{display:flex;justify-content:flex-end;gap:.3rem;}
.icon-btn{width:30px;height:30px;border-radius:6px;border:1px solid #e2e8f0;background:white;color:#64748b;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;}
.icon-btn:hover:not(:disabled){background:#f1f5f9;color:#334155;border-color:#cbd5e1;}
.icon-btn:disabled{opacity:.6;cursor:not-allowed;}
.icon-btn.send:hover:not(:disabled){background:#f0fdf4;color:#059669;border-color:#bbf7d0;}

/* Status badges */
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:.22rem .6rem;border-radius:9999px;font-size:.68rem;font-weight:700;white-space:nowrap;}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.status-success{background:#d1fae5;color:#065f46;}
.status-info{background:#f1f5f9;color:#475569;}
.status-warning{background:#fef3c7;color:#92400e;}
.status-neutral{background:#f1f5f9;color:#64748b;}

/* Empty / loading */
.state-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.875rem;padding:4rem 2rem;text-align:center;color:#94a3b8;}
.state-empty p{margin:0;font-size:.875rem;color:#64748b;}
.spinner{width:40px;height:40px;border:3px solid #e2e8f0;border-top-color:#64748b;border-radius:50%;animation:spin 1s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(4px);z-index:100;display:flex;justify-content:center;align-items:center;padding:1rem;}
.modal-box{background:white;width:100%;max-width:480px;max-height:92vh;border-radius:16px;box-shadow:0 25px 60px rgba(0,0,0,.15);display:flex;flex-direction:column;overflow:hidden;border:1px solid #e2e8f0;animation:slideUp .22s ease-out;}
.modal-box.large{max-width:760px;}
@keyframes slideUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:none;}}
.modal-header{padding:1.375rem 1.625rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
.mh-left{display:flex;align-items:center;gap:.875rem;}
.mh-avatar{width:46px;height:46px;border-radius:11px;flex-shrink:0;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569;font-weight:900;font-size:.82rem;letter-spacing:.04em;}
.mh-avatar.icon-av{font-size:0;}
.mh-name{margin:0;font-size:1.05rem;font-weight:700;color:#1e293b;}
.mh-sub{margin:.15rem 0 0;font-size:.8rem;color:#64748b;}
.mh-close{width:34px;height:34px;border-radius:50%;background:white;border:1px solid #e2e8f0;color:#475569;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.mh-close:hover{background:#fee2e2;color:#dc2626;border-color:#fca5a5;}
.modal-body{padding:1.375rem 1.625rem;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:1.25rem;}
.form-body{gap:0;}
.modal-footer{padding:1.1rem 1.625rem;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;justify-content:flex-end;gap:.75rem;flex-shrink:0;}

/* Company strip */
.company-strip{text-align:center;border-bottom:1px solid #e2e8f0;padding-bottom:1rem;}
.co-name{font-size:1.1rem;font-weight:800;color:#0f172a;}
.co-addr{font-size:.8rem;color:#64748b;margin-top:.2rem;}
.period-pill{display:inline-block;margin-top:.5rem;background:#f1f5f9;padding:.2rem .875rem;border-radius:9999px;font-size:.8rem;font-weight:600;color:#334155;}

/* Modal stats */
.modal-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;}
.mstat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem;text-align:center;display:flex;flex-direction:column;align-items:center;gap:.25rem;}
.mstat small{font-size:.62rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;}
.mstat strong{font-size:.875rem;font-weight:700;color:#1e293b;}

/* Detail split */
.detail-split{display:grid;grid-template-columns:1fr 1fr;gap:1.125rem;}
.detail-col{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;}
.detail-heading{display:flex;align-items:center;gap:.45rem;font-size:.72rem;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem;padding-bottom:.5rem;border-bottom:1px solid #e2e8f0;}
.col-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.col-dot.green{background:#10b981;}
.col-dot.red{background:#ef4444;}
.line-items{display:flex;flex-direction:column;}
.line-item{display:flex;justify-content:space-between;align-items:center;padding:.35rem .35rem;border-radius:5px;font-size:.8rem;}
.line-item:hover{background:white;}
.line-item span:first-child{color:#64748b;}
.line-item span:last-child{font-family:'SFMono-Regular',Consolas,monospace;font-weight:600;font-size:.77rem;color:#1e293b;}
.line-total{display:flex;justify-content:space-between;font-weight:700;border-top:1px solid #e2e8f0;padding-top:.55rem;margin-top:.35rem;font-size:.875rem;}

/* Net Pay Card */
.net-pay-card{background:#1e293b;color:white;padding:1.5rem 1.75rem;border-radius:12px;display:flex;justify-content:space-between;align-items:center;position:relative;overflow:hidden;}
.np-label{font-size:.62rem;opacity:.75;letter-spacing:.14em;font-weight:700;margin-bottom:.35rem;}
.np-amount{font-size:2.25rem;font-weight:800;line-height:1.1;}
.np-words{font-size:.78rem;opacity:.65;margin-top:.25rem;font-style:italic;}
.np-bg{position:absolute;right:-10px;bottom:-22px;font-size:4.5rem;font-weight:900;opacity:.06;letter-spacing:-.05em;}

/* Form */
.form-group{margin-bottom:1.125rem;display:flex;flex-direction:column;gap:.3rem;}
.form-group label{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.875rem;}
.hint{font-size:.72rem;color:#94a3b8;margin-top:.15rem;}
.info-notice{display:flex;align-items:center;gap:.5rem;padding:.7rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;color:#475569;margin-bottom:.5rem;}
.info-notice svg{stroke:#475569 !important;}
.info-notice strong{color:#334155;font-weight:700;}

/* Transitions */
.modal-fade-enter-active,.modal-fade-leave-active{transition:opacity .22s ease;}
.modal-fade-enter-from,.modal-fade-leave-to{opacity:0;}

/* Responsive */
@media(max-width:900px){
  .ps-grid{grid-template-columns:2fr 1fr 1fr 1fr .85fr .7fr;}
  .ps-grid.with-biz{grid-template-columns:1.8fr 1fr 1fr 1fr .9fr .7fr;}
  .ps-grid>:nth-child(3){display:none;}
  .modal-stats{grid-template-columns:1fr 1fr;}
  .detail-split{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .payslip-view{padding:1rem 1rem 2rem;}
  .header-inner{flex-direction:column;align-items:flex-start;}
  .header-actions{width:100%;flex-wrap:wrap;}
  .payslip-grid{grid-template-columns:1fr;}
  .table-wrap .list-header{display:none;}
  .ps-grid{grid-template-columns:1fr auto;gap:.5rem;}
  .form-row{grid-template-columns:1fr;}
  .sticky-inner{padding:.65rem 1rem;}
  .sticky-title{display:none;}
}
@media(max-width:480px){
  .amount-strip{flex-direction:column;}
  .amount-cell{border-right:none;border-bottom:1px solid #e2e8f0;}
  .amount-cell:last-child{border-bottom:none;}
  .modal-stats{grid-template-columns:1fr 1fr;}
}
</style>