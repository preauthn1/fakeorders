import { get, post, put, del } from '@/utils/request'


// 示例代码
export const initialization = (params) => get(`/api/xyt/activity/info`, params);
export const getComments = (trackId, params = {}) => get(`/api/home/comment/${trackId}`, params);
export const getHome = (data) => post(`/api/home`, data);
export const updateComment = (commentId, data) => put(`/api/home/comment/${commentId}`, data);
export const deleteComment = (params) => del('/api/home/comment', params);


// 获取系统配置
export const getConfig = () => {
    return get('/wallet/v1/config')
}

//获取多语言翻译
export const getTranslations = (params) => {
    return get('/wallet/v1/lang_list', params)
}

// 获取用户信息
export const getUserInfo = () => {
    return get('/wallet/v1/user/info')
}

export const addLanguage = (data) => {
    return get('/wallet/v1/add_lang', data)
}