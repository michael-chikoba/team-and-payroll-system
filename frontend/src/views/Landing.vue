<template>
  <div class="landing-page">
    <!-- Navigation -->
    <nav class="navbar">
      <div class="container">
        <div class="nav-brand">
          <h1>Archangel Payroll</h1>
        </div>
        <div class="nav-links">
          <a href="#features" @click.prevent="scrollToSection('features')">Features</a>
          <a href="#solutions" @click.prevent="scrollToSection('solutions')">Solutions</a>
          <a href="#pricing" @click.prevent="scrollToSection('pricing')">Pricing</a>
          <a href="#testimonials" @click.prevent="scrollToSection('testimonials')">Testimonials</a>
          <a href="#contact" @click.prevent="scrollToSection('contact')">Contact</a>
          <button @click="scrollToDemo" class="btn-primary">Request Demo</button>
        </div>
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" @click="mobileMenuOpen = !mobileMenuOpen">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
      <!-- Mobile Menu -->
      <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
        <a href="#features" @click="closeMobileMenu">Features</a>
        <a href="#solutions" @click="closeMobileMenu">Solutions</a>
        <a href="#pricing" @click="closeMobileMenu">Pricing</a>
        <a href="#testimonials" @click="closeMobileMenu">Testimonials</a>
        <a href="#contact" @click="closeMobileMenu">Contact</a>
        <button @click="scrollToDemo" class="btn-primary">Request Demo</button>
      </div>
    </nav>

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
            <button @click="scrollToDemo" class="btn-primary btn-lg">
              Start Free Trial
            </button>
            <button @click="scrollToContact" class="btn-secondary btn-lg">
              Schedule a Demo
            </button>
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

    <!-- Features Section -->
    <section id="features" class="features">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Everything You Need to Manage Payroll</h2>
          <p class="section-subtitle">
            Comprehensive features designed to simplify every aspect of payroll management
          </p>
        </div>
        <div class="features-grid">
          <!-- Payroll Management -->
          <div class="feature-card">
            <div class="feature-icon">💰</div>
            <h3>Automated Payroll Processing</h3>
            <p>Process payroll automatically with accurate calculations for salaries, bonuses, allowances, and deductions. Support for multiple pay periods and payment methods.</p>
            <ul class="feature-list">
              <li>✓ Automated salary calculations</li>
              <li>✓ Tax withholding & compliance</li>
              <li>✓ Direct deposit integration</li>
              <li>✓ Payslip generation</li>
            </ul>
          </div>

          <!-- Employee Management -->
          <div class="feature-card">
            <div class="feature-icon">👥</div>
            <h3>Employee Management</h3>
            <p>Centralized employee database with comprehensive profiles, document management, and onboarding workflows.</p>
            <ul class="feature-list">
              <li>✓ Employee profiles & records</li>
              <li>✓ Document storage</li>
              <li>✓ Onboarding automation</li>
              <li>✓ Role-based access control</li>
            </ul>
          </div>

          <!-- Attendance Tracking -->
          <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h3>Time & Attendance</h3>
            <p>Track employee attendance, working hours, overtime, and absences with integrated time-tracking tools.</p>
            <ul class="feature-list">
              <li>✓ Clock-in/out tracking</li>
              <li>✓ Overtime calculations</li>
              <li>✓ Shift management</li>
              <li>✓ GPS location verification</li>
            </ul>
          </div>

          <!-- Leave Management -->
          <div class="feature-card">
            <div class="feature-icon">🏖️</div>
            <h3>Leave Management</h3>
            <p>Streamline leave requests, approvals, and balance tracking with automated workflows and notifications.</p>
            <ul class="feature-list">
              <li>✓ Leave request & approval</li>
              <li>✓ Multiple leave types</li>
              <li>✓ Balance tracking</li>
              <li>✓ Calendar integration</li>
            </ul>
          </div>

          <!-- Multi-Country Support -->
          <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <h3>Multi-Country & Currency</h3>
            <p>Manage payroll across multiple countries with support for different currencies, tax rules, and labor laws.</p>
            <ul class="feature-list">
              <li>✓ 50+ countries supported</li>
              <li>✓ Multi-currency payments</li>
              <li>✓ Local compliance</li>
              <li>✓ Exchange rate updates</li>
            </ul>
          </div>

          <!-- Reports & Analytics -->
          <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Reports & Analytics</h3>
            <p>Generate comprehensive reports with real-time insights into payroll costs, trends, and employee data.</p>
            <ul class="feature-list">
              <li>✓ Payroll reports</li>
              <li>✓ Tax reports</li>
              <li>✓ Custom dashboards</li>
              <li>✓ Export to Excel/PDF</li>
            </ul>
          </div>

          <!-- Tax Compliance -->
          <div class="feature-card">
            <div class="feature-icon">📋</div>
            <h3>Tax Compliance</h3>
            <p>Automated tax calculations, filing, and compliance with up-to-date regulations and requirements.</p>
            <ul class="feature-list">
              <li>✓ Automated tax calculations</li>
              <li>✓ Tax filing assistance</li>
              <li>✓ Compliance alerts</li>
              <li>✓ Audit trails</li>
            </ul>
          </div>

          <!-- Integration -->
          <div class="feature-card">
            <div class="feature-icon">🔗</div>
            <h3>Integrations</h3>
            <p>Seamlessly connect with your existing tools including accounting software, HR systems, and banks.</p>
            <ul class="feature-list">
              <li>✓ Accounting software</li>
              <li>✓ Banking systems</li>
              <li>✓ HR platforms</li>
              <li>✓ API access</li>
            </ul>
          </div>

          <!-- Security -->
          <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Enterprise Security</h3>
            <p>Bank-level security with encryption, regular backups, and compliance with international standards.</p>
            <ul class="feature-list">
              <li>✓ 256-bit encryption</li>
              <li>✓ Two-factor authentication</li>
              <li>✓ SOC 2 compliant</li>
              <li>✓ Daily backups</li>
            </ul>
          </div>

          <!-- Mobile Access -->
          <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>Mobile Access</h3>
            <p>Full-featured mobile apps for iOS and Android, allowing employees and managers to access payroll on the go.</p>
            <ul class="feature-list">
              <li>✓ iOS & Android apps</li>
              <li>✓ Employee self-service</li>
              <li>✓ Push notifications</li>
              <li>✓ Offline access</li>
            </ul>
          </div>

          <!-- Notifications -->
          <div class="feature-card">
            <div class="feature-icon">🔔</div>
            <h3>Smart Notifications</h3>
            <p>Automated notifications for payroll processing, leave approvals, and important deadlines via email, SMS, and Slack.</p>
            <ul class="feature-list">
              <li>✓ Email notifications</li>
              <li>✓ SMS alerts</li>
              <li>✓ Slack integration</li>
              <li>✓ Custom triggers</li>
            </ul>
          </div>

          <!-- Business Management -->
          <div class="feature-card">
            <div class="feature-icon">🏢</div>
            <h3>Multi-Business Support</h3>
            <p>Manage multiple businesses or entities from a single account with separate payroll configurations.</p>
            <ul class="feature-list">
              <li>✓ Multiple entities</li>
              <li>✓ Separate configurations</li>
              <li>✓ Consolidated reporting</li>
              <li>✓ Business switching</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="solutions">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Solutions for Every Business</h2>
          <p class="section-subtitle">
            Whether you're a startup or enterprise, we have the right solution
          </p>
        </div>
        <div class="solutions-grid">
          <div class="solution-card">
            <div class="solution-icon">🚀</div>
            <h3>For Startups</h3>
            <p>Simple, affordable payroll for growing teams</p>
            <ul>
              <li>1-50 employees</li>
              <li>Basic payroll processing</li>
              <li>Employee self-service</li>
              <li>Email support</li>
            </ul>
            <button class="btn-outline">Learn More</button>
          </div>

          <div class="solution-card featured">
            <div class="featured-badge">Most Popular</div>
            <div class="solution-icon">🏢</div>
            <h3>For SMBs</h3>
            <p>Complete payroll and HR for medium businesses</p>
            <ul>
              <li>50-500 employees</li>
              <li>Advanced features</li>
              <li>Multi-location support</li>
              <li>Priority support</li>
            </ul>
            <button class="btn-primary">Learn More</button>
          </div>

          <div class="solution-card">
            <div class="solution-icon">🌟</div>
            <h3>For Enterprise</h3>
            <p>Scalable payroll for large organizations</p>
            <ul>
              <li>500+ employees</li>
              <li>Custom workflows</li>
              <li>Dedicated account manager</li>
              <li>24/7 support</li>
            </ul>
            <button class="btn-outline">Learn More</button>
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
              <h3>Save Time & Money</h3>
              <p>Reduce payroll processing time by up to 70% and eliminate costly errors with automation.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">02</div>
            <div class="benefit-content">
              <h3>Stay Compliant</h3>
              <p>Automatic updates to tax rules and labor regulations ensure you're always compliant.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">03</div>
            <div class="benefit-content">
              <h3>Empower Employees</h3>
              <p>Self-service portal allows employees to view payslips, request leave, and update information.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">04</div>
            <div class="benefit-content">
              <h3>Scale Effortlessly</h3>
              <p>Grow your business without worrying about payroll complexity or capacity limitations.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Simple, Transparent Pricing</h2>
          <p class="section-subtitle">No hidden fees. Cancel anytime.</p>
        </div>
        <div class="pricing-grid">
          <div class="pricing-card">
            <div class="pricing-header">
              <h3>Starter</h3>
              <div class="pricing-amount">
                <span class="currency">$</span>
                <span class="amount">29</span>
                <span class="period">/month</span>
              </div>
              <p>Perfect for small teams</p>
            </div>
            <ul class="pricing-features">
              <li>✓ Up to 10 employees</li>
              <li>✓ Automated payroll</li>
              <li>✓ Employee self-service</li>
              <li>✓ Basic reports</li>
              <li>✓ Email support</li>
            </ul>
            <button class="btn-outline btn-block">Start Free Trial</button>
          </div>

          <div class="pricing-card featured">
            <div class="featured-badge">Most Popular</div>
            <div class="pricing-header">
              <h3>Professional</h3>
              <div class="pricing-amount">
                <span class="currency">$</span>
                <span class="amount">79</span>
                <span class="period">/month</span>
              </div>
              <p>For growing businesses</p>
            </div>
            <ul class="pricing-features">
              <li>✓ Up to 100 employees</li>
              <li>✓ All Starter features</li>
              <li>✓ Time & attendance</li>
              <li>✓ Leave management</li>
              <li>✓ Advanced reports</li>
              <li>✓ Priority support</li>
            </ul>
            <button class="btn-primary btn-block">Start Free Trial</button>
          </div>

          <div class="pricing-card">
            <div class="pricing-header">
              <h3>Enterprise</h3>
              <div class="pricing-amount">
                <span class="amount">Custom</span>
              </div>
              <p>For large organizations</p>
            </div>
            <ul class="pricing-features">
              <li>✓ Unlimited employees</li>
              <li>✓ All Professional features</li>
              <li>✓ Multi-country support</li>
              <li>✓ Custom integrations</li>
              <li>✓ Dedicated account manager</li>
              <li>✓ 24/7 support</li>
            </ul>
            <button class="btn-outline btn-block">Contact Sales</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Trusted by Leading Companies</h2>
          <p class="section-subtitle">See what our customers have to say</p>
        </div>
        <div class="testimonials-grid">
          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">
              "Archangel Payroll has transformed how we manage payroll. What used to take days now takes hours. The automation and accuracy are incredible."
            </p>
            <div class="testimonial-author">
              <div class="author-avatar">JD</div>
              <div class="author-info">
                <div class="author-name">Jane Doe</div>
                <div class="author-title">HR Director, TechCorp</div>
              </div>
            </div>
          </div>

          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">
              "The multi-country support is a game-changer for our global team. We can now manage payroll for all our offices from one platform."
            </p>
            <div class="testimonial-author">
              <div class="author-avatar">MS</div>
              <div class="author-info">
                <div class="author-name">Michael Smith</div>
                <div class="author-title">CFO, Global Solutions Inc</div>
              </div>
            </div>
          </div>

          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">
              "Outstanding customer support and incredibly intuitive interface. Our employees love the self-service portal."
            </p>
            <div class="testimonial-author">
              <div class="author-avatar">SK</div>
              <div class="author-info">
                <div class="author-name">Sarah Kim</div>
                <div class="author-title">Operations Manager, StartupXYZ</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Demo Request Section -->
    <section id="demo" class="demo-section">
      <div class="container">
        <div class="demo-content">
          <h2>See Archangel Payroll in Action</h2>
          <p>Request a personalized demo and discover how we can transform your payroll process</p>
        </div>
        <div class="demo-form-wrapper">
          <form @submit.prevent="submitDemoRequest" class="demo-form">
            <div class="form-group">
              <label for="company_name">Company Name *</label>
              <input
                id="company_name"
                v-model="demoForm.company_name"
                type="text"
                required
                placeholder="Your company name"
              />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="first_name">First Name *</label>
                <input
                  id="first_name"
                  v-model="demoForm.first_name"
                  type="text"
                  required
                  placeholder="John"
                />
              </div>
              <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input
                  id="last_name"
                  v-model="demoForm.last_name"
                  type="text"
                  required
                  placeholder="Doe"
                />
              </div>
            </div>
            <div class="form-group">
              <label for="email">Business Email *</label>
              <input
                id="email"
                v-model="demoForm.email"
                type="email"
                required
                placeholder="john@company.com"
              />
            </div>
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <input
                id="phone"
                v-model="demoForm.phone"
                type="tel"
                required
                placeholder="+1 (555) 000-0000"
              />
            </div>
            <div class="form-group">
              <label for="employee_count">Number of Employees</label>
              <select id="employee_count" v-model="demoForm.employee_count">
                <option value="">Select range</option>
                <option value="1-10">1-10</option>
                <option value="11-50">11-50</option>
                <option value="51-200">51-200</option>
                <option value="201-500">201-500</option>
                <option value="500+">500+</option>
              </select>
            </div>
            <div class="form-group">
              <label for="message">Tell us about your needs</label>
              <textarea
                id="message"
                v-model="demoForm.message"
                rows="4"
                placeholder="What are your payroll challenges?"
              ></textarea>
            </div>
            <button type="submit" class="btn-primary btn-lg btn-block" :disabled="isSubmitting">
              {{ isSubmitting ? 'Submitting...' : 'Request Demo' }}
            </button>
            <p v-if="demoMessage" :class="['form-message', demoMessageType]">
              {{ demoMessage }}
            </p>
          </form>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Get in Touch</h2>
          <p class="section-subtitle">We're here to help with any questions</p>
        </div>
        <div class="contact-grid">
          <div class="contact-info">
            <div class="contact-card">
              <div class="contact-card-icon">📧</div>
              <h3>Email Us</h3>
              <p>sales@archangelpayroll.com</p>
              <p>support@archangelpayroll.com</p>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">📞</div>
              <h3>Call Us</h3>
              <p>Sales: +1 (555) 123-4567</p>
              <p>Support: +1 (555) 123-4568</p>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">🕒</div>
              <h3>Business Hours</h3>
              <p>Monday - Friday</p>
              <p>9:00 AM - 6:00 PM EST</p>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">💬</div>
              <h3>Live Chat</h3>
              <p>Available 24/7</p>
              <button class="btn-outline btn-sm">Start Chat</button>
            </div>
          </div>

          <div class="contact-form-wrapper">
            <form @submit.prevent="submitContactForm" class="contact-form">
              <div class="form-group">
                <label for="contact_name">Name *</label>
                <input
                  id="contact_name"
                  v-model="contactForm.name"
                  type="text"
                  required
                  placeholder="Your name"
                />
              </div>
              <div class="form-group">
                <label for="contact_email">Email *</label>
                <input
                  id="contact_email"
                  v-model="contactForm.email"
                  type="email"
                  required
                  placeholder="your@email.com"
                />
              </div>
              <div class="form-group">
                <label for="contact_subject">Subject *</label>
                <input
                  id="contact_subject"
                  v-model="contactForm.subject"
                  type="text"
                  required
                  placeholder="How can we help?"
                />
              </div>
              <div class="form-group">
                <label for="contact_message">Message *</label>
                <textarea
                  id="contact_message"
                  v-model="contactForm.message"
                  rows="5"
                  required
                  placeholder="Your message..."
                ></textarea>
              </div>
              <button type="submit" class="btn-primary btn-lg btn-block" :disabled="isContactSubmitting">
                {{ isContactSubmitting ? 'Sending...' : 'Send Message' }}
              </button>
              <p v-if="contactMessage" :class="['form-message', contactMessageType]">
                {{ contactMessage }}
              </p>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Frequently Asked Questions</h2>
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

    <!-- CTA Section -->
    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>Ready to Transform Your Payroll?</h2>
          <p>Join thousands of companies that trust Archangel Payroll</p>
          <div class="cta-actions">
            <button @click="scrollToDemo" class="btn-primary btn-lg">
              Start Free Trial
            </button>
            <button @click="scrollToContact" class="btn-secondary btn-lg">
              Contact Sales
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-section">
            <h3>Archangel Payroll</h3>
            <p>Modern payroll management for modern businesses. Simplify your payroll, empower your team.</p>
            <div class="social-links">
              <a href="#" aria-label="Twitter">𝕏</a>
              <a href="#" aria-label="LinkedIn">in</a>
              <a href="#" aria-label="Facebook">f</a>
            </div>
          </div>
          <div class="footer-section">
            <h4>Product</h4>
            <ul>
              <li><a href="#features">Features</a></li>
              <li><a href="#solutions">Solutions</a></li>
              <li><a href="#pricing">Pricing</a></li>
              <li><a href="#demo">Request Demo</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Company</h4>
            <ul>
              <li><a href="#">About Us</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Blog</a></li>
              <li><a href="#">Press</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Resources</h4>
            <ul>
              <li><a href="#">Documentation</a></li>
              <li><a href="#">API Reference</a></li>
              <li><a href="#">Help Center</a></li>
              <li><a href="#">Community</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Legal</h4>
            <ul>
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Terms of Service</a></li>
              <li><a href="#">Security</a></li>
              <li><a href="#">Compliance</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; {{ new Date().getFullYear() }} Archangel Payroll. All rights reserved.</p>
          <div class="footer-badges">
            <span class="badge">🔒 SOC 2 Certified</span>
            <span class="badge">✓ GDPR Compliant</span>
            <span class="badge">⭐ 99.9% Uptime</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const mobileMenuOpen = ref(false)
