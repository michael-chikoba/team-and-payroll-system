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
            Insights & Resources
          </div>
          <h1 class="page-title">The Archangel Payroll Blog</h1>
          <p class="page-subtitle">Expert insights on payroll management, HR best practices, compliance updates, and building better teams.</p>
        </div>
      </section>

      <!-- ── Featured Post ── -->
      <section class="featured-section">
        <div class="container">
          <div class="featured-post">
            <div class="featured-label">Featured Article</div>
            <div class="featured-body">
              <div class="featured-text">
                <div class="post-category">Compliance</div>
                <h2 class="featured-title">2025 Payroll Compliance: What Every Business Needs to Know</h2>
                <p class="featured-excerpt">From updated tax brackets to new remote work regulations, we break down every compliance change that matters for your payroll in 2025 and how to prepare your team today.</p>
                <div class="post-meta">
                  <div class="author-chip">
                    <div class="author-avatar av--blue">SR</div>
                    <span>Sarah Reynolds</span>
                  </div>
                  <span class="meta-sep">·</span>
                  <span class="meta-date">March 3, 2026</span>
                  <span class="meta-sep">·</span>
                  <span class="meta-read">12 min read</span>
                </div>
                <router-link to="/contact" class="read-more-btn">Read Article →</router-link>
              </div>
              <div class="featured-visual">
                <div class="blog-image-placeholder blog-image-placeholder--large">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="56" height="56">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Posts Grid ── -->
      <section class="posts-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Latest Articles</h2>
          </div>

          <div class="category-tabs">
            <button class="cat-tab active">All</button>
            <button class="cat-tab">Compliance</button>
            <button class="cat-tab">HR & People</button>
            <button class="cat-tab">Product Updates</button>
            <button class="cat-tab">Guides</button>
          </div>

          <div class="posts-grid">

            <article class="post-card" v-for="post in posts" :key="post.id">
              <div class="post-thumb" :class="`post-thumb--${post.color}`">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32" height="32">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                </svg>
              </div>
              <div class="post-body">
                <div class="post-category">{{ post.category }}</div>
                <h3 class="post-title">{{ post.title }}</h3>
                <p class="post-excerpt">{{ post.excerpt }}</p>
                <div class="post-footer">
                  <div class="post-meta">
                    <div class="author-avatar av--sm" :class="`av--${post.color}`">{{ post.authorInitials }}</div>
                    <span class="meta-author">{{ post.author }}</span>
                    <span class="meta-sep">·</span>
                    <span class="meta-date">{{ post.date }}</span>
                  </div>
                  <router-link to="/contact" class="post-link">Read →</router-link>
                </div>
              </div>
            </article>

          </div>

          <div class="load-more-wrap">
            <button class="load-more-btn">Load More Articles</button>
          </div>
        </div>
      </section>

      <!-- ── Newsletter ── -->
      <section class="newsletter-section">
        <div class="container">
          <div class="newsletter-card">
            <div class="newsletter-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30" height="30">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
              </svg>
            </div>
            <div class="newsletter-text">
              <h3>Stay ahead of payroll changes</h3>
              <p>Get compliance updates, product news, and expert insights delivered to your inbox every week.</p>
            </div>
            <div class="newsletter-form">
              <input type="email" placeholder="Enter your work email" class="newsletter-input" />
              <button class="newsletter-btn">Subscribe</button>
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

const posts = [
  { id: 1, category: 'HR & People', title: 'How to Build a Remote-First Onboarding Process', excerpt: 'Remote employees need structure from day one. Here\'s how leading companies create onboarding that sticks.', author: 'Michael Chen', authorInitials: 'MC', date: 'Feb 24, 2026', color: 'blue' },
  { id: 2, category: 'Guides', title: 'The Complete Guide to Employee Benefits Administration', excerpt: 'From health insurance to equity compensation, managing benefits is complex. We simplify it.', author: 'Priya Sharma', authorInitials: 'PS', date: 'Feb 18, 2026', color: 'green' },
  { id: 3, category: 'Compliance', title: 'Multi-State Payroll: Navigating Tax Obligations', excerpt: 'Hiring across states? Each state has different rules. Here\'s what you need to know to stay compliant.', author: 'Sarah Reynolds', authorInitials: 'SR', date: 'Feb 10, 2026', color: 'orange' },
  { id: 4, category: 'Product Updates', title: 'New: Automated Tax Filing Now Available for All Plans', excerpt: 'We\'ve launched fully automated federal and state tax filing. Here\'s everything you need to know.', author: 'James Okafor', authorInitials: 'JO', date: 'Feb 3, 2026', color: 'blue' },
  { id: 5, category: 'HR & People', title: 'Salary Benchmarking: Paying Your Team Competitively', excerpt: 'Compensation transparency is on the rise. How to use data to stay competitive and retain top talent.', author: 'Aisha Kim', authorInitials: 'AK', date: 'Jan 27, 2026', color: 'green' },
  { id: 6, category: 'Guides', title: 'Contractor vs. Employee: Classification Made Simple', excerpt: 'Misclassification is one of the most common (and expensive) payroll mistakes. Avoid it with this guide.', author: 'Michael Chen', authorInitials: 'MC', date: 'Jan 20, 2026', color: 'orange' },
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
    radial-gradient(ellipse 80% 70% at 85% 20%, rgba(0, 71, 171, 0.45) 0%, transparent 60%),
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
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.2rem); opacity: 0.88; line-height: 1.7; font-weight: 300; max-width: 600px; margin: 0 auto; }

