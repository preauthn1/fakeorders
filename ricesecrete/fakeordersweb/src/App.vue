<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { useLanguage } from '@/composables/useLanguage'
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import router from './router'
import { useCounterStore } from './stores/counter'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useNotificationStore } from '@/stores/notification'
import { useLanguageConfigStore } from '@/stores/languageConfig'
import { useI18n } from 'vue-i18n'


import { Push } from '@/utils/push-vue'

</script>

<template>
  <RouterView class="container mx-auto px-4 md:px-0 md:w-[1440px]" />
</template>

<style scoped>

</style>
