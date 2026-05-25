<?php

declare(strict_types=1);

namespace app\common\api;

use app\BaseController;
use think\facade\Request;
use think\facade\Cache;
use app\common\model\VerifyCode;
use app\common\model\manage\ConfCategory;
use app\common\model\manage\Conf;
use app\common\library\Jwt;

/**
 * 系统API控制器
 */
class System extends BaseController
{
    /**
     * 发送验证码
     * POST /api/system/sendcode
     */
    public function sendCode()
    {
        try {
            $target = Request::post('target', '');
            $type = Request::post('type/d', 0);
            $scene = Request::post('scene', '');

            if (empty($target)) {
                return $this->error('请输入目标地址');
            }

            if (!in_array($type, [VerifyCode::TYPE_SMS, VerifyCode::TYPE_EMAIL])) {
                return $this->error('验证码类型错误');
            }

            if (empty($scene)) {
                return $this->error('请指定场景');
            }

            // 验证目标格式
            if ($type == VerifyCode::TYPE_SMS) {
                if (!preg_match('/^1[3-9]\d{9}$/', $target)) {
                    return $this->error('手机号格式不正确');
                }
            } elseif ($type == VerifyCode::TYPE_EMAIL) {
                if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                    return $this->error('邮箱格式不正确');
                }
            }

            // 发送验证码
            $result = VerifyCode::sendCode($target, $type, $scene);

            if ($result) {
                return $this->success([], '验证码发送成功');
            } else {
                return $this->error('验证码发送失败', 500);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 验证验证码
     * POST /api/system/verify-code
     */
    public function verifyCode()
    {
        try {
            $target = Request::post('target', '');
            $code = Request::post('code', '');
            $type = Request::post('type/d', 0);
            $scene = Request::post('scene', '');

            if (empty($target) || empty($code)) {
                return $this->error('请输入目标地址和验证码');
            }

            if (!in_array($type, [VerifyCode::TYPE_SMS, VerifyCode::TYPE_EMAIL])) {
                return $this->error('验证码类型错误');
            }

            if (empty($scene)) {
                return $this->error('请指定场景');
            }

            // 验证验证码
            $result = VerifyCode::verifyCode($target, $code, $type, $scene);

            if ($result) {
                return $this->success([], '验证码验证成功');
            } else {
                return $this->error('验证码验证失败');
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 联系客服
     * POST /api/system/contact
     */
    public function contact()
    {
        try {
            $name = Request::post('name', '');
            $email = Request::post('email', '');
            $phone = Request::post('phone', '');
            $content = Request::post('content', '');

            if (empty($content)) {
                return $this->error('请输入联系内容');
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('邮箱格式不正确');
            }

            if (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
                return $this->error('手机号格式不正确');
            }
            // 这里可以保存联系记录到数据库
            // 也可以发送邮件通知客服
            return $this->success([], '提交成功，我们会尽快联系您');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 在线留言
     * POST /api/system/leave-message
     */
    public function leaveMessage()
    {
        try {
            $name = Request::post('name', '');
            $email = Request::post('email', '');
            $content = Request::post('content', '');

            if (empty($content)) {
                return $this->error('请输入留言内容');
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('邮箱格式不正确');
            }

            // 这里可以保存留言到数据库
            // 也可以发送邮件通知管理员
            return $this->success([], '留言成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取app基本信息
     * GET /api/system/appconfig
     * 
     * @return array
     */
    public function appConfig()
    {
        try {
            $config = [];
            $result = ConfCategory::where('status', 1)->whereIn('ename', 'basic,exterior,contact,other')->select()->toArray();
            foreach ($result as $key => $value) {
                $config[$value['ename']] = Conf::where('status', 1)->where('model', $value['id'])->column('value', 'ename');
            }
            $config = [
                'appName' => $config['basic']['sitename'] ?? '飞鸟漫画',
                'appLogo' => $this->formatImage($config['exterior']['logo']),
                'appStatus' => $config['basic']['siteon'] ? 1 : 0, // 1: 正常, 0: 禁用
                'appVersion' => '1.0.0',
                'appDescription' => $config['basic']['banquan'] ?? '一款专注于漫画阅读的应用',
                'disableMessage' => '系统维护中，暂时无法使用！',
                'contactEmail' => $config['contact']['email'] ?? '888@paheng.com',
                'officialWebsite' => $config['contact']['www'] ?? 'https://feiniao.paheng.net'
            ];
            return $this->success($config, '获取成功');;
        } catch (\Exception $e) {
            return $this->success([
                'appName' => '飞鸟漫画',
                'appLogo' => '',
                'appStatus' =>  0, // 1: 正常, 0: 禁用
                'appVersion' => '1.0.0',
                'appDescription' => '一款专注于漫画阅读的应用',
                'disableMessage' => '系统维护中，暂时无法使用！',
                'contactEmail' => '888@paheng.com',
                'officialWebsite' => 'https://feiniao.paheng.net'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * 获取匿名Token（用于未登录用户）
     * POST /api/system/get-token
     * 
     * @return array
     */
    public function getToken()
    {
        try {
            // 生成匿名标识
            $anonymousId = 'anon_' . uniqid() . '_' . time();
            
            // 构建匿名payload
            $payload = [
                'iss' => 'comic-system',
                'sub' => $anonymousId,
                'type' => 'anonymous',
                'data' => [
                    'anonymous_id' => $anonymousId,
                ]
            ];
            
            // 生成token
            $accessExpire = config('jwt.expire') ?: 604800; // 默认7天
            $refreshExpire = config('jwt.refresh_expire') ?: 2592000; // 默认30天
            
            $accessToken = Jwt::encode($payload, '', $accessExpire);
            $refreshToken = Jwt::encode($payload, '', $refreshExpire);
            
            // 缓存refreshToken，用于后续验证
            $cacheKey = 'refresh_token:' . $anonymousId;
            Cache::set($cacheKey, $refreshToken, $refreshExpire);
            
            return $this->success([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $accessExpire,
                'token_type' => 'Bearer'
            ], '获取成功');
            
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
