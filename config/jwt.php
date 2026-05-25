<?php

return [
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),
    'expire' => env('JWT_EXPIRE', 604800), // 默认7天
    'refresh_expire' => env('JWT_REFRESH_EXPIRE', 2592000), // 刷新Token有效期30天
];
