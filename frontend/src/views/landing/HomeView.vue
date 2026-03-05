<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>
      <!-- Hero Section -->
      <section class="hero">
        <div class="container">
          <div class="hero-content">
            <div class="hero-badge">🚀 Trusted by 500+ Companies</div>
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
              <router-link to="/contact" class="btn-primary btn-lg">Start Free Trial</router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">Schedule a Demo</router-link>
            </div>
            <div class="hero-trust">
              <p>No credit card required • 14-day free trial • Cancel anytime</p>
            </div>
          </div>
          <div class="hero-image">
            <div class="dashboard-preview">
              <div class="preview-header">
                <div class="preview-dots">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
                <div class="preview-title">Payroll Dashboard</div>
              </div>
              <div class="preview-content">
                <div class="preview-card">
                  <div class="card-icon">💰</div>
                  <div class="card-info">
                    <div class="card-label">Total Payroll</div>
                    <div class="card-value">$245,890</div>
                  </div>
                </div>
                <div class="preview-card">
                  <div class="card-icon">👥</div>
                  <div class="card-info">
                    <div class="card-label">Active Employees</div>
                    <div class="card-value">143</div>
                  </div>
                </div>
                <div class="preview-card">
                  <div class="card-icon">📊</div>
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

      <!-- Benefits Section -->
      <section class="benefits">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Why Choose Archangel Payroll?</h2>
          </div>
          <div class="benefits-grid">
            <div class="benefit-item">
              <div class="benefit-number">01</div>
              <div class="benefit-content">
                <h3>All Features Included</h3>
                <p>Get access to every feature regardless of your plan. Only pay for the team size you need.</p>
              </div>
            </div>
            <div class="benefit-item">
              <div class="benefit-number">02</div>
              <div class="benefit-content">
                <h3>Priority Support</h3>
                <p>Higher plans include faster response times, dedicated account managers, and 24/7 premium support.</p>
              </div>
            </div>
            <div class="benefit-item">
              <div class="benefit-number">03</div>
              <div class="benefit-content">
                <h3>Scale Effortlessly</h3>
                <p>Upgrade your plan as you grow without losing features. All plans include our complete feature set.</p>
              </div>
            </div>
            <div class="benefit-item">
              <div class="benefit-number">04</div>
              <div class="benefit-content">
                <h3>Transparent Pricing</h3>
                <p>No hidden fees. Pay only for the number of employees. All features included in every plan.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <h2>Ready to Transform Your Payroll?</h2>
            <p>Join thousands of companies that trust Archangel Payroll</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">Start Free Trial</router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">Contact Sales</router-link>
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
/* Page-specific styles only — globals live in landing.css */

/* ── Hero ── */
.hero {
  padding-top: calc(clamp(3rem, 8vw, 6rem) + 72px);
  padding-bottom: clamp(2rem, 6vw, 4rem);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.hero-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.hero-title { font-size: clamp(2rem, 6vw, 3.5rem); font-weight: 800; margin-bottom: 1.5rem; line-height: 1.2; }
.hero-subtitle { font-size: clamp(1.1rem, 3vw, 1.35rem); margin-bottom: 2rem; opacity: 0.95; line-height: 1.6; }

.hero-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
}

.stat-number { font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; margin-bottom: 0.5rem; }
.stat-label  { font-size: 0.9rem; opacity: 0.9; }

.hero-actions { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
@media (min-width: 768px) { .hero-actions { flex-direction: row; } }
.hero-trust { font-size: 0.9rem; opacity: 0.8; }

/* Dashboard mockup */
.dashboard-preview {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  color: #333;
  max-width: 500px;
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
.preview-dots span { width: 12px; height: 12px; border-radius: 50%; background: #d1d5db; }
.preview-title { font-weight: 600; color: #1a1a1a; }
.preview-content { display: grid; gap: 1rem; }

.preview-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.card-icon  { font-size: 2rem; }
.card-label { font-size: 0.85rem; color: #666; }
.card-value { font-size: 1.5rem; font-weight: 700; color: #1a1a1a; }

/* ── Benefits ── */
.benefits { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
  gap: clamp(2rem, 4vw, 3rem);
}

.benefit-item { display: flex; gap: clamp(1rem, 2vw, 2rem); align-items: flex-start; }

.benefit-number {
  font-size: clamp(2.5rem, 5vw, 3.5rem);
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  flex-shrink: 0;
}

.benefit-content h3 { font-size: 1.5rem; margin-bottom: 0.75rem; color: #1a1a1a; }
.benefit-content p  { color: #666; line-height: 1.7; }

/* ── CTA ── */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}

.cta-content h2 { font-size: clamp(2rem, 6vw, 3rem); margin-bottom: 1rem; }
.cta-content p  { font-size: clamp(1rem, 2.5vw, 1.25rem); margin-bottom: 2rem; opacity: 0.95; }

@media (max-width: 575px) {
  .hero-actions { flex-direction: column; }
  .hero-actions a { width: 100%; text-align: center; }
}
</style>