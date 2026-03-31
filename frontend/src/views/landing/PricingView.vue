<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Page Hero ── -->
      <section class="page-hero">
        <div class="container">
          <!-- Heroicon: tag -->
          <div class="hero-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="52" height="52">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
            </svg>
          </div>
          <h1 class="page-title">Simple, Transparent Pricing</h1>
          <p class="page-subtitle">All features included on every plan. Pay only for your team size.</p>

          <!-- Billing toggle -->
          <div class="pricing-toggle">
            <span :class="{ active: !annualBilling }" @click="annualBilling = false">Monthly</span>
            <button class="toggle-switch" @click="annualBilling = !annualBilling" :aria-label="annualBilling ? 'Switch to monthly' : 'Switch to annual'">
              <span class="toggle-slider" :class="{ annual: annualBilling }"></span>
            </button>
            <span :class="{ active: annualBilling }" @click="annualBilling = true">
              Annual
              <span class="save-badge">
                <!-- Heroicon: arrow-trending-down -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="11" height="11">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                </svg>
                Save 20%
              </span>
            </span>
          </div>
        </div>
      </section>

      <!-- ── Pricing Cards ── -->
      <section class="pricing">
        <div class="container">
          <div class="pricing-grid">

            <!-- Starter -->
            <div class="pricing-card">
              <div class="plan-icon plan-icon--blue">
                <!-- Heroicon: rocket-launch -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                </svg>
              </div>
              <div class="pricing-header">
                <h3>Starter</h3>
                <div class="pricing-amount">
                  <span class="currency">$</span>
                  <span class="amount">{{ annualBilling ? '39' : '49' }}</span>
                  <span class="period">/month</span>
                </div>
                <p v-if="annualBilling" class="annual-savings">Billed annually ($468/year)</p>
                <p v-else class="plan-desc">Perfect for small teams</p>
              </div>
              <div class="employee-limit">
                <!-- Heroicon: users -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Up to 10 employees
              </div>
              <ul class="pricing-features">
                <li>All Features Included</li>
                <li>Automated payroll</li>
                <li>Employee self-service</li>
                <li>Time &amp; attendance</li>
                <li>Leave management</li>
                <li>Standard support</li>
              </ul>
              <router-link to="/contact" class="btn-outline btn-block">
                <!-- Heroicon: arrow-right -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                Start Free Trial
              </router-link>
            </div>

            <!-- Professional — featured -->
            <div class="pricing-card pricing-card--featured">
              <div class="featured-badge">
                <!-- Heroicon: star -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                  <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                </svg>
                Most Popular
              </div>
              <div class="plan-icon plan-icon--orange">
                <!-- Heroicon: bolt -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                </svg>
              </div>
              <div class="pricing-header">
                <h3>Professional</h3>
                <div class="pricing-amount">
                  <span class="currency">$</span>
                  <span class="amount">{{ annualBilling ? '89' : '109' }}</span>
                  <span class="period">/month</span>
                </div>
                <p v-if="annualBilling" class="annual-savings">Billed annually ($1,068/year)</p>
                <p v-else class="plan-desc">For growing businesses</p>
              </div>
              <div class="employee-limit employee-limit--featured">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Up to 100 employees
              </div>
              <ul class="pricing-features">
                <li>All Features Included</li>
                <li>Everything in Starter</li>
                <li>Multi-country support</li>
                <li>Advanced analytics</li>
                <li>API access</li>
                <li>Priority support</li>
              </ul>
              <router-link to="/contact" class="btn-primary btn-block">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
                Start Free Trial
              </router-link>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
              <div class="plan-icon plan-icon--green">
                <!-- Heroicon: building-office-2 -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
              </div>
              <div class="pricing-header">
                <h3>Enterprise</h3>
                <div class="pricing-amount">
                  <span class="amount amount--custom">Custom</span>
                </div>
                <p class="plan-desc">For large organizations</p>
              </div>
              <div class="employee-limit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Unlimited employees
              </div>
              <ul class="pricing-features">
                <li>All Features Included</li>
                <li>Everything in Professional</li>
                <li>Custom integrations</li>
                <li>Dedicated account manager</li>
                <li>Custom SLAs</li>
                <li>24/7 premium support</li>
              </ul>
              <router-link to="/contact" class="btn-outline btn-block">
                <!-- Heroicon: phone -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                Contact Sales
              </router-link>
            </div>

          </div>

          <!-- Free trial note -->
          <div class="pricing-note">
            <!-- Heroicon: shield-check -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            All plans include a 14-day free trial. No credit card required.
          </div>

          <!-- Comparison block -->
          <div class="comparison-note">
            <div class="comparison-header-icon">
              <!-- Heroicon: squares-2x2 -->
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32" height="32">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
              </svg>
            </div>
            <h3>Every Plan Includes Every Feature</h3>
            <p>We believe great software shouldn't be gatekept. Whether you're on Starter or Enterprise, you get access to the same full-featured platform. The difference is team size capacity and support level.</p>

            <div class="comparison-grid">
              <div class="comparison-item comparison-item--blue">
                <div class="comparison-icon">
                  <!-- Heroicon: light-bulb -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                  </svg>
                </div>
                <h4>Starter</h4>
                <p>Standard email &amp; chat support with 24hr response time</p>
              </div>
              <div class="comparison-item comparison-item--orange">
                <div class="comparison-icon">
                  <!-- Heroicon: bolt -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                  </svg>
                </div>
                <h4>Professional</h4>
                <p>Priority support with 4hr response time</p>
              </div>
              <div class="comparison-item comparison-item--green">
                <div class="comparison-icon">
                  <!-- Heroicon: trophy -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                  </svg>
                </div>
                <h4>Enterprise</h4>
                <p>24/7 dedicated account manager with 1hr response SLA</p>
              </div>
            </div>
          </div>

        </div>
      </section>
    </main>
    <LandingFooter />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

