<template>
  <div class="group-details-container">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i> Loading group details...
    </div>

    <div v-else-if="group" class="group-content">
      <!-- Header -->
      <div class="group-header">
        <div class="header-left">
          <button @click="goBack" class="back-btn">
            <i class="fas fa-arrow-left"></i>
          </button>

          <div>
            <h1>{{ group.name }}</h1>
            <p class="group-meta">
              <span class="type-badge" :class="`badge-${group.group_type}`">
                {{ formatGroupType(group.group_type) }}
              </span>
              <span class="separator">•</span>
              <span>{{ group.businesses_count || 0 }} Businesses</span>
            </p>
          </div>
        </div>

        <!-- ✅ ACTION BUTTONS -->
        <div class="header-actions">
          <button
            @click="openInviteModal"
            class="btn-secondary"
          >
            <i class="fas fa-user-plus"></i>
            Invite Business
          </button>

          <button
            @click="openSettingsModal"
            class="btn-primary"
          >
            <i class="fas fa-cog"></i>
            Settings
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon building">
            <i class="fas fa-building"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total_businesses || 0 }}</div>
            <div class="stat-label">Businesses</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon users">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total_employees || 0 }}</div>
            <div class="stat-label">Employees</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon tickets">
            <i class="fas fa-ticket-alt"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total_tickets || 0 }}</div>
            <div class="stat-label">Tickets</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon tasks">
            <i class="fas fa-tasks"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total_tasks || 0 }}</div>
            <div class="stat-label">Tasks</div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-container">
        <div class="tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="['tab', { active: activeTab === tab.key }]"
            @click="activeTab = tab.key"
          >
            <i :class="tab.icon"></i>
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <div v-if="activeTab === 'members'" class="members-list">
          <div v-for="business in members" :key="business.id" class="member-card">
            <div class="member-info">
              <h3>{{ business.name }}</h3>
              <p>{{ business.country?.name }}</p>
            </div>

            <div class="member-meta">
              <span class="role-badge" :class="`role-${business.pivot.role}`">
                {{ business.pivot.role.toUpperCase() }}
              </span>
              <span class="joined-date">
                Joined {{ formatDate(business.pivot.joined_at) }}
              </span>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'activity'" class="activity-list">
          <div v-for="activity in activities" :key="activity.id" class="activity-item">
            <div class="activity-icon">
              <i :class="getActivityIcon(activity.action)"></i>
            </div>
            <div class="activity-content">
              <p class="activity-description">{{ activity.description }}</p>
              <span class="activity-time">
                {{ formatDateTime(activity.created_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ MODALS -->
    <InviteBusinessModal
      v-if="showInviteModal"
      :group-id="group.id"
      @close="showInviteModal = false"
      @invited="onBusinessInvited"
    />

    <GroupSettingsModal
      v-if="showSettingsModal"
      :group="group"
      @close="showSettingsModal = false"
      @updated="onGroupUpdated"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

import InviteBusinessModal from './InviteBusinessModal.vue'
import GroupSettingsModal from './GroupSettingsModal.vue'

export default {
  name: 'BusinessGroupDetails',
  components: {
    InviteBusinessModal,
    GroupSettingsModal
  },
  setup () {
    const router = useRouter()
    const route = useRoute()

    const group = ref(null)
    const members = ref([])
    const stats = ref({})
    const activities = ref([])
    const loading = ref(true)

    const activeTab = ref('members')
    const showInviteModal = ref(false)
    const showSettingsModal = ref(false)

    const tabs = [
      { key: 'members', label: 'Members', icon: 'fas fa-users' },
      { key: 'activity', label: 'Activity', icon: 'fas fa-history' }
    ]

    const fetchGroupDetails = async () => {
      loading.value = true
      try {
        const id = route.params.id

        const [groupRes, membersRes, statsRes, activityRes] =
          await Promise.all([
            axios.get(`/api/business-groups/${id}`),
            axios.get(`/api/business-groups/${id}/members`),
            axios.get(`/api/business-groups/${id}/stats`),
            axios.get(`/api/business-groups/${id}/activity`)
          ])

        group.value = groupRes.data.data
        members.value = membersRes.data.data
        stats.value = statsRes.data.data
        activities.value = activityRes.data.data?.data || []
      } finally {
        loading.value = false
      }
    }

    const openInviteModal = () => {
      showInviteModal.value = true
    }

    const openSettingsModal = () => {
      showSettingsModal.value = true
    }

    const onBusinessInvited = () => {
      showInviteModal.value = false
      fetchGroupDetails()
    }

    const onGroupUpdated = updated => {
      group.value = updated
      showSettingsModal.value = false
    }

    const goBack = () => router.push({ name: 'business-groups' })

    const formatGroupType = type =>
      type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')

    const formatDate = date =>
      new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })

    const formatDateTime = dt =>
      new Date(dt).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
      })

    const getActivityIcon = action => ({
      group_created: 'fas fa-plus-circle',
      business_joined: 'fas fa-user-plus',
      business_left: 'fas fa-user-minus',
      invitation_sent: 'fas fa-paper-plane',
      settings_updated: 'fas fa-cog'
    }[action] || 'fas fa-circle')

    onMounted(fetchGroupDetails)

    return {
      group,
      members,
      stats,
      activities,
      loading,
      activeTab,
      tabs,
      showInviteModal,
      showSettingsModal,
      openInviteModal,
      openSettingsModal,
      onBusinessInvited,
      onGroupUpdated,
      goBack,
      formatGroupType,
      formatDate,
      formatDateTime,
      getActivityIcon
    }
  }
}
</script>

