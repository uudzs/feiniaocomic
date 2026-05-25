<?php
declare(strict_types=1);

namespace app\common\library;

/**
 * JWT 工具类
 * 用于生成和验证JSON Web Token
 */
class Jwt
{
    /**
     * 头部
     */
    private static array $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    /**
     * 生成Token
     * 
     * @param array $payload 载荷数据
     * @param string $secret 密钥
     * @param int $expire 过期时间（秒），默认7天
     * @return string
     */
    public static function encode(array $payload, string $secret = '', int $expire = 604800): string
    {
        if (empty($secret)) {
            $secret = config('jwt.secret', 'your-secret-key');
        }
        
        // 添加标准声明
        $payload['iat'] = $payload['iat'] ?? time();
        $payload['exp'] = $payload['exp'] ?? (time() + $expire);
        $payload['jti'] = $payload['jti'] ?? uniqid();
        
        // 编码头部和载荷
        $base64UrlHeader = self::base64UrlEncode(json_encode(self::$header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        // 生成签名
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * 解析Token
     * 
     * @param string $token JWT Token
     * @param string $secret 密钥
     * @return array|null
     */
    public static function decode(string $token, string $secret = ''): ?array
    {
        if (empty($secret)) {
            $secret = config('jwt.secret', 'your-secret-key');
        }
        
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
        
        // 验证签名
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);
        $expectedSignature = self::base64UrlEncode($signature);
        
        if ($base64UrlSignature !== $expectedSignature) {
            return null;
        }
        
        // 解析载荷
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        if (!is_array($payload)) {
            return null;
        }
        
        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * 验证Token是否有效
     * 
     * @param string $token JWT Token
     * @param string $secret 密钥
     * @return bool
     */
    public static function verify(string $token, string $secret = ''): bool
    {
        return self::decode($token, $secret) !== null;
    }
    
    /**
     * 从Token中获取用户ID
     * 
     * @param string $token JWT Token
     * @param string $secret 密钥
     * @return int|null
     */
    public static function getUserId(string $token, string $secret = ''): ?int
    {
        $payload = self::decode($token, $secret);
        
        if ($payload === null) {
            return null;
        }
        
        return $payload['data']['user_id'] ?? null;
    }
    
    /**
     * 刷新Token
     * 
     * @param string $token 原Token
     * @param string $secret 密钥
     * @param int $expire 新Token的过期时间
     * @return string|null
     */
    public static function refresh(string $token, string $secret = '', int $expire = 604800): ?string
    {
        $payload = self::decode($token, $secret);
        
        if ($payload === null) {
            return null;
        }
        
        // 移除过期时间，让encode重新生成
        unset($payload['exp']);
        
        return self::encode($payload, $secret, $expire);
    }
    
    /**
     * Base64 URL 编码
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL 解码
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
