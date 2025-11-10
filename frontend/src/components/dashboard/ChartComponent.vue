<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

export default {
  name: 'ChartComponent',
  props: {
    type: {
      type: String,
      default: 'line',
      validator: (value) => ['line', 'bar', 'pie', 'doughnut'].includes(value)
    },
    data: {
      type: Object,
      required: true
    },
    options: {
      type: Object,
      default: () => ({})
    },
    height: {
      type: Number,
      default: 300
    }
  },
  setup(props) {
    const chartCanvas = ref(null)
    let chartInstance = null

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true
          }
        },
        y: {
          display: true,
          title: {
            display: true
          }
        }
      }
    }

    onMounted(() => {
      if (chartCanvas.value) {
        createChart()
      }
    })

    watch(
      () => [props.data, props.options],
      () => {
        if (chartInstance) {
          updateChart()
        }
      },
      { deep: true }
    )

    onUnmounted(() => {
      if (chartInstance) {
        chartInstance.destroy()
      }
    })

    function createChart() {
      const ctx = chartCanvas.value.getContext('2d')
      chartInstance = new Chart(ctx, {
        type: props.type,
        data: props.data,
        options: { ...defaultOptions, ...props.options }
      })
    }

    function updateChart() {
      chartInstance.data = props.data
      chartInstance.options = { ...defaultOptions, ...props.options }
      chartInstance.update()
    }

    function destroyChart() {
      if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
      }
    }

    return {
      chartCanvas,
      destroyChart
    }
  }
}
</script>

<style scoped>
.chart-container {
  position: relative;
  height: v-bind(height + 'px');
  width: 100%;
}
</style>