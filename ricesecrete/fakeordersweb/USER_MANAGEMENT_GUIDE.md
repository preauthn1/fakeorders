# 用户信息管理系统使用说明

## 功能特性

1. **用户状态持久化**: 用户登录后信息会自动保存到localStorage，刷新页面不会丢失
2. **自动token验证**: 页面加载时自动验证token有效性，无效时清除本地数据
3. **统一状态管理**: 使用Pinia store管理用户状态，全局可访问
4. **便捷工具函数**: 提供useUser组合式函数，方便在组件中使用

## 主要文件

- `src/stores/user.js` - 用户状态管理store
- `src/composables/useUser.js` - 用户信息操作工具函数
- `src/api/user.js` - 用户相关API接口

## 在组件中使用

### 方式一：直接使用store
```vue
<script setup>
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

// 访问用户状态
console.log(userStore.isLoggedIn)
console.log(userStore.userInfo)

// 更新用户信息
userStore.updateUserInfo({ nickname: '新昵称' })

// 获取最新用户信息
await userStore.fetchLatestUserInfo()
</script>
```

### 方式二：使用工具函数
```vue
<script setup>
import { useUser } from '@/composables/useUser'

const { isLoggedIn, userInfo, updateInfo, fetchInfo } = useUser()

// 检查登录状态
if (isLoggedIn.value) {
  console.log('用户已登录:', userInfo.value)
}

// 更新用户信息
await updateInfo({ nickname: '新昵称' })

// 获取最新信息
await fetchInfo()
</script>
```

## 用户状态结构

```javascript
userInfo: {
  uid: '',          // 用户ID
  email: '',        // 邮箱
  avatar: '',       // 头像URL
  nickname: '',     // 昵称
  phone: '',        // 手机号
  realName: '',     // 真实姓名
  isVerified: false // 是否实名认证
}
```

## 自动化流程

1. **页面加载**: 自动检查localStorage中的token和用户信息
2. **token验证**: 如果存在token，向服务器验证有效性
3. **信息同步**: token有效时获取最新用户信息
4. **自动清理**: token无效时自动清除本地缓存

## API响应适配

系统会自动适配不同的API响应格式：
- `response.user` 或 `response.data` 或 `response` 作为用户数据
- `response.token` 或 `response.access_token` 或 `response.authToken` 作为token

根据实际API返回格式，系统会自动匹配正确的字段。