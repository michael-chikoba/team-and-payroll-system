<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Hero ── -->
      <section class="hero">
        <div class="container">
          <div class="hero-content">
            <div class="hero-badge">
              <!-- Heroicon: rocket-launch -->
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
              </svg>
              Trusted by 500+ Companies
            </div>

            <h1 class="hero-title">Complete Payroll Management for Modern Businesses</h1>
            <p class="hero-subtitle">
              Streamline payroll, manage employees, track attendance, and ensure compliance—all in one powerful, cloud-based platform.
            </p>

            <div class="hero-stats">
              <div class="stat-item">
                <div class="stat-number">70%</div>
                <div class="stat-label">Time Saved</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Accuracy</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support</div>
              </div>
            </div>

            <div class="hero-actions">
              <router-link to="/contact" class="btn-primary btn-lg">
                <!-- Heroicon: play -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                </svg>
                Start Free Trial
              </router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">
                <!-- Heroicon: calendar-days -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Schedule a Demo
              </router-link>
            </div>

            <div class="hero-trust">
              <!-- Heroicon: shield-check -->
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              No credit card required • 14-day free trial • Cancel anytime
            </div>
          </div>

          <!-- Dashboard mockup -->
          <div class="hero-image">
            <div class="dashboard-preview">
              <div class="preview-header">
                <div class="preview-dots">
                  <span class="dot dot-red"></span>
                  <span class="dot dot-orange"></span>
                  <span class="dot dot-green"></span>
                </div>
                <div class="preview-title">Payroll Dashboard</div>
              </div>
              <div class="preview-content">
                <div class="preview-card">
                  <div class="card-icon-wrap card-icon--blue">
                    <!-- Heroicon: banknotes -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                  </div>
                  <div class="card-info">
                    <div class="card-label">Total Payroll</div>
                    <div class="card-value">$245,890</div>
                  </div>
                </div>
                <div class="preview-card">
                  <div class="card-icon-wrap card-icon--orange">
                    <!-- Heroicon: users -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                  </div>
                  <div class="card-info">
                    <div class="card-label">Active Employees</div>
                    <div class="card-value">143</div>
                  </div>
                </div>
                <div class="preview-card">
                  <div class="card-icon-wrap card-icon--green">
                    <!-- Heroicon: chart-bar -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                  </div>
                  <div class="card-info">
                    <div class="card-label">Processing</div>
                    <div class="card-value">On Time</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Benefits ── -->
      <section class="benefits">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Why Choose Archangel Payroll?</h2>
          </div>
          <div class="benefits-grid">

            <div class="benefit-item">
              <div class="benefit-icon benefit-icon--blue">
                <!-- Heroicon: squares-2x2 -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
              </div>
              <div class="benefit-content">
                <h3>All Features Included</h3>
                <p>Get access to every feature regardless of your plan. Only pay for the team size you need.</p>
              </div>
            </div>

            <div class="benefit-item">
              <div class="benefit-icon benefit-icon--orange">
                <!-- Heroicon: bolt -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                </svg>
              </div>
              <div class="benefit-content">
                <h3>Priority Support</h3>
                <p>Higher plans include faster response times, dedicated account managers, and 24/7 premium support.</p>
              </div>
            </div>

            <div class="benefit-item">
              <div class="benefit-icon benefit-icon--green">
                <!-- Heroicon: arrow-trending-up -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                </svg>
              </div>
              <div class="benefit-content">
                <h3>Scale Effortlessly</h3>
                <p>Upgrade your plan as you grow without losing features. All plans include our complete feature set.</p>
              </div>
            </div>

            <div class="benefit-item">
              <div class="benefit-icon benefit-icon--blue">
                <!-- Heroicon: tag -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
              </div>
              <div class="benefit-content">
                <h3>Transparent Pricing</h3>
                <p>No hidden fees. Pay only for the number of employees. All features included in every plan.</p>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- ── CTA ── -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <!-- Heroicon: sparkles -->
            <div class="cta-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36" height="36">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
              </svg>
            </div>
            <h2>Ready to Transform Your Payroll?</h2>
            <p>Join thousands of companies that trust Archangel Payroll</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">
                <!-- Heroicon: arrow-right -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                Start Free Trial
              </router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">
                <!-- Heroicon: phone -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                Contact Sales
              </router-link>
            </div>
          </div>
        </div>
      </section>

    </main>
    <LandingFooter />
  </div>
</template>

<script setup>
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
</script>

<style scoped>


.landing-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  width: 100%;
}

.main-content {
  flex: 1 0 auto;
  width: 100%;
}

.container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 clamp(1rem, 4vw, 2rem);
}

.hero {
  padding-top: calc(clamp(3rem, 8vw, 6rem) + 72px);
  padding-bottom: clamp(2rem, 6vw, 4rem);
  /* Rest of your hero styles remain the same */
}
/* ── Hero ── */
.hero {
  padding-top: calc(clamp(3rem, 8vw, 6rem) + 72px);
  padding-bottom: clamp(2rem, 6vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    radial-gradient(ellipse 80% 70% at 85% 20%, rgba(0, 71, 171, 0.45) 0%, transparent 60%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white;
}

.hero .container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 3rem;
  align-items: center;
}

@media (min-width: 992px) {
  .hero .container { grid-template-columns: 1fr 1fr; gap: 4rem; }
}

/* Badge — orange tint matching logo amber */
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(245, 166, 35, 0.18);
  border: 1px solid rgba(245, 166, 35, 0.4);
  color: #F5A623;
  padding: 0.45rem 1rem;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: clamp(2rem, 6vw, 3.5rem);
  font-weight: 800;
  margin-bottom: 1.5rem;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: #ffffff;
}

