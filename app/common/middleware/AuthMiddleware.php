<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\library\Jwt;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 用户鉴权中间件
 */
class AuthMiddleware
{
    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // 获取Token
        $token = $this->getToken($request);

        if (empty($token)) {
            return $this->error('缺少访问令牌', 401);
        }
       
        // 验证Token
        $userData = $this->verifyToken($token);
        
        if (empty($userData)) {
            return $this->error('访问令牌无效或已过期', 401);
        }
        
        // 将用户信息注入请求
        $request->user = $userData;
        $request->userId = $userData['user_id'] ?? 0;
        
        return $next($request);
    }
    
    /**
     * 获取Token
     */
    private function getToken(Request $request): string
    {
        // 从Header获取
        $authorization = $request->header('Authorization');
        if (!empty($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }
        
        // 从参数获取
        return $request->param('token', '');
    }
    
    /**
     * 验证Token
     */
    private function verifyToken(string $token): ?array
    {
        try {
            // 使用JWT库验证
            $payload = Jwt::decode($token);
            
            if (empty($payload)) {
                return null;
            }
            
            // 检查Token是否在黑名单中
            $blacklistKey = 'token_blacklist_' . $payload['jti'];
            if (Cache::get($blacklistKey)) {
                return null;
            }
            
            return $payload['data'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * 返回错误响应
     */
    private function error(string $message, int $code = 401): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time(),
            'request_id' => uniqid('req_')
        ], $code);
    }
}