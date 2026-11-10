# 钱包连接和支付系统 - 整理后的文件结构

## 📁 核心文件结构

```
src/
├── components/
│   └── WalletSelector.vue          # 钱包选择弹窗组件
├── composables/
│   └── useWallet.js               # 钱包连接逻辑
├── stores/
│   └── wallet.js                  # 钱包状态管理
├── utils/
│   ├── walletConfig.js            # 钱包配置
│   ├── walletConnectors.js        # 钱包连接器
│   └── dynamicPaymentConfig.js    # 动态收款地址配置
└── views/
    └── PersonalCenter.vue         # 个人中心页面（集成钱包功能）
```

## 🔧 各文件功能说明

### 1. WalletSelector.vue
- 钱包选择弹窗组件
- 支持PC端和移动端响应式设计
- 自动筛选可用钱包
- 显示钱包安装状态

### 2. useWallet.js
- 钱包连接逻辑封装
- 错误处理和用户提示
- 事件监听和状态管理
- 交易发送和余额查询

### 3. wallet.js (Store)
- Pinia状态管理
- 钱包连接状态
- 账户信息和网络状态
- 本地存储持久化

### 4. walletConfig.js
- 支持的钱包配置
- 网络配置
- 代币合约配置
- 设备检测工具

### 5. walletConnectors.js
- MetaMask连接器
- TronLink连接器
- 统一的连接接口
- EVM和TRON网络支持

### 6. dynamicPaymentConfig.js
- 动态收款地址配置
- 从系统配置获取收款地址
- 支持TRC20和BSC网络
- 错误处理和默认地址

### 7. PersonalCenter.vue
- 集成钱包连接功能
- 支付界面和逻辑
- 钱包状态显示
- 用户交互处理

## 🚀 功能特性

### 支持的网络
- **BSC (Binance Smart Chain)**
  - MetaMask
  - Trust Wallet
  - WalletConnect

- **TRON**
  - TronLink

### 支持的代币
- **BSC**: USDT (BEP-20), USDC
- **TRC20**: USDT (TRC-20)

### 核心功能
- ✅ 多钱包连接
- ✅ 网络自动切换
- ✅ 实时余额查询
- ✅ 交易发送和跟踪
- ✅ 动态收款地址
- ✅ 错误处理
- ✅ 响应式设计

## 📝 使用流程

### 1. 钱包连接流程
```
用户点击"连接钱包" 
    ↓
打开WalletSelector弹窗
    ↓
用户选择网络和钱包
    ↓
调用相应的钱包连接器
    ↓
更新钱包状态和用户界面
```

### 2. 支付流程
```
检查钱包连接状态
    ↓
验证网络是否正确
    ↓
获取动态收款地址
    ↓
发送交易到钱包
    ↓
等待用户确认
    ↓
监控交易状态
    ↓
更新余额和界面
```

### 3. 收款地址配置
```
初始化时调用 initializePaymentConfig()
    ↓
从 languageConfigStore.config.wallet 获取配置
    ↓
解析 TRC20-USDT 和 BSC-USDT 地址
    ↓
更新 paymentAddresses 状态
    ↓
支付时调用 getRecipientAddress(network)
```

## 🔒 安全特性

- 钱包连接状态验证
- 网络切换确认
- 交易前余额检查
- 错误处理和用户提示
- 地址格式验证

## 📱 响应式支持

- 桌面端和移动端适配
- 不同设备钱包筛选
- 触摸友好的界面
- 移动端钱包跳转

## ⚙️ 配置说明

### 收款地址配置
在 `languageConfigStore.config.wallet` 中配置：
```javascript
{
  wallet: [
    {
      name: 'TRC20-USDT',
      address: 'T你的TRON收款地址'
    },
    {
      name: 'BSC-USDT',  // 或 'BEP20-USDT'
      address: '0x你的BSC收款地址'
    }
  ]
}
```

### 环境变量配置
```env
VITE_DEFAULT_BSC_ADDRESS=0x默认BSC地址
VITE_DEFAULT_TRON_ADDRESS=T默认TRON地址
```

这个整理后的系统更加简洁、高效，去除了冗余代码，保留了核心功能。