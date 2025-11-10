<template>
  <div class="simple-layout">
    <header class="header">
      <h1>DefaultLayout</h1>
      <nav>
        <router-link to="/auth/login">Login</router-link>
        <router-link to="/employee/dashboard">Employee</router-link>
        <router-link to="/manager/dashboard">Manager</router-link>
        <router-link to="/admin/dashboard">Admin</router-link>
      </nav>
    </header>
    <main class="main">
      <slot />
    </main>
  </div>
</template>

<script>
export default {
  name: 'DefaultLayout'
}
</script>

<style scoped>
.simple-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header {
  background: #2c3e50;
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h1 {
  margin: 0;
  font-size: 1.5rem;
}

nav {
  display: flex;
  gap: 1rem;
}

nav a {
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  transition: background 0.3s;
}

nav a:hover {
  background: rgba(255,255,255,0.1);
}

.main {
  flex: 1;
  padding: 2rem;
  background: #f8f9fa;
}
</style>
