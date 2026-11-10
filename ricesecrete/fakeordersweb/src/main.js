import './assets/main.css'
import './assets/tailwind.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import i18n from './i18n'

import Vant from 'vant';
import 'vant/lib/index.css';
import { getConfig, getTranslations } from '@/api/index.js'
import { useLanguageConfigStore } from '@/stores/languageConfig.js'
import { setFavicon } from "@/utils/utils"

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(i18n)
app.use(Vant)


// 初始化语言配置store
const languageConfigStore = useLanguageConfigStore()
languageConfigStore.initializeFromStorage()

const getCon = async () => {
  try {
    const response = await getConfig();
    if (response && response.data) {
      console.log('获取配置成功:', response.data);

      languageConfigStore.setLanguageConfig(response.data)
      i18n.global.locale.value = languageConfigStore.currentLanguage

      const script = document.createElement('script');
      script.src = response.data.web.service;
      script.async = true;

      script.onload = () => {
        console.log("外部脚本已加载！");
      };

      document.head.appendChild(script); // 将脚本插入到head标签中

      // 更新标题和图标
      document.title = response.data.web.title;
      setFavicon(response.data.web.logo);

      const langTime = localStorage.getItem('langUpdateTime');
      if (response.data.lang_cache_time && langTime != response.data.lang_cache_time) {
        localStorage.setItem('langUpdateTime', response.data.lang_cache_time || '');
        getTranslationsList();
      } else {
        const savedLanguage = localStorage.getItem('translations');
        if (savedLanguage) {
          updateLocaleList(JSON.parse(savedLanguage));
        } else {
          getTranslationsList();
        }
      }
    }
  } catch (error) {
    console.error('获取配置失败:', error)
  }
}
getCon();
const getTranslationsList = async () => {
  try {
    const response = await getTranslations();
    if (response && response.data) {
      localStorage.setItem('translations', JSON.stringify(response.data));
      updateLocaleList(response.data);
    }
  } catch (error) {
    console.error('获取翻译失败:', error);
  }
}
const updateLocaleList = (data) => {
  Object.keys(data).forEach(langCode => {
    i18n.global.setLocaleMessage(langCode, data[langCode]);
  });
}

app.mount('#app')
