<template>
  <div :class="['stat-card', `stat-card-${color}`]">
    <div class="stat-card-content">
      <div class="stat-card-left">
        <div class="stat-card-title">{{ title }}</div>
        <div class="stat-card-value">{{ value }}</div>
        <div v-if="trend" class="stat-card-trend">
          <span :class="trendIconClass">{{ trendIcon }}</span>
          <span>{{ trend }}</span>
        </div>
      </div>
      <div class="stat-card-right">
        <div class="stat-card-icon">
          <slot name="icon">
            <div class="default-icon">{{ iconText }}</div>
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatCard',
  props: {
    title: {
      type: String,
      required: true
    },
    value: {
      type: [String, Number],
      required: true
    },
    trend: {
      type: String,
      default: ''
    },
    color: {
      type: String,
      default: 'blue',
      validator: (value) => ['blue', 'green', 'yellow', 'red', 'purple'].includes(value)
    },
    icon: {
      type: String,
      default: ''
    }
  },
  computed: {
    iconText() {
      if (this.icon) return this.icon;
      return this.title.charAt(0).toUpperCase();
    },
    trendIcon() {
      if (!this.trend) return '';
      return this.trend.includes('+') ? '📈' : '📉';
    },
    trendIconClass() {
      if (!this.trend) return '';
      return this.trend.includes('+') ? 'trend-up' : 'trend-down';
    }
  }
}
</script>

<style scoped>
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-card-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-card-left {
  flex: 1;
}

.stat-card-title {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
  font-weight: 500;
}

.stat-card-value {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 4px;
}

.stat-card-trend {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 500;
}

.trend-up {
  color: #10b981;
}

.trend-down {
  color: #ef4444;
}

.stat-card-right {
  margin-left: 16px;
}

.stat-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

/* Color variants */
.stat-card-blue .stat-card-icon {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.stat-card-blue .stat-card-value {
  color: #3b82f6;
}

.stat-card-green .stat-card-icon {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-card-green .stat-card-value {
  color: #10b981;
}

.stat-card-yellow .stat-card-icon {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.stat-card-yellow .stat-card-value {
  color: #f59e0b;
}

.stat-card-red .stat-card-icon {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-card-red .stat-card-value {
  color: #ef4444;
}

.stat-card-purple .stat-card-icon {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.stat-card-purple .stat-card-value {
  color: #8b5cf6;
}

.default-icon {
  font-weight: 700;
}
</style>