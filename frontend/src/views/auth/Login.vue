<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-header">
        <div class="logo-section">
          <BuildingOfficeIcon class="logo-icon" />
          <h1 class="title">Payroll Portal</h1>
        </div>
        <p class="subtitle">Welcome back. Sign in to continue.</p>
      </div>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email" class="label">Email Address</label>
          <div class="input-wrapper">
            <EnvelopeIcon class="input-icon" />
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              placeholder="you@example.com"
              class="form-input"
            >
          </div>
        </div>
        <div class="form-group">
          <label for="password" class="label">Password</label>
          <div class="input-wrapper">
            <LockClosedIcon class="input-icon" />
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              placeholder="Enter your password"
              class="form-input"
            >
          </div>
        </div>
        <button
          type="submit"
          class="login-btn"
          :disabled="loading"
          :class="{ 'loading': loading }"
        >
          <span v-if="!loading" class="btn-text">
            <ArrowRightOnRectangleIcon class="btn-icon" />
            Sign In
          </span>
          <span v-else class="btn-loading">
            <div class="spinner"></div>
            Signing In...
          </span>
        </button>
      </form>
      <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
      <div class="login-footer">
        <p class="signup-link">
          Don't have an account?
          <router-link to="/auth/register" class="link">Sign up here</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { 
  BuildingOfficeIcon, 
  EnvelopeIcon, 
  LockClosedIcon,
  ArrowRightOnRectangleIcon 
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: ''
})

const loading = ref(false)
const errorMessage = ref(null)

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await authStore.login(form.value)

    // Redirect based on user role
    const role = authStore.user?.role || 'employee'

    switch (role) {
      case 'admin':
        await router.push('/admin/dashboard')
        break
      case 'manager':
        await router.push('/manager/dashboard')
        break
      default:
        await router.push('/employee/dashboard')
    }
  } catch (error) {
    let msg = 'Login failed. Please check your credentials and try again.'

    if (error.response?.status === 401) {
      msg = 'Invalid email or password. Please try again.'
    } else if (error.response?.status === 500) {
      msg = 'Server error. Please try again later or contact support.'
    } else if (error.response?.status === 422) {
      msg = error.response.data?.message || 'Validation error. Please check your input.'
    } else if (error.response?.data?.message) {
      msg = error.response.data.message
    } else if (!navigator.onLine) {
      msg = 'No internet connection. Please check your network.'
    }

    errorMessage.value = msg
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  padding: 1rem;
}

.login-container {
  background: #ffffff;
  padding: 2.5rem;
  border-radius: 16px;
  width: 100%;
  max-width: 420px;
  position: relative;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #f0f0f0;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.logo-icon {
  width: 2rem;
  height: 2rem;
  color: #4f46e5;
}

.title {
  color: #111827;
  font-size: 1.875rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
  font-weight: 400;
}

.login-form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  width: 1.25rem;
  height: 1.25rem;
  color: #9ca3af;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #ffffff;
  color: #111827;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-input::placeholder {
  color: #9ca3af;
}

.login-btn {
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  overflow: hidden;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.95;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login-btn.loading {
  pointer-events: none;
}

.btn-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.btn-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  color: #dc2626;
  font-size: 0.875rem;
  text-align: center;
  margin: 1rem 0;
  padding: 0.5rem;
  background: #fef2f2;
  border-radius: 8px;
}

.login-footer {
  text-align: center;
}

.signup-link {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.link {
  color: #4f46e5;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
}

.link:hover {
  color: #7c3aed;
  text-decoration: underline;
}

/* Responsive design */
@media (max-width: 480px) {
  .login-container {
    padding: 2rem;
    margin: 1rem;
    border-radius: 12px;
  }
  .title {
    font-size: 1.5rem;
  }
  .logo-icon {
    width: 1.75rem;
    height: 1.75rem;
  }
}
</style>