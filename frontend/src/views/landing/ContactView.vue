<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Contact Header ── -->
      <section class="contact-header">
        <div class="container">
          <div class="section-header">
            <h1 class="section-title">Get in Touch</h1>
            <p class="section-subtitle">We're here to help with any questions</p>
          </div>
        </div>
      </section>

      <!-- ── Contact Grid ── -->
      <section class="contact-grid-section">
        <div class="container">
          <div class="contact-grid">

            <!-- Info cards -->
            <div class="contact-info">
              <div class="contact-card">
                <div class="contact-card-icon">📧</div>
                <h3>Email Us</h3>
                <p>sales@archangelpayroll.com</p>
                <p>support@archangelpayroll.com</p>
              </div>
              <div class="contact-card">
                <div class="contact-card-icon">📞</div>
                <h3>Call Us</h3>
                <p>Sales: +260 211 123 4567</p>
                <p>Support: +260 211 123 4568</p>
              </div>
              <div class="contact-card">
                <div class="contact-card-icon">🕒</div>
                <h3>Business Hours</h3>
                <p>Monday – Friday</p>
                <p>8:00 AM – 6:00 PM CAT</p>
              </div>
              <div class="contact-card">
                <div class="contact-card-icon">💬</div>
                <h3>Live Chat</h3>
                <p>Available 24/7</p>
                <button class="btn-outline btn-sm" type="button" @click="startLiveChat">Start Chat</button>
              </div>
            </div>

            <!-- Contact form -->
            <div class="contact-form-wrapper">
              <h2 class="form-section-title">Send Us a Message</h2>

              <!-- Success banner — mirrors applySuccessMessage pattern from Leaves -->
              <div v-if="contactSuccessMessage" class="alert alert-success">
                {{ contactSuccessMessage }}
              </div>

              <form v-else @submit.prevent="submitContactForm" class="contact-form">

                <div class="form-group">
                  <label class="form-label">Name *</label>
                  <input
                    v-model="contactForm.name"
                    type="text"
                    placeholder="Your full name"
                    maxlength="255"
                    autocomplete="name"
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Email *</label>
                  <input
                    v-model="contactForm.email"
                    type="email"
                    placeholder="your@email.com"
                    maxlength="255"
                    autocomplete="email"
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Subject *</label>
                  <input
                    v-model="contactForm.subject"
                    type="text"
                    placeholder="How can we help?"
                    maxlength="255"
                    class="form-control"
                  />
                </div>

                <div class="form-group full-width">
                  <label class="form-label">Message *</label>
                  <textarea
                    v-model="contactForm.message"
                    rows="5"
                    placeholder="Your message..."
                    maxlength="1000"
                    class="form-textarea"
                  ></textarea>
                  <p class="char-count">{{ contactForm.message.length }}/1000</p>
                </div>

                <!-- Error — same alert-danger as Leaves -->
                <div v-if="contactFormError" class="alert alert-danger">
                  {{ contactFormError }}
                </div>

                <button
                  type="submit"
                  class="btn-primary btn-block"
                  :disabled="contactSubmitting"
                >
                  <span v-if="contactSubmitting" class="spinner-small"></span>
                  {{ contactSubmitting ? 'Sending...' : 'Send Message' }}
                </button>

              </form>
            </div>

          </div>
        </div>
      </section>

      <!-- ── Demo Request Section ── -->
      <section class="demo-section">
        <div class="container">
          <div class="demo-content">
            <h2>See Archangel Payroll in Action</h2>
            <p>Request a personalized demo and discover how we can transform your payroll process</p>
          </div>

          <div class="demo-form-wrapper">

            <!-- Success state — mirrors applySuccessMessage + Done button pattern -->
            <div v-if="demoSuccessMessage" class="demo-form success-card">
              <div class="success-icon">✓</div>
              <h3>Request Received!</h3>
              <p>{{ demoSuccessMessage }}</p>
              <button class="btn-primary" @click="resetDemoForm">Submit Another Request</button>
            </div>

            <form v-else @submit.prevent="submitDemoRequest" class="demo-form">

              <div class="form-group">
                <label class="form-label">Company Name *</label>
                <input
                  v-model="demoForm.company_name"
                  type="text"
                  placeholder="Your company name"
                  maxlength="255"
                  autocomplete="organization"
                  class="form-control"
                />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">First Name *</label>
                  <input
                    v-model="demoForm.first_name"
                    type="text"
                    placeholder="John"
                    maxlength="255"
                    autocomplete="given-name"
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name *</label>
                  <input
                    v-model="demoForm.last_name"
                    type="text"
                    placeholder="Doe"
                    maxlength="255"
                    autocomplete="family-name"
                    class="form-control"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Business Email *</label>
                <input
                  v-model="demoForm.email"
                  type="email"
                  placeholder="john@company.com"
                  maxlength="255"
                  autocomplete="email"
                  class="form-control"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input
                  v-model="demoForm.phone"
                  type="tel"
                  placeholder="+260 211 000 0000"
                  maxlength="20"
                  autocomplete="tel"
                  class="form-control"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Number of Employees</label>
                <select v-model="demoForm.employee_count" class="form-select">
                  <option value="">Select range</option>
                  <option value="1-10">1–10</option>
                  <option value="11-50">11–50</option>
                  <option value="51-200">51–200</option>
                  <option value="201-500">201–500</option>
                  <option value="500+">500+</option>
                </select>
              </div>

              <div class="form-group full-width">
                <label class="form-label">Tell us about your needs</label>
                <textarea
                  v-model="demoForm.message"
                  rows="4"
                  placeholder="What are your payroll challenges?"
                  maxlength="1000"
                  class="form-textarea"
                ></textarea>
                <p class="char-count">{{ demoForm.message.length }}/1000</p>
              </div>

              <!-- Error — same alert-danger as Leaves -->
              <div v-if="demoFormError" class="alert alert-danger">
                {{ demoFormError }}
              </div>

              <button
                type="submit"
                class="btn-primary btn-block"
                :disabled="demoSubmitting"
              >
                <span v-if="demoSubmitting" class="spinner-small"></span>
                {{ demoSubmitting ? 'Submitting...' : 'Request Demo' }}
              </button>

            </form>
          </div>
        </div>
      </section>

    </main>
    <LandingFooter />

    <!-- ── Toast — exact same markup as Leaves page ── -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>{{ toast.message }}</span>
      </div>
    </transition>

  </div>
