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

const handleLogin = async () => {
  loading.value = true
  try {
    await authStore.login(form.value)
   
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
    console.error('Login failed:', error)
    alert('Login failed. Please check your credentials and try again.')
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
  background: #0a0a0a;
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(1px);
}

.blob1 {
  top: 10%;
  right: 10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(147, 51, 234, 0.3), transparent);
}

.blob2 {
  bottom: 20%;
  left: 10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.2), transparent);
}

.login-container {
  background: #1a1a2e;
  padding: 3rem 2.5rem;
  border-radius: 20px;
  width: 100%;
  max-width: 400px;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.1);
  z-index: 10;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 1rem;
}

.logo-icon {
  font-size: 2rem;
  color: #3b82f6;
}

.title {
  color: #ffffff;
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #a1a1aa;
  font-size: 1rem;
  margin: 0;
  font-weight: 400;
}

.login-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.label {
  display: block;
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: #e4e4e7;
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
  font-size: 1.125rem;
  color: #71717a;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 1px solid #404040;
  border-radius: 12px;
  font-size: 1rem;
  background: #1a1a2e;
  color: #ffffff;
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-input::placeholder {
  color: #71717a;
}

.login-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
  position: relative;
  overflow: hidden;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.9;
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.login-btn.loading {
  pointer-events: none;
}

.btn-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-icon {
  font-size: 1rem;
}

.btn-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.login-footer {
  text-align: center;
}

.signup-link {
  color: #a1a1aa;
  margin: 0;
  font-size: 0.875rem;
}

.link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.link:hover {
  color: #8b5cf6;
  text-decoration: underline;
}

/* Responsive design */
@media (max-width: 480px) {
  .login-container {
    padding: 2rem 2rem;
    margin: 1rem;
    border-radius: 16px;
  }

  .title {
    font-size: 1.75rem;
  }

  .logo-icon {
    font-size: 1.75rem;
  }
}
</style>