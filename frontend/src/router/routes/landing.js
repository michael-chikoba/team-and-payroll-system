// Landing pages (public, no auth required)
export default [
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/landing/HomeView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/features',
    name: 'Features',
    component: () => import('@/views/landing/FeaturesView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/solutions',
    name: 'Solutions',
    component: () => import('@/views/landing/SolutionsView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/pricing',
    name: 'Pricing',
    component: () => import('@/views/landing/PricingView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/testimonials',
    name: 'Testimonials',
    component: () => import('@/views/landing/TestimonialsView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/contact',
    name: 'Contact',
    component: () => import('@/views/landing/ContactView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },

  // ── Company Pages ──
  {
    path: '/careers',
    name: 'Careers',
    component: () => import('@/views/landing/CareersView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/blog',
    name: 'Blog',
    component: () => import('@/views/landing/BlogView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/press',
    name: 'Press',
    component: () => import('@/views/landing/PressView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },

  // ── Legal & Trust Pages ──
  {
    path: '/privacy',
    name: 'PrivacyPolicy',
    component: () => import('@/views/landing/PrivacyPolicyView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/terms',
    name: 'TermsOfService',
    component: () => import('@/views/landing/TermsOfServiceView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/security',
    name: 'Security',
    component: () => import('@/views/landing/SecurityView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  },
  {
    path: '/compliance',
    name: 'Compliance',
    component: () => import('@/views/landing/ComplianceView.vue'),
    meta: {
      public: true,
      layout: 'landing'
    }
  }
]