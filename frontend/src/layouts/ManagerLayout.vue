<template>
  <div class="modern-manager-layout">
    <aside class="sidebar">
      <div class="logo-section">
        <span class="logo-icon">📊</span>
        <h1 class="title">Manager Hub</h1>
      </div>

      <nav class="nav">
        <router-link to="/manager/dashboard" class="nav-link" active-class="active">
          <span class="link-icon">🏠</span>
          Dashboard
        </router-link>
        <router-link to="/manager/employees" class="nav-link" active-class="active">
          <span class="link-icon">👥</span>
          Employees
        </router-link>
        <router-link to="/manager/attendance" class="nav-link" active-class="active">
          <span class="link-icon">⏰</span>
          Attendance
        </router-link>
        <router-link to="/manager/leave-approvals" class="nav-link" active-class="active">
          <span class="link-icon">✔️</span>
          Approvals
        </router-link>
        <router-link to="/manager/reports" class="nav-link" active-class="active">
          <span class="link-icon">📈</span>
          Reports
        </router-link>
        <router-link to="/manager/productivity" class="nav-link" active-class="active">
          <span class="link-icon">⚡</span>
          Productivity
        </router-link>
        <router-link to="/manager/Task" class="nav-item">Assign Tasks</router-link>
      </nav>

      <div class="sidebar-footer">
        <router-link to="/auth/login" class="logout-link">
          <span class="link-icon">🚪</span>
          Logout
        </router-link>
      </div>
    </aside>

    <div class="content-area">
      <header class="top-bar">
        <div class="user-profile">
            <span class="user-avatar">M</span>
            <span class="user-name">Hello, Manager!</span>
        </div>
      </header>
      <main class="main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ManagerLayout'
}
</script>

<style scoped>
/* Modern Color Palette & Typography */
:root {
  --primary-color: #007bff; /* Vibrant professional blue */
  --background-color: #f4f7f9; /* Soft background */
  --sidebar-dark-blue: #2c3e50; /* New Darkish blue color for the sidebar */
  --sidebar-text-color: rgba(255, 255, 255, 0.8); /* Light text for dark background */
  --text-color: #343a40;
  --text-light: #6c757d;
  --border-color: #e9ecef;
  --logout-color: #dc3545;
}

/* Base Layout */
.modern-manager-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--background-color);
}

/* Sidebar Styling */
.sidebar {
  width: 240px;
  /* MODIFICATION: Apply dark blue background */
  background-color: var(--sidebar-dark-blue);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2); /* Adjusted shadow for dark background */
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: sticky;
  top: 0;
  height: 100vh;
}

/* Logo Section */
.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2.5rem;
  color: var(--primary-color);
}

.logo-icon {
  font-size: 1.8rem;
}

.title {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  /* MODIFICATION: Ensure title is visible */
  color: white; 
}

/* Navigation Links */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-grow: 1; 
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  /* MODIFICATION: Light text color for dark background */
  color: var(--sidebar-text-color); 
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 8px; 
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}

.nav-link:hover {
  /* MODIFICATION: Subtle hover effect on dark background */
  background-color: rgba(255, 255, 255, 0.1); 
  color: white;
}

.nav-link.active {
  background-color: var(--primary-color);
  color: white;
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
}

.nav-link.active .link-icon {
  color: white;
}

.link-icon {
  font-size: 1.1rem;
  /* MODIFICATION: Light icon color for dark background */
  color: var(--sidebar-text-color); 
  transition: color 0.2s;
}

/* Sidebar Footer (Logout) */
.sidebar-footer {
  padding-top: 1.5rem;
  /* MODIFICATION: Lighter border for dark background */
  border-top: 1px solid rgba(255, 255, 255, 0.1); 
}

.logout-link {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--logout-color);
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.logout-link:hover {
  background-color: rgba(220, 53, 69, 0.2); /* Adjusted for dark theme */
}

/* Content Area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Top Bar/Header */
.top-bar {
  /* Background remains white for separation */
  background-color: white; 
  padding: 1rem 3rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end; 
  align-items: center;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: #50e3c2; 
    color: var(--text-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
}

.user-name {
    font-weight: 500;
    color: var(--text-color);
}

/* Main Content Wrapper */
.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto;
}

/* Responsive Adjustments (for Tablets and Mobile) */
@media (max-width: 992px) {
  .sidebar {
    width: 70px; /* Collapse sidebar to icon-only */
    padding: 1rem 0.5rem;
  }
  
  .title, .user-name {
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
  
  .nav-link span:not(.link-icon), .logout-link span:not(.link-icon) {
    display: none;
  }

  .logout-link {
    justify-content: center;
    padding: 0.75rem;
  }

  .main {
    padding: 1.5rem;
  }
  
  .top-bar {
    padding: 1rem 1.5rem;
  }
}
</style>