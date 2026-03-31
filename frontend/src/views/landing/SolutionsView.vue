<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Page Hero ── -->
      <section class="page-hero">
        <div class="container">
          <!-- Heroicon: light-bulb -->
          <div class="hero-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="52" height="52">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
          </div>
          <h1 class="page-title">Solutions for Every Business</h1>
          <p class="page-subtitle">Whether you're a startup or enterprise, we have the right solution for your team</p>
        </div>
      </section>

      <!-- ── Solution Cards ── -->
      <section class="solutions">
        <div class="container">
          <div class="solutions-grid">

            <!-- Startups -->
            <div class="solution-card">
              <div class="solution-icon-wrap solution-icon--blue">
                <!-- Heroicon: rocket-launch -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30" height="30">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                </svg>
              </div>
              <h3>For Startups</h3>
              <p>Simple, affordable payroll for growing teams</p>
              <ul class="solution-list">
                <li>1–50 employees</li>
                <li>Standard support</li>
                <li>All features included</li>
                <li>Email &amp; chat support</li>
              </ul>
              <router-link to="/pricing" class="btn-outline">
                <!-- Heroicon: arrow-right -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                View Pricing
              </router-link>
            </div>

            <!-- SMBs — featured -->
            <div class="solution-card solution-card--featured">
              <div class="featured-badge">
                <!-- Heroicon: star (filled) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                  <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                </svg>
                Most Popular
              </div>
              <div class="solution-icon-wrap solution-icon--orange">
                <!-- Heroicon: building-office-2 -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30" height="30">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
              </div>
              <h3>For SMBs</h3>
              <p>Complete payroll and HR for medium businesses</p>
              <ul class="solution-list">
                <li>50–500 employees</li>
                <li>Priority support</li>
                <li>Multi-location support</li>
                <li>Dedicated account manager</li>
              </ul>
              <router-link to="/pricing" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                View Pricing
              </router-link>
            </div>

            <!-- Enterprise -->
            <div class="solution-card">
              <div class="solution-icon-wrap solution-icon--green">
                <!-- Heroicon: trophy -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30" height="30">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                </svg>
              </div>
              <h3>For Enterprise</h3>
              <p>Scalable payroll for large organizations</p>
              <ul class="solution-list">
                <li>500+ employees</li>
                <li>Custom workflows</li>
                <li>24/7 premium support</li>
                <li>Custom SLAs</li>
              </ul>
              <router-link to="/contact" class="btn-outline">
                <!-- Heroicon: phone -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                Contact Sales
              </router-link>
            </div>

          </div>
        </div>
      </section>

      <!-- ── Industry Solutions ── -->
      <section class="industry">
        <div class="container">
          <div class="section-header">
            <!-- Heroicon: squares-2x2 -->
            <div class="section-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36" height="36">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
              </svg>
            </div>
            <h2 class="section-title">Built for Every Industry</h2>
            <p class="section-subtitle">Archangel Payroll adapts to your industry's unique compliance and workflow needs</p>
          </div>

          <div class="industry-grid">

            <div class="industry-card industry-card--blue">
              <div class="industry-icon">
                <!-- Heroicon: banknotes -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
              </div>
              <h3>Finance &amp; Banking</h3>
              <p>Full compliance with financial sector regulations, robust audit trails, and secure data handling.</p>
            </div>

            <div class="industry-card industry-card--orange">
              <div class="industry-icon">
                <!-- Heroicon: heart (healthcare) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </div>
              <h3>Healthcare</h3>
              <p>Manage complex shift patterns, licensing requirements, and specialized pay structures for medical staff.</p>
            </div>

            <div class="industry-card industry-card--green">
              <div class="industry-icon">
                <!-- Heroicon: shopping-bag -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
              </div>
              <h3>Retail &amp; Hospitality</h3>
              <p>Handle high-volume hourly workers, seasonal staff, and tip management seamlessly.</p>
            </div>

            <div class="industry-card industry-card--blue">
              <div class="industry-icon">
                <!-- Heroicon: wrench-screwdriver -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                </svg>
              </div>
              <h3>Construction</h3>
              <p>Project-based payroll, contractor management, and multi-site workforce tracking.</p>
            </div>

            <div class="industry-card industry-card--orange">
              <div class="industry-icon">
                <!-- Heroicon: computer-desktop -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
                </svg>
              </div>
              <h3>Technology</h3>
              <p>Equity compensation, remote team payroll across jurisdictions, and flexible benefit management.</p>
            </div>

            <div class="industry-card industry-card--green">
              <div class="industry-icon">
                <!-- Heroicon: academic-cap -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
              </div>
              <h3>Education</h3>
              <p>Term-time pay schedules, grant management, and support for both teaching and administrative staff.</p>
            </div>

          </div>
        </div>
      </section>

      <!-- ── CTA ── -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <!-- Heroicon: chat-bubble-left-ellipsis -->
            <div class="cta-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="40" height="40">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
              </svg>
            </div>
            <h2>Not Sure Which Solution Fits?</h2>
            <p>Talk to our team and we'll help you find the perfect plan for your business</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">
                <!-- Heroicon: phone -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                Talk to Sales
              </router-link>
              <router-link to="/pricing" class="btn-secondary btn-lg">
                <!-- Heroicon: tag -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
                View Pricing
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
/* ── Page Hero ── */
.page-hero {
  padding-top: calc(clamp(2.5rem, 6vw, 4rem) + 72px);
  padding-bottom: clamp(2.5rem, 6vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    radial-gradient(ellipse 80% 70% at 85% 20%, rgba(0, 71, 171, 0.45) 0%, transparent 60%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white;
  text-align: center;
}

.hero-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #F5A623;
}

.page-title {
  font-size: clamp(2rem, 5vw, 3rem);
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.15;
  letter-spacing: -0.02em;
}

.page-subtitle {
  font-size: clamp(1rem, 2.5vw, 1.2rem);
  color: rgba(255, 255, 255, 0.72);
  max-width: 560px;
  margin: 0 auto;
  font-weight: 300;
  line-height: 1.7;
}

/* ── Solution Cards ── */
.solutions { padding: clamp(3rem, 8vw, 6rem) 0; background: #f0f4f8; }

.solutions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
  max-width: 1000px;
  margin: 0 auto;
}

.solution-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  border-radius: 16px;
  border: 1.5px solid #dde3ee;
  text-align: center;
  transition: all 0.28s;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0, 71, 171, 0.05);
}