const activeFaq = ref(null)

const demoForm = ref({
  company_name: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  employee_count: '',
  message: ''
})

const contactForm = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const isSubmitting = ref(false)
const demoMessage = ref('')
const demoMessageType = ref('')

const isContactSubmitting = ref(false)
const contactMessage = ref('')
const contactMessageType = ref('')

const faqs = [
  {
    question: 'How long does it take to set up?',
    answer: 'Most companies are up and running within 24-48 hours. Our onboarding team will guide you through the entire process.'
  },
  {
    question: 'Can I import my existing employee data?',
    answer: 'Yes! We support bulk import from Excel, CSV, and can integrate with most HR systems to transfer your data seamlessly.'
  },
  {
    question: 'What payment methods do you support?',
    answer: 'We support direct deposit, checks, and various payment methods depending on your country and banking preferences.'
  },
  {
    question: 'Is my data secure?',
    answer: 'Absolutely. We use bank-level 256-bit encryption, are SOC 2 compliant, and perform daily backups. Your data is stored in secure, geo-redundant data centers.'
  },
  {
    question: 'Do you offer customer support?',
    answer: 'Yes! We offer email support for all plans, priority support for Professional plans, and 24/7 dedicated support for Enterprise customers.'
  },
  {
    question: 'Can I cancel anytime?',
    answer: 'Yes, you can cancel your subscription at any time with no penalties. We also offer a 14-day free trial with no credit card required.'
  }
]

