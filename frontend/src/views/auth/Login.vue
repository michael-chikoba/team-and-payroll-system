<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-header">
        <h1>Welcome Back</h1>
        <p class="subtitle">Sign in to your payroll account</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="Enter your email"
            class="form-input"
          >
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="Enter your password"
            class="form-input"
          >
        </div>

        <button 
          type="submit" 
          class="login-btn" 
          :disabled="loading"
          :class="{ 'loading': loading }"
        >
          <span v-if="!loading">Sign In</span>
          <span v-else>Signing In...</span>
        </button>
      </form>

      <div class="login-footer">
        <p class="signup-link">
          Don't have an account? 
          <router-link to="/auth/register" class="link">Sign up here</router-link>
        </p>
        
        <div class="demo-links">
          <p class="demo-title">Quick Access:</p>
          <div class="demo-buttons">
            <button @click="fillDemo('employee')" class="demo-btn employee">Employee Demo</button>
            <button @click="fillDemo('manager')" class="demo-btn manager">Manager Demo</button>
            <button @click="fillDemo('admin')" class="demo-btn admin">Admin Demo</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'Login',
  setup() {
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

    const fillDemo = (role) => {
      const demos = {
        employee: { email: 'employee@payroll.com', password: 'password' },
        manager: { email: 'manager@payroll.com', password: 'password' },
        admin: { email: 'admin@payroll.com', password: 'password' }
      }
      
      form.value = { ...demos[role] }
      // Auto-submit after filling
      setTimeout(() => handleLogin(), 100)
    }

    return {
      form,
      loading,
      handleLogin,
      fillDemo
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.login-container {
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 440px;
}

.login-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.login-header h1 {
  color: #1f2937;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #6b7280;
  font-size: 1rem;
}

.login-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #fafafa;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.login-btn {
  width: 100%;
  padding: 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.login-btn:hover:not(:disabled) {
  background: #5a6fd8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.login-btn.loading {
  pointer-events: none;
}

.login-footer {
  text-align: center;
}

.signup-link {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.link:hover {
  text-decoration: underline;
}

.demo-links {
  border-top: 1px solid #e5e7eb;
  padding-top: 1.5rem;
}

.demo-title {
  color: #6b7280;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

.demo-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: center;
}

.demo-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.demo-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.demo-btn.employee:hover {
  border-color: #667eea;
  color: #667eea;
}

.demo-btn.manager:hover {
  border-color: #10b981;
  color: #10b981;
}

.demo-btn.admin:hover {
  border-color: #f59e0b;
  color: #f59e0b;
}

/* Responsive design */
@media (max-width: 480px) {
  .login-container {
    padding: 2rem 1.5rem;
  }
  
  .demo-buttons {
    flex-direction: column;
  }
  
  .demo-btn {
    width: 100%;
  }
}
</style>