.section-header { text-align: center; margin-bottom: 2rem; }
.section-title { font-size: clamp(1.75rem, 4vw, 2.4rem); font-weight: 800; color: #001a5e; letter-spacing: -0.02em; }

/* Featured */
.featured-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.featured-post { background: white; border-radius: 20px; overflow: hidden; border: 1px solid #dde3ee; box-shadow: 0 4px 20px rgba(0,71,171,0.08); }
.featured-label { background: linear-gradient(135deg, #0047AB, #4A90D9); color: white; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; padding: 0.5rem 1.5rem; }
.featured-body { display: grid; grid-template-columns: 1fr; gap: 0; }
@media (min-width: 768px) { .featured-body { grid-template-columns: 1fr 380px; } }
.featured-text { padding: clamp(1.5rem, 4vw, 2.5rem); }
.featured-visual { background: linear-gradient(135deg, #001a5e 0%, #0047AB 100%); display: flex; align-items: center; justify-content: center; min-height: 220px; color: rgba(255,255,255,0.3); }
.featured-title { font-size: clamp(1.4rem, 4vw, 1.9rem); font-weight: 800; color: #001a5e; margin: 0.5rem 0 1rem; line-height: 1.3; }
.featured-excerpt { color: #4b5563; line-height: 1.7; margin-bottom: 1.5rem; }
.post-category { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #0047AB; margin-bottom: 0.5rem; }
.post-meta { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; font-size: 0.85rem; color: #6b7280; }
.author-chip { display: flex; align-items: center; gap: 0.4rem; }
.author-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; color: white; }
.av--blue   { background: linear-gradient(135deg, #0047AB, #4A90D9); }
.av--green  { background: linear-gradient(135deg, #2DB34A, #5dbe6e); }
.av--orange { background: linear-gradient(135deg, #d18b00, #F5A623); }
.av--sm { width: 22px; height: 22px; font-size: 0.6rem; }
.meta-sep { color: #d1d5db; }
.read-more-btn { display: inline-flex; align-items: center; font-weight: 700; color: #0047AB; text-decoration: none; font-size: 0.95rem; transition: gap 0.2s; }
.read-more-btn:hover { opacity: 0.8; }

/* Posts Grid */
.posts-section { padding: clamp(3rem, 7vw, 5rem) 0; background: white; }
.category-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2.5rem; }
.cat-tab { padding: 0.45rem 1.1rem; border-radius: 9999px; border: 1.5px solid #dde3ee; background: white; color: #4b5563; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.cat-tab:hover { border-color: #0047AB; color: #0047AB; }
.cat-tab.active { background: #0047AB; border-color: #0047AB; color: white; }

.posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr)); gap: 1.75rem; }
.post-card { border: 1px solid #dde3ee; border-radius: 14px; overflow: hidden; transition: all 0.28s; background: white; }
.post-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,71,171,0.1); border-color: #4A90D9; }
.post-thumb { height: 140px; display: flex; align-items: center; justify-content: center; }
.post-thumb--blue   { background: linear-gradient(135deg, rgba(0,71,171,0.08), rgba(74,144,217,0.15)); color: #4A90D9; }
.post-thumb--green  { background: linear-gradient(135deg, rgba(45,179,74,0.08), rgba(45,179,74,0.15)); color: #2DB34A; }
.post-thumb--orange { background: linear-gradient(135deg, rgba(245,166,35,0.08), rgba(245,166,35,0.15)); color: #d18b00; }
.post-body { padding: 1.25rem 1.5rem; }
.post-title { font-size: 1.05rem; font-weight: 700; color: #001a5e; margin: 0.4rem 0 0.75rem; line-height: 1.4; }
.post-excerpt { color: #4b5563; font-size: 0.9rem; line-height: 1.65; margin-bottom: 1.25rem; }
.post-footer { display: flex; align-items: center; justify-content: space-between; }
.meta-author { font-size: 0.82rem; color: #4b5563; font-weight: 500; }
.meta-date { font-size: 0.82rem; color: #9ca3af; }
.post-link { font-size: 0.88rem; font-weight: 700; color: #0047AB; text-decoration: none; }
.post-link:hover { opacity: 0.75; }

.load-more-wrap { text-align: center; margin-top: 3rem; }
.load-more-btn { padding: 0.75rem 2.5rem; border: 2px solid #0047AB; border-radius: 9999px; background: white; color: #0047AB; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.22s; }
.load-more-btn:hover { background: #0047AB; color: white; }

/* Newsletter */
.newsletter-section { padding: clamp(3rem, 6vw, 5rem) 0; background: #f0f4f8; }
.newsletter-card { background: white; border-radius: 16px; padding: clamp(1.5rem, 4vw, 2.5rem); border: 1px solid #dde3ee; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; box-shadow: 0 4px 20px rgba(0,71,171,0.07); }
.newsletter-icon { width: 56px; height: 56px; background: rgba(0,71,171,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0047AB; flex-shrink: 0; }
.newsletter-text { flex: 1; min-width: 200px; }
.newsletter-text h3 { font-size: 1.1rem; font-weight: 700; color: #001a5e; margin-bottom: 0.3rem; }
.newsletter-text p  { font-size: 0.9rem; color: #4b5563; }
.newsletter-form { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.newsletter-input { padding: 0.65rem 1rem; border: 1.5px solid #dde3ee; border-radius: 9999px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; min-width: 220px; }
.newsletter-input:focus { border-color: #0047AB; }
.newsletter-btn { padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0047AB, #4A90D9); color: white; border: none; border-radius: 9999px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: opacity 0.2s; }
.newsletter-btn:hover { opacity: 0.88; }
</style>