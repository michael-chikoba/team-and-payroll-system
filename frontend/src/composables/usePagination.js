import { ref, computed } from 'vue'

export function usePination(initialPage = 1, initialPerPage = 10) {
  const currentPage = ref(initialPage)
  const perPage = ref(initialPerPage)
  const totalItems = ref(0)

  const totalPages = computed(() => {
    return Math.ceil(totalItems.value / perPage.value)
  })

  const offset = computed(() => {
    return (currentPage.value - 1) * perPage.value
  })

  function setPage(page) {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
    }
  }

  function nextPage() {
    if (currentPage.value < totalPages.value) {
      currentPage.value++
    }
  }

  function previousPage() {
    if (currentPage.value > 1) {
      currentPage.value--
    }
  }

  function setTotalItems(total) {
    totalItems.value = total
  }

  function reset() {
    currentPage.value = 1
    totalItems.value = 0
  }

  return {
    currentPage,
    perPage,
    totalItems,
    totalPages,
    offset,
    setPage,
    nextPage,
    previousPage,
    setTotalItems,
    reset
  }
}