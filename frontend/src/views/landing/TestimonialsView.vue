<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Page Hero ── -->
      <section class="page-hero">
        <div class="container">
          <!-- Heroicon: chat-bubble-left-right -->
          <div class="hero-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="52" height="52">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
            </svg>
          </div>
          <h1 class="page-title">Trusted by Leading Companies</h1>
          <p class="page-subtitle">See what our customers have to say about Archangel Payroll</p>
        </div>
      </section>

      <!-- ── Testimonials Grid ── -->
      <section class="testimonials">
        <div class="container">
          <div class="testimonials-grid">

            <div class="testimonial-card" v-for="(t, i) in testimonials" :key="i">
              <!-- Star rating using Heroicon: star (filled) -->
              <div class="testimonial-rating">
                <svg v-for="s in 5" :key="s" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                  <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                </svg>
              </div>

              <!-- Quote icon -->
              <div class="quote-icon">
                <!-- Heroicon: chat-bubble-oval-left (decorative) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
              </div>

              <p class="testimonial-text">{{ t.text }}</p>

              <div class="testimonial-author">
                <div class="author-avatar">{{ t.initials }}</div>
                <div class="author-info">
                  <div class="author-name">{{ t.name }}</div>
                  <div class="author-title">{{ t.title }}</div>
                </div>
              </div>
            </div>

          </div>

          <!-- ── Stats Bar ── -->
          <div class="stats-bar">
            <div class="stat-item">
              <!-- Heroicon: building-office-2 -->
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
              </div>
              <div class="stat-number">500+</div>
              <div class="stat-label">Companies</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <!-- Heroicon: users -->
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
              </div>
              <div class="stat-number">50,000+</div>
              <div class="stat-label">Employees Managed</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <!-- Heroicon: heart -->
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
              </div>
              <div class="stat-number">99.9%</div>
              <div class="stat-label">Satisfaction Rate</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <!-- Heroicon: globe-alt -->
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
              </div>
              <div class="stat-number">15+</div>
              <div class="stat-label">Countries</div>
            </div>
          </div>

        </div>
      </section>

      <!-- ── FAQ ── -->
      <section class="faq">
        <div class="container">
          <div class="section-header">
            <!-- Heroicon: question-mark-circle -->
            <div class="section-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36" height="36">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
              </svg>
            </div>
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Everything you need to know about Archangel Payroll</p>
          </div>

          <div class="faq-grid">
            <div class="faq-item" v-for="(faq, index) in faqs" :key="index">
              <div class="faq-question" @click="toggleFaq(index)">
                <h4>{{ faq.question }}</h4>
                <!-- Heroicon: plus / minus -->
                <span class="faq-icon-wrap" :class="{ open: activeFaq === index }">
                  <svg v-if="activeFaq !== index" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                  </svg>
                </span>
              </div>
              <div class="faq-answer" :class="{ active: activeFaq === index }">
                <p>{{ faq.answer }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA ── -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <!-- Heroicon: trophy -->
            <div class="cta-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="40" height="40">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
              </svg>
            </div>
            <h2>Join 500+ Companies Already Thriving</h2>
            <p>Start your 14-day free trial — no credit card required</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">
                <!-- Heroicon: play -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                </svg>
                Start Free Trial
              </router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">
                <!-- Heroicon: phone -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                Talk to Sales
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
import { ref } from 'vue'
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

const activeFaq = ref(null)
const toggleFaq = (i) => { activeFaq.value = activeFaq.value === i ? null : i }

const testimonials = [
  { initials: 'NN', name: 'Mr. Nawa Nawa',   title: 'HR Director, EaglePay',             text: '"Archangel Payroll has completely transformed our HR operations. The multi-country support handles our Zambian, Kenyan, and South African payroll seamlessly."' },
  { initials: 'MM', name: 'Margret Mwale',   title: 'Managing Director, Castlebet',      text: '"As Managing Director of Castlebet, I need reliability and accuracy. Archangel Payroll delivers both. The compliance features ensure we\'re always up-to-date."' },
  { initials: 'AZ', name: 'Andrew Zulu',     title: 'Founder, Zambezi Solutions',        text: '"We switched to Archangel Payroll last year and haven\'t looked back. The platform scales perfectly with our growing team across Africa."' },
  { initials: 'CB', name: 'Chanda Banda',    title: 'CFO, Copper Ridge Mining',          text: '"The onboarding process was smooth and the support team was incredible. We had our entire 200-person company migrated within a week."' },
  { initials: 'TM', name: 'Tendai Moyo',     title: 'Operations Manager, Savanna Logistics', text: '"Real-time payroll processing and instant payslips have saved our payroll team countless hours every month. Our employees love the self-service portal."' },
  { initials: 'KP', name: 'Kunda Phiri',     title: 'CEO, Lusakatech',                   text: '"The task management and team collaboration tools are a huge bonus. We weren\'t expecting it, but now our whole company runs on Archangel Payroll."' },
]

const faqs = [
  { question: 'Do all plans include all features?',         answer: 'Yes! All plans include access to every feature. You only pay for the number of employees. Higher plans include priority support and faster response times.' },
  { question: 'What support is included with each plan?',   answer: 'Starter includes standard email and chat support. Professional includes priority support. Enterprise includes 24/7 premium support with dedicated account managers.' },
  { question: 'How does the team management feature work?', answer: 'Our team management system works like Trello and Jira, offering Kanban boards, ticket systems, task tracking, and collaboration tools all built in.' },
  { question: 'How long does it take to set up?',           answer: 'Most companies are up and running within 24–48 hours. Our onboarding team will guide you through the entire process at no extra charge.' },
  { question: 'Can I import my existing employee data?',    answer: 'Yes! We support bulk import from Excel, CSV, and can integrate with most HR systems. Our team can assist with the migration.' },
  { question: 'Is my data secure?',                         answer: 'Absolutely. We use bank-level 256-bit encryption, are SOC 2 compliant, and perform daily backups. Your data is always protected.' },
  { question: 'Can I switch plans later?',                  answer: 'Yes, you can upgrade or downgrade your plan at any time. Changes take effect on your next billing cycle and all your data is preserved.' },
  { question: 'Do you support multi-country payroll?',      answer: 'Yes! We support payroll in 50+ countries with local tax compliance, multi-currency payments, and country-specific labor law compliance built in.' },
]
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

/* ── Testimonials ── */
.testimonials { padding: clamp(3rem, 8vw, 6rem) 0; background: #f0f4f8; }

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: clamp(1.25rem, 2.5vw, 1.75rem);
  margin-bottom: clamp(3rem, 6vw, 5rem);
}

/* Testimonial card */
.testimonial-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2rem);
  border-radius: 14px;
  border: 1px solid #dde3ee;
  border-top: 3px solid transparent;
  box-shadow: 0 2px 10px rgba(0, 71, 171, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: all 0.28s;
  position: relative;
}