.solution-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 32px rgba(0, 71, 171, 0.13);
  border-color: #4A90D9;
}

.solution-card--featured {
  border: 2px solid #0047AB;
  box-shadow: 0 10px 30px rgba(0, 71, 171, 0.18);
  background: linear-gradient(160deg, rgba(0,71,171,0.03) 0%, rgba(74,144,217,0.05) 100%);
}

/* Featured badge */
.featured-badge {
  position: absolute;
  top: -13px; left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white;
  padding: 0.35rem 1.1rem;
  border-radius: 9999px;
  font-size: 0.73rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  white-space: nowrap;
  display: flex; align-items: center; gap: 0.35rem;
  box-shadow: 0 4px 14px rgba(0, 71, 171, 0.32);
}

/* Solution icon wraps */
.solution-icon-wrap {
  width: 68px; height: 68px;
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.25rem;
}

.solution-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.solution-icon--orange { background: rgba(245, 166, 35, 0.12); color: #c47f00; }
.solution-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }

.solution-card h3 {
  font-size: 1.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  color: #001a5e;
  letter-spacing: -0.015em;
}

.solution-card > p {
  color: #4b5563;
  margin-bottom: 1.75rem;
  font-size: 0.95rem;
  line-height: 1.6;
}

.solution-list {
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
  text-align: left;
  width: 100%;
  flex-grow: 1;
}

