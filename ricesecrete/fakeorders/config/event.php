<?php

return [
    'user.register' => [
        [app\event\User::class, 'register'],
        // ...其它事件处理函数...
    ],
    'recharge.success' => [
        [app\event\Recharge::class, 'success'],
        // ...其它事件处理函数...
    ],
];
