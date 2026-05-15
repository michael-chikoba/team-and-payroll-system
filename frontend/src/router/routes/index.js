// src/router/routes/index.js
import authRoutes from './auth'
import employeeRoutes from './employee'
import managerRoutes from './manager'
import adminRoutes from './admin'
import notificationChannelRoutes from './notification-channel'
import landingRoutes from './landing'

export default [
  // Landing pages (public, no auth required)
  ...landingRoutes,

  // Business Group Routes
  {
    path: '/business-groups',
    name: 'BusinessGroups',
    component: () => import('@/components/BusinessGroups/BusinessGroupList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/invitations',
    name: 'Invitations',
    component: () => import('@/components/BusinessGroups/InvitationsDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/group-tickets',
    name: 'GroupTickets',
    component: () => import('@/components/BusinessGroups/GroupTickets.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/group-tasks',
    name: 'GroupTasks',
    component: () => import('@/components/BusinessGroups/GroupTasks.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/notifications',
    name: 'NotificationPreferences',
    component: () => import('@/components/NotificationPreferences.vue'),
    meta: { requiresAuth: true }
  },

  // ============================================
  // LEARNING & DEVELOPMENT ROUTES
  // ============================================
  
  // ----- Main Learning Routes (Role-based navigation) -----
  {
    path: '/learning',
    name: 'LearningCatalog',
    component: () => import('@/views/learning/LearningCatalog.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/learning/my-progress',
    name: 'LearningMyProgress',
    component: () => import('@/views/learning/MyProgress.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/learning/courses/:id',
    name: 'LearningCourseDetail',
    component: () => import('@/views/learning/CourseDetail.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/learning/courses/:id/assessment',
    name: 'LearningAssessment',
    component: () => import('@/views/learning/CourseAssessment.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/learning/courses/:id/results/:attemptId',
    name: 'LearningAssessmentResults',
    component: () => import('@/views/learning/AssessmentResults.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/learning/certificate/:courseId',
    name: 'LearningCertificate',
    component: () => import('@/views/learning/CertificateView.vue'),
    meta: { requiresAuth: true }
  },

  // ----- Admin/Manager Only Learning Routes -----
  {
    path: '/learning/manage',
    name: 'LearningCourseManagement',
    component: () => import('@/views/learning/CourseManagement.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] }
  },
  {
    path: '/learning/reports',
    name: 'LearningReports',
    component: () => import('@/views/learning/LearningReports.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] }
  },
  {
    path: '/learning/courses/:id/enrollments',
    name: 'LearningCourseEnrollments',
    component: () => import('@/views/learning/CourseEnrollments.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] }
  },
  {
    path: '/learning/courses/:id/answers',
    name: 'LearningEmployeeAnswers',
    component: () => import('@/views/learning/EmployeeAnswers.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] }
  },
  {
    path: '/learning/employee/:employeeId/progress',
    name: 'LearningEmployeeProgress',
    component: () => import('@/views/learning/EmployeeProgress.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] }
  },

  // ----- Admin Specific Learning Routes -----
  {
    path: '/admin/learning',
    name: 'AdminLearningCatalog',
    component: () => import('@/views/learning/LearningCatalog.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/my-progress',
    name: 'AdminMyProgress',
    component: () => import('@/views/learning/MyProgress.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/manage',
    name: 'AdminCourseManagement',
    component: () => import('@/views/learning/CourseManagement.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/reports',
    name: 'AdminLearningReports',
    component: () => import('@/views/learning/LearningReports.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/courses/:id',
    name: 'AdminCourseDetail',
    component: () => import('@/views/learning/CourseDetail.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/courses/:id/assessment',
    name: 'AdminAssessment',
    component: () => import('@/views/learning/CourseAssessment.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/certificate/:courseId',
    name: 'AdminCertificate',
    component: () => import('@/views/learning/CertificateView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/courses/:id/answers',
    name: 'AdminEmployeeAnswers',
    component: () => import('@/views/learning/EmployeeAnswers.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/learning/courses/:id/enrollments',
    name: 'AdminCourseEnrollments',
    component: () => import('@/views/learning/CourseEnrollments.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },

  // ----- Manager Specific Learning Routes -----
  {
    path: '/manager/learning',
    name: 'ManagerLearningCatalog',
    component: () => import('@/views/learning/LearningCatalog.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/my-progress',
    name: 'ManagerMyProgress',
    component: () => import('@/views/learning/MyProgress.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/reports',
    name: 'ManagerLearningReports',
    component: () => import('@/views/learning/LearningReports.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/courses/:id',
    name: 'ManagerCourseDetail',
    component: () => import('@/views/learning/CourseDetail.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/courses/:id/assessment',
    name: 'ManagerAssessment',
    component: () => import('@/views/learning/CourseAssessment.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/certificate/:courseId',
    name: 'ManagerCertificate',
    component: () => import('@/views/learning/CertificateView.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/courses/:id/answers',
    name: 'ManagerEmployeeAnswers',
    component: () => import('@/views/learning/EmployeeAnswers.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },
  {
    path: '/manager/learning/courses/:id/enrollments',
    name: 'ManagerCourseEnrollments',
    component: () => import('@/views/learning/CourseEnrollments.vue'),
    meta: { requiresAuth: true, roles: ['manager'] }
  },

  // ----- Employee Specific Learning Routes -----
  {
    path: '/employee/learning',
    name: 'EmployeeLearningCatalog',
    component: () => import('@/views/learning/LearningCatalog.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },
  {
    path: '/employee/learning/my-progress',
    name: 'EmployeeMyProgress',
    component: () => import('@/views/learning/MyProgress.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },
  {
    path: '/employee/learning/courses/:id',
    name: 'EmployeeCourseDetail',
    component: () => import('@/views/learning/CourseDetail.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },
  {
    path: '/employee/learning/courses/:id/assessment',
    name: 'EmployeeAssessment',
    component: () => import('@/views/learning/CourseAssessment.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },
  {
    path: '/employee/learning/courses/:id/results/:attemptId',
    name: 'EmployeeAssessmentResults',
    component: () => import('@/views/learning/AssessmentResults.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },
  {
    path: '/employee/learning/certificate/:courseId',
    name: 'EmployeeCertificate',
    component: () => import('@/views/learning/CertificateView.vue'),
    meta: { requiresAuth: true, roles: ['employee'] }
  },

  // Auth routes (guest only)
  ...authRoutes,

  // Protected routes (require auth)
  ...employeeRoutes,
  ...managerRoutes,
  ...adminRoutes,
  ...notificationChannelRoutes,

  // Error pages
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('@/views/Unauthorized.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]