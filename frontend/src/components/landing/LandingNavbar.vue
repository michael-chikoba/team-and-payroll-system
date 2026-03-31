<template>
  <nav class="navbar">
    <div class="container">

      <!-- ── Brand ── -->
      <div class="nav-brand">
        <router-link to="/">
          <div class="brand-logo">
            <img src="@/assets/logo/PayLogo.png" alt="Payroll System Logo" class="logo-image" />
            <h1>
              <span class="brand-amber">Arch</span>
              <span class="brand-green">angel</span>
              <span class="brand-dot">·</span>
              <span class="brand-blue">Payroll</span>
            </h1>
          </div>
        </router-link>
      </div>

      <!-- ── Desktop Links ── -->
      <div class="nav-links">
        <router-link to="/features">Features</router-link>
        <router-link to="/solutions">Solutions</router-link>
        <router-link to="/pricing">Pricing</router-link>
        <router-link to="/testimonials">Testimonials</router-link>
        <router-link to="/contact">Contact</router-link>
        <router-link to="/contact" class="nav-cta">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
          </svg>
          Request Demo
        </router-link>
      </div>

      <!-- ── Hamburger ── -->
      <button class="mobile-menu-btn" @click="toggleMobileMenu" :aria-expanded="mobileMenuOpen" aria-label="Toggle menu">
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
        <span :class="{ open: mobileMenuOpen }"></span>
      </button>
    </div>

    <!-- ── Mobile Dropdown ── -->
    <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
      <div class="mobile-menu-container">

        <router-link to="/features" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
          </svg>
          Features
        </router-link>

        <router-link to="/solutions" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
          </svg>
          Solutions
        </router-link>

        <router-link to="/pricing" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
          </svg>
          Pricing
        </router-link>

        <router-link to="/testimonials" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
          </svg>
          Testimonials
        </router-link>

        <router-link to="/contact" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
          </svg>
          Contact
        </router-link>

        <router-link to="/contact" class="mobile-cta" @click="closeMobileMenu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
          </svg>
          Request Demo
        </router-link>

      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// ── Chat Widget ──────────────────────────────────────────────
const WIDGET_ID  = '83d3a04a-5ecb-4e60-8382-c0ce520cb599'
const WIDGET_URL = 'https://archangelchat.it.com/widget/chat-widget.js'

function loadChatWidget() {
  if (document.getElementById('cs-widget-script')) {
    console.warn('[ChatWidget] Already loaded — skipping')
    return
  }
  console.log('[ChatWidget] Loading...')
  window.ChatSystemConfig = { widgetId: WIDGET_ID }
  const script   = document.createElement('script')
  script.id      = 'cs-widget-script'
  script.src     = WIDGET_URL
  script.async   = true
  script.onload  = () => console.log('[ChatWidget] ✅ Loaded successfully')
  script.onerror = (e) => console.error('[ChatWidget] ❌ Failed to load', e)
  document.body.appendChild(script)
}

function removeChatWidget() {
  document.getElementById('cs-widget-script')?.remove()
  document.getElementById('cs-widget-root')?.remove()
  delete window.ChatSystemConfig
  console.log('[ChatWidget] Removed')
}
// ─────────────────────────────────────────────────────────────

// ── Mobile menu ──────────────────────────────────────────────
const mobileMenuOpen   = ref(false)
const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
const closeMobileMenu  = () => { mobileMenuOpen.value = false }

const handleClickOutside = (e) => {
  const nav = document.querySelector('.navbar')
  if (mobileMenuOpen.value && nav && !nav.contains(e.target)) closeMobileMenu()
}
// ─────────────────────────────────────────────────────────────

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadChatWidget()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  removeChatWidget()
})
</script>

