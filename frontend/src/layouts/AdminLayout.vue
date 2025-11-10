<template>
  <div class="admin-layout">
    <!-- Header -->
    <header class="header">
      <div class="logo">
        <span class="logo-icon">💼</span>
        <span class="logo-text">Payroll System</span>
      </div>
      
      <div class="header-right">
        <div class="user-info">
          <span class="user-name">{{ user?.name || 'Admin User' }}</span>
          <button @click="logout" class="logout-btn">Logout</button>
        </div>
      </div>
    </header>

    <div class="layout-body">
      <!-- Sidebar -->
      <aside class="sidebar">
        <nav class="sidebar-nav">
          <router-link to="/admin/dashboard" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-text">Dashboard</span>
          </router-link>
          
          <router-link to="/admin/employees" class="nav-item">
            <span class="nav-icon">👥</span>
            <span class="nav-text">Employees</span>
          </router-link>
          
          <router-link to="/admin/payroll" class="nav-item">
            <span class="nav-icon">💰</span>
            <span class="nav-text">Payroll</span>
          </router-link>
          
          <router-link to="/admin/payslips" class="nav-item">
            <span class="nav-icon">📄</span>
            <span class="nav-text">Payslips</span>
          </router-link>
          
          <router-link to="/admin/attendance" class="nav-item">
            <span class="nav-icon">⏰</span>
            <span class="nav-text">Attendance</span>
          </router-link>
          
          <router-link to="/admin/leaves" class="nav-item">
            <span class="nav-icon">🏖️</span>
            <span class="nav-text">Leave Management</span>
          </router-link>
          
          <router-link to="/admin/tax" class="nav-item">
            <span class="nav-icon">📋</span>
            <span class="nav-text">Tax Configuration</span>
          </router-link>
          
          <router-link to="/admin/reports" class="nav-item">
            <span class="nav-icon">📈</span>
            <span class="nav-text">Reports</span>
          </router-link>
          
          <router-link to="/admin/audit-logs" class="nav-item">
            <span class="nav-icon">🔍</span>
            <span class="nav-text">Audit Logs</span>
          </router-link>
          
          <router-link to="/admin/settings" class="nav-item">
            <span class="nav-icon">⚙️</span>
            <span class="nav-text">Settings</span>
          </router-link>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'AdminLayout',
  setup() {
    const authStore = useAuthStore()
    
    const logout = async () => {
      await authStore.logout()
    }
    
    return {
      logout,
      user: authStore.user
    }
  },
  mounted() {
    console.log('AdminLayout mounted - current route:', this.$route.path)
    console.log('Current user:', this.user)
  }
}
</script>

<style scoped>
.admin-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

/* Header */
.header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.logo-icon {
  font-size: 2rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  font-weight: 500;
}

.logout-btn {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Layout Body */
.layout-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  width: 260px;
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
  overflow-y: auto;
}

.sidebar-nav {
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.5rem;
  color: #4a5568;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: #f7fafc;
  color: #667eea;
}

.nav-item.router-link-active {
  background: #eef2ff;
  color: #667eea;
  border-left-color: #667eea;
  font-weight: 600;
}

.nav-icon {
  font-size: 1.25rem;
  width: 24px;
  text-align: center;
}

.nav-text {
  font-size: 0.95rem;
}

/* Main Content */
.main-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: #f5f7fa;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 80px;
  }
  
  .nav-text {
    display: none;
  }
  
  .nav-item {
    justify-content: center;
    padding: 1rem;
  }
  
  .logo-text {
    display: none;
  }
}
</style>