</template>

<script>
import axios from 'axios'
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

export default {
  name: 'ContactView',

  components: { LandingNavbar, LandingFooter },

  data() {
    return {
      // ── Contact form ──────────────────────────────────────────────────────
      contactForm: {
        name:    '',
        email:   '',
        subject: '',
        message: '',
      },
      contactSubmitting:    false,
      contactFormError:     null,
      contactSuccessMessage: null,

      // ── Demo request form ─────────────────────────────────────────────────
      demoForm: {
        company_name:   '',
        first_name:     '',
        last_name:      '',
        email:          '',
        phone:          '',
        employee_count: '',
        message:        '',
      },
      demoSubmitting:    false,
      demoFormError:     null,
      demoSuccessMessage: null,

      // ── Toast — same shape as Leaves page ────────────────────────────────
      toast: { show: false, message: '', type: 'success' },
    }
  },

  methods: {

    // ── Live chat ──────────────────────────────────────────────────────────
    startLiveChat() {
      if (window.ChatSystem && typeof window.ChatSystem.open === 'function') {
        window.ChatSystem.open()
      }
    },

    // ── Toast — exact same as Leaves page ─────────────────────────────────
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },

    // ── Error handler — mirrors handleApplyApiError from Leaves exactly ────
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'

      if (err.code === 'ERR_NETWORK') {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 422) {
        const responseData = err.response.data
        console.log('Validation errors:', responseData)

        if (responseData.errors) {
          const errors = Object.values(responseData.errors).flat()
          errorMsg = errors.join('. ')
        } else if (responseData.message) {
          errorMsg = responseData.message
        } else {
          errorMsg = 'Please check the form for errors.'
        }
      } else if (err.response?.status === 429) {
        errorMsg = 'Too many requests. Please wait a moment and try again.'
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error. Please try again later or contact support.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }

      // Same debug log as Leaves
      console.error('API Error Details:', {
        status:  err.response?.status,
        data:    err.response?.data,
        message: err.message,
      })

      return errorMsg
    },

    // ── Contact form validation — mirrors validateForm() from Leaves ───────
    validateContactForm() {
      this.contactFormError = null

      if (!this.contactForm.name.trim()) {
        this.contactFormError = 'Please enter your name.'
        return false
      }
      if (!this.contactForm.email.trim()) {
        this.contactFormError = 'Please enter your email address.'
        return false
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.contactForm.email)) {
        this.contactFormError = 'Please enter a valid email address.'
        return false
      }
      if (!this.contactForm.subject.trim()) {
        this.contactFormError = 'Please enter a subject.'
        return false
      }
      if (!this.contactForm.message.trim()) {
        this.contactFormError = 'Please enter your message.'
        return false
      }

      return true
    },

    // ── Submit contact — mirrors submitLeave() from Leaves exactly ─────────
    // Route: POST /api/contact  →  PublicController@storeContactRequest
    async submitContactForm() {
      if (!this.validateContactForm()) return

      this.contactSubmitting  = true
      this.contactFormError   = null

      try {
        const payload = new FormData()
        payload.append('name',    this.contactForm.name)
        payload.append('email',   this.contactForm.email)
        payload.append('subject', this.contactForm.subject)
        payload.append('message', this.contactForm.message)

       
        const response = await axios.post('/api/contact', payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Accept':        'application/json',
          },
          timeout: 10000,
        })

      //  console.log('Contact form submitted successfully:', response.data)

        this.contactSuccessMessage = response.data.message || "Message sent! We'll get back to you soon."
        this.showToast('Message sent successfully!', 'success')

      } catch (err) {
        console.error('Contact form error:', err)
        this.contactFormError = this.handleApiError(err)
      } finally {
        this.contactSubmitting = false
      }
    },

    // ── Demo form validation — mirrors validateForm() from Leaves ──────────
    validateDemoForm() {
      this.demoFormError = null

      if (!this.demoForm.company_name.trim()) {
        this.demoFormError = 'Please enter your company name.'
        return false
      }
      if (!this.demoForm.first_name.trim()) {
        this.demoFormError = 'Please enter your first name.'
        return false
      }
      if (!this.demoForm.last_name.trim()) {
        this.demoFormError = 'Please enter your last name.'
        return false
      }
      if (!this.demoForm.email.trim()) {
        this.demoFormError = 'Please enter your email address.'
        return false
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.demoForm.email)) {
        this.demoFormError = 'Please enter a valid email address.'
        return false
      }
      if (!this.demoForm.phone.trim()) {
        this.demoFormError = 'Please enter your phone number.'
        return false
      }

      return true
    },

    // ── Submit demo — mirrors submitLeave() from Leaves exactly ───────────
    // Route: POST /api/demo-requests  →  PublicController@storeDemoRequest
    async submitDemoRequest() {
      if (!this.validateDemoForm()) return

      this.demoSubmitting = true
      this.demoFormError  = null

      try {
        const payload = new FormData()
        payload.append('company_name', this.demoForm.company_name)
        payload.append('first_name',   this.demoForm.first_name)
        payload.append('last_name',    this.demoForm.last_name)
        payload.append('email',        this.demoForm.email)
        payload.append('phone',        this.demoForm.phone)

        // Only append nullable fields when they have a value — mirrors
        // the attachment conditional in Leaves submitLeave()
        if (this.demoForm.employee_count) {
          payload.append('employee_count', this.demoForm.employee_count)
        }
        if (this.demoForm.message.trim()) {
          payload.append('message', this.demoForm.message)
        }

      

        const response = await axios.post('/api/demo-requests', payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Accept':        'application/json',
          },
          timeout: 10000,
        })

       // console.log('Demo request submitted successfully:', response.data)

        this.demoSuccessMessage = response.data.message || "Thank you! We'll contact you shortly to schedule your demo."
        this.showToast('Demo request submitted successfully!', 'success')

      } catch (err) {
        console.error('Demo request error:', err)
        this.demoFormError = this.handleApiError(err)
      } finally {
        this.demoSubmitting = false
      }
    },

    // ── Reset demo form — mirrors resetAndCloseApplyModal from Leaves ──────
    resetDemoForm() {
      this.demoForm = {
        company_name:   '',
        first_name:     '',
        last_name:      '',
        email:          '',
        phone:          '',
        employee_count: '',
        message:        '',
      }
      this.demoFormError     = null
      this.demoSuccessMessage = null
      this.demoSubmitting    = false
    },
  },
}
</script>

