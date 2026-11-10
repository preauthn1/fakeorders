import { createI18n } from 'vue-i18n'
import zh from './locales/zh.js'
import en from './locales/en.js'
import { addLanguage } from '@/api/index.js'

const messages = {
  zh,
  en
}

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('language') || 'en',
  fallbackLocale: 'en',
  messages,
  missing: (locale, key) => {
    console.warn(`Missing translation for key: "${key}" in locale: "${locale}"`);
    const keyLoc = localStorage.getItem(key)
    if (keyLoc) {
      return key;
    }
    localStorage.setItem(key, key);
    addLanguage({title:key})
    return key; // 返回key作为默认值
  },
})

export default i18n