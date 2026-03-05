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
        <router-link to="/contact" class="nav-cta">Request Demo</router-link>
      </div>
      <button class="mobile-menu-btn" @click="toggleMobileMenu" :aria-expanded="mobileMenuOpen">
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
      </button>
    </div>
    <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
      <div class="mobile-menu-container">
        <router-link to="/features"     @click="closeMobileMenu">Features</router-link>
        <router-link to="/solutions"    @click="closeMobileMenu">Solutions</router-link>
        <router-link to="/pricing"      @click="closeMobileMenu">Pricing</router-link>
        <router-link to="/testimonials" @click="closeMobileMenu">Testimonials</router-link>
        <router-link to="/contact"      @click="closeMobileMenu">Contact</router-link>
        <router-link to="/contact" class="mobile-cta" @click="closeMobileMenu">Request Demo</router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const WIDGET_ID = '3cfddf9e-c0b4-41e4-8af3-9aeaf58fe973'
const API_BASE  = 'https://preactively-martial-darcel.ngrok-free.dev/api'

function loadChatWidget() {
  if (document.getElementById('cs-widget-script')) return
  if (!WIDGET_ID || WIDGET_ID === 'YOUR_WIDGET_ID_HERE') return
  window.ChatSystemConfig = { widgetId: WIDGET_ID }
  const script  = document.createElement('script')
  script.src    = `${API_BASE.replace('/api', '')}/widget/chat-widget.js`
  script.async  = true
  script.id     = 'cs-widget-script'
  document.body.appendChild(script)
}

function removeChatWidget() {
  document.getElementById('cs-widget-script')?.remove()
  document.getElementById('cs-widget-root')?.remove()
}

const mobileMenuOpen   = ref(false)
const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
const closeMobileMenu  = () => { mobileMenuOpen.value = false }

const handleClickOutside = (e) => {
  const nav = document.querySelector('.navbar')
  if (mobileMenuOpen.value && nav && !nav.contains(e.target)) closeMobileMenu()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadChatWidget()
})
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  removeChatWidget()
})
</script>

<!-- =====================================================
     LIGHT THEME  —  Primary: #0F7ADB  |  Secondary: #F04E37
     ===================================================== -->
<style scoped>

/* ── Shell ─────────────────────────────────────────────────── */
.navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1000;
  padding: 0.9rem 0;

  /* Frosted-glass white surface */
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);

  /* Hairline bottom border + soft drop shadow */
  box-shadow:
    0 1px 0 #E4E9F0,
    0 4px 24px rgba(13, 17, 23, 0.06);

  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Container row ─────────────────────────────────────────── */
.navbar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

/* ── Brand ─────────────────────────────────────────────────── */
.nav-brand a { text-decoration: none; }

.nav-brand h1 {
  font-size: clamp(1.2rem, 3vw, 1.45rem);
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.02em;

  /* Azure blue → lighter azure tint */
  background: linear-gradient(135deg, #0F7ADB 0%, #3FA8FF 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── Desktop links ─────────────────────────────────────────── */
.nav-links {
  display: flex;
  gap: clamp(0.75rem, 2vw, 1.75rem);
  align-items: center;
}

.nav-links a {
  text-decoration: none;
  color: #4B5563;
  font-weight: 500;
  font-size: 0.96rem;
  padding: 0.25rem 0;
  position: relative;
  transition: color 0.22s;
}

/* Sliding underline in primary blue */
.nav-links a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0; right: 0;
  height: 2px;
  background: #0F7ADB;
  border-radius: 9999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.22s ease;
}

.nav-links a:hover,
.nav-links a.router-link-active { color: #0F7ADB; }

.nav-links a:hover::after,
.nav-links a.router-link-active::after { transform: scaleX(1); }

/* CTA pill — primary blue, pill-shaped */
.nav-cta {
  background: #0F7ADB;
  color: #fff !important;
  padding: 0.6rem 1.35rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.93rem;
  white-space: nowrap;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: all 0.22s;
  box-shadow: 0 4px 16px rgba(15, 122, 219, 0.28);
}

.nav-cta::after { display: none; } /* no underline on button */

.nav-cta:hover {
  background: #0B62B0;
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(15, 122, 219, 0.36);
  color: #fff !important;
}

/* ── Hamburger ─────────────────────────────────────────────── */
.mobile-menu-btn {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 6px;
}

.mobile-menu-btn span {
  width: 22px;
  height: 2px;
  background: #4B5563;
  display: block;
  border-radius: 2px;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

/* X animation */
.mobile-menu-btn span.open:nth-child(1) { transform: translateY(7px) rotate(45deg);  background: #0F7ADB; }
.mobile-menu-btn span.open:nth-child(2) { opacity: 0; transform: scaleX(0); }
.mobile-menu-btn span.open:nth-child(3) { transform: translateY(-7px) rotate(-45deg); background: #0F7ADB; }

/* ── Mobile dropdown ───────────────────────────────────────── */
.mobile-menu {
  display: none;
  position: absolute;
  top: 100%; left: 0; right: 0;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);

  background: #FFFFFF;
  border-top: 1px solid #E4E9F0;
  box-shadow: 0 8px 32px rgba(13, 17, 23, 0.1);
}

.mobile-menu.open { max-height: 520px; }

.mobile-menu-container {
  padding: 1.25rem 1.5rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.mobile-menu-container a {
  text-decoration: none;
  color: #4B5563;
  font-weight: 500;
  padding: 0.85rem 0.5rem;
  font-size: 1.05rem;
  border-bottom: 1px solid #F0F4F8;
  transition: color 0.2s;
}

.mobile-menu-container a:last-of-type { border-bottom: none; }

.mobile-menu-container a:hover,
.mobile-menu-container a.router-link-active { color: #0F7ADB; }

/* Mobile CTA mirrors desktop pill */
.mobile-cta {
  margin-top: 1.25rem;
  width: 100%;
  text-align: center;
  display: flex !important;
  justify-content: center;
  padding: 0.875rem 1.5rem;
  border-radius: 9999px;
  background: #0F7ADB;
  color: #fff !important;
  font-weight: 700;
  box-shadow: 0 4px 16px rgba(15, 122, 219, 0.28);
  border-bottom: none !important;
  transition: all 0.22s;
}

.mobile-cta:hover {
  background: #0B62B0;
  color: #fff !important;
}

/* ── Responsive ────────────────────────────────────────────── */
@media (max-width: 991px) {
  .nav-links       { display: none; }
  .mobile-menu-btn { display: flex; }
  .mobile-menu     { display: block; }
}
</style>