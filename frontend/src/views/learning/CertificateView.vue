<!-- src/views/learning/CertificateView.vue -->
<template>
  <div class="certificate-view">
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <div v-else-if="certificate" class="certificate-container">
      <div class="certificate-card" ref="certificateCard">
        <div class="certificate-header">
          <div class="certificate-badge">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M12 2L15 9H22L16 14L19 21L12 16.5L5 21L8 14L2 9H9L12 2Z" fill="currentColor" stroke="none"/>
            </svg>
          </div>
          <h1>CERTIFICATE OF COMPLETION</h1>
          <p>This certificate is proudly presented to</p>
          <h2>{{ certificate.employee_name }}</h2>
        </div>

        <div class="certificate-body">
          <p>For successfully completing the course</p>
          <h3>{{ certificate.course_title }}</h3>
          <div class="course-details" v-if="certificate.course_description">
            <p>{{ certificate.course_description }}</p>
          </div>
          <div class="score-info">
            <span>Score: {{ certificate.score }}%</span>
            <span>Passing Score: {{ certificate.passing_score }}%</span>
            <span>Date: {{ formatDate(certificate.completed_at) }}</span>
          </div>
        </div>

        <div class="certificate-footer">
          <div class="signature">
            <div class="signature-line"></div>
            <p>Authorized Signature</p>
          </div>
          <div class="certificate-id">
            <p>Certificate ID: {{ certificate.certificate_id }}</p>
          </div>
        </div>
      </div>

      <div class="actions">
        <button @click="downloadCertificate" class="download-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Download Certificate (PDF)
        </button>
        <button @click="printCertificate" class="print-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V3h12v6"/>
            <path d="M6 21H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="15" width="12" height="4" rx="1"/>
          </svg>
          Print
        </button>
        <button @click="goBack" class="back-btn">Back to Course</button>
      </div>
    </div>

    <div v-else class="error-state">
      <h2>Certificate Not Available</h2>
      <p>Complete the course assessment to earn your certificate.</p>
      <button @click="goBack" class="back-btn">Back to Course</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'

export default {
  name: 'CertificateView',
  data() {
    return {
      loading: false,
      certificate: null,
      courseId: null,
      userRole: 'employee'
    }
  },
  mounted() {
    this.courseId = this.$route.params.courseId
    this.getUserRole()
    this.fetchCertificate()
  },
  methods: {
    getUserRole() {
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        this.userRole = user.role || localStorage.getItem('user_role') || 'employee'
      } catch (error) {
        this.userRole = 'employee'
      }
    },
    
    async fetchCertificate() {
      this.loading = true
      try {
        const response = await axios.get(`/api/learning/certificate/${this.courseId}`)
        this.certificate = response.data.data
      } catch (error) {
        console.error('Failed to fetch certificate:', error)
      } finally {
        this.loading = false
      }
    },
    
    async downloadCertificate() {
      const element = this.$refs.certificateCard
      const canvas = await html2canvas(element, {
        scale: 2,
        backgroundColor: '#ffffff'
      })
      const imgData = canvas.toDataURL('image/png')
      const pdf = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
      })
      const imgWidth = 297
      const imgHeight = (canvas.height * imgWidth) / canvas.width
      pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight)
      pdf.save(`certificate_${this.certificate.certificate_id}.pdf`)
    },
    
    printCertificate() {
      window.print()
    },
    
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    
    goBack() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/courses/${this.courseId}`)
    }
  }
}
</script>

<style scoped>
.certificate-view {
  min-height: 100vh;
  background: #f3f4f6;
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.certificate-container {
  max-width: 900px;
  width: 100%;
}

.certificate-card {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  border: 1px solid #e5e7eb;
  text-align: center;
}

.certificate-header {
  margin-bottom: 2rem;
  border-bottom: 2px solid #f3f4f6;
  padding-bottom: 2rem;
}

.certificate-badge {
  color: #f59e0b;
  margin-bottom: 1rem;
}

.certificate-header h1 {
  font-size: 1.75rem;
  letter-spacing: 4px;
  color: #4f46e5;
  margin: 0 0 1rem;
  font-weight: 700;
}

.certificate-header p {
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0;
}

.certificate-header h2 {
  font-size: 2rem;
  color: #111827;
  margin: 0.5rem 0 0;
  font-weight: 600;
}

.certificate-body {
  margin-bottom: 2rem;
}

.certificate-body p {
  color: #6b7280;
  font-size: 1rem;
  margin: 0 0 0.5rem;
}

.certificate-body h3 {
  font-size: 1.5rem;
  color: #4f46e5;
  margin: 0 0 1rem;
  font-weight: 600;
}

.course-details {
  margin: 1rem 0;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.score-info {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f3f4f6;
}

.score-info span {
  font-size: 0.8rem;
  color: #6b7280;
}

.certificate-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 2rem;
  border-top: 1px solid #f3f4f6;
}

.signature {
  text-align: left;
}

.signature-line {
  width: 200px;
  height: 2px;
  background: #111827;
  margin-bottom: 0.5rem;
}

.signature p {
  margin: 0;
  font-size: 0.7rem;
  color: #6b7280;
}

.certificate-id p {
  margin: 0;
  font-size: 0.7rem;
  color: #9ca3af;
}

.actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.download-btn,
.print-btn,
.back-btn {
  padding: 0.625rem 1.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.download-btn {
  background: #4f46e5;
  border: none;
  color: white;
}

.download-btn:hover {
  background: #4338ca;
}

.print-btn {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #374151;
}

.print-btn:hover {
  background: #e5e7eb;
}

.back-btn {
  background: white;
  border: 1px solid #e5e7eb;
  color: #374151;
}

.back-btn:hover {
  background: #f3f4f6;
}

.error-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 12px;
  max-width: 500px;
}

.error-state h2 {
  font-size: 1.5rem;
  color: #111827;
  margin-bottom: 1rem;
}

.error-state p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

@media print {
  .certificate-view {
    background: white;
    padding: 0;
  }
  
  .actions {
    display: none;
  }
  
  .certificate-card {
    box-shadow: none;
    padding: 1rem;
  }
}
</style>