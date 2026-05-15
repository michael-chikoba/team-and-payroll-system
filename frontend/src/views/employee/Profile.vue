<template>
  <div class="profile-view">
    <div class="profile-container">

      <!-- LEFT COLUMN: Identity Card -->
      <aside class="profile-sidebar">
        <div class="identity-card">
          <div class="profile-header-bg"></div>

          <div class="avatar-wrapper">
            <div class="avatar-container">
              <img
                :src="profilePicUrl || '/default-avatar.png'"
                alt="Profile"
                class="profile-pic"
                @error="handleImageError"
              />
              <label for="profile-pic-upload" class="avatar-overlay">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hero-icon">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                <span class="upload-text">Change</span>
                <input id="profile-pic-upload" type="file" accept="image/*" @change="handleProfilePicUpload" ref="profilePicInput" />
              </label>
            </div>
          </div>

          <div class="identity-info">
            <h2 class="user-fullname">{{ form.first_name }} {{ form.last_name }}</h2>
            <p class="user-email">{{ form.email }}</p>
            <div class="user-role-badge" v-if="employeeInfo">{{ employeeInfo.position }}</div>
          </div>

          <div class="identity-stats" v-if="employeeInfo">
            <div class="stat-item">
              <span class="stat-label">ID</span>
              <span class="stat-value">{{ employeeInfo.employee_id }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Dept</span>
              <span class="stat-value">{{ employeeInfo.department }}</span>
            </div>
          </div>

          <!-- Supervisor quick-card -->
          <div class="supervisor-card" v-if="employeeInfo && employeeInfo.manager">
            <span class="supervisor-label">Direct Supervisor</span>
            <div class="supervisor-info">
              <div class="supervisor-avatar">
                {{ getSupervisorInitials(employeeInfo.manager) }}
              </div>
              <div class="supervisor-details">
                <span class="supervisor-name">{{ getManagerName(employeeInfo.manager) }}</span>
              </div>
            </div>
            <button class="btn-msg-supervisor" @click="openMessageSupervisor">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
              </svg>
              Message Supervisor
            </button>
          </div>
        </div>
      </aside>

      <!-- RIGHT COLUMN: Tabbed Content -->
      <main class="profile-content">
        <nav class="profile-tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="['tab-btn', { active: activeTab === tab.id }]"
          >
            <!-- Personal Details Icon -->
            <svg v-if="tab.id === 'personal'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <!-- Job Details Icon -->
            <svg v-if="tab.id === 'job'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            <!-- Bank Details Icon -->
            <svg v-if="tab.id === 'bank'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <!-- Security Icon -->
            <svg v-if="tab.id === 'security'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <!-- Documents Icon -->
            <svg v-if="tab.id === 'documents'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            <!-- Message Icon -->
            <svg v-if="tab.id === 'messages'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ tab.name }}
            <span v-if="tab.id === 'messages' && unreadMessageCount > 0" class="tab-badge">{{ unreadMessageCount }}</span>
          </button>
        </nav>

        <div class="tab-panel">

          <!-- TAB 1: Personal Details -->
          <div v-if="activeTab === 'personal'" class="fade-in">
            <div class="panel-header">
              <h3>Personal Details</h3>
              <p>Update your personal and contact information.</p>
            </div>

            <form @submit.prevent="updateProfile" class="modern-form">
              <div class="section-label">Basic Information</div>
              <div class="form-grid">
                <div class="form-group">
                  <label>First Name</label>
                  <input v-model="form.first_name" type="text" required />
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input v-model="form.last_name" type="text" required />
                </div>
                <div class="form-group">
                  <label>Email Address</label>
                  <input v-model="form.email" type="email" required />
                </div>
                <div class="form-group">
                  <label>Phone Number</label>
                  <input v-model="form.phone" type="tel" />
                </div>
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input v-model="form.date_of_birth" type="date" :max="today" />
                </div>
                <div class="form-group">
                  <label>National ID / Passport</label>
                  <input v-model="form.national_id" type="text" />
                </div>
                <div class="form-group full-width">
                  <label>Residential Address</label>
                  <textarea v-model="form.address" rows="2"></textarea>
                </div>
              </div>

              <div class="section-label">Emergency Contact</div>
              <div class="form-grid">
                <div class="form-group">
                  <label>Contact Name</label>
                  <input v-model="form.emergency_contact.name" type="text" placeholder="Full name" />
                </div>
                <div class="form-group">
                  <label>Relationship</label>
                  <select v-model="form.emergency_contact.relationship">
                    <option value="">Select relationship</option>
                    <option value="spouse">Spouse</option>
                    <option value="parent">Parent</option>
                    <option value="sibling">Sibling</option>
                    <option value="child">Child</option>
                    <option value="friend">Friend</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Contact Phone</label>
                  <input v-model="form.emergency_contact.phone" type="tel" placeholder="+260 97x xxx xxx" />
                </div>
                <div class="form-group">
                  <label>Contact Email <span class="optional-label">(optional)</span></label>
                  <input v-model="form.emergency_contact.email" type="email" placeholder="email@example.com" />
                </div>
              </div>

              <div v-if="formError" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ formError }}
              </div>
              <div v-if="successMessage" class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ successMessage }}
              </div>

              <div class="form-actions">
                <button type="button" @click="resetForm" class="btn-ghost">Reset Changes</button>
                <button type="submit" class="btn-primary" :disabled="updatingProfile">
                  <svg v-if="!updatingProfile" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                  </svg>
                  {{ updatingProfile ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 2: Job Details (Read Only) -->
          <div v-if="activeTab === 'job'" class="fade-in">
            <div class="panel-header">
              <h3>Employment Information</h3>
              <p>Your official employment records (Read-only).</p>
            </div>

            <div v-if="employeeInfo" class="info-grid">
              <div class="info-card">
                <span class="info-label">Employee ID</span>
                <span class="info-value">{{ employeeInfo.employee_id }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Position</span>
                <span class="info-value">{{ employeeInfo.position }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Department</span>
                <span class="info-value">{{ employeeInfo.department }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Employment Type</span>
                <span class="info-value">{{ formatEmploymentType(employeeInfo.employment_type) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Hire Date</span>
                <span class="info-value">{{ formatDate(employeeInfo.hire_date) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Direct Supervisor</span>
                <span class="info-value">{{ getManagerName(employeeInfo.manager) }}</span>
              </div>
            </div>
          </div>

          <!-- TAB 3: Bank Details -->
          <div v-if="activeTab === 'bank'" class="fade-in">
            <div class="panel-header">
              <h3>Bank Details</h3>
              <p>Your payment information is encrypted and only used for payroll processing.</p>
            </div>

            <div class="bank-notice">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="notice-icon">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
              <div>
                <strong>Secure &amp; Encrypted</strong>
                <p>Your bank details are encrypted at rest. Changes take effect from the next payroll cycle.</p>
              </div>
            </div>

            <form @submit.prevent="updateBankDetails" class="modern-form">
              <div class="form-grid">
                <div class="form-group">
                  <label>Bank Name</label>
                  <select v-model="bankForm.bank_name">
                    <option value="">Select bank</option>
                    <option value="Zanaco">Zanaco</option>
                    <option value="FNB Zambia">FNB Zambia</option>
                    <option value="Standard Chartered">Standard Chartered</option>
                    <option value="Stanbic Bank">Stanbic Bank</option>
                    <option value="Absa Zambia">Absa Zambia</option>
                    <option value="Indo Zambia Bank">Indo Zambia Bank</option>
                    <option value="Atlas Mara">Atlas Mara</option>
                    <option value="Finance Bank">Finance Bank</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Account Name</label>
                  <input v-model="bankForm.account_name" type="text" placeholder="As it appears on your bank card" />
                </div>
                <div class="form-group">
                  <label>Account Number</label>
                  <input v-model="bankForm.account_number" type="text" placeholder="e.g. 1234567890" :class="{ 'error-border': bankForm.account_number && !isValidAccountNumber }" />
                  <span v-if="bankForm.account_number && !isValidAccountNumber" class="field-error">Account number must be 8–16 digits</span>
                </div>
                <div class="form-group">
                  <label>Branch Code <span class="optional-label">(optional)</span></label>
                  <input v-model="bankForm.branch_code" type="text" placeholder="e.g. 260001" />
                </div>
                <div class="form-group full-width">
                  <label>SWIFT / BIC Code <span class="optional-label">(for international transfers)</span></label>
                  <input v-model="bankForm.swift_code" type="text" placeholder="e.g. ZNCOZMLU" />
                </div>
              </div>

              <div v-if="bankError" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ bankError }}
              </div>
              <div v-if="bankSuccess" class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ bankSuccess }}
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="updatingBank || !isValidAccountNumber">
                  <svg v-if="!updatingBank" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                  </svg>
                  {{ updatingBank ? 'Saving...' : 'Save Bank Details' }}
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 4: Security -->
          <div v-if="activeTab === 'security'" class="fade-in">
            <div class="panel-header">
              <h3>Security Settings</h3>
              <p>Manage your password and account security.</p>
            </div>

            <form @submit.prevent="updatePassword" class="modern-form password-form-wrapper">
              <div class="form-group">
                <label>Current Password</label>
                <input v-model="passwordForm.current_password" type="password" required />
              </div>
              <div class="password-split">
                <div class="form-group">
                  <label>New Password</label>
                  <input v-model="passwordForm.password" type="password" required minlength="8" />
                  <div class="password-strength" v-if="passwordForm.password">
                    <div class="strength-bar">
                      <div class="strength-fill" :class="passwordStrength.class" :style="{ width: passwordStrength.width }"></div>
                    </div>
                    <span class="strength-label" :class="passwordStrength.class">{{ passwordStrength.label }}</span>
                  </div>
                </div>
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    required
                    :class="{ 'error-border': passwordForm.password && passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation }"
                  />
                </div>
              </div>

              <div v-if="passwordError" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ passwordError }}
              </div>
              <div v-if="passwordSuccess" class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ passwordSuccess }}
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="updatingPassword || (passwordForm.password !== passwordForm.password_confirmation)">
                  <svg v-if="!updatingPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  {{ updatingPassword ? 'Updating...' : 'Update Password' }}
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 5: Documents -->
          <div v-if="activeTab === 'documents'" class="fade-in">
            <div class="panel-header">
              <h3>My Documents</h3>
              <p>Manage your official files and contracts.</p>
            </div>

            <div class="upload-zone">
              <label for="document-upload" class="upload-trigger">
                <div class="upload-content">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="upload-hero-icon">
                    <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                  </svg>
                  <span class="upload-text">Click to upload files</span>
                  <span class="upload-sub">PDF, DOC, JPG (Max 5MB)</span>
                </div>
                <input id="document-upload" type="file" accept=".pdf,.doc,.docx,.jpg,.png" multiple @change="handleDocumentUpload" ref="documentInput" />
              </label>
            </div>

            <div v-if="documents.length > 0" class="docs-list">
              <div v-for="doc in documents" :key="doc.id" class="doc-item">
                <div class="doc-file-icon">
                  <svg v-if="getFileType(doc.file_name) === 'pdf'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon pdf">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                  </svg>
                  <svg v-else-if="getFileType(doc.file_name) === 'doc'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon doc">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon generic">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>
                  </svg>
                </div>
                <div class="doc-details">
                  <span class="doc-name">{{ doc.file_name }}</span>
                  <span class="doc-meta">{{ formatDate(doc.created_at) }} • {{ doc.type || 'General' }}</span>
                </div>
                <div class="doc-tools">
                  <button @click="downloadDocument(doc.id)" class="tool-btn download" title="Download">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tool-icon">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </button>
                  <button @click="deleteDocument(doc.id)" class="tool-btn delete" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tool-icon">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="empty-docs">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
              </svg>
              <p>No documents uploaded yet.</p>
            </div>
          </div>

          <!-- TAB 6: Message Supervisor -->
          <div v-if="activeTab === 'messages'" class="fade-in">
            <div class="panel-header">
              <h3>Message Supervisor</h3>
              <p>Send a private message directly to your direct supervisor.</p>
            </div>

            <!-- No supervisor state -->
            <div v-if="(!employeeInfo || !employeeInfo.manager) && supervisorMessages.length === 0" class="empty-docs">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
              <p>No supervisor assigned to your account yet.</p>
            </div>

            <template v-else>
              <!-- Supervisor identity strip -->
              <div class="msg-supervisor-header">
                <div class="supervisor-avatar large">
                  {{ getSupervisorDisplayInitials() }}
                </div>
                <div>
                  <p class="msg-sup-name">{{ getSupervisorDisplayName() }}</p>
                  <p class="msg-sup-role">Your direct supervisor</p>
                </div>
              </div>

              <!-- Message thread -->
              <div class="message-thread" ref="messageThread">
                <div v-if="loadingMessages" class="thread-loading">
                  <div class="spinner small"></div>
                  <span>Loading messages…</span>
                </div>

                <div v-else-if="supervisorMessages.length === 0" class="thread-empty">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon small">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                  </svg>
                  <p>No messages yet. Start the conversation below.</p>
                </div>

                <template v-else>
                  <div
                    v-for="msg in supervisorMessages"
                    :key="msg.id"
                    :class="['message-bubble', msg.sender_id === authStore.user.id ? 'bubble-mine' : 'bubble-theirs']"
                  >
                    <div class="bubble-meta">
                      <span class="bubble-sender">{{ msg.sender_id === authStore.user.id ? 'You' : getSenderName(msg) }}</span>
                      <span class="bubble-time">{{ formatMessageTime(msg.created_at) }}</span>
                    </div>
                    <div class="bubble-body">{{ msg.message }}</div>
                    <span v-if="msg.sender_id === authStore.user.id" class="bubble-status" :title="msg.read_at ? 'Read' : 'Delivered'">
                      <svg v-if="msg.read_at" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="tick-icon read">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="tick-icon">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                    </span>
                  </div>
                </template>
              </div>

              <!-- Compose area -->
              <div class="message-compose">
                <div class="subject-row" v-if="!hasActiveThread">
                  <label class="compose-label">Subject</label>
                  <select v-model="messageForm.category" class="subject-select">
                    <option value="">Select topic (optional)</option>
                    <option value="leave">Leave / Time-off query</option>
                    <option value="performance">Performance review</option>
                    <option value="payroll">Payroll / Benefits</option>
                    <option value="workload">Workload concern</option>
                    <option value="schedule">Schedule issue</option>
                    <option value="general">General question</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div class="compose-area">
                  <textarea
                    v-model="messageForm.message"
                    :placeholder="messagePlaceholder"
                    rows="3"
                    class="compose-textarea"
                    @keydown.enter.ctrl.prevent="sendMessage"
                    :disabled="sendingMessage"
                  ></textarea>
                  <div class="compose-actions">
                    <span class="compose-hint">Ctrl+Enter to send</span>
                    <button
                      class="btn-primary btn-send"
                      @click="sendMessage"
                      :disabled="!messageForm.message.trim() || sendingMessage"
                    >
                      <svg v-if="!sendingMessage" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                      </svg>
                      {{ sendingMessage ? 'Sending…' : 'Send' }}
                    </button>
                  </div>
                </div>

                <div v-if="messageError" class="alert alert-error" style="margin-top:0.75rem">
                  {{ messageError }}
                </div>
              </div>
            </template>
          </div>

        </div>
      </main>
    </div>

    <!-- Global Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'Profile',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      activeTab: 'personal',
      tabs: [
        { id: 'personal',  name: 'Personal Details' },
        { id: 'job',       name: 'Job Details' },
        { id: 'bank',      name: 'Bank Details' },
        { id: 'security',  name: 'Security' },
        { id: 'documents', name: 'Documents' },
        { id: 'messages',  name: 'Supervisor' }
      ],

      // Personal form
      form: {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        national_id: '',
        address: '',
        emergency_contact: {
          name: '',
          relationship: '',
          phone: '',
          email: ''
        }
      },

      // Bank details form
      bankForm: {
        bank_name: '',
        account_name: '',
        account_number: '',
        branch_code: '',
        swift_code: ''
      },

      // Password form
      passwordForm: {
        current_password: '',
        password: '',
        password_confirmation: ''
      },

      // Message form
      messageForm: {
        message: '',
        category: ''
      },

      employeeInfo: null,
      profilePicUrl: '',
      documents: [],
      supervisorMessages: [],

      today: new Date().toISOString().split('T')[0],

      // Loading / status flags
      loading: false,
      updatingProfile: false,
      updatingPassword: false,
      updatingBank: false,
      uploadingDoc: false,
      sendingMessage: false,
      loadingMessages: false,

      // Alerts
      formError: null,
      successMessage: null,
      passwordError: null,
      passwordSuccess: null,
      bankError: null,
      bankSuccess: null,
      messageError: null,

      originalFormData: null,
      originalBankData: null,
      unreadMessageCount: 0,
      pollingInterval: null
    }
  },

  computed: {
    isValidAccountNumber() {
      return /^\d{8,16}$/.test(this.bankForm.account_number)
    },

    hasActiveThread() {
      return this.supervisorMessages.length > 0
    },

    messagePlaceholder() {
      const cat = this.messageForm.category
      const placeholders = {
        leave:       'e.g. I wanted to ask about my upcoming leave request for next month…',
        performance: 'e.g. I would appreciate feedback on my recent project deliverables…',
        payroll:     'e.g. I noticed a discrepancy in my last payslip for the transport allowance…',
        workload:    'e.g. I am finding the current sprint volume difficult to manage…',
        schedule:    'e.g. My shift on Friday conflicts with a prior commitment…',
        general:     'Type your message…',
        other:       'Type your message…'
      }
      return placeholders[cat] || 'Type your message to your supervisor…'
    },

    passwordStrength() {
      const p = this.passwordForm.password
      if (!p) return { class: '', width: '0%', label: '' }
      let score = 0
      if (p.length >= 8)  score++
      if (p.length >= 12) score++
      if (/[A-Z]/.test(p)) score++
      if (/[0-9]/.test(p)) score++
      if (/[^A-Za-z0-9]/.test(p)) score++

      if (score <= 1) return { class: 'strength-weak',   width: '20%',  label: 'Weak' }
      if (score <= 2) return { class: 'strength-fair',   width: '50%',  label: 'Fair' }
      if (score <= 3) return { class: 'strength-good',   width: '75%',  label: 'Good' }
      return              { class: 'strength-strong', width: '100%', label: 'Strong' }
    }
  },

  mounted() {
    this.fetchProfile()
  },

  watch: {
    activeTab(newTab) {
      if (newTab === 'messages') {
        this.fetchSupervisorMessages()
        this.startMessagePolling()
      } else {
        this.stopMessagePolling()
      }
    }
  },

  beforeUnmount() {
    this.stopMessagePolling()
  },

  methods: {
    // ─── Profile ─────────────────────────────────────────────────

    async fetchProfile() {
      this.loading = true
      this.error = null
      try {
        const [profileRes, docsRes] = await Promise.all([
          axios.get('/api/profile'),
          axios.get('/api/employee/documents').catch(() => ({ data: { data: [] } }))
        ])

        const user     = profileRes.data.user     || profileRes.data
        const employee = profileRes.data.employee?.data || profileRes.data.employee

        // Parse emergency_contact — may arrive as JSON string or object
        let ec = employee?.emergency_contact || {}
        if (typeof ec === 'string') { try { ec = JSON.parse(ec) } catch { ec = {} } }

        this.form = {
          first_name:  user.first_name  || '',
          last_name:   user.last_name   || '',
          email:       user.email       || '',
          phone:       employee?.phone  || '',
          date_of_birth: employee?.date_of_birth
            ? new Date(employee.date_of_birth).toISOString().split('T')[0]
            : '',
          national_id: employee?.national_id || '',
          address:     employee?.address     || '',
          emergency_contact: {
            name:         ec.name         || '',
            relationship: ec.relationship || '',
            phone:        ec.phone        || '',
            email:        ec.email        || ''
          }
        }

        this.originalFormData = JSON.parse(JSON.stringify(this.form))

        if (employee) {
          this.employeeInfo = {
            employee_id:    employee.employee_id,
            position:       employee.position,
            department:     employee.department,
            employment_type: employee.employment_type,
            hire_date:      employee.hire_date,
            manager:        employee.manager || null
          }

          // Populate bank form
          let bd = employee.bank_details || {}
          if (typeof bd === 'string') { try { bd = JSON.parse(bd) } catch { bd = {} } }
          this.bankForm = {
            bank_name:      bd.bank_name      || '',
            account_name:   bd.account_name   || '',
            account_number: bd.account_number || '',
            branch_code:    bd.branch_code    || '',
            swift_code:     bd.swift_code     || ''
          }
          this.originalBankData = { ...this.bankForm }
        }

        this.profilePicUrl = employee?.profile_pic ? `/storage/${employee.profile_pic}` : ''
        this.documents     = docsRes.data.data || []

        // Unread count
        this.fetchUnreadCount()

      } catch (err) {
        this.handleApiError(err, 'Failed to load profile')
      } finally {
        this.loading = false
      }
    },

    async updateProfile() {
      this.updatingProfile = true
      this.formError       = null
      this.successMessage  = null

      try {
        const payload = {}
        const flat = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'national_id', 'address']
        flat.forEach(key => {
          if (this.form[key] !== this.originalFormData[key]) payload[key] = this.form[key]
        })

        // Always send emergency_contact as object
        payload.emergency_contact = this.form.emergency_contact

        if (Object.keys(payload).length === 1 &&
            JSON.stringify(payload.emergency_contact) === JSON.stringify(this.originalFormData.emergency_contact)) {
          this.formError = 'No changes detected'
          this.updatingProfile = false
          return
        }

        await axios.put('/api/profile', payload)

        if (this.authStore.user) {
          this.authStore.user.first_name = this.form.first_name
          this.authStore.user.last_name  = this.form.last_name
          this.authStore.user.email      = this.form.email
        }

        this.originalFormData = JSON.parse(JSON.stringify(this.form))
        this.successMessage   = 'Profile updated successfully!'
        setTimeout(() => this.successMessage = null, 3000)

      } catch (err) {
        this.handleApiError(err, 'Failed to update profile')
      } finally {
        this.updatingProfile = false
      }
    },

    // ─── Bank Details ─────────────────────────────────────────────

    async updateBankDetails() {
      this.updatingBank = true
      this.bankError    = null
      this.bankSuccess  = null

      try {
        await axios.put('/api/profile', { bank_details: this.bankForm })
        this.originalBankData = { ...this.bankForm }
        this.bankSuccess = 'Bank details saved. Changes take effect from the next payroll cycle.'
        setTimeout(() => this.bankSuccess = null, 5000)
      } catch (err) {
        this.bankError = err.response?.data?.message || 'Failed to save bank details'
      } finally {
        this.updatingBank = false
      }
    },

    // ─── Password ─────────────────────────────────────────────────

    async updatePassword() {
      if (this.passwordForm.password.length < 8) {
        this.passwordError = 'New password must be at least 8 characters long'
        return
      }
      if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
        this.passwordError = 'Passwords do not match'
        return
      }

      this.updatingPassword = true
      this.passwordError    = null

      try {
        await axios.post('/api/profile/password', this.passwordForm)
        this.resetPasswordForm()
        this.passwordSuccess = 'Password updated successfully!'
        setTimeout(() => this.passwordSuccess = null, 3000)
      } catch (err) {
        this.handleApiError(err, 'Failed to update password', 'password')
      } finally {
        this.updatingPassword = false
      }
    },

    resetPasswordForm() {
      this.passwordForm = { current_password: '', password: '', password_confirmation: '' }
    },

    // ─── Supervisor Messaging ─────────────────────────────────────

    async fetchSupervisorMessages() {
      this.loadingMessages = true
      try {
        const res = await axios.get('/api/supervisor-messages')
        this.supervisorMessages = res.data.data || []
        
        // If we have messages but no manager info, try to extract from the first message
        if (this.supervisorMessages.length > 0 && this.supervisorMessages[0].supervisor) {
          const supervisor = this.supervisorMessages[0].supervisor
          if (this.employeeInfo && !this.employeeInfo.manager) {
            this.employeeInfo.manager = {
              id: supervisor.id,
              first_name: supervisor.first_name,
              last_name: supervisor.last_name,
              email: supervisor.email
            }
          }
        }
        
        this.$nextTick(() => this.scrollThreadToBottom())
      } catch (err) {
        console.error('Failed to fetch messages:', err)
        this.supervisorMessages = []
      } finally {
        this.loadingMessages = false
      }
    },

    async fetchUnreadCount() {
      try {
        const res = await axios.get('/api/supervisor-messages/unread-count')
        this.unreadMessageCount = res.data.count || 0
      } catch { /* silent */ }
    },

    async sendMessage() {
      if (!this.messageForm.message.trim()) return
      this.sendingMessage = true
      this.messageError   = null

      try {
        const res = await axios.post('/api/supervisor-messages', {
          message:  this.messageForm.message.trim(),
          category: this.messageForm.category || null
        })
        
        const newMessage = res.data.data
        this.supervisorMessages.push(newMessage)
        
        // If this is the first message and we have supervisor data, update employeeInfo
        if (newMessage.supervisor && this.employeeInfo && !this.employeeInfo.manager) {
          this.employeeInfo.manager = {
            id: newMessage.supervisor.id,
            first_name: newMessage.supervisor.first_name,
            last_name: newMessage.supervisor.last_name,
            email: newMessage.supervisor.email
          }
        }
        
        this.messageForm.message = ''
        this.messageForm.category = ''
        this.$nextTick(() => this.scrollThreadToBottom())
      } catch (err) {
        this.messageError = err.response?.data?.message || 'Failed to send message. Please try again.'
      } finally {
        this.sendingMessage = false
      }
    },

    openMessageSupervisor() {
      this.activeTab = 'messages'
    },

    scrollThreadToBottom() {
      const el = this.$refs.messageThread
      if (el) el.scrollTop = el.scrollHeight
    },

    startMessagePolling() {
      this.pollingInterval = setInterval(() => {
        this.fetchSupervisorMessages()
        this.fetchUnreadCount()
      }, 15000) // poll every 15 s
    },

    stopMessagePolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval)
        this.pollingInterval = null
      }
    },

    // ─── Profile Picture ──────────────────────────────────────────

    async handleProfilePicUpload(event) {
      const file = event.target.files[0]
      if (!file) return
      const formData = new FormData()
      formData.append('profile_pic', file)
      try {
        const response = await axios.post('/api/employee/profile-pic', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.profilePicUrl = response.data.profile_pic_url || `/storage/${response.data.profile_pic}`
      } catch (err) {
        this.handleApiError(err, 'Failed to upload profile picture')
      }
    },

    handleImageError() {
      this.profilePicUrl = ''
    },

    // ─── Documents ────────────────────────────────────────────────

    async handleDocumentUpload(event) {
      const files = Array.from(event.target.files)
      if (files.length === 0) return
      this.uploadingDoc = true
      try {
        const formData = new FormData()
        for (const file of files) formData.append('documents', file)
        const response = await axios.post('/api/employee/documents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.documents = [...this.documents, ...(response.data.newDocuments || [])]
      } catch (err) {
        this.handleApiError(err, 'Failed to upload documents')
      } finally {
        this.uploadingDoc = false
        event.target.value = ''
      }
    },

    async downloadDocument(id) {
      try {
        const response = await axios.get(`/api/employee/documents/${id}/download`, { responseType: 'blob' })
        const url  = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `document-${id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (err) {
        console.error(err)
      }
    },

    async deleteDocument(id) {
      if (!confirm('Delete this document?')) return
      try {
        await axios.delete(`/api/employee/documents/${id}`)
        this.documents = this.documents.filter(doc => doc.id !== id)
      } catch (err) {
        console.error(err)
      }
    },

    // ─── Helpers ──────────────────────────────────────────────────

    resetForm() {
      if (this.originalFormData) this.form = JSON.parse(JSON.stringify(this.originalFormData))
      this.formError = null
    },

    getFileType(fileName) {
      const ext = fileName?.split('.').pop()?.toLowerCase()
      if (ext === 'pdf') return 'pdf'
      if (['doc', 'docx'].includes(ext)) return 'doc'
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'img'
      return 'generic'
    },

    handleApiError(err, defaultMsg, target = 'form') {
      let msg = defaultMsg
      if (err.response?.data?.message) msg = err.response.data.message
      if (err.response?.data?.errors) {
        const errs = err.response.data.errors
        msg = Object.values(errs).flat().join('. ')
      }
      if (target === 'password') this.passwordError = msg
      else this.formError = msg
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-ZM', { year: 'numeric', month: 'short', day: 'numeric' })
    },

    formatMessageTime(dateStr) {
      if (!dateStr) return ''
      const d   = new Date(dateStr)
      const now = new Date()
      const isToday = d.toDateString() === now.toDateString()
      if (isToday) return d.toLocaleTimeString('en-ZM', { hour: '2-digit', minute: '2-digit' })
      return d.toLocaleDateString('en-ZM', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
    },

    formatEmploymentType(type) {
      if (!type) return 'N/A'
      return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
    },

    getManagerName(manager) {
      if (!manager) return 'N/A'
      if (typeof manager === 'object') {
        return `${manager.first_name || ''} ${manager.last_name || ''}`.trim() || 'Supervisor'
      }
      return 'Supervisor'
    },

    getSupervisorInitials(manager) {
      if (!manager) return '?'
      if (typeof manager === 'object') {
        const firstInitial = (manager.first_name || '')[0] || ''
        const lastInitial = (manager.last_name || '')[0] || ''
        return `${firstInitial}${lastInitial}`.toUpperCase() || '?'
      }
      return '?'
    },

    getSupervisorDisplayName() {
      // Try from employeeInfo first
      if (this.employeeInfo && this.employeeInfo.manager) {
        return this.getManagerName(this.employeeInfo.manager)
      }
      // Try from messages
      if (this.supervisorMessages.length > 0 && this.supervisorMessages[0].supervisor) {
        const sup = this.supervisorMessages[0].supervisor
        return `${sup.first_name || ''} ${sup.last_name || ''}`.trim() || 'Supervisor'
      }
      return 'Supervisor'
    },

    getSupervisorDisplayInitials() {
      // Try from employeeInfo first
      if (this.employeeInfo && this.employeeInfo.manager) {
        return this.getSupervisorInitials(this.employeeInfo.manager)
      }
      // Try from messages
      if (this.supervisorMessages.length > 0 && this.supervisorMessages[0].supervisor) {
        const sup = this.supervisorMessages[0].supervisor
        const firstInitial = (sup.first_name || '')[0] || ''
        const lastInitial = (sup.last_name || '')[0] || ''
        return `${firstInitial}${lastInitial}`.toUpperCase() || '?'
      }
      return '?'
    },

    getSenderName(msg) {
      if (msg.sender) {
        return `${msg.sender.first_name || ''} ${msg.sender.last_name || ''}`.trim() || 'Supervisor'
      }
      return 'Supervisor'
    }
  }
}
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────── */
.profile-view {
  min-height: 100vh;
  background-color: #f3f4f6;
  padding: 2rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: #1f2937;
}

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
  align-items: start;
}

/* ── Sidebar / Identity card ─────────────────────────────────── */
.identity-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
  overflow: hidden;
  position: sticky;
  top: 2rem;
}

.profile-header-bg {
  height: 90px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.avatar-wrapper {
  margin-top: -50px;
  display: flex;
  justify-content: center;
}

.avatar-container {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid white;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  background: #e5e7eb;
}

.profile-pic {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.6);
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s;
  cursor: pointer;
  gap: 4px;
}

.avatar-container:hover .avatar-overlay { opacity: 1; }
.avatar-overlay input { display: none; }
.avatar-overlay .upload-text { font-size: 0.7rem; font-weight: 600; color: white; }
.hero-icon { width: 20px; height: 20px; }

.identity-info {
  text-align: center;
  padding: 1rem 1.5rem 0.5rem;
}

.user-fullname { font-size: 1.15rem; font-weight: 700; color: #111827; margin: 0; }
.user-email    { color: #6b7280; font-size: 0.8rem; margin: 0.2rem 0 0.6rem; }

.user-role-badge {
  display: inline-block;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.identity-stats {
  border-top: 1px solid #f3f4f6;
  display: flex;
  padding: 0.75rem 0;
  margin-top: 0.75rem;
}

.stat-item {
  flex: 1;
  text-align: center;
  border-right: 1px solid #f3f4f6;
}
.stat-item:last-child { border-right: none; }
.stat-label  { display: block; font-size: 0.65rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value  { display: block; font-weight: 600; color: #374151; font-size: 0.82rem; }

/* Supervisor quick-card */
.supervisor-card {
  border-top: 1px solid #f3f4f6;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.supervisor-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #9ca3af;
  font-weight: 600;
}

.supervisor-info {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.supervisor-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.supervisor-avatar.large {
  width: 48px;
  height: 48px;
  font-size: 1rem;
}

.supervisor-details { display: flex; flex-direction: column; }
.supervisor-name { font-size: 0.88rem; font-weight: 600; color: #1f2937; }

.btn-msg-supervisor {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  justify-content: center;
  background: #f5f3ff;
  color: #4f46e5;
  border: 1px solid #e0e7ff;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  font-size: 0.82rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
  width: 100%;
}

.btn-msg-supervisor:hover {
  background: #ede9fe;
  border-color: #c4b5fd;
}

/* ── Tabs ────────────────────────────────────────────────────── */
.profile-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  min-height: 500px;
  display: flex;
  flex-direction: column;
}

.profile-tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 1rem;
  overflow-x: auto;
  scrollbar-width: none;
}

.profile-tabs::-webkit-scrollbar { display: none; }

.tab-btn {
  background: none;
  border: none;
  padding: 0.9rem 1.1rem;
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
  position: relative;
}

.tab-btn:hover    { color: #4f46e5; }
.tab-btn.active   { color: #4f46e5; border-bottom-color: #4f46e5; }
.tab-icon         { width: 16px; height: 16px; flex-shrink: 0; }

.tab-badge {
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 9999px;
  line-height: 1.5;
}

/* ── Tab panels ──────────────────────────────────────────────── */
.tab-panel {
  padding: 1.75rem 2rem;
  flex: 1;
}

.panel-header { margin-bottom: 1.5rem; }
.panel-header h3 { font-size: 1.35rem; font-weight: 600; color: #111827; margin: 0 0 0.35rem; }
.panel-header p  { color: #6b7280; margin: 0; font-size: 0.875rem; }

/* ── Section labels ──────────────────────────────────────────── */
.section-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #9ca3af;
  margin-bottom: 0.75rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid #f3f4f6;
}

/* ── Forms ───────────────────────────────────────────────────── */
.modern-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group.full-width { grid-column: 1 / -1; }

label { font-size: 0.82rem; font-weight: 500; color: #374151; }
.optional-label { font-weight: 400; color: #9ca3af; font-size: 0.75rem; }

input, textarea, select {
  padding: 0.65rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
  font-family: inherit;
  background: white;
  color: #1f2937;
}

input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.error-border { border-color: #ef4444 !important; }

.field-error {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 2px;
}

/* ── Info cards (Job tab) ────────────────────────────────────── */
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }

.info-card {
  background: #f9fafb;
  padding: 0.9rem;
  border-radius: 8px;
  border: 1px solid #f3f4f6;
}

.info-label { display: block; font-size: 0.72rem; color: #6b7280; margin-bottom: 0.25rem; }
.info-value { font-weight: 600; color: #1f2937; font-size: 0.95rem; }

/* ── Bank details notice ─────────────────────────────────────── */
.bank-notice {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  color: #166534;
}

.bank-notice strong { display: block; margin-bottom: 0.2rem; }
.bank-notice p { margin: 0; opacity: 0.85; }
.notice-icon { width: 20px; height: 20px; flex-shrink: 0; margin-top: 1px; }

/* ── Password ────────────────────────────────────────────────── */
.password-split { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

.password-strength { margin-top: 6px; }
.strength-bar {
  height: 4px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 4px;
}
.strength-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
.strength-label { font-size: 0.72rem; font-weight: 500; }
.strength-weak   .strength-fill, .strength-weak   { color: #ef4444; background: #ef4444; }
.strength-fair   .strength-fill, .strength-fair   { color: #f59e0b; background: #f59e0b; }
.strength-good   .strength-fill, .strength-good   { color: #3b82f6; background: #3b82f6; }
.strength-strong .strength-fill, .strength-strong { color: #10b981; background: #10b981; }

/* ── Actions ─────────────────────────────────────────────────── */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 0.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f3f4f6;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 0.6rem 1.4rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.9rem;
}

.btn-primary:hover:not(:disabled) { background: #4338ca; }
.btn-primary:disabled { background: #a5b4fc; cursor: not-allowed; }
.btn-icon { width: 15px; height: 15px; flex-shrink: 0; }

.btn-ghost {
  background: transparent;
  color: #6b7280;
  border: none;
  padding: 0.6rem 1rem;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-ghost:hover { color: #1f2937; background: #f3f4f6; border-radius: 8px; }

/* ── Alerts ──────────────────────────────────────────────────── */
.alert {
  padding: 0.85rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.alert-icon    { width: 17px; height: 17px; flex-shrink: 0; }
.alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

/* ── Documents ───────────────────────────────────────────────── */
.upload-zone { margin-bottom: 1.5rem; }

.upload-trigger {
  display: block;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  padding: 1.75rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}
.upload-trigger:hover { border-color: #4f46e5; background: #f5f3ff; }
.upload-trigger input { display: none; }

.upload-hero-icon {
  width: 2.2rem;
  height: 2.2rem;
  color: #9ca3af;
  display: block;
  margin: 0 auto 0.4rem;
  transition: color 0.2s;
}
.upload-trigger:hover .upload-hero-icon { color: #4f46e5; }
.upload-text { display: block; font-weight: 600; color: #4f46e5; font-size: 0.9rem; }
.upload-sub  { font-size: 0.75rem; color: #9ca3af; display: block; margin-top: 0.2rem; }

.docs-list    { display: flex; flex-direction: column; gap: 0.6rem; }

.doc-item {
  display: flex;
  align-items: center;
  padding: 0.7rem 0.9rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: box-shadow 0.2s;
}
.doc-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

.doc-file-icon { margin-right: 0.9rem; display: flex; align-items: center; }
.file-type-icon { width: 26px; height: 26px; }
.file-type-icon.pdf     { color: #dc2626; }
.file-type-icon.doc     { color: #2563eb; }
.file-type-icon.generic { color: #6b7280; }

.doc-details { flex: 1; display: flex; flex-direction: column; }
.doc-name    { font-weight: 500; font-size: 0.9rem; color: #374151; }
.doc-meta    { font-size: 0.72rem; color: #9ca3af; }

.doc-tools { display: flex; gap: 0.4rem; }
.tool-btn {
  width: 30px; height: 30px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.tool-icon   { width: 14px; height: 14px; }
.download    { background: #f0fdf4; color: #166534; }
.download:hover { background: #dcfce7; }
.delete      { background: #fef2f2; color: #991b1b; }
.delete:hover { background: #fee2e2; }

.empty-docs {
  text-align: center;
  padding: 3rem 0;
  color: #9ca3af;
}
.empty-icon       { width: 3rem; height: 3rem; margin: 0 auto 0.75rem; opacity: 0.5; }
.empty-icon.small { width: 2rem; height: 2rem; }

/* ── Supervisor Messaging ─────────────────────────────────────── */
.msg-supervisor-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  background: #f9fafb;
  border-radius: 10px;
  border: 1px solid #f3f4f6;
  margin-bottom: 1.25rem;
}

.msg-sup-name { font-weight: 600; font-size: 0.95rem; color: #1f2937; margin: 0; }
.msg-sup-role { font-size: 0.78rem; color: #9ca3af; margin: 2px 0 0; }

.message-thread {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1rem;
  min-height: 280px;
  max-height: 380px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-bottom: 1rem;
  background: #fafafa;
  scroll-behavior: smooth;
}

.thread-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #9ca3af;
  font-size: 0.875rem;
  flex: 1;
}

.thread-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  color: #9ca3af;
  font-size: 0.875rem;
  gap: 0.5rem;
}

.message-bubble {
  display: flex;
  flex-direction: column;
  max-width: 80%;
  position: relative;
}

.bubble-mine   { align-self: flex-end; align-items: flex-end; }
.bubble-theirs { align-self: flex-start; align-items: flex-start; }

.bubble-meta {
  display: flex;
  gap: 0.5rem;
  align-items: baseline;
  margin-bottom: 3px;
}

.bubble-sender { font-size: 0.72rem; font-weight: 600; color: #6b7280; }
.bubble-time   { font-size: 0.65rem; color: #9ca3af; }

.bubble-body {
  padding: 0.6rem 0.85rem;
  border-radius: 12px;
  font-size: 0.875rem;
  line-height: 1.5;
  word-break: break-word;
}

.bubble-mine   .bubble-body { background: #4f46e5; color: white; border-bottom-right-radius: 4px; }
.bubble-theirs .bubble-body { background: white; color: #1f2937; border: 1px solid #e5e7eb; border-bottom-left-radius: 4px; }

.bubble-status { display: flex; margin-top: 3px; }
.tick-icon     { width: 13px; height: 13px; color: #9ca3af; }
.tick-icon.read { color: #4f46e5; }

/* Compose */
.message-compose { display: flex; flex-direction: column; gap: 0.6rem; }

.subject-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.compose-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  min-width: 60px;
}

.subject-select {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  background: white;
  color: #1f2937;
  cursor: pointer;
}

.compose-area {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  overflow: hidden;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.compose-area:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.compose-textarea {
  display: block;
  width: 100%;
  padding: 0.75rem;
  border: none;
  resize: none;
  font-size: 0.9rem;
  font-family: inherit;
  background: white;
  color: #1f2937;
  box-sizing: border-box;
}
.compose-textarea:focus { outline: none; box-shadow: none; border: none; }
.compose-textarea:disabled { background: #f9fafb; cursor: not-allowed; }

.compose-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.compose-hint { font-size: 0.72rem; color: #9ca3af; }

.btn-send {
  padding: 0.45rem 1rem;
  font-size: 0.82rem;
}

/* ── Loading ──────────────────────────────────────────────────── */
.loading-overlay {
  position: fixed;
  inset: 0;
  background: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.spinner {
  width: 40px; height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.spinner.small {
  width: 20px; height: 20px;
  border-width: 2px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Animations ──────────────────────────────────────────────── */
.fade-in {
  animation: fadeIn 0.25s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 900px) {
  .profile-container { grid-template-columns: 1fr; gap: 1.25rem; }
  .identity-card { position: static; }
  .form-grid, .info-grid, .password-split { grid-template-columns: 1fr; }
  .profile-tabs { white-space: nowrap; }
}

@media (max-width: 600px) {
  .profile-view { padding: 1rem; }
  .tab-panel    { padding: 1.25rem; }
  .message-bubble { max-width: 95%; }
}
</style>