.hero-subtitle {
  font-size: clamp(1rem, 2.5vw, 1.2rem);
  margin-bottom: 2rem;
  opacity: 0.88;
  line-height: 1.7;
  font-weight: 300;
  color: rgba(255,255,255,0.9);
}

/* Stats bar */
.hero-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.25rem 1.5rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(74, 144, 217, 0.25);
  border-radius: 12px;
}

.stat-number {
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  font-weight: 800;
  margin-bottom: 0.25rem;
  /* Orange accent for the numbers */
  color: #F5A623;
}

.stat-label { font-size: 0.85rem; opacity: 0.8; }

.hero-actions { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }

@media (min-width: 600px) { .hero-actions { flex-direction: row; } }

/* Primary CTA — solid blue */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important;
  border: none;
  padding: 0.82rem 1.75rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.24s;
  box-shadow: 0 4px 18px rgba(0, 71, 171, 0.4);
  text-decoration: none;
  white-space: nowrap;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(0, 71, 171, 0.5);
}

.btn-lg { padding: 1rem 2.2rem; font-size: 1rem; }

/* Secondary CTA — outline white */
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: transparent;
  color: white !important;
  border: 2px solid rgba(255, 255, 255, 0.55);
  padding: 0.82rem 1.75rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.24s;
  text-decoration: none;
  white-space: nowrap;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}

.hero-trust {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  color: rgba(255,255,255,0.6);
}

/* Dashboard mockup */
.dashboard-preview {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.35);
  color: #333;
  max-width: 480px;
  margin: 0 auto;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.preview-dots { display: flex; gap: 6px; }
.dot { width: 12px; height: 12px; border-radius: 50%; }
.dot-red    { background: #ef4444; }
.dot-orange { background: #F5A623; }
.dot-green  { background: #2DB34A; }

.preview-title { font-weight: 700; color: #001a5e; font-size: 0.9rem; }
.preview-content { display: grid; gap: 0.85rem; }

.preview-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

/* Colored icon backgrounds using logo colors */
.card-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.card-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.card-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.card-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }

.card-label { font-size: 0.78rem; color: #64748b; font-weight: 500; margin-bottom: 0.2rem; }
.card-value { font-size: 1.3rem; font-weight: 800; color: #001a5e; }

/* ── Benefits ── */
.benefits { padding: clamp(3rem, 8vw, 6rem) 0; background: #f0f4f8; }

.section-header { text-align: center; margin-bottom: 3rem; }

.section-title {
  font-size: clamp(1.75rem, 5vw, 2.6rem);
  font-weight: 800;
  color: #001a5e;
  letter-spacing: -0.02em;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr));
  gap: clamp(1.5rem, 3vw, 2.5rem);
}

.benefit-item {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #dde3ee;
  border-left: 4px solid transparent;
  box-shadow: 0 2px 10px rgba(0, 71, 171, 0.06);
  transition: all 0.28s;
}

.benefit-item:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 28px rgba(0, 71, 171, 0.12);
}

.benefit-item:nth-child(1) { border-left-color: #0047AB; }
.benefit-item:nth-child(2) { border-left-color: #F5A623; }
.benefit-item:nth-child(3) { border-left-color: #2DB34A; }
.benefit-item:nth-child(4) { border-left-color: #4A90D9; }

/* Benefit icons */
.benefit-icon {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.benefit-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.benefit-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.benefit-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }

.benefit-content h3 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.6rem;
  color: #001a5e;
}

.benefit-content p { color: #4b5563; line-height: 1.7; font-size: 0.93rem; }

/* ── CTA ── */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 70% 60% at 90% 100%, rgba(245, 166, 35, 0.15) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 5% 5%, rgba(45, 179, 74, 0.1) 0%, transparent 60%),
    linear-gradient(148deg, #001a5e 0%, #0047AB 55%, #001240 100%);
  color: white;
  text-align: center;
}

.cta-icon-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #F5A623;
}

.cta-content h2 {
  font-size: clamp(2rem, 6vw, 3rem);
  margin-bottom: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.cta-content p {
  font-size: clamp(1rem, 2.5vw, 1.2rem);
  margin-bottom: 2.5rem;
  color: rgba(255,255,255,0.75);
  font-weight: 300;
}

.cta-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: center;
  align-items: center;
}

@media (min-width: 600px) { .cta-actions { flex-direction: row; } }

/* Override btn-secondary for dark background */
.cta .btn-secondary {
  color: white !important;
  border-color: rgba(74, 144, 217, 0.6);
}

.cta .btn-secondary:hover {
  background: rgba(74, 144, 217, 0.2);
  border-color: #4A90D9;
}
</style>