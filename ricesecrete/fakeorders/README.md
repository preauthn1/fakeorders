[comment]: <> (系统启动)
php start.php start

[comment]: <> (开启守护进程)
php start.php start -d

[comment]: <> (脚本多语言翻译)
php webman lang

# 本系统集成了think-orm

# 使用了webman/admin, webman/redis,webman/push等插件

# 数据连接配置 config/thinkorm.php ,plugin/config/thinkorm.php

# 定时任务 app/process/FileExport.php 用于文件导出处理

# 定时任务 app/process/MinuteTasks.php 用于分钟任务处理

# 定时任务 app/process/SecondTasks.php 用于秒任务处理

# 充值事务app/event/Recharge.php 用于处理用户充值回调（充值返佣等）
# 用户事务app/event/User.php 用于处理用户注册后逻辑关系