/* Cycle top-border through logo colors */
.testimonial-card:nth-child(3n+1) { border-top-color: #0047AB; }
.testimonial-card:nth-child(3n+2) { border-top-color: #F5A623; }
.testimonial-card:nth-child(3n+3) { border-top-color: #2DB34A; }

.testimonial-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 32px rgba(0, 71, 171, 0.12);
}

/* Stars — amber/orange */
.testimonial-rating {
  display: flex;
  gap: 2px;
  color: #F5A623;
}

/* Subtle quote icon */
.quote-icon {
  color: rgba(0, 71, 171, 0.18);
}

.testimonial-text {
  color: #374151;
  font-size: 0.95rem;
  line-height: 1.82;
  font-style: italic;
  flex-grow: 1;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

/* Avatar — cycles through logo colors */
.author-avatar {
  width: 46px; height: 46px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800;
  font-size: 0.85rem;
  flex-shrink: 0;
  letter-spacing: 0.03em;
}

.testimonial-card:nth-child(3n+1) .author-avatar { background: rgba(0, 71, 171, 0.12); color: #0047AB; }
.testimonial-card:nth-child(3n+2) .author-avatar { background: rgba(245, 166, 35, 0.14); color: #c47f00; }
.testimonial-card:nth-child(3n+3) .author-avatar { background: rgba(45, 179, 74, 0.12); color: #2DB34A; }

.author-name  { font-weight: 700; color: #001a5e; font-size: 0.92rem; }
.author-title { font-size: 0.8rem; color: #9ca3af; margin-top: 0.15rem; }

/* ── Stats Bar ── */
.stats-bar {
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 70% 60% at 90% 100%, rgba(245, 166, 35, 0.18) 0%, transparent 55%),
    linear-gradient(148deg, #001a5e 0%, #0047AB 55%, #001240 100%);
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

.stat-item { min-width: 130px; }

.stat-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 0.65rem;
  color: #F5A623;
}

.stat-number {
  font-size: clamp(1.9rem, 5vw, 2.75rem);
  font-weight: 900;
  line-height: 1;
  margin-bottom: 0.4rem;
  letter-spacing: -0.02em;
}

.stat-label { font-size: 0.85rem; color: rgba(255,255,255,0.65); font-weight: 300; }

.stat-divider {
  width: 1px; height: 70px;
  background: rgba(74, 144, 217, 0.3);
}

@media (max-width: 600px) { .stat-divider { display: none; } }

/* ── FAQ ── */
.faq { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }

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
  max-width: 500px;
  margin: 0 auto;
  font-weight: 300;
  line-height: 1.7;
}

.faq-grid { max-width: 780px; margin: 0 auto; display: flex; flex-direction: column; gap: 0.6rem; }

.faq-item {
  border: 1.5px solid #dde3ee;
  border-radius: 12px;
  overflow: hidden;
  background: white;
  transition: all 0.22s;
}

.faq-item:hover { border-color: #0047AB; box-shadow: 0 2px 12px rgba(0, 71, 171, 0.08); }

.faq-question {
  padding: 1.25rem 1.5rem;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  transition: background 0.22s;
  user-select: none;
}

.faq-question:hover { background: rgba(0, 71, 171, 0.04); }

.faq-question h4 {
  margin: 0;
  font-size: 0.97rem;
  color: #001a5e;
  line-height: 1.5;
  font-weight: 700;
}

.faq-icon-wrap {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: rgba(0, 71, 171, 0.08);
  color: #0047AB;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all 0.24s;
}

.faq-icon-wrap.open {
  background: #0047AB;
  color: white;
}

.faq-answer {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.38s ease, padding 0.38s ease;
  background: #f8fafc;
}

.faq-answer.active { max-height: 300px; padding: 1.25rem 1.5rem; }

.faq-answer p {
  margin: 0;
  color: #4b5563;
  line-height: 1.82;
  font-size: 0.93rem;
  font-weight: 300;
}

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
  color: rgba(255,255,255,0.7);
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

.btn-primary {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important; border: none;
  padding: 0.9rem 2rem; border-radius: 9999px;
  font-weight: 700; font-size: 0.95rem;
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
  border: 2px solid rgba(255,255,255,0.5);
  padding: 0.9rem 2rem; border-radius: 9999px;
  font-weight: 600; font-size: 0.95rem;
  cursor: pointer; transition: all 0.24s;
  text-decoration: none; white-space: nowrap;
}

.btn-secondary:hover {
  background: rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.9);
  transform: translateY(-2px);
}

.btn-lg { padding: 1rem 2.2rem; font-size: 1rem; }
</style>