const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    closeMobileMenu()
  }
}

const scrollToDemo = () => {
  scrollToSection('demo')
}

const scrollToContact = () => {
  scrollToSection('contact')
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const toggleFaq = (index) => {
  activeFaq.value = activeFaq.value === index ? null : index
}

const submitDemoRequest = async () => {
  isSubmitting.value = true
  demoMessage.value = ''
  
  try {
    await axios.post('/api/demo-requests', demoForm.value)
    demoMessage.value = 'Thank you! We\'ll contact you shortly to schedule your demo.'
    demoMessageType.value = 'success'
    
    // Reset form
    demoForm.value = {
      company_name: '',
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      employee_count: '',
      message: ''
    }
  } catch (error) {
    demoMessage.value = 'Something went wrong. Please try again or contact us directly.'
    demoMessageType.value = 'error'
    console.error('Demo request error:', error)
  } finally {
    isSubmitting.value = false
  }
}

const submitContactForm = async () => {
  isContactSubmitting.value = true
  contactMessage.value = ''
  
  try {
    await axios.post('/api/contact', contactForm.value)
    contactMessage.value = 'Message sent successfully! We\'ll get back to you soon.'
    contactMessageType.value = 'success'
    
    // Reset form
    contactForm.value = {
      name: '',
      email: '',
      subject: '',
      message: ''
    }
  } catch (error) {
    contactMessage.value = 'Failed to send message. Please try again or email us directly.'
    contactMessageType.value = 'error'
    console.error('Contact form error:', error)
  } finally {
    isContactSubmitting.value = false
  }
}
</script>

<style scoped>
/* General Styles */
.landing-page {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  color: #1a1a1a;
  line-height: 1.6;
  overflow-x: hidden;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

.section-subtitle {
  font-size: 1.25rem;
  color: #666;
  max-width: 600px;
  margin: 0 auto;
}

/* Navbar */
.navbar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 1000;
  padding: 1rem 0;
}

.navbar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-brand h1 {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
}

.nav-links {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.nav-links a {
  text-decoration: none;
  color: #666;
  font-weight: 500;
  transition: color 0.3s;
  position: relative;
}

.nav-links a::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 0;
  height: 2px;
  background: #667eea;
  transition: width 0.3s;
}

.nav-links a:hover {
  color: #667eea;
}

.nav-links a:hover::after {
  width: 100%;
}

.mobile-menu-btn {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}

.mobile-menu-btn span {
  width: 24px;
  height: 2px;
  background: #333;
  transition: all 0.3s;
}

.mobile-menu {
  display: none;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  background: #94a3b8;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-secondary:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

.btn-outline {
  background: transparent;
  color: #667eea;
  border: 2px solid #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-outline:hover {
  background: #667eea;
  color: white;
}

.btn-lg {
  padding: 1rem 2rem;
  font-size: 1.1rem;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.btn-block {
  width: 100%;
}

/* Hero Section */
.hero {
  padding: 6rem 0 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
  opacity: 0.3;
}

.hero .container {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.hero-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  backdrop-filter: blur(10px);
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 1.35rem;
  margin-bottom: 2rem;
  opacity: 0.95;
  line-height: 1.6;
}

.hero-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.hero-trust {
  font-size: 0.9rem;
  opacity: 0.8;
}

.dashboard-preview {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
  color: #333;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.preview-dots {
  display: flex;
  gap: 6px;
}

.preview-dots span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #d1d5db;
}

.preview-title {
  font-weight: 600;
  color: #1a1a1a;
}

.preview-content {
  display: grid;
  gap: 1rem;
}

.preview-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.card-icon {
  font-size: 2rem;
}

.card-label {
  font-size: 0.85rem;
  color: #666;
}

.card-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
}

/* Features Section */
.features {
  padding: 6rem 0;
  background: #f9fafb;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
}

.feature-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
  border: 1px solid #e5e7eb;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.feature-card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

.feature-card > p {
  color: #666;
  margin-bottom: 1.5rem;
  line-height: 1.7;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.feature-list li {
  color: #555;
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
  position: relative;
  line-height: 1.6;
}

.feature-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #10b981;
  font-weight: bold;
}

/* Solutions Section */
.solutions {
  padding: 6rem 0;
  background: white;
}

.solutions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.solution-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  text-align: center;
  transition: all 0.3s;
  position: relative;
}

