import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getUserInfo } from '@/api/index'
export const useUserStore = defineStore('user', () => {
  // 状态
  const isLoggedIn = ref(false)
  const userInfo = ref({})
  const token = ref('')

  const configInfo = ref({})

  // 保存用户信息到localStorage
  const saveUserToLocal = () => {
    if (isLoggedIn.value && token.value) {
      localStorage.setItem('token', token.value)
      localStorage.setItem('userInfo', JSON.stringify(userInfo.value))
      localStorage.setItem('isLoggedIn', 'true')
      console.log('localStorage已保存token和用户信息')
    } else {
      console.log('localStorage保存条件不满足:', { 
        isLoggedIn: isLoggedIn.value, 
        hasToken: !!token.value 
      })
    }
  }

  // 从localStorage恢复用户信息
  const restoreUserFromLocal = () => {
    try {
      const savedToken = localStorage.getItem('token')
      const savedUserInfo = localStorage.getItem('userInfo')
      const savedLoginStatus = localStorage.getItem('isLoggedIn')

      if (savedToken && savedUserInfo && savedLoginStatus === 'true') {
        token.value = savedToken
        userInfo.value = JSON.parse(savedUserInfo)
        isLoggedIn.value = true
        return true
      }
    } catch (error) {
      console.error('恢复用户信息失败:', error)
      clearUser()
    }
    return false
  }

  // 设置用户登录信息
  const updateToken = (userToken) => {
    token.value = userToken
    isLoggedIn.value = true
    saveUserToLocal()
  }

  // 更新用户信息
  const updateUserInfo = (newUserInfo) => {
    userInfo.value = { ...userInfo.value, ...newUserInfo }
    saveUserToLocal()
  }

  // 获取最新的用户信息
  const fetchLatestUserInfo = async () => {
    if (!token.value) {
      throw new Error('未登录')
    }
    try {
      const response = await getUserInfo()
      if (response && response.data) {
        updateUserInfo(response.data)
        return response.data
      }
      return userInfo.value
    } catch (error) {
      if (error.response?.status === 401) {
        clearUser()
      }
      throw error
    }
  }

  // 清除用户信息
  const clearUser = () => {
    token.value = ''
    userInfo.value = {
      uid: '',
      email: '',
      avatar: '',
      nickname: '',
      phone: '',
      realName: '',
      isVerified: false
    }
    isLoggedIn.value = false
    
    // 清除localStorage
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    localStorage.removeItem('isLoggedIn')
  }

  // 检查登录状态
  const checkAuthStatus = async () => {
    // 先尝试从本地恢复
    const hasLocalData = restoreUserFromLocal()
    if (hasLocalData && token.value) {
      try {
        // 验证token是否有效，并获取最新用户信息
        await fetchLatestUserInfo()
        return true
      } catch (error) {
        // token无效，清除本地数据
        clearUser()
        return false
      }
    }
    
    return false
  }

  const saveConfigInfo = (info) => {
    configInfo.value = info
    localStorage.setItem('configInfo', JSON.stringify(info))
  }

  return {
    // 状态
    isLoggedIn,
    userInfo,
    token,
    configInfo,
    
    // 方法
    updateToken,
    updateUserInfo,
    fetchLatestUserInfo,
    clearUser,
    checkAuthStatus,
    saveUserToLocal,
    restoreUserFromLocal,
    saveConfigInfo
  }
},
{
    persist: true
});