<style scoped>
.group-details-container {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
  font-size: 18px;
}

.group-content h1 {
  font-size: 32px;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  background: none;
  border: 1px solid #d1d5db;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
  color: #374151;
}

.back-btn:hover {
  background-color: #f9fafb;
}

.group-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 14px;
  margin-top: 4px;
}

.type-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-holding { background-color: #dbeafe; color: #1e40af; }
.badge-franchise { background-color: #fef3c7; color: #92400e; }
.badge-subsidiary { background-color: #d1fae5; color: #065f46; }
.badge-partnership { background-color: #e0e7ff; color: #3730a3; }

.separator {
  color: #d1d5db;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn-secondary {
  background-color: white;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

.btn-primary {
  background-color: #4f46e5;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  background-color: #4338ca;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-icon.building { background-color: #dbeafe; color: #1e40af; }
.stat-icon.users { background-color: #d1fae5; color: #065f46; }
.stat-icon.tickets { background-color: #fef3c7; color: #92400e; }
.stat-icon.tasks { background-color: #e0e7ff; color: #3730a3; }

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
}

.tabs-container {
  margin-bottom: 24px;
}

.tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
}

.tab {
  padding: 12px 24px;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tab:hover {
  color: #1a202c;
}

.tab.active {
  color: #1a202c;
  border-bottom: 2px solid #4f46e5;
}

.tab-content {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.member-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: box-shadow 0.2s;
}

.member-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.member-info h3 {
  margin: 0;
  font-size: 18px;
  color: #1a202c;
}

.member-info p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 14px;
}

.member-meta {
  display: flex;
  align-items: center;
  gap: 16px;
}

.role-badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.role-admin { background-color: #dbeafe; color: #1e40af; }
.role-member { background-color: #d1fae5; color: #065f46; }

.joined-date {
  color: #9ca3af;
  font-size: 13px;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.activity-item {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  font-size: 18px;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-description {
  margin: 0;
  color: #374151;
  font-size: 15px;
  line-height: 1.5;
}

.activity-time {
  display: block;
  color: #9ca3af;
  font-size: 13px;
  margin-top: 4px;
}
</style>