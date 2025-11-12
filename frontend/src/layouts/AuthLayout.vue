<template>
  <div class="modern-layout">
    <header class="modern-header">
      <div class="logo">
        <span class="logo-text">Payroll System</span>
      </div>
      <nav class="auth-nav">
        <router-link to="/auth/login" class="nav-link">Login</router-link>
        <router-link to="/auth/register" class="nav-link-primary">Sign Up</router-link>
      </nav>
    </header>

    <main class="modern-main">
      <div class="role-selection-container">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script>
export default {
  name: 'AuthLayout'
}
</script>

<style scoped>
/* CSS Variables for Modern Design */
:root {
  --color-primary: #1e40af;
  --color-secondary: #10b981;
  --color-dark: #1f2937;
  --color-light-gray: #f9fafb;
  --color-white: #ffffff;
  --color-border: #e5e7eb;
  --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --border-radius: 0.75rem;
}

/* Base Layout */
.modern-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: var(--color-light-gray);
  line-height: 1.6;
}

/* Modern Header */
.modern-header {
  background: var(--color-white);
  border-bottom: 1px solid var(--color-border);
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: var(--shadow-sm);
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary);
}

.auth-nav {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.nav-link {
  color: var(--color-dark);
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  transition: all 0.3s ease;
}

.nav-link:hover {
  color: var(--color-primary);
  background: #eff6ff;
}

.nav-link-primary {
  color: var(--color-white);
  background: var(--color-primary);
  text-decoration: none;
  font-weight: 600;
  padding: 0.5rem 1.5rem;
  border-radius: var(--border-radius);
  transition: all 0.3s ease;
}

.nav-link-primary:hover {
  background: #1e3a8a;
  transform: translateY(-1px);
}

/* Main Content Area - REMOVED PADDING */
.modern-main {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  /* Removed: padding: 2rem; */
}

.role-selection-container {
  max-width: 1000px;
  width: 100%;
  text-align: center;
}

.role-selection-container h1 {
  color: var(--color-dark);
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  font-weight: 800;
}

.role-selection-container .subtitle {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 3rem;
}

/* Action Buttons (Login/Register) */
.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1.5rem;
  background: var(--color-white);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-md);
  text-decoration: none;
  border: 2px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-primary {
  border-color: var(--color-primary);
}

.action-secondary {
  border-color: var(--color-secondary);
}

.action-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.action-btn h2 {
  font-size: 1.3rem;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: var(--color-dark);
}

.action-btn p {
  color: #6b7280;
  font-size: 0.9rem;
}

.action-primary h2 {
  color: var(--color-primary);
}

.action-secondary h2 {
  color: var(--color-secondary);
}

/* Divider */
.divider {
  position: relative;
  margin: 2.5rem 0;
  color: #9ca3af;
  font-size: 0.9rem;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--color-border);
  z-index: 1;
}

.divider span {
  background: var(--color-light-gray);
  padding: 0 1rem;
  position: relative;
  z-index: 2;
}

/* Role Cards */
.role-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.role-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1.5rem;
  background: var(--color-white);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-md);
  text-decoration: none;
  border: 2px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.role-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.15);
  border-color: var(--color-primary);
}

.role-card h2 {
  font-size: 1.3rem;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: var(--color-dark);
}

.role-card p {
  color: #6b7280;
  font-size: 0.9rem;
  line-height: 1.5;
}

/* Icon Styling */
.role-card i,
.action-btn i {
  font-size: 2.5rem;
  line-height: 1;
  margin-bottom: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modern-header {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
  }
  
  .role-selection-container h1 {
    font-size: 2rem;
  }
  
  .action-buttons,
  .role-cards {
    grid-template-columns: 1fr;
  }
  
  /* Removed: .modern-main { padding: 1rem; } */
}
</style>