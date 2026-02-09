<template>
  <div class="login-page">
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>
    <div class="login-container">
      <div class="login-header">
        <div class="logo-section">
          <span class="logo-icon">💼</span>
          <h1 class="title">Payroll Portal</h1>
        </div>
        <p class="subtitle">Welcome back. Sign in to continue.</p>
      </div>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email" class="label">Email Address</label>
          <div class="input-wrapper">
            <span class="input-icon">📧</span>
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
            <span class="input-icon">🔒</span>
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
            <span class="btn-icon">🚀</span>
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
  background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0.6;
  animation: blob-move 20s infinite ease-in-out;
}

.blob1 {
  top: -10%;
  right: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(147, 51, 234, 0.4), transparent);
}

.blob2 {
  bottom: -20%;
  left: -10%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.3), transparent);
}

@keyframes blob-move {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(20px, -20px) scale(1.1); }
  100% { transform: translate(0, 0) scale(1); }
}

.login-container {
  background: rgba(26, 26, 46, 0.9);
  padding: 2.5rem;
  border-radius: 16px;
  width: 100%;
  max-width: 420px;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.05);
  z-index: 10;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(10px);
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
  font-size: 1.75rem;
  color: #3b82f6;
}

.title {
  color: #ffffff;
  font-size: 1.875rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #9ca3af;
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
  color: #d1d5db;
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
  font-size: 1rem;
  color: #6b7280;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1px solid #374151;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #111827;
  color: #ffffff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.form-input::placeholder {
  color: #6b7280;
}

.login-btn {
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.2s ease;
  position: relative;
  overflow: hidden;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.95;
  transform: translateY(-1px);
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
  font-size: 0.875rem;
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
  color: #ef4444;
  font-size: 0.875rem;
  text-align: center;
  margin: 1rem 0;
  padding: 0.5rem;
  background: rgba(248, 113, 113, 0.1);
  border-radius: 8px;
}

.login-footer {
  text-align: center;
}

.signup-link {
  color: #9ca3af;
  margin: 0;
  font-size: 0.875rem;
}

.link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
}

.link:hover {
  color: #8b5cf6;
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
    font-size: 1.5rem;
  }
}
</style>