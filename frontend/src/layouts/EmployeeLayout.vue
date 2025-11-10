<template>
  <div class="employee-layout">
    <aside class="sidebar">
      <div class="logo-section">
        <span class="logo-icon">🚀</span>
        <h1 class="title">Portal</h1>
      </div>
      <nav class="nav">
        <router-link to="/employee/dashboard" class="nav-link" active-class="active">
          <span class="link-icon">🏠</span>
          Dashboard
        </router-link>
        <router-link to="/employee/attendance" class="nav-link" active-class="active">
          <span class="link-icon">⏰</span>
          Attendance
        </router-link>
        <router-link to="/employee/leaves" class="nav-link" active-class="active">
          <span class="link-icon">🏖️</span>
          Leaves
        </router-link>
        <router-link to="/employee/apply-leave" class="nav-link" active-class="active">
          <span class="link-icon">📝</span>
          Apply Leave
        </router-link>
        <router-link to="/employee/payslips" class="nav-link" active-class="active">
          <span class="link-icon">💵</span>
          Payslips
        </router-link>
        <router-link to="/employee/profile" class="nav-link" active-class="active">
          <span class="link-icon">👤</span>
          Profile
        </router-link>
        <router-link :to="{ name: 'TaskBoard' }">
          <span class="link-icon">👤</span>
          Tasks
        </router-link>
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-info">
          <span class="avatar-placeholder">J.D.</span>
          <span class="user-name">{{ authStore.user?.fullName || 'Employee' }}</span>
        </div>
        <button @click="logout" class="logout-btn">
          <span class="link-icon">➡️</span>
          Logout
        </button>
      </div>
    </aside>

    <div class="content-area">
      <header class="top-header">
        <h2 class="page-title">Welcome Back!</h2> </header>
      <main class="main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ModernEmployeeLayout', // Changed component name
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  methods: {
    logout() {
      this.authStore.clearAuth()
      this.$router.push({ name: 'login' })
    }
  }
}
</script>
<style scoped>
/* Color Palette */
:root {
  --primary-color: #4A90E2; /* A modern blue */
  --secondary-color: #50e3c2;
  --background-color: #F8F9FB; /* Light, soft background */
  --surface-color: #FFFFFF; /* White surfaces */
  --text-color: #333333;
  --text-light: #7a7a7a;
  --border-color: #eaeaea;
}

/* Base Layout */
.employee-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background-color: var(--background-color);
}

/* Sidebar Styling (Modern Navigation) */
.sidebar {
  width: 260px; /* Wider sidebar */
  background-color: var(--surface-color);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); /* Subtle shadow */
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between; /* Push footer down */
  position: sticky;
  top: 0;
  height: 100vh;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2rem;
  padding: 0 0.5rem;
  color: var(--primary-color);
}

.logo-icon {
  font-size: 1.5rem;
}

.title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 15px;
  color: var(--text-light);
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 12px; /* Increased rounded corners */
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.nav-link:hover {
  background-color: #f0f4f8; /* Very light hover state */
  color: var(--primary-color);
}

.nav-link.active {
  background-color: rgba(74, 144, 226, 0.1); /* Light primary background */
  color: var(--primary-color);
  font-weight: 600;
}

.link-icon {
  font-size: 1.2rem;
}

/* Sidebar Footer (User Info & Logout) */
.sidebar-footer {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  padding: 0.5rem;
}

.avatar-placeholder {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.user-name {
  font-weight: 600;
  color: var(--text-color);
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: none;
  color: #E24A4A; /* Reddish for warning/logout */
  border: 1px solid #E24A4A;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s ease;
}

.logout-btn:hover {
  background: rgba(226, 74, 74, 0.1);
  color: #C0392B;
}

/* Content Area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Top Header (Above Main Content) */
.top-header {
  background-color: var(--surface-color);
  padding: 1.5rem 3rem;
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}

.page-title {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 300;
  color: var(--text-color);
}

/* Main Content Wrapper */
.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto;
}

/* Responsive Adjustments (For Mobile/Tablet) */
@media (max-width: 992px) {
  .sidebar {
    width: 80px; /* Collapsed sidebar on smaller screens */
    padding: 1rem 0.5rem;
  }
  
  .title, .user-name, .link-icon + span {
    display: none; /* Hide text, only show icons */
  }

  .logo-section {
    justify-content: center;
    margin-bottom: 1rem;
  }

  .nav-link {
    justify-content: center;
    padding: 0.75rem;
  }

  .sidebar-footer {
    border-top: none;
    padding-top: 0;
  }
  
  .user-info {
    justify-content: center;
    margin-bottom: 0.5rem;
  }
  
  .logout-btn {
    padding: 0.75rem 0;
    font-size: 0; /* Hide text */
    justify-content: center;
  }
  
  .logout-btn .link-icon {
    font-size: 1.2rem;
  }
  
  .main {
    padding: 1.5rem;
  }
}
</style>