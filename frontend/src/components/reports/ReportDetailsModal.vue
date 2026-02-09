<template>
  <div v-if="show && report" class="modal-overlay" @click.self="closeModal">
    <div class="modal-container">
      <div class="modal-header">
        <div class="header-title">
          <h2>📄 Report Details</h2>
          <span :class="['status-badge-large', getStatusClass(report.status)]">
            {{ formatStatus(report.status) }}
          </span>
        </div>
        <button @click="closeModal" class="close-btn">✕</button>
      </div>

      <div class="modal-body">
        <!-- Schedule Information -->
        <div class="section">
          <h3 class="section-title">📅 Schedule Information</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Schedule Title:</span>
              <span class="info-value">{{ report.schedule?.title || 'Untitled' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Employee:</span>
              <span class="info-value">{{ report.employee?.name || 'Unknown' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Submitted:</span>
              <span class="info-value">{{ formatDate(report.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Report Type:</span>
              <span class="info-value">{{ formatReportType(report.report_type) }}</span>
            </div>
          </div>
        </div>

        <!-- Report Content -->
        <div v-if="report.report_content" class="section">
          <h3 class="section-title">📝 Report Content</h3>
          <div class="content-box">
            <p>{{ report.report_content }}</p>
          </div>
        </div>

        <!-- Metadata -->
        <div v-if="report.metadata && Object.keys(report.metadata).length > 0" class="section">
          <h3 class="section-title">📊 Additional Information</h3>
          <div class="metadata-grid">
            <div v-if="report.metadata.hours_worked" class="metadata-card">
              <div class="metadata-icon">⏰</div>
              <div class="metadata-content">
                <span class="metadata-label">Hours Worked</span>
                <span class="metadata-value">{{ report.metadata.hours_worked }} hours</span>
              </div>
            </div>
            <div v-if="report.metadata.tasks_completed" class="metadata-card">
              <div class="metadata-icon">✅</div>
              <div class="metadata-content">
                <span class="metadata-label">Tasks Completed</span>
                <span class="metadata-value">{{ report.metadata.tasks_completed }}</span>
              </div>
            </div>
          </div>

          <div v-if="report.metadata.achievements" class="subsection">
            <h4>🏆 Achievements</h4>
            <div class="content-box success">
              <p>{{ report.metadata.achievements }}</p>
            </div>
          </div>

          <div v-if="report.metadata.challenges" class="subsection">
            <h4>⚠️ Challenges</h4>
            <div class="content-box warning">
              <p>{{ report.metadata.challenges }}</p>
            </div>
          </div>
        </div>

        <!-- File Information -->
        <div v-if="report.file_path" class="section">
          <h3 class="section-title">📎 Attached File</h3>
          <div class="file-card">
            <div class="file-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="file-info">
              <div class="file-name">{{ report.file_name }}</div>
              <div class="file-meta">
                <span>{{ formatFileSize(report.file_size) }}</span>
                <span>•</span>
                <span>{{ formatFileType(report.file_type) }}</span>
              </div>
            </div>
            <button @click="handleDownload" class="btn-download-file">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Download
            </button>
          </div>
        </div>

        <!-- Review Information -->
        <div v-if="report.reviewed_at" class="section">
          <h3 class="section-title">👁️ Review Information</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Reviewed By:</span>
              <span class="info-value">{{ report.reviewer?.name || 'Unknown' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Reviewed At:</span>
              <span class="info-value">{{ formatDate(report.reviewed_at) }}</span>
            </div>
          </div>
          <div v-if="report.review_comments" class="review-box">
            <strong>Review Comments:</strong>
            <p>{{ report.review_comments }}</p>
          </div>
        </div>

        <!-- Review Form (if not reviewed) -->
        <div v-if="canReview" class="section">
          <h3 class="section-title">✍️ Review Report</h3>
          <form @submit.prevent="submitReview">
            <div class="form-group">
              <label>Review Status:</label>
              <div class="status-buttons">
                <label class="status-button">
                  <input type="radio" v-model="reviewForm.status" value="reviewed" />
                  <span class="status-button-content reviewed">
                    <span class="status-icon">👁️</span>
                    Mark as Reviewed
                  </span>
                </label>
                <label class="status-button">
                  <input type="radio" v-model="reviewForm.status" value="approved" />
                  <span class="status-button-content approved">
                    <span class="status-icon">✓</span>
                    Approve
                  </span>
                </label>
                <label class="status-button">
                  <input type="radio" v-model="reviewForm.status" value="rejected" />
                  <span class="status-button-content rejected">
                    <span class="status-icon">✕</span>
                    Reject
                  </span>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label for="comments">Review Comments:</label>
              <textarea
                id="comments"
                v-model="reviewForm.comments"
                rows="4"
                placeholder="Add your comments here..."
                :required="reviewForm.status === 'rejected'"
              ></textarea>
              <small v-if="reviewForm.status === 'rejected'" class="helper-text">
                Comments are required when rejecting a report
              </small>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-submit" :disabled="!reviewForm.status || submitting">
                {{ submitting ? 'Submitting...' : 'Submit Review' }}
              </button>
              <button type="button" @click="closeModal" class="btn-cancel">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReportDetailsModal',

  props: {
    show: {
      type: Boolean,
      required: true
    },
    report: {
      type: Object,
      default: null
    }
  },

  emits: ['close', 'review', 'download'],

  data() {
    return {
      reviewForm: {
        status: '',
        comments: ''
      },
      submitting: false
    }
  },

  computed: {
    canReview() {
      return this.report && this.report.status === 'submitted'
    }
  },

  watch: {
    show(newVal) {
      if (newVal) {
        this.resetForm()
      }
    }
  },

  methods: {
    resetForm() {
      this.reviewForm = {
        status: '',
        comments: ''
      }
      this.submitting = false
    },

    async submitReview() {
      if (!this.reviewForm.status) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Please select a review status'
        })
        return
      }

      if (this.reviewForm.status === 'rejected' && !this.reviewForm.comments.trim()) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Comments are required when rejecting a report'
        })
        return
      }

      this.submitting = true

      try {
        this.$emit('review', this.report.id, this.reviewForm.status, this.reviewForm.comments)
        this.resetForm()
      } catch (error) {
        console.error('Error submitting review:', error)
      } finally {
        this.submitting = false
      }
    },

    handleDownload() {
      this.$emit('download', this.report.id)
    },

    closeModal() {
      this.$emit('close')
    },

    getStatusClass(status) {
      const classes = {
        submitted: 'status-submitted',
        reviewed: 'status-reviewed',
        approved: 'status-approved',
        rejected: 'status-rejected'
      }
      return classes[status] || 'status-submitted'
    },

    formatStatus(status) {
      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'
    },

    formatReportType(type) {
      const types = {
        text: 'Text Report',
        file: 'File Upload',
        both: 'Text & File'
      }
      return types[type] || type
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    formatFileSize(bytes) {
      if (!bytes) return 'Unknown size'
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
    },

    formatFileType(type) {
      if (!type) return 'Unknown type'
      const types = {
        'application/pdf': 'PDF',
        'application/msword': 'Word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'Word',
        'text/plain': 'Text'
      }
      return types[type] || type.split('/')[1]?.toUpperCase() || 'File'
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1001;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px 16px 0 0;
  color: white;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-title h2 {
  margin: 0;
  font-size: 1.5rem;
}

.status-badge-large {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 700;
  background: white;
}

.status-badge-large.status-submitted {
  color: #1e40af;
}

.status-badge-large.status-reviewed {
  color: #92400e;
}

.status-badge-large.status-approved {
  color: #065f46;
}

.status-badge-large.status-rejected {
  color: #991b1b;
}

.close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.section {
  margin-bottom: 2rem;
}

.section:last-child {
  margin-bottom: 0;
}

.section-title {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.125rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  background: #f7fafc;
  padding: 1.5rem;
  border-radius: 8px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: #718096;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 1rem;
  color: #2d3748;
  font-weight: 600;
}

.content-box {
  background: #f7fafc;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.content-box.success {
  border-left-color: #10b981;
  background: #d1fae5;
}

.content-box.warning {
  border-left-color: #f59e0b;
  background: #fef3c7;
}

.content-box p {
  margin: 0;
  color: #2d3748;
  line-height: 1.6;
  white-space: pre-wrap;
}

.subsection {
  margin-top: 1rem;
}

.subsection h4 {
  margin: 0 0 0.75rem 0;
  color: #4a5568;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.metadata-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.metadata-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.metadata-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.metadata-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metadata-label {
  font-size: 0.75rem;
  color: #718096;
  font-weight: 600;
}

.metadata-value {
  font-size: 1.125rem;
  color: #2d3748;
  font-weight: 700;
}

.file-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #f7fafc;
  border-radius: 8px;
  border: 2px dashed #cbd5e0;
}

.file-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.file-icon svg {
  width: 30px;
  height: 30px;
}

.file-info {
  flex: 1;
}

.file-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.file-meta {
  font-size: 0.875rem;
  color: #718096;
  display: flex;
  gap: 0.5rem;
}

.btn-download-file {
  padding: 0.75rem 1.5rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-download-file svg {
  width: 20px;
  height: 20px;
}

.btn-download-file:hover {
  background: #5a6fd8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.review-box {
  margin-top: 1rem;
  padding: 1rem;
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
  border-radius: 8px;
}

.review-box strong {
  color: #92400e;
  display: block;
  margin-bottom: 0.5rem;
}

.review-box p {
  margin: 0;
  color: #78350f;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.75rem;
  font-weight: 700;
  color: #2d3748;
}

.status-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.status-button {
  cursor: pointer;
}

.status-button input[type="radio"] {
  display: none;
}

.status-button-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
  background: white;
}

.status-button input:checked + .status-button-content {
  border-color: #667eea;
  background: #f0f4ff;
}

.status-button-content.reviewed {
  color: #92400e;
}

.status-button input:checked + .status-button-content.reviewed {
  border-color: #f59e0b;
  background: #fffbeb;
}

.status-button-content.approved {
  color: #065f46;
}

.status-button input:checked + .status-button-content.approved {
  border-color: #10b981;
  background: #d1fae5;
}

.status-button-content.rejected {
  color: #991b1b;
}

.status-button input:checked + .status-button-content.rejected {
  border-color: #ef4444;
  background: #fee2e2;
}

.status-icon {
  font-size: 1.5rem;
}

textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #cbd5e0;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
  transition: all 0.2s;
}

textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.helper-text {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #991b1b;
  font-style: italic;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-submit,
.btn-cancel {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-submit {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-cancel:hover {
  background: #cbd5e0;
}

@media (max-width: 768px) {
  .modal-container {
    max-height: 95vh;
  }

  .modal-header {
    padding: 1rem 1.5rem;
  }

  .header-title {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .metadata-grid {
    grid-template-columns: 1fr;
  }

  .status-buttons {
    grid-template-columns: 1fr;
  }

  .file-card {
    flex-direction: column;
    text-align: center;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .btn-submit,
  .btn-cancel {
    width: 100%;
  }
}
</style>