.solution-list li {
  padding: 0.7rem 0 0.7rem 1.6rem;
  border-bottom: 1px solid #f0f4f8;
  color: #374151;
  font-size: 0.92rem;
  position: relative;
}

.solution-list li:last-child { border-bottom: none; }

.solution-list li::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 16px; height: 16px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%232DB34A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m4.5 12.75 6 6 9-13.5'/%3E%3C/svg%3E");
  background-size: contain;
  background-repeat: no-repeat;
}

.solution-card a { margin-top: auto; width: 100%; }

/* ── Industry ── */
.industry { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }

.section-header { text-align: center; margin-bottom: 3rem; }

.section-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
  color: #0047AB;
}

.section-title {
  font-size: clamp(1.75rem, 5vw, 2.6rem);
  font-weight: 800;
  color: #001a5e;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
}

.section-subtitle {
  font-size: clamp(0.95rem, 2.5vw, 1.1rem);
  color: #4b5563;
  max-width: 560px;
  margin: 0 auto;
  font-weight: 300;
  line-height: 1.7;
}

.industry-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: clamp(1.25rem, 2.5vw, 1.75rem);
}

.industry-card {
  background: white;
  padding: 1.75rem;
  border-radius: 14px;
  border: 1px solid #dde3ee;
  border-top: 3px solid transparent;
  box-shadow: 0 2px 10px rgba(0, 71, 171, 0.05);
  transition: all 0.28s;
}

.industry-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0, 71, 171, 0.12);
}

.industry-card--blue   { border-top-color: #0047AB; }
.industry-card--orange { border-top-color: #F5A623; }
.industry-card--green  { border-top-color: #2DB34A; }

.industry-icon {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.1rem;
}

.industry-card--blue   .industry-icon { background: rgba(0, 71, 171, 0.1);   color: #0047AB; }
.industry-card--orange .industry-icon { background: rgba(245, 166, 35, 0.12); color: #c47f00; }
.industry-card--green  .industry-icon { background: rgba(45, 179, 74, 0.1);   color: #2DB34A; }

.industry-card h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.65rem;
  color: #001a5e;
}

.industry-card p { color: #4b5563; line-height: 1.72; font-size: 0.92rem; margin: 0; }

/* ── CTA ── */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 70% 60% at 90% 100%, rgba(245, 166, 35, 0.18) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 5% 5%, rgba(45, 179, 74, 0.1) 0%, transparent 60%),
    linear-gradient(148deg, #001a5e 0%, #0047AB 55%, #001240 100%);
  color: white;
  text-align: center;
}

.cta-icon {
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
  color: rgba(255, 255, 255, 0.7);
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

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important; border: none;
  padding: 0.82rem 1.75rem; border-radius: 9999px;
  font-weight: 700; font-size: 0.93rem;
  cursor: pointer; transition: all 0.24s;
  box-shadow: 0 4px 18px rgba(0, 71, 171, 0.4);
  text-decoration: none; white-space: nowrap;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 26px rgba(0, 71, 171, 0.5);
}

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: transparent; color: white !important;
  border: 2px solid rgba(255, 255, 255, 0.5);
  padding: 0.82rem 1.75rem; border-radius: 9999px;
  font-weight: 600; font-size: 0.93rem;
  cursor: pointer; transition: all 0.24s;
  text-decoration: none; white-space: nowrap;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}

.btn-outline {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
  background: transparent; color: #0047AB !important;
  border: 2px solid #0047AB;
  padding: 0.78rem 1.5rem; border-radius: 9999px;
  font-weight: 700; font-size: 0.9rem;
  cursor: pointer; transition: all 0.24s;
  text-decoration: none; white-space: nowrap;
}

.btn-outline:hover {
  background: #0047AB;
  color: white !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 71, 171, 0.3);
}

.btn-lg { padding: 1rem 2.2rem; font-size: 1rem; }
</style>