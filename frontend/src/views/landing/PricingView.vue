<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>
      <!-- Page Hero -->
      <section class="page-hero">
        <div class="container">
          <h1 class="page-title">Simple, Transparent Pricing</h1>
          <p class="page-subtitle">All features included on every plan. Pay only for your team size.</p>
          <div class="pricing-toggle">
            <span :class="{ active: !annualBilling }" @click="annualBilling = false">Monthly</span>
            <button class="toggle-switch" @click="annualBilling = !annualBilling">
              <span class="toggle-slider" :class="{ annual: annualBilling }"></span>
            </button>
            <span :class="{ active: annualBilling }" @click="annualBilling = true">Annual <span class="save-badge">Save 20%</span></span>
          </div>
        </div>
      </section>

      <!-- Pricing Section -->
      <section class="pricing">
        <div class="container">
          <div class="pricing-grid">
            <div class="pricing-card">
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
              <div class="employee-limit"><span class="limit-label">Up to 10 employees</span></div>
              <ul class="pricing-features">
                <li>✓ All Features Included</li>
                <li>✓ Automated payroll</li>
                <li>✓ Employee self-service</li>
                <li>✓ Time & attendance</li>
                <li>✓ Leave management</li>
                <li>✓ Standard support</li>
              </ul>
              <router-link to="/contact" class="btn-outline btn-block">Start Free Trial</router-link>
            </div>

            <div class="pricing-card featured">
              <div class="featured-badge">Most Popular</div>
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
              <div class="employee-limit"><span class="limit-label">Up to 100 employees</span></div>
              <ul class="pricing-features">
                <li>✓ All Features Included</li>
                <li>✓ Everything in Starter</li>
                <li>✓ Multi-country support</li>
                <li>✓ Advanced analytics</li>
                <li>✓ API access</li>
                <li>✓ Priority support</li>
              </ul>
              <router-link to="/contact" class="btn-primary btn-block">Start Free Trial</router-link>
            </div>

            <div class="pricing-card">
              <div class="pricing-header">
                <h3>Enterprise</h3>
                <div class="pricing-amount"><span class="amount custom-amount">Custom</span></div>
                <p class="plan-desc">For large organizations</p>
              </div>
              <div class="employee-limit"><span class="limit-label">Unlimited employees</span></div>
              <ul class="pricing-features">
                <li>✓ All Features Included</li>
                <li>✓ Everything in Professional</li>
                <li>✓ Custom integrations</li>
                <li>✓ Dedicated account manager</li>
                <li>✓ Custom SLAs</li>
                <li>✓ 24/7 premium support</li>
              </ul>
              <router-link to="/contact" class="btn-outline btn-block">Contact Sales</router-link>
            </div>
          </div>

          <div class="pricing-note">
            <p>All plans include a 14-day free trial. No credit card required.</p>
          </div>

          <!-- Feature comparison note -->
          <div class="comparison-note">
            <h3>Every Plan Includes Every Feature</h3>
            <p>We believe great software shouldn't be gatekept. Whether you're on Starter or Enterprise, you get access to the same full-featured platform. The difference is team size capacity and support level.</p>
            <div class="comparison-grid">
              <div class="comparison-item">
                <div class="comparison-icon">💡</div>
                <h4>Starter</h4>
                <p>Standard email & chat support with 24hr response time</p>
              </div>
              <div class="comparison-item">
                <div class="comparison-icon">⚡</div>
                <h4>Professional</h4>
                <p>Priority support with 4hr response time</p>
              </div>
              <div class="comparison-item">
                <div class="comparison-icon">🏆</div>
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
/* Page-specific styles only — globals live in landing.css */

.page-hero {
  padding-top: calc(clamp(2.5rem, 6vw, 4rem) + 72px);
  padding-bottom: clamp(2.5rem, 6vw, 4rem);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}
.page-title    { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 1rem; line-height: 1.2; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.25rem); opacity: 0.95; max-width: 600px; margin: 0 auto 2rem; }

.save-badge {
  background: #10b981;
  color: white;
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  border-radius: 50px;
  font-weight: 600;
}

/* ── Pricing Cards ── */
.pricing { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }

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
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  transition: all 0.3s;
  position: relative;
  display: flex;
  flex-direction: column;
}
.pricing-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
.pricing-card.featured { border-color: #667eea; box-shadow: 0 12px 24px rgba(102,126,234,0.2); }

.pricing-header { text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e5e7eb; }
.pricing-header h3 { font-size: 1.5rem; margin-bottom: 1rem; color: #1a1a1a; }

.pricing-amount {
  display: flex;
  align-items: baseline;
  justify-content: center;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
  gap: 0.25rem;
}
.currency { font-size: 1.5rem; font-weight: 600; color: #666; }
.amount {
  font-size: clamp(3rem, 6vw, 4rem);
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
}
.custom-amount { font-size: clamp(2rem, 5vw, 2.75rem); }
.period { font-size: 1rem; color: #666; }
.annual-savings { color: #10b981; font-weight: 600; font-size: 0.9rem; }
.plan-desc { color: #666; }

.employee-limit {
  text-align: center;
  margin: 1.5rem 0;
  padding: 0.75rem;
  background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
  border-radius: 8px;
}
.limit-label { font-weight: 600; color: #667eea; font-size: 1.1rem; }

.pricing-features { list-style: none; padding: 0; margin-bottom: 2rem; flex-grow: 1; }
.pricing-features li { padding: 0.75rem 0; color: #555; border-bottom: 1px solid #f3f4f6; font-size: 0.95rem; }

.pricing-note { text-align: center; margin-top: 3rem; color: #666; font-size: 0.95rem; }

/* ── Comparison block ── */
.comparison-note {
  margin-top: clamp(3rem, 6vw, 5rem);
  background: white;
  border-radius: 16px;
  padding: clamp(2rem, 4vw, 3rem);
  border: 1px solid #e5e7eb;
  text-align: center;
}
.comparison-note h3 { font-size: clamp(1.5rem, 3vw, 2rem); margin-bottom: 1rem; color: #1a1a1a; }
.comparison-note > p { color: #666; max-width: 700px; margin: 0 auto 2.5rem; line-height: 1.7; }

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 240px), 1fr));
  gap: 1.5rem;
}
.comparison-item { padding: 1.5rem; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; }
.comparison-icon { font-size: 2rem; margin-bottom: 0.75rem; }
.comparison-item h4 { font-size: 1.1rem; margin-bottom: 0.5rem; color: #1a1a1a; }
.comparison-item p  { color: #666; font-size: 0.93rem; line-height: 1.6; }
</style>