<style scoped>
/* ── Shell ── */
.navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1000;
  padding: 0.85rem 0;
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(20px) saturate(1.4);
  -webkit-backdrop-filter: blur(20px) saturate(1.4);
  box-shadow:
    0 1px 0 #dde3ee,
    0 4px 24px rgba(0, 31, 91, 0.07);
  border-bottom: 1px solid rgba(0, 71, 171, 0.08);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.navbar .container {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 1rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ── Brand ── */
.nav-brand a { text-decoration: none; }

.brand-logo {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.logo-image {
  width: 36px;
  height: 36px;
  object-fit: contain;
  flex-shrink: 0;
}

.nav-brand h1 {
  font-size: clamp(1.1rem, 3vw, 1.35rem);
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.02em;
  display: flex;
  align-items: baseline;
  gap: 0;
  line-height: 1;
}

.brand-amber { color: #F5A623; }
.brand-green { color: #2DB34A; }
.brand-dot   { color: #2DB34A; font-weight: 900; margin: 0 0.05em; font-size: 1.1em; }
.brand-blue  { color: #0047AB; }

/* ── Desktop Links ── */
.nav-links {
  margin-left: auto;
  display: flex;
  gap: clamp(0.75rem, 2vw, 1.75rem);
  align-items: center;
}

.nav-links a {
  text-decoration: none;
  color: #4b5563;
  font-weight: 500;
  font-size: 0.93rem;
  padding: 0.25rem 0;
  position: relative;
  transition: color 0.22s;
}

.nav-links a:not(.nav-cta)::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, #0047AB, #4A90D9);
  border-radius: 9999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.24s ease;
}

.nav-links a:not(.nav-cta):hover,
.nav-links a:not(.nav-cta).router-link-active { color: #0047AB; }

.nav-links a:not(.nav-cta):hover::after,
.nav-links a:not(.nav-cta).router-link-active::after { transform: scaleX(1); }

.nav-cta {
  display: inline-flex !important;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important;
  padding: 0.6rem 1.35rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.9rem;
  white-space: nowrap;
  text-decoration: none;
  transition: all 0.22s;
  box-shadow: 0 4px 16px rgba(0, 71, 171, 0.3);
}

.nav-cta::after { display: none !important; }

.nav-cta:hover {
  background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 71, 171, 0.4);
  color: white !important;
}

/* ── Hamburger ── */
.mobile-menu-btn {
  display: none;
  margin-left: auto;
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
  width: 22px; height: 2px;
  background: #4b5563;
  display: block;
  border-radius: 2px;
  transition: all 0.24s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-menu-btn span.open:nth-child(1) { transform: translateY(7px) rotate(45deg);  background: #0047AB; }
.mobile-menu-btn span.open:nth-child(2) { opacity: 0; transform: scaleX(0); }
.mobile-menu-btn span.open:nth-child(3) { transform: translateY(-7px) rotate(-45deg); background: #0047AB; }

/* ── Mobile Dropdown ── */
.mobile-menu {
  display: none;
  position: absolute;
  top: 100%; left: 0; right: 0;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255, 255, 255, 0.99);
  border-top: 1px solid #dde3ee;
  box-shadow: 0 8px 32px rgba(0, 31, 91, 0.1);
}

.mobile-menu.open { max-height: 580px; }

.mobile-menu-container {
  padding: 1.25rem 1.5rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.mobile-menu-container a {
  text-decoration: none;
  color: #4b5563;
  font-weight: 500;
  padding: 0.85rem 0.5rem;
  font-size: 1rem;
  border-bottom: 1px solid #f0f4f8;
  transition: color 0.2s;
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.mobile-menu-container a svg { color: #0047AB; flex-shrink: 0; }
.mobile-menu-container a:last-of-type { border-bottom: none; }

.mobile-menu-container a:hover,
.mobile-menu-container a.router-link-active { color: #0047AB; }

.mobile-cta {
  margin-top: 1.25rem;
  width: 100%;
  justify-content: center;
  padding: 0.9rem 1.5rem !important;
  border-radius: 9999px !important;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%) !important;
  color: white !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 16px rgba(0, 71, 171, 0.3);
  border-bottom: none !important;
  transition: all 0.22s !important;
}

.mobile-cta svg { color: white !important; }
.mobile-cta:hover { background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%) !important; color: white !important; }

/* ── Responsive ── */
@media (max-width: 991px) {
  .nav-links       { display: none; }
  .mobile-menu-btn { display: flex; }
  .mobile-menu     { display: block; }
}
</style>