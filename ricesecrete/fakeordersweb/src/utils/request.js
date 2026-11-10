import axios from 'axios'
import { useUserStore } from '@/stores/user'
import router from '@/router'
import { showToast } from 'vant'

const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'https://api.hkxahxcoin.com',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

request.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    const lang = localStorage.getItem('language') || 'zh'
    if (config.method === "get") {
      config.params = { ...config.params, lang }
    } else {
      config.url = config.url + (config.url.includes('?') ? '&' : '?') + `lang=${lang}`
    }

    return config
  },
  error => {
    return Promise.reject(error)
  }
)

request.interceptors.response.use(
  response => {
    const { data } = response

    // 检查响应数据结构
    if (data && typeof data === 'object') {
      // 如果有错误码且不为成功状态
      if (data.code !== 0) {
        const errorMessage = data.message || data.msg || '请求失败'
        console.error('API响应错误:', { code: data.code, message: errorMessage, data })

        // 特殊处理4001错误码（登录过期）
        if (data.code === 4001) {
          handleLogout()
          showToast('登录已过期，请重新登录')
        } else {
          showToast(errorMessage)
        }
        return Promise.reject(new Error(errorMessage))
      }
    }
    return data
  },
  error => {
    let message = '网络错误'

    if (error.response) {
      const { status, data } = error.response
      console.error('错误响应数据:', data)

      switch (status) {
        case 400:
          message = data?.message || data?.msg || '请求参数错误'
          break
        case 401:
        case 4001:
          handleLogout()
          message = '登录已过期，请重新登录'
          break
        case 403:
          message = '没有权限访问'
          break
        case 404:
          message = '请求的资源不存在'
          break
        case 422:
          message = data?.message || data?.msg || '数据验证失败'
          break
        case 500:
          message = '服务器内部错误'
          break
        default:
          message = data?.message || data?.msg || `请求失败 (${status})`
      }
    }

    showToast(message)
    return Promise.reject(error)
  }
)

// 处理登出逻辑
function handleLogout() {
  // 清除本地存储
  localStorage.removeItem('token')
  localStorage.removeItem('userInfo')
  localStorage.removeItem('isLoggedIn')

  // 清除用户store状态（如果在组件外使用store）
  try {
    const userStore = useUserStore()
    userStore.clearUser()
  } catch (error) {
    console.warn('无法在拦截器中使用store，将通过事件处理')
  }

  // 触发全局事件或跳转登录页
  if (router.currentRoute.value.path !== '/login') {
    router.push('/login')
  }

  // 或者触发全局事件显示登录弹窗
  // window.dispatchEvent(new CustomEvent('show-login-modal'))
}

/**
 * @description 封装get方法
 * @param {String} url 请求地址
 * @param {Object} params 请求参数
 * @param {Object=} config 额外可选配置
 * @return {Promise} 返回一个promise
 */
export const get = (url, params = {}, config = {}) => {
  return request({
    method: 'get',
    url,
    params,
    ...config
  })
}

/**
 * @description 封装post方法
 * @param {String} url 请求地址
 * @param {Object} data 请求参数
 * @param {Object=} config 额外可选配置
 * @return {Promise} 返回一个promise
 */
export const post = (url, data = {}, config = {}) => {
  return request({
    method: 'post',
    url,
    data,
    ...config
  })
}

/**
 * @description 封装put方法
 * @param {String} url 请求地址
 * @param {Object} data 请求参数
 * @param {Object=} config 额外可选配置
 * @return {Promise} 返回一个promise
 */
export const put = (url, data = {}, config = {}) => {
  return request({
    method: 'put',
    url,
    data,
    ...config
  })
}

/**
 * @description 封装delete方法
 * @param {String} url 请求地址
 * @param {Object} params 请求参数
 * @param {Object=} config 额外可选配置
 * @return {Promise} 返回一个promise
 */
export const del = (url, params = {}, config = {}) => {
  return request({
    method: 'delete',
    url,
    params,
    ...config
  })
}

export default request