<style scoped>
/* ── Contact Header ─────────────────────────────────────────── */
.contact-header {
  padding: clamp(3rem, 8vw, 6rem) 0;
  padding-top: calc(clamp(3rem, 8vw, 6rem) + 72px);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

/* ── Contact Grid section ───────────────────────────────────── */
.contact-grid-section {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background: #f9fafb;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(2rem, 4vw, 4rem);
}

@media (min-width: 992px) {
  .contact-grid { grid-template-columns: 1fr 1.5fr; }
}

.contact-info {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr));
  gap: clamp(1rem, 2vw, 1.5rem);
  align-content: start;
}

.contact-form-wrapper {
  background: white;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.form-section-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1.5rem;
}

/* ── Demo section ───────────────────────────────────────────── */
.demo-section {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.demo-content {
  text-align: center;
  margin-bottom: clamp(2rem, 5vw, 3rem);
}

.demo-content h2 {
  font-size: clamp(1.75rem, 5vw, 2.5rem);
  margin: 0 0 1rem;
  font-weight: 700;
}

.demo-content p {
  font-size: clamp(1rem, 2.5vw, 1.25rem);
  opacity: 0.95;
  margin: 0;
}

.demo-form-wrapper { max-width: 700px; margin: 0 auto; }

/* ── Forms ──────────────────────────────────────────────────── */
/* Same display:flex flex-direction:column gap as Leaves leave-form */
.contact-form,
.demo-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.demo-form {
  background: white;
  padding: clamp(1.5rem, 4vw, 3rem);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

/* ── Success card ───────────────────────────────────────────── */
.success-card {
  text-align: center;
  align-items: center;
}

.success-icon {
  width: 56px; height: 56px;
  background: #d1fae5;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #065f46; font-weight: 700;
  margin: 0 auto 1rem;
}

.success-card h3 { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem; }
.success-card p  { color: #475569; margin: 0 0 1.5rem; font-size: 0.95rem; }

/* ── Form layout helpers — exact same as Leaves ─────────────── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group.full-width { grid-column: 1 / -1; }

/* ── Labels — exact same as Leaves form-label ───────────────── */
.form-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ── Inputs — exact same tokens as Leaves ───────────────────── */
.form-control,
.form-select,
.form-textarea {
  padding: 0.6rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #1e293b;
  font-size: 0.9rem;
  font-family: inherit;
  transition: all 0.2s;
  width: 100%;
}

.form-control:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  background: white;
}

.form-textarea { resize: vertical; min-height: 100px; }

/* ── Char counter ───────────────────────────────────────────── */
.char-count {
  margin: 0;
  text-align: right;
  font-size: 0.75rem;
  color: #94a3b8;
}

/* ── Alerts — exact same as Leaves ──────────────────────────── */
.alert {
  padding: 1rem 1.25rem;
  border-radius: 10px;
  font-size: 0.9rem;
  line-height: 1.5;
}

.alert-success {
  background: #d1fae5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}

.alert-danger {
  background: #fee2e2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

/* ── Buttons — exact same as Leaves ─────────────────────────── */
.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
}

.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-block { width: 100%; }

.btn-outline {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.9rem;
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }

.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.78rem; }

/* ── Spinner — exact same as Leaves spinner-small ───────────── */
.spinner-small {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Toast — exact same as Leaves ───────────────────────────── */
.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: white;
  padding: 0.875rem 1.25rem;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
  gap: 0.65rem;
  z-index: 200;
  font-size: 0.875rem;
  font-weight: 500;
  border-left: 4px solid #10b981;
}

.toast.error   { border-left-color: #ef4444; }
.toast.warning { border-left-color: #f59e0b; }
.toast.success svg { color: #10b981; }
.toast.error   svg { color: #ef4444; }

/* ── Transitions — exact same as Leaves ─────────────────────── */
.toast-slide-enter-active,
.toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from,
.toast-slide-leave-to     { opacity: 0; transform: translateY(12px); }
</style>