const annualBilling = ref(false)
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
  color: rgba(255, 255, 255, 0.75);
  max-width: 520px;
  margin: 0 auto 2rem;
  font-weight: 300;
  line-height: 1.7;
}

/* Billing toggle */
.pricing-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 1.5rem;
}

.pricing-toggle span {
  color: rgba(255, 255, 255, 0.55);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.22s;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.pricing-toggle span.active { color: #ffffff; font-weight: 700; }

.save-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: #2DB34A;
  color: white;
  font-size: 0.72rem;
  padding: 0.22rem 0.6rem;
  border-radius: 9999px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

/* Toggle switch */
.toggle-switch {
  width: 52px; height: 28px;
  background: rgba(74, 144, 217, 0.35);
  border-radius: 9999px;
  border: none;
  position: relative;
  cursor: pointer;
  transition: background 0.3s;
}

.toggle-switch:hover { background: rgba(74, 144, 217, 0.55); }

.toggle-slider {
  position: absolute;
  left: 3px; top: 3px;
  width: 22px; height: 22px;
  background: #ffffff;
  border-radius: 50%;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.toggle-slider.annual { transform: translateX(24px); }

/* ── Pricing section ── */
.pricing { padding: clamp(3rem, 8vw, 6rem) 0; background: #f0f4f8; }

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
  max-width: 1000px;
  margin: 0 auto;
}

.pricing-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  border-radius: 16px;
  border: 1.5px solid #dde3ee;
  transition: all 0.28s;
  position: relative;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 12px rgba(0, 71, 171, 0.06);
}

.pricing-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 36px rgba(0, 71, 171, 0.14);
}

/* Featured professional card */
.pricing-card--featured {
  border: 2px solid #0047AB;
  box-shadow: 0 10px 32px rgba(0, 71, 171, 0.2);
  background: linear-gradient(160deg, rgba(0,71,171,0.03) 0%, rgba(74,144,217,0.05) 100%);
}

/* Plan icons */
.plan-icon {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.1rem;
}

.plan-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.plan-icon--orange { background: rgba(245, 166, 35, 0.12); color: #c47f00; }
.plan-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }

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

.pricing-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.pricing-header h3 {
  font-size: 1.35rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  color: #001a5e;
}

.pricing-amount {
  display: flex;
  align-items: baseline;
  gap: 0.2rem;
  margin-bottom: 0.4rem;
  flex-wrap: wrap;
}

.currency { font-size: 1.35rem; font-weight: 700; color: #4b5563; }

.amount {
  font-size: clamp(2.75rem, 6vw, 3.75rem);
  font-weight: 900;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  letter-spacing: -0.03em;
}

.amount--custom {
  font-size: clamp(2rem, 5vw, 2.6rem);
}

.period { font-size: 0.95rem; color: #9ca3af; font-weight: 400; }

.annual-savings {
  color: #2DB34A;
  font-weight: 700;
  font-size: 0.85rem;
  margin: 0;
}

.plan-desc { color: #9ca3af; margin: 0; font-size: 0.88rem; }

/* Employee limit pill */
.employee-limit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1.5rem;
  padding: 0.65rem 1rem;
  background: rgba(0, 71, 171, 0.06);
  border: 1px solid rgba(0, 71, 171, 0.15);
  border-radius: 10px;
  font-weight: 700;
  color: #0047AB;
  font-size: 0.9rem;
}

.employee-limit--featured {
  background: rgba(245, 166, 35, 0.08);
  border-color: rgba(245, 166, 35, 0.25);
  color: #c47f00;
}

/* Feature list */
.pricing-features {
  list-style: none;
  padding: 0;
  margin: 0 0 1.75rem;
  flex-grow: 1;
}

.pricing-features li {
  padding: 0.65rem 0 0.65rem 1.6rem;
  color: #374151;
  border-bottom: 1px solid #f0f4f8;
  font-size: 0.9rem;
  position: relative;
  line-height: 1.45;
}

.pricing-features li:last-child { border-bottom: none; }

.pricing-features li::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 16px; height: 16px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%232DB34A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m4.5 12.75 6 6 9-13.5'/%3E%3C/svg%3E");
  background-size: contain;
  background-repeat: no-repeat;
}

/* Pricing note */
.pricing-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-align: center;
  margin-top: 2.5rem;
  color: #4b5563;
  font-size: 0.9rem;
  font-weight: 500;
}

.pricing-note svg { color: #2DB34A; flex-shrink: 0; }

/* Comparison block */
.comparison-note {
  margin-top: clamp(3rem, 6vw, 5rem);
  background: white;
  border-radius: 16px;
  padding: clamp(2rem, 4vw, 3.5rem);
  border: 1px solid #dde3ee;
  text-align: center;
  box-shadow: 0 2px 14px rgba(0, 71, 171, 0.06);
}

.comparison-header-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
  color: #0047AB;
}

.comparison-note h3 {
  font-size: clamp(1.4rem, 3vw, 2rem);
  font-weight: 800;
  margin-bottom: 1rem;
  color: #001a5e;
  letter-spacing: -0.015em;
}

.comparison-note > p {
  color: #4b5563;
  max-width: 680px;
  margin: 0 auto 2.5rem;
  line-height: 1.75;
  font-size: 0.95rem;
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 220px), 1fr));
  gap: 1.25rem;
}

