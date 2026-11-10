import { useUserStore } from '@/stores/user'

// 用户信息管理工具
export const useUser = () => {
  const userStore = useUserStore()
  
  return {
    // 状态
    isLoggedIn: userStore.isLoggedIn,
    userInfo: userStore.userInfo,
    token: userStore.token,
    
    // 方法
    login: userStore.setUser,
    logout: userStore.clearUser,
    updateInfo: userStore.updateUserInfo,
    fetchInfo: userStore.fetchLatestUserInfo,
    checkAuth: userStore.checkAuthStatus,
    
    // 便捷方法
    isAuthenticated: () => userStore.isLoggedIn && userStore.token,
    getUserId: () => userStore.userInfo.uid,
    getUserEmail: () => userStore.userInfo.email,
    getUserAvatar: () => userStore.userInfo.avatar,
    isVerified: () => userStore.userInfo.isVerified
  }
}