import { useI18n } from 'vue-i18n'
import { computed } from 'vue'
import { Locale } from 'vant'
import zhCN from 'vant/es/locale/lang/zh-CN'
import enUS from 'vant/es/locale/lang/en-US'

export function useLanguage() {
  const { locale, t } = useI18n({ useScope: 'global' })

  const currentLanguage = computed(() => locale.value)

  const switchLanguage = (lang) => {
    console.log('切换语言:', lang)
    locale.value = lang
    localStorage.setItem('language', lang)
    
    // 同时切换Vant组件的语言
    if (lang == 'zh') {
      Locale.use('zh-CN', zhCN)
    } else if (lang == 'en') {
      Locale.use('en-US', enUS)
    }
  }

  return {
    currentLanguage,
    switchLanguage,
    t
  }
}