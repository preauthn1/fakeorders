# Akasdao 移动应用开发指南

你的Vue项目现在已经配置好了Capacitor，可以打包成iOS和Android应用！

## 📱 已完成的配置

✅ 安装了Capacitor核心依赖  
✅ 初始化了Capacitor配置  
✅ 添加了iOS和Android平台  
✅ 配置了应用信息和启动画面  
✅ 更新了package.json脚本  
✅ 构建并同步了移动应用

## 🚀 可用的npm脚本

```bash
# 构建并同步到移动平台
npm run cap:build

# 打开Android Studio开发Android应用
npm run cap:android

# 打开Xcode开发iOS应用
npm run cap:ios

# 只同步代码到平台（不构建）
npm run cap:sync

# 在Android设备/模拟器上运行
npm run cap:run:android

# 在iOS设备/模拟器上运行
npm run cap:run:ios
```

## 📋 开发和测试步骤

### Android开发
1. 安装Android Studio
2. 运行 `npm run cap:android` 打开项目
3. 在Android Studio中构建和运行应用

### iOS开发（需要Mac）
1. 安装Xcode
2. 运行 `npm run cap:ios` 打开项目
3. 在Xcode中构建和运行应用

## 🎨 自定义应用图标和启动画面

### 应用图标
- Android: 替换 `android/app/src/main/res/mipmap-*/ic_launcher.png`
- iOS: 替换 `ios/App/App/Assets.xcassets/AppIcon.appiconset/` 中的图标

### 启动画面
- Android: 替换 `android/app/src/main/res/drawable-*/splash.png`
- iOS: 在Xcode中编辑 `LaunchScreen.storyboard`

## 🔧 配置文件

主要配置在 `capacitor.config.json`:
- `appId`: com.akasdao.app
- `appName`: Akasdao
- `webDir`: dist

## 📦 发布应用

### Android
1. 在Android Studio中生成签名APK或AAB
2. 上传到Google Play Store

### iOS
1. 在Xcode中配置证书和描述文件
2. Archive并上传到App Store Connect

## 🛠 开发工作流

1. 修改Vue代码
2. 运行 `npm run cap:build` 构建并同步
3. 在相应平台上测试应用
4. 重复上述过程

## 📱 设备功能

Capacitor支持访问：
- 相机和相册
- GPS定位
- 推送通知
- 设备信息
- 网络状态
- 文件系统
- 等等...

需要时可以安装相应的Capacitor插件。