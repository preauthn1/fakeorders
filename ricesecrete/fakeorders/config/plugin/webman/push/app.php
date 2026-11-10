<?php
return [
    'enable' => true,
    'websocket' => 'websocket://0.0.0.0:' . env('PUSH_WEBSOCKET_PORT', 3131),
    'api' => 'http://0.0.0.0:' . env('PUSH_API_PORT', 3232),
    'app_key' => 'f49fc68dc849bcc0dfe9cdb047d237ad',
    'app_secret' => 'REDACTED',
    'channel_hook' => 'http://127.0.0.1:' . env('HTTP_PORT', 8787) . '/plugin/webman/push/hook',
    'auth' => '/plugin/webman/push/auth'
];