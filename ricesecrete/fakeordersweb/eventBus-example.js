// 安装 mitt
// npm install mitt

import { createApp } from 'vue'
import mitt from 'mitt'

const app = createApp(App)
const emitter = mitt()

// 将事件总线挂载到全局属性
app.config.globalProperties.$emitter = emitter

// 在组件中使用：
// this.$emitter.emit('new-notification', data)
// this.$emitter.on('new-notification', (data) => { ... })