.solution-card:hover {
  transform: translateY(-5px);
  border-color: #667eea;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.solution-card.featured {
  border-color: #667eea;
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.2);
}

.featured-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
}

.solution-icon {
  font-size: 3.5rem;
  margin-bottom: 1rem;
}

.solution-card h3 {
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
  color: #1a1a1a;
}

.solution-card > p {
  color: #666;
  margin-bottom: 2rem;
}

.solution-card ul {
  list-style: none;
  padding: 0;
  margin-bottom: 2rem;
  text-align: left;
}

.solution-card li {
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
  color: #555;
}

.solution-card li:last-child {
  border-bottom: none;
}

/* Benefits Section */
.benefits {
  padding: 6rem 0;
  background: white;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 3rem;
}

.benefit-item {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.benefit-number {
  font-size: 3.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  flex-shrink: 0;
}

.benefit-content h3 {
  font-size: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1a1a1a;
}

.benefit-content p {
  color: #666;
  line-height: 1.7;
}

/* Pricing Section */
.pricing {
  padding: 6rem 0;
  background: #f9fafb;
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.pricing-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  transition: all 0.3s;
  position: relative;
}

.pricing-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.pricing-card.featured {
  border-color: #667eea;
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.2);
}

.pricing-header {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.pricing-header h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

.pricing-amount {
  display: flex;
  align-items: baseline;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.currency {
  font-size: 1.5rem;
  font-weight: 600;
  color: #666;
}

.amount {
  font-size: 4rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.period {
  font-size: 1rem;
  color: #666;
  margin-left: 0.5rem;
}

.pricing-header p {
  color: #666;
}

.pricing-features {
  list-style: none;
  padding: 0;
  margin-bottom: 2rem;
}

.pricing-features li {
  padding: 0.75rem 0;
  color: #555;
  border-bottom: 1px solid #f3f4f6;
}

.pricing-features li:last-child {
  border-bottom: none;
}

/* Testimonials Section */
.testimonials {
  padding: 6rem 0;
  background: white;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2rem;
}

.testimonial-card {
  background: #f9fafb;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.testimonial-rating {
  font-size: 1.25rem;
  margin-bottom: 1rem;
}

.testimonial-text {
  color: #333;
  font-size: 1.1rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
  font-style: italic;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.author-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  flex-shrink: 0;
}

.author-name {
  font-weight: 600;
  color: #1a1a1a;
}

.author-title {
  font-size: 0.9rem;
  color: #666;
}

/* Demo Section */
.demo-section {
  padding: 6rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.demo-content {
  text-align: center;
  margin-bottom: 3rem;
}

.demo-content h2 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.demo-form-wrapper {
  max-width: 700px;
  margin: 0 auto;
}

.demo-form {
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

/* Contact Section */
.contact-section {
  padding: 6rem 0;
  background: #f9fafb;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 4rem;
}

.contact-info {
  display: grid;
  gap: 1.5rem;
}

.contact-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  text-align: center;
}

.contact-card-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.contact-card h3 {
  font-size: 1.25rem;
  margin-bottom: 0.75rem;
  color: #1a1a1a;
}

.contact-card p {
  color: #666;
  margin: 0.25rem 0;
}

.contact-form-wrapper {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

/* Form Styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.demo-form label,
.contact-form label {
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-message {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  font-weight: 500;
}

.form-message.success {
  background: #d1fae5;
  color: #065f46;
}

.form-message.error {
  background: #fee2e2;
  color: #991b1b;
}

/* FAQ Section */
.faq {
  padding: 6rem 0;
  background: white;
}

.faq-grid {
  max-width: 800px;
  margin: 0 auto;
}

.faq-item {
  margin-bottom: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.faq-question {
  padding: 1.5rem;
  background: white;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: background 0.3s;
}

.faq-question:hover {
  background: #f9fafb;
}

.faq-question h4 {
  margin: 0;
  font-size: 1.1rem;
  color: #1a1a1a;
}

.faq-icon {
  font-size: 1.5rem;
  color: #667eea;
  font-weight: 300;
}

.faq-answer {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease, padding 0.3s ease;
  background: #f9fafb;
}

.faq-answer.active {
  max-height: 500px;
  padding: 1.5rem;
}

.faq-answer p {
  margin: 0;
  color: #666;
  line-height: 1.7;
}

/* CTA Section */
.cta {
  padding: 6rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}

.cta-content h2 {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.cta-content p {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.95;
}

.cta-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

/* Footer */
.footer {
  background: #1a1a1a;
  color: white;
  padding: 4rem 0 2rem;
}

.footer-content {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 2rem;
  margin-bottom: 3rem;
}

.footer-section h3,
.footer-section h4 {
  margin-bottom: 1rem;
  color: white;
}

.footer-section p {
  color: #9ca3af;
  line-height: 1.7;
  margin-bottom: 1rem;
}

.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section ul li {
  margin-bottom: 0.75rem;
}

.footer-section a {
  color: #9ca3af;
  text-decoration: none;
  transition: color 0.3s;
}

.footer-section a:hover {
  color: white;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-links a {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.social-links a:hover {
  background: #667eea;
  transform: translateY(-3px);
}

.footer-bottom {
  border-top: 1px solid #374151;
  padding-top: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #9ca3af;
}

.footer-badges {
  display: flex;
  gap: 1rem;
}

.badge {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .hero .container {
    grid-template-columns: 1fr;
  }

  .contact-grid {
    grid-template-columns: 1fr;
  }

  .footer-content {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .nav-links {
    display: none;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .mobile-menu {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s, padding 0.3s;
  }

  .mobile-menu.open {
    max-height: 500px;
    padding: 1rem;
  }

  .mobile-menu a {
    text-decoration: none;
    color: #666;
    font-weight: 500;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-stats {
    grid-template-columns: 1fr;
  }

  .hero-actions {
    flex-direction: column;
  }

  .section-title {
    font-size: 2rem;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .benefits-grid {
    grid-template-columns: 1fr;
  }

  .solutions-grid {
    grid-template-columns: 1fr;
  }

  .pricing-grid {
    grid-template-columns: 1fr;
  }

  .testimonials-grid {
    grid-template-columns: 1fr;
  }

  .footer-content {
    grid-template-columns: 1fr;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .cta-content h2 {
    font-size: 2rem;
  }

  .cta-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 2rem;
  }

  .section-title {
    font-size: 1.75rem;
  }

  .benefit-item {
    flex-direction: column;
  }

  .benefit-number {
    font-size: 2.5rem;
  }
}
</style>