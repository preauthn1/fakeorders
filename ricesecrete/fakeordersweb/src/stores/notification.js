import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  // 通知列表
  const notifications = ref([])
  
  // 未读通知数量
  const unreadCount = ref(0)

  const refreshKey = ref(0) // 刷新计数器
  function triggerRefresh() {
    refreshKey.value++   // 每次加 1，通知监听者
  }
  
  // 添加新通知
  const addNotification = (notification) => {
    const newNotification = {
      id: Date.now() + Math.random(), // 生成唯一ID
      ...notification,
      timestamp: new Date(),
      read: false
    }
    
    notifications.value.unshift(newNotification)
    unreadCount.value++
    
    // 可以在这里添加浏览器通知
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title || '新消息', {
        body: notification.content || notification.message,
        icon: '/logo.png'
      })
    }
  }
  
  // 标记通知为已读
  const markAsRead = (notificationId) => {
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification && !notification.read) {
      notification.read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }
  
  // 标记所有通知为已读
  const markAllAsRead = () => {
    notifications.value.forEach(notification => {
      notification.read = true
    })
    unreadCount.value = 0
  }
  
  // 删除通知
  const removeNotification = (notificationId) => {
    const index = notifications.value.findIndex(n => n.id === notificationId)
    if (index > -1) {
      const notification = notifications.value[index]
      if (!notification.read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
      notifications.value.splice(index, 1)
    }
  }
  
  // 清空所有通知
  const clearAll = () => {
    notifications.value = []
    unreadCount.value = 0
  }
  
  // 请求浏览器通知权限
  const requestNotificationPermission = async () => {
    if ('Notification' in window) {
      const permission = await Notification.requestPermission()
      return permission === 'granted'
    }
    return false
  }
  
  return {
    notifications,
    unreadCount,
    refreshKey,
    triggerRefresh,
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAll,
    requestNotificationPermission
  }
})