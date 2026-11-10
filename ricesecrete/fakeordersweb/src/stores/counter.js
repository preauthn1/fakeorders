import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useCounterStore = defineStore('counter', () => {
  const count = ref(0)
  const doubleCount = computed(() => count.value * 2)
  
  // 检测是否为PC端
  const detectDevice = () => {
    const userAgent = navigator.userAgent.toLowerCase()
    const isMobileDevice = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent)
    const isTablet = /ipad|android(?!.*mobile)/i.test(userAgent)
    const isSmallScreen = window.innerWidth <= 768
    
    return !isMobileDevice && !isTablet && !isSmallScreen
  }
  
  const isPC = ref(detectDevice())
  
  // 监听窗口大小变化
  const updateDevice = () => {
    isPC.value = detectDevice()
  }
  
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', updateDevice)
  }
  
  return { count, doubleCount, isPC }
})
