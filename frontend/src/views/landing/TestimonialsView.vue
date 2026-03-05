<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>
      <!-- Page Hero -->
      <section class="page-hero">
        <div class="container">
          <h1 class="page-title">Trusted by Leading Companies</h1>
          <p class="page-subtitle">See what our customers have to say about Archangel Payroll</p>
        </div>
      </section>

      <!-- Testimonials Section -->
      <section class="testimonials">
        <div class="container">
          <div class="testimonials-grid">
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"Archangel Payroll has completely transformed our HR operations. The multi-country support handles our Zambian, Kenyan, and South African payroll seamlessly."</p>
              <div class="testimonial-author">
                <div class="author-avatar">NN</div>
                <div class="author-info">
                  <div class="author-name">Mr. Nawa Nawa</div>
                  <div class="author-title">HR Director, EaglePay</div>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"As Managing Director of Castlebet, I need reliability and accuracy. Archangel Payroll delivers both. The compliance features ensure we're always up-to-date."</p>
              <div class="testimonial-author">
                <div class="author-avatar">MM</div>
                <div class="author-info">
                  <div class="author-name">Margret Mwale</div>
                  <div class="author-title">Managing Director, Castlebet</div>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"We switched to Archangel Payroll last year and haven't looked back. The platform scales perfectly with our growing team across Africa."</p>
              <div class="testimonial-author">
                <div class="author-avatar">AZ</div>
                <div class="author-info">
                  <div class="author-name">Andrew Zulu</div>
                  <div class="author-title">Founder, Zambezi Solutions</div>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"The onboarding process was smooth and the support team was incredible. We had our entire 200-person company migrated within a week."</p>
              <div class="testimonial-author">
                <div class="author-avatar">CB</div>
                <div class="author-info">
                  <div class="author-name">Chanda Banda</div>
                  <div class="author-title">CFO, Copper Ridge Mining</div>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"Real-time payroll processing and instant payslips have saved our payroll team countless hours every month. Our employees love the self-service portal."</p>
              <div class="testimonial-author">
                <div class="author-avatar">TM</div>
                <div class="author-info">
                  <div class="author-name">Tendai Moyo</div>
                  <div class="author-title">Operations Manager, Savanna Logistics</div>
                </div>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
              <p class="testimonial-text">"The task management and team collaboration tools are a huge bonus. We weren't expecting it, but now our whole company runs on Archangel Payroll."</p>
              <div class="testimonial-author">
                <div class="author-avatar">KP</div>
                <div class="author-info">
                  <div class="author-name">Kunda Phiri</div>
                  <div class="author-title">CEO, Lusakatech</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats bar -->
          <div class="stats-bar">
            <div class="stat-item">
              <div class="stat-number">500+</div>
              <div class="stat-label">Companies</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-number">50,000+</div>
              <div class="stat-label">Employees Managed</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-number">99.9%</div>
              <div class="stat-label">Satisfaction Rate</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-number">15+</div>
              <div class="stat-label">Countries</div>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ Section -->
      <section class="faq">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Everything you need to know about Archangel Payroll</p>
          </div>
          <div class="faq-grid">
            <div class="faq-item" v-for="(faq, index) in faqs" :key="index">
              <div class="faq-question" @click="toggleFaq(index)">
                <h4>{{ faq.question }}</h4>
                <span class="faq-icon">{{ activeFaq === index ? '−' : '+' }}</span>
              </div>
              <div class="faq-answer" :class="{ active: activeFaq === index }">
                <p>{{ faq.answer }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <h2>Join 500+ Companies Already Thriving</h2>
            <p>Start your 14-day free trial — no credit card required</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">Start Free Trial</router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">Talk to Sales</router-link>
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

const activeFaq = ref(null)
const toggleFaq = (i) => { activeFaq.value = activeFaq.value === i ? null : i }

const faqs = [
  {
    question: 'Do all plans include all features?',
    answer: 'Yes! All plans include access to every feature. You only pay for the number of employees. Higher plans include priority support and faster response times.'
  },
  {
    question: 'What support is included with each plan?',
    answer: 'Starter includes standard email and chat support. Professional includes priority support. Enterprise includes 24/7 premium support with dedicated account managers.'
  },
  {
    question: 'How does the team management feature work?',
    answer: 'Our team management system works like Trello and Jira, offering Kanban boards, ticket systems, task tracking, and collaboration tools all built in.'
  },
  {
    question: 'How long does it take to set up?',
    answer: 'Most companies are up and running within 24–48 hours. Our onboarding team will guide you through the entire process at no extra charge.'
  },
  {
    question: 'Can I import my existing employee data?',
    answer: 'Yes! We support bulk import from Excel, CSV, and can integrate with most HR systems. Our team can assist with the migration.'
  },
  {
    question: 'Is my data secure?',
    answer: 'Absolutely. We use bank-level 256-bit encryption, are SOC 2 compliant, and perform daily backups. Your data is always protected.'
  },
  {
    question: 'Can I switch plans later?',
    answer: 'Yes, you can upgrade or downgrade your plan at any time. Changes take effect on your next billing cycle and all your data is preserved.'
  },
  {
    question: 'Do you support multi-country payroll?',
    answer: 'Yes! We support payroll in 50+ countries with local tax compliance, multi-currency payments, and country-specific labor law compliance built in.'
  }
]
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
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.25rem); opacity: 0.95; max-width: 600px; margin: 0 auto; }

/* ── Testimonials ── */
.testimonials { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
  margin-bottom: clamp(3rem, 6vw, 5rem);
}

/* ── Stats Bar ── */
.stats-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: clamp(2rem, 4vw, 3rem);
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 1.5rem;
  color: white;
  text-align: center;
}
.stat-item   { min-width: 120px; }
.stat-number { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; line-height: 1; margin-bottom: 0.5rem; }
.stat-label  { font-size: 0.9rem; opacity: 0.9; }
.stat-divider { width: 1px; height: 60px; background: rgba(255,255,255,0.3); }
@media (max-width: 600px) { .stat-divider { display: none; } }

/* ── FAQ ── */
.faq { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }
.faq-grid { max-width: 800px; margin: 0 auto; }

/* ── CTA ── */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}
.cta-content h2 { font-size: clamp(2rem, 6vw, 3rem); margin-bottom: 1rem; }
.cta-content p  { font-size: clamp(1rem, 2.5vw, 1.25rem); margin-bottom: 2rem; opacity: 0.95; }
</style>