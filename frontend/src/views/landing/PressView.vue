<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Hero ── -->
      <section class="page-hero">
        <div class="container">
          <div class="hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
            </svg>
            Press & Media
          </div>
          <h1 class="page-title">Archangel Payroll in the News</h1>
          <p class="page-subtitle">Read our latest press releases, company announcements, and media coverage. For press inquiries, contact our media team.</p>
          <router-link to="/contact" class="hero-contact-btn">Media Inquiries →</router-link>
        </div>
      </section>

      <!-- ── Stats ── -->
      <section class="stats-section">
        <div class="container">
          <div class="stats-row">
            <div class="stat-card">
              <div class="stat-number">500+</div>
              <div class="stat-label">Companies Served</div>
            </div>
            <div class="stat-card">
              <div class="stat-number">$2B+</div>
              <div class="stat-label">Payroll Processed</div>
            </div>
            <div class="stat-card">
              <div class="stat-number">2019</div>
              <div class="stat-label">Founded</div>
            </div>
            <div class="stat-card">
              <div class="stat-number">12+</div>
              <div class="stat-label">Countries</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Press Releases ── -->
      <section class="press-section">
        <div class="container">
          <div class="press-layout">

            <div class="press-main">
              <h2 class="section-title">Press Releases</h2>

              <div class="press-list">
                <article class="press-item" v-for="release in pressReleases" :key="release.id">
                  <div class="press-date-col">
                    <div class="press-month">{{ release.month }}</div>
                    <div class="press-day">{{ release.day }}</div>
                    <div class="press-year">{{ release.year }}</div>
                  </div>
                  <div class="press-content">
                    <div class="press-tag">{{ release.tag }}</div>
                    <h3 class="press-title">{{ release.title }}</h3>
                    <p class="press-excerpt">{{ release.excerpt }}</p>
                    <router-link to="/contact" class="press-link">Read Full Release →</router-link>
                  </div>
                </article>
              </div>
            </div>

            <div class="press-sidebar">
              <!-- Media Coverage -->
              <div class="sidebar-card">
                <h3 class="sidebar-title">Media Coverage</h3>
                <div class="coverage-list">
                  <div class="coverage-item" v-for="item in mediaCoverage" :key="item.id">
                    <div class="coverage-outlet">{{ item.outlet }}</div>
                    <div class="coverage-headline">{{ item.headline }}</div>
                    <div class="coverage-date">{{ item.date }}</div>
                  </div>
                </div>
              </div>

              <!-- Press Kit -->
              <div class="sidebar-card press-kit-card">
                <div class="kit-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                  </svg>
                </div>
                <h3>Press Kit</h3>
                <p>Download logos, brand assets, executive headshots, and company fact sheets.</p>
                <router-link to="/contact" class="kit-btn">Download Press Kit</router-link>
              </div>

              <!-- Contact -->
              <div class="sidebar-card">
                <h3 class="sidebar-title">Media Contact</h3>
                <div class="contact-info">
                  <div class="contact-name">Natalie Owens</div>
                  <div class="contact-role">Head of Communications</div>
                  <a href="mailto:press@archangelpayroll.com" class="contact-email">press@archangelpayroll.com</a>
                </div>
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
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

const pressReleases = [
  { id: 1, month: 'MAR', day: '01', year: '2026', tag: 'Product Launch', title: 'Archangel Payroll Launches AI-Powered Compliance Engine', excerpt: 'New AI engine automatically monitors regulatory changes across all 50 states and updates payroll configurations in real time, eliminating manual compliance work.' },
  { id: 2, month: 'JAN', day: '15', year: '2026', tag: 'Funding', title: 'Archangel Payroll Raises $45M Series B to Accelerate International Expansion', excerpt: 'Led by Vertex Ventures, the round will fund expansion into the EU and APAC markets, growing the team from 120 to 250 employees globally.' },
  { id: 3, month: 'NOV', day: '08', year: '2025', tag: 'Partnership', title: 'Strategic Partnership with QuickBooks Brings Seamless Accounting Integration', excerpt: 'The new integration allows mutual customers to sync payroll data, journal entries, and tax filings automatically between both platforms.' },
  { id: 4, month: 'SEP', day: '22', year: '2025', tag: 'Milestone', title: 'Archangel Payroll Processes Its 10 Millionth Payroll Run', excerpt: 'The milestone marks five years of growth, now serving over 500 companies and processing more than $2B in payroll annually.' },
]

const mediaCoverage = [
  { id: 1, outlet: 'TechCrunch', headline: '"The Startup Fixing America\'s Payroll Problem"', date: 'Feb 2026' },
  { id: 2, outlet: 'Forbes', headline: '"25 HR Tech Startups to Watch in 2026"', date: 'Jan 2026' },
  { id: 3, outlet: 'HR Dive', headline: '"Archangel\'s Compliance Engine Is a Game Changer"', date: 'Dec 2025' },
  { id: 4, outlet: 'Inc. Magazine', headline: '"Built Different: Payroll That Actually Works"', date: 'Oct 2025' },
]
</script>

