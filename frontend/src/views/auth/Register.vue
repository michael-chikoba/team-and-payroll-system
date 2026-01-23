<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-header">
        <h1>Create Account</h1>
        <p class="subtitle">Sign up for your payroll account</p>
      </div>

      <form @submit.prevent="handleRegister" class="register-form">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input
              id="firstName"
              v-model="form.firstName"
              type="text"
              required
              placeholder="Enter your first name"
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input
              id="lastName"
              v-model="form.lastName"
              type="text"
              required
              placeholder="Enter your last name"
              class="form-input"
            >
          </div>
        </div>

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
            placeholder="Create a password"
            class="form-input"
          >
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <input
            id="confirmPassword"
            v-model="form.confirmPassword"
            type="password"
            required
            placeholder="Confirm your password"
            class="form-input"
          >
        </div>

        <div class="form-group">
          <label for="role">Account Type</label>
          <select
            id="role"
            v-model="form.role"
            required
            class="form-input"
          >
            <option value="">Select your role</option>
            <option value="employee">Employee</option>
            <option value="manager">Manager</option>
            <option value="admin">Administrator</option>
          </select>
        </div>

        <div class="form-group checkbox-group">
          <input
            id="terms"
            v-model="form.agreeTerms"
            type="checkbox"
            required
            class="checkbox-input"
          >
          <label for="terms" class="checkbox-label">
            I agree to the <a href="#" class="link">Terms of Service</a> and <a href="#" class="link">Privacy Policy</a>
          </label>
        </div>

        <button 
          type="submit" 
          class="register-btn" 
          :disabled="loading"
          :class="{ 'loading': loading }"
        >
          <span v-if="!loading">Create Account</span>
          <span v-else>Creating Account...</span>
        </button>
      </form>

      <div class="register-footer">
        <p class="login-link">
          Already have an account? 
          <router-link to="/auth/login" class="link">Sign in here</router-link>
        </p>
        
        <div class="demo-links">
          <p class="demo-title">Quick Demo Accounts:</p>
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
  name: 'Register',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    
    const form = ref({
      firstName: '',
      lastName: '',
      email: '',
      password: '',
      confirmPassword: '',
      role: '',
      agreeTerms: false
    })
    const loading = ref(false)

    const handleRegister = async () => {
      // Basic validation
      if (form.value.password !== form.value.confirmPassword) {
        alert('Passwords do not match!')
        return
      }

      if (!form.value.agreeTerms) {
        alert('Please agree to the terms and conditions')
        return
      }

      loading.value = true
      try {
        await authStore.register(form.value)
        
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
        console.error('Registration failed:', error)
        alert('Registration failed. Please try again.')
      } finally {
        loading.value = false
      }
    }

    const fillDemo = (role) => {
      const demos = {
        employee: { 
          firstName: 'John',
          lastName: 'Employee',
          email: 'employee@payroll.com', 
          password: 'password',
          confirmPassword: 'password',
          role: 'employee',
          agreeTerms: true
        },
        manager: { 
          firstName: 'Sarah',
          lastName: 'Manager', 
          email: 'manager@payroll.com', 
          password: 'password',
          confirmPassword: 'password',
          role: 'manager',
          agreeTerms: true
        },
        admin: { 
          firstName: 'Admin',
          lastName: 'User',
          email: 'admin@payroll.com', 
          password: 'password',
          confirmPassword: 'password',
          role: 'admin',
          agreeTerms: true
        }
      }
      
      form.value = { ...demos[role] }
    }

    return {
      form,
      loading,
      handleRegister,
      fillDemo
    }
  }
}
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.register-container {
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 500px;
}

.register-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.register-header h1 {
  color: #1f2937;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #6b7280;
  font-size: 1rem;
}

.register-form {
  margin-bottom: 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 2rem;
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
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

select.form-input {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%23666' d='M2 0L0 2h4zm0 5L0 3h4z'/></svg>");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 0.65rem;
  padding-right: 2.5rem;
}

/* Checkbox styles */
.checkbox-group {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.checkbox-input {
  margin-top: 0.25rem;
  transform: scale(1.2);
}

.checkbox-label {
  font-size: 0.9rem;
  color: #6b7280;
  line-height: 1.4;
  margin-bottom: 0;
}

.register-btn {
  width: 100%;
  padding: 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.register-btn:hover:not(:disabled) {
  background: #0da271;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.register-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.register-btn.loading {
  pointer-events: none;
}

.register-footer {
  text-align: center;
}

.login-link {
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
  .register-container {
    padding: 2rem 1.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .demo-buttons {
    flex-direction: column;
  }
  
  .demo-btn {
    width: 100%;
  }
}
</style>