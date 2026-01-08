<template>
  <div class="pagination">
    <button
      @click="goToPage(currentPage - 1)"
      :disabled="currentPage === 1 || loading"
      class="pagination-button"
    >
      &laquo;
    </button>
    
    <button
      v-for="page in pages"
      :key="page"
      @click="goToPage(page)"
      :class="['pagination-page', { active: page === currentPage }]"
      :disabled="loading"
    >
      {{ page }}
    </button>
    
    <button
      @click="goToPage(currentPage + 1)"
      :disabled="currentPage === totalPages || loading"
      class="pagination-button"
    >
      &raquo;
    </button>
    
    <div class="pagination-info">
      Page {{ currentPage }} of {{ totalPages }}
      <span v-if="totalItems">({{ totalItems }} items)</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Pagination',
  props: {
    currentPage: {
      type: Number,
      required: true,
      default: 1
    },
    totalPages: {
      type: Number,
      required: true,
      default: 1
    },
    totalItems: {
      type: Number,
      default: 0
    },
    maxVisible: {
      type: Number,
      default: 5
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['page-change'],
  computed: {
    pages() {
      const half = Math.floor(this.maxVisible / 2);
      let start = this.currentPage - half;
      let end = this.currentPage + half;
      
      if (start < 1) {
        start = 1;
        end = Math.min(this.maxVisible, this.totalPages);
      }
      
      if (end > this.totalPages) {
        end = this.totalPages;
        start = Math.max(1, end - this.maxVisible + 1);
      }
      
      const pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.$emit('page-change', page);
      }
    }
  }
}
</script>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  flex-wrap: wrap;
}

.pagination-button,
.pagination-page {
  min-width: 36px;
  height: 36px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.pagination-button:hover:not(:disabled),
.pagination-page:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.pagination-button:disabled,
.pagination-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-page.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.pagination-page.active:hover {
  background: #2563eb;
}

.pagination-info {
  margin-left: 16px;
  font-size: 14px;
  color: #6b7280;
}

@media (max-width: 640px) {
  .pagination {
    gap: 2px;
  }
  
  .pagination-button,
  .pagination-page {
    min-width: 32px;
    height: 32px;
    font-size: 12px;
  }
  
  .pagination-info {
    margin-left: 8px;
    font-size: 12px;
  }
}
</style>