<style scoped>
.container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 clamp(1rem, 4vw, 2rem); }

.page-hero {
  padding-top: calc(clamp(3rem, 8vw, 5rem) + 72px);
  padding-bottom: clamp(2rem, 5vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white; text-align: center;
}

.hero-badge {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: rgba(245, 166, 35, 0.18); border: 1px solid rgba(245, 166, 35, 0.4);
  color: #F5A623; padding: 0.45rem 1rem; border-radius: 9999px;
  font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;
}
.page-title { font-size: clamp(2rem, 6vw, 3.2rem); font-weight: 800; margin-bottom: 1.25rem; line-height: 1.15; letter-spacing: -0.02em; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.2rem); opacity: 0.88; line-height: 1.7; font-weight: 300; max-width: 600px; margin: 0 auto 2rem; }
.hero-contact-btn { display: inline-flex; align-items: center; gap: 0.35rem; color: #F5A623; font-weight: 700; text-decoration: none; font-size: 1rem; border-bottom: 1px solid rgba(245,166,35,0.4); padding-bottom: 2px; transition: opacity 0.2s; }
.hero-contact-btn:hover { opacity: 0.8; }

/* Stats */
.stats-section { padding: 3rem 0; background: white; border-bottom: 1px solid #e5e7eb; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.5rem; }
.stat-card { text-align: center; padding: 1rem; }
.stat-number { font-size: clamp(2rem, 5vw, 2.8rem); font-weight: 800; color: #0047AB; margin-bottom: 0.25rem; }
.stat-label { font-size: 0.88rem; color: #6b7280; font-weight: 500; }

/* Press */
.press-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.section-title { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 800; color: #001a5e; margin-bottom: 1.75rem; letter-spacing: -0.02em; }
.press-layout { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
@media (min-width: 992px) { .press-layout { grid-template-columns: 1fr 340px; } }

.press-list { display: flex; flex-direction: column; gap: 1.25rem; }
.press-item { display: flex; gap: 1.5rem; background: white; padding: 1.5rem; border-radius: 14px; border: 1px solid #dde3ee; transition: all 0.25s; }
.press-item:hover { box-shadow: 0 8px 24px rgba(0,71,171,0.1); transform: translateY(-2px); }
.press-date-col { text-align: center; flex-shrink: 0; width: 48px; }
.press-month { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #0047AB; letter-spacing: 0.05em; }
.press-day { font-size: 1.6rem; font-weight: 800; color: #001a5e; line-height: 1; }
.press-year { font-size: 0.72rem; color: #9ca3af; }
.press-tag { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #F5A623; margin-bottom: 0.4rem; }
.press-title { font-size: 1.05rem; font-weight: 700; color: #001a5e; margin-bottom: 0.6rem; line-height: 1.4; }
.press-excerpt { font-size: 0.9rem; color: #4b5563; line-height: 1.65; margin-bottom: 1rem; }
.press-link { font-size: 0.88rem; font-weight: 700; color: #0047AB; text-decoration: none; }
.press-link:hover { opacity: 0.75; }

/* Sidebar */
.press-sidebar { display: flex; flex-direction: column; gap: 1.25rem; }
.sidebar-card { background: white; border-radius: 14px; padding: 1.5rem; border: 1px solid #dde3ee; }
.sidebar-title { font-size: 1rem; font-weight: 700; color: #001a5e; margin-bottom: 1.25rem; }
.coverage-list { display: flex; flex-direction: column; gap: 1rem; }
.coverage-item { padding-bottom: 1rem; border-bottom: 1px solid #f0f4f8; }
.coverage-item:last-child { border-bottom: none; padding-bottom: 0; }
.coverage-outlet { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #0047AB; margin-bottom: 0.25rem; }
.coverage-headline { font-size: 0.9rem; font-weight: 600; color: #1f2937; line-height: 1.4; margin-bottom: 0.25rem; }
.coverage-date { font-size: 0.78rem; color: #9ca3af; }

.press-kit-card { background: linear-gradient(135deg, #001a5e, #0047AB); color: white; }
.kit-icon { width: 52px; height: 52px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
.press-kit-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
.press-kit-card p { font-size: 0.9rem; opacity: 0.8; line-height: 1.6; margin-bottom: 1.25rem; }
.kit-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.5rem; background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4); color: white; text-decoration: none; border-radius: 9999px; font-weight: 700; font-size: 0.88rem; transition: all 0.22s; }
.kit-btn:hover { background: rgba(255,255,255,0.25); }

.contact-info { display: flex; flex-direction: column; gap: 0.25rem; }
.contact-name { font-weight: 700; color: #001a5e; font-size: 1rem; }
.contact-role { font-size: 0.88rem; color: #6b7280; }
.contact-email { font-size: 0.88rem; color: #0047AB; text-decoration: none; font-weight: 600; margin-top: 0.25rem; }
.contact-email:hover { text-decoration: underline; }
</style>