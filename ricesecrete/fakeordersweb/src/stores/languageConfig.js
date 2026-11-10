
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLanguageConfigStore = defineStore('languageConfig', () => {
  // 可用语言列表
  const availableLanguages = ref([])
  
  // 默认语言
  const defaultLanguage = ref('en')
  
  // 当前选中的语言
  const currentLanguage = ref('en')
  
  // 系统配置
  const config = ref({})
  
  // 设置语言配置
  const setLanguageConfig = (configData) => {
    if (configData.lang) {
      // 将对象转换为数组格式，方便遍历
      availableLanguages.value = Object.values(configData.lang).map(lang => ({
        id: lang.id,
        title: lang.title,
        code: lang.lang, // 这是i18n需要的语言代码
        isDefault: lang.is_default === 1,
        remark: lang.remark
      }))
    }
    const defaultLang = localStorage.getItem('preferred-language')
    if (defaultLang) {
      currentLanguage.value = defaultLang;
    } else {
      if (configData.default_lang) {
        defaultLanguage.value = configData.default_lang
        currentLanguage.value = configData.default_lang
      }
    }
    
    if (configData) {
      config.value = configData
    }
  }
  
  // 切换语言
  const switchLanguage = (languageCode) => {
    const language = availableLanguages.value.find(lang => lang.code === languageCode)
    if (language) {
      currentLanguage.value = languageCode
      // 保存到本地存储
      localStorage.setItem('preferred-language', languageCode)
      return language
    }
    return null
  }
  
  // 获取当前语言信息
  const getCurrentLanguageInfo = () => {
    return availableLanguages.value.find(lang => lang.code === currentLanguage.value)
  }
  
  // 从本地存储初始化语言设置
  const initializeFromStorage = () => {
    const savedLanguage = localStorage.getItem('preferred-language')
    if (savedLanguage) {
      currentLanguage.value = savedLanguage
    }
  }
  
  return {
    availableLanguages,
    defaultLanguage,
    currentLanguage,
    config,
    setLanguageConfig,
    switchLanguage,
    getCurrentLanguageInfo,
    initializeFromStorage
  }
})