.comparison-item {
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #dde3ee;
  transition: all 0.28s;
}

.comparison-item:hover { transform: translateY(-3px); }

.comparison-item--blue  { border-top: 3px solid #0047AB; }
.comparison-item--orange { border-top: 3px solid #F5A623; }
.comparison-item--green { border-top: 3px solid #2DB34A; }

.comparison-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 0.85rem;
}

.comparison-item--blue  .comparison-icon { color: #0047AB; }
.comparison-item--orange .comparison-icon { color: #c47f00; }
.comparison-item--green .comparison-icon { color: #2DB34A; }

.comparison-item h4 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #001a5e;
}

.comparison-item p { color: #4b5563; font-size: 0.88rem; line-height: 1.65; margin: 0; }

/* ── Shared buttons ── */
.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important;
  border: none;
  padding: 0.82rem 1.5rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  transition: all 0.24s;
  box-shadow: 0 4px 14px rgba(0, 71, 171, 0.3);
  text-decoration: none;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0, 71, 171, 0.42);
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  background: transparent;
  color: #0047AB !important;
  border: 2px solid #0047AB;
  padding: 0.78rem 1.5rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  transition: all 0.24s;
  text-decoration: none;
}

.btn-outline:hover {
  background: #0047AB;
  color: white !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 71, 171, 0.3);
}

.btn-block { width: 100%; }
</style>