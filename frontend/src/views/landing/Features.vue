<template>
  <nav class="navbar">
    <div class="container">
      <div class="nav-brand">
        <router-link to="/">
          <h1>Archangel Payroll</h1>
        </router-link>
      </div>
      <div class="nav-links">
        <router-link to="/features">Features</router-link>
        <router-link to="/solutions">Solutions</router-link>
        <router-link to="/pricing">Pricing</router-link>
        <router-link to="/testimonials">Testimonials</router-link>
        <router-link to="/contact">Contact</router-link>
        <router-link to="/contact" class="btn-primary desktop-demo-btn">Request Demo</router-link>
      </div>
      <!-- Mobile Menu Button -->
      <button class="mobile-menu-btn" @click="toggleMobileMenu" :aria-expanded="mobileMenuOpen">
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
      </button>
    </div>
    <!-- Mobile Menu -->
    <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
      <div class="mobile-menu-container">
        <router-link to="/features" @click="closeMobileMenu">Features</router-link>
        <router-link to="/solutions" @click="closeMobileMenu">Solutions</router-link>
        <router-link to="/pricing" @click="closeMobileMenu">Pricing</router-link>
        <router-link to="/testimonials" @click="closeMobileMenu">Testimonials</router-link>
        <router-link to="/contact" @click="closeMobileMenu">Contact</router-link>
        <router-link to="/contact" class="btn-primary mobile-demo-btn" @click="closeMobileMenu">Request Demo</router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const mobileMenuOpen = ref(false)
const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
const closeMobileMenu  = () => { mobileMenuOpen.value = false }

const handleClickOutside = (event) => {
  const nav = document.querySelector('.navbar')
  if (mobileMenuOpen.value && nav && !nav.contains(event.target)) {
    closeMobileMenu()
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.navbar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 1rem 0;
}
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}
.nav-brand a {
  text-decoration: none;
}
.nav-brand h1 {
  font-size: clamp(1.25rem, 3vw, 1.5rem);
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
}
.nav-links {
  display: flex;
  gap: clamp(1rem, 2vw, 2rem);
  align-items: center;
}
.nav-links a {
  text-decoration: none;
  color: #666;
  font-weight: 500;
  transition: color 0.3s;
  font-size: clamp(0.9rem, 1vw, 1rem);
}
.nav-links a:hover,
.nav-links a.router-link-active {
  color: #667eea;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white !important;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  white-space: nowrap;
  text-decoration: none;
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}
.mobile-menu-btn {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}
.mobile-menu-btn span {
  width: 24px;
  height: 2px;
  background: #333;
  transition: all 0.3s;
  display: block;
}
.mobile-menu-btn span.open:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.mobile-menu-btn span.open:nth-child(2) {
  opacity: 0;
}
.mobile-menu-btn span.open:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}
.mobile-menu {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.4s ease-out;
}
.mobile-menu.open {
  max-height: 500px;
}
.mobile-menu-container {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.mobile-menu-container a {
  text-decoration: none;
  color: #666;
  font-weight: 500;
  padding: 0.75rem 0;
  font-size: 1.1rem;
  border-bottom: 1px solid #f3f4f6;
  transition: color 0.3s;
}
.mobile-menu-container a:hover,
.mobile-menu-container a.router-link-active {
  color: #667eea;
}
.mobile-demo-btn {
  margin-top: 1rem;
  width: 100%;
  text-align: center;
  display: block;
  padding: 0.875rem 1.5rem !important;
}

@media (max-width: 991px) {
  .nav-links {
    display: none;
  }
  .mobile-menu-btn {
    display: flex;
  }
  .mobile-menu {
    display: block;
  }
}
</style>