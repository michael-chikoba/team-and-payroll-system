<template>
  <div class="login-page">
    <div class="bg-elements">
      <div class="bg-element bg1"></div>
      <div class="bg-element bg2"></div>
      <div class="bg-element bg3"></div>
    </div>
    <div class="login-container">
      <div class="login-header">
        <div class="logo-section">
          <span class="logo-icon">💼</span>
          <h1 class="title">Payroll Portal</h1>
        </div>
        <p class="subtitle">Welcome back. Sign in to continue</p>
      </div>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group" style="animation-delay: 0.2s;">
          <label for="email" class="label">Email Address</label>
          <div class="input-wrapper">
            <span class="input-icon">📧</span>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              placeholder="Enter your email"
              class="form-input"
            >
          </div>
        </div>
        <div class="form-group" style="animation-delay: 0.4s;">
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
/* Base Page Styling with Background Gradient Animation */
.login-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  /* Dynamic gradient background */
  background: linear-gradient(-45deg, #0f172a, #1e293b, #334155, #475569, #0f172a);
  background-size: 400% 400%; /* Important for the gradient animation */
  padding: 1rem;
  position: relative;
  overflow: hidden;
  animation: gradientShift 15s ease infinite; /* New gradient animation */
}

/* Keyframe for the gradient background animation */
@keyframes gradientShift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

/* Container for floating background images */
.bg-elements {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0; /* Ensures elements are behind the login container */
}

/* Individual floating background image element styling */
.bg-element {
  position: absolute;
  width: 300px; /* Adjust size as needed */
  height: 300px;
  opacity: 0.08; /* Subtle opacity */
  background-size: cover;
  background-position: center;
  filter: blur(1px); /* Soft blur effect */
  border-radius: 50%; /* Make them circular for a softer look */
  animation: float 25s infinite ease-in-out; /* Floating animation */
}

/* Positioning and specific image for each background element */
.bg1 {
  top: 10%;
  left: 10%;
  background-image: url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
  animation-delay: 0s;
  animation-duration: 30s;
}

.bg2 {
  top: 60%;
  right: 20%;
  background-image: url('https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
  animation-delay: -10s; /* Stagger animation start */
  animation-duration: 35s;
}

.bg3 {
  bottom: 20%;
  left: 50%;
  background-image: url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80');
  animation-delay: -20s;
  animation-duration: 40s;
}

/* Keyframe for floating animation */
@keyframes float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg) scale(1);
  }
  33% {
    transform: translateY(-20px) rotate(120deg) scale(1.05);
  }
  66% {
    transform: translateY(10px) rotate(240deg) scale(0.95);
  }
}

/* Subtle grain overlay for texture */
.login-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  pointer-events: none;
  animation: grainFloat 20s linear infinite;
  z-index: 1; /* Above bg-elements, below login-container */
  opacity: 0.5; /* Increased visibility */
}

@keyframes grainFloat {
  0%, 100% { transform: translateX(0) translateY(0); }
  50% { transform: translateX(-5px) translateY(-3px); }
}

/* Login Container Styling with Entry Animation */
.login-container {
  background: rgba(255, 255, 255, 0.95); /* Slightly less opaque */
  backdrop-filter: blur(20px); /* Retain blur for frosted glass effect */
  padding: 4rem 3.5rem;
  border-radius: 28px;
  box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1);
  width: 100%;
  max-width: 480px;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.2);
  animation: fadeInUp 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  overflow: hidden;
  z-index: 10; /* Ensures container is always on top */
}

/* Inner glow effect on container hover */
.login-container::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(16, 185, 129, 0.02) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.login-container:hover::before {
  opacity: 1;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(40px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Header and Title Styling */
.login-header {
  text-align: center;
  margin-bottom: 3rem;
}

.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 1.25rem;
  animation: logoPulse 2s ease-in-out infinite;
}

@keyframes logoPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.logo-icon {
  font-size: 2.25rem;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

.title {
  color: #0f172a;
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: textGlow 3s ease-in-out infinite alternate;
}

@keyframes textGlow {
  from {
    filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.3));
  }
  to {
    filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.6));
  }
}

.subtitle {
  color: #64748b;
  font-size: 1.125rem;
  margin: 0;
  font-weight: 400;
  animation: fadeInSlide 0.6s ease-out 0.3s both;
}

@keyframes fadeInSlide {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.login-form {
  margin-bottom: 3rem;
}

.form-group {
  margin-bottom: 2rem;
  opacity: 0;
  animation: fadeInUp 0.6s ease-out forwards;
}

.label {
  display: block;
  margin-bottom: 0.875rem;
  font-weight: 600;
  color: #374151;
  font-size: 1rem;
  letter-spacing: -0.025em;
  animation: fadeInSlide 0.6s ease-out 0.1s both;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  font-size: 1.25rem;
  color: #9ca3af;
  z-index: 1;
  transition: color 0.3s ease;
}

.form-input:focus + .input-icon {
  color: #3b82f6;
  animation: iconSpin 0.6s ease-out;
}

@keyframes iconSpin {
  from {
    transform: rotate(0deg) scale(1);
  }
  50% {
    transform: rotate(180deg) scale(1.1);
  }
  to {
    transform: rotate(360deg) scale(1);
  }
}

.form-input {
  width: 100%;
  padding: 1.125rem 1rem 1.125rem 3.25rem;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  font-size: 1rem;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  background: #f8fafc;
  color: #374151;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 12px rgba(59, 130, 246, 0.15);
  transform: translateY(-2px) scale(1.02);
}

.form-input::placeholder {
  color: #9ca3af;
}

.login-btn {
  width: 100%;
  padding: 1.25rem;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #7c3aed 100%);
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 1.0625rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  animation: fadeInSlide 0.6s ease-out 0.6s both;
}

.login-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s;
}

.login-btn:hover:not(:disabled)::before {
  left: 100%;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
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
  font-size: 1.125rem;
}

.btn-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spinner {
  width: 20px;
  height: 20px;
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
  color: #64748b;
  margin-bottom: 0;
  font-size: 0.975rem;
  animation: fadeInSlide 0.6s ease-out 0.8s both;
}

.link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.link:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

/* Responsive design */
@media (max-width: 480px) {
  .login-container {
    padding: 3rem 2.5rem;
    margin: 0.5rem;
    border-radius: 20px;
  }
  
  .title {
    font-size: 2.125rem;
  }
  
  .logo-icon {
    font-size: 2rem;
  }

  .bg-element {
    width: 200px;
    height: 200px;
  }
}
</style>