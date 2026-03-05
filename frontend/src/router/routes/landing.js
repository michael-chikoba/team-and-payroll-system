// Landing pages (public, no auth required)
export default [
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/landing/HomeView.vue'),
    meta: {
      public: true,
      layout: 'landing' // You can use this for layout switching if needed
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
  }
]