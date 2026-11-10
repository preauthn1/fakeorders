import request from './request'

const api = {
  get(url, params = {}) {
    return request({
      method: 'GET',
      url,
      params
    })
  },

  post(url, data = {}) {
    return request({
      method: 'POST',
      url,
      data,
    })
  },

  put(url, data = {}) {
    return request({
      method: 'PUT',
      url,
      data
    })
  },

  patch(url, data = {}) {
    return request({
      method: 'PATCH',
      url,
      data,
    })
  },

  delete(url, params = {}) {
    return request({
      method: 'DELETE',
      url,
      params
    })
  },

  upload(url, formData) {
    return request({
      method: 'POST',
      url,
      data: formData,
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  }
}

export default api