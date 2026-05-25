<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 验证码模型
 */
class VerifyCode extends Model
{
    // 表名
    protected $name = 'verify_code';

    // 主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = true;

    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 字段类型
    protected $cast = [
        'id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'expire_time' => 'datetime',
    ];

    // 类型常量
    const TYPE_SMS = 1;    // 短信验证码
    const TYPE_EMAIL = 2;  // 邮件验证码

    // 状态常量
    const STATUS_UNUSED = 0;   // 未使用
    const STATUS_USED = 1;     // 已使用
    const STATUS_EXPIRED = 2;  // 已过期

    /**
     * 生成验证码
     * @param int $length 验证码长度
     * @return string
     */
    public static function generateCode(int $length = 6): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= mt_rand(0, 9);
        }
        return $code;
    }

    /**
     * 发送验证码
     * @param string $target 目标（手机号或邮箱）
     * @param int $type 验证码类型
     * @param string $scene 场景
     * @return bool
     * @throws \Exception
     */
    public static function sendCode(string $target, int $type, string $scene): bool
    {
        // 检查发送频率
        self::checkSendFrequency($target, $type);

        // 生成验证码
        $code = self::generateCode();

        // 计算过期时间（默认5分钟）
        $expireTime = date('Y-m-d H:i:s', time() + 300);

        // 保存验证码记录
        $verifyCode = new self();
        $verifyCode->target = $target;
        $verifyCode->code = $code;
        $verifyCode->type = $type;
        $verifyCode->scene = $scene;
        $verifyCode->expire_time = $expireTime;
        $verifyCode->status = self::STATUS_UNUSED;
        $verifyCode->ip = request()->ip();

        if (!$verifyCode->save()) {
            throw new \Exception('验证码保存失败');
        }

        // 根据类型发送
        if ($type == self::TYPE_SMS) {
            // 发送短信
            return self::sendSms($target, $code, $scene);
        } elseif ($type == self::TYPE_EMAIL) {
            // 发送邮件
            return self::sendEmail($target, $code, $scene);
        }

        throw new \Exception('验证码类型错误');
    }

    /**
     * 验证验证码
     * @param string $target 目标
     * @param string $code 验证码
     * @param int $type 类型
     * @param string $scene 场景
     * @return bool
     * @throws \Exception
     */
    public static function verifyCode(string $target, string $code, int $type, string $scene): bool
    {
        // 查找验证码
        $verifyCode = self::where('target', $target)
            ->where('code', $code)
            ->where('type', $type)
            ->where('scene', $scene)
            ->where('status', self::STATUS_UNUSED)
            ->where('expire_time', '>', date('Y-m-d H:i:s'))
            ->order('create_time desc')
            ->find();

        if (!$verifyCode) {
            throw new \Exception('验证码无效或已过期');
        }

        // 标记为已使用
        $verifyCode->status = self::STATUS_USED;
        $verifyCode->use_time = date('Y-m-d H:i:s');
        $verifyCode->save();

        return true;
    }

    /**
     * 检查发送频率
     * @param string $target 目标
     * @param int $type 类型
     * @throws \Exception
     */
    private static function checkSendFrequency(string $target, int $type): void
    {
        // 检查1分钟内发送次数（最多1次）
        $oneMinuteAgo = date('Y-m-d H:i:s', time() - 60);
        $count = self::where('target', $target)
            ->where('type', $type)
            ->where('create_time', '>', $oneMinuteAgo)
            ->count();

        if ($count >= 1) {
            throw new \Exception('发送过于频繁，请1分钟后再试');
        }

        // 检查1小时内发送次数（最多5次）
        $oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
        $count = self::where('target', $target)
            ->where('type', $type)
            ->where('create_time', '>', $oneHourAgo)
            ->count();

        if ($count >= 5) {
            throw new \Exception('发送次数过多，请1小时后再试');
        }
    }

    /**
     * 发送短信
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $scene 场景
     * @return bool
     */
    private static function sendSms(string $phone, string $code, string $scene): bool
    {
        // 这里需要集成短信发送API
        // 例如：阿里云短信、腾讯云短信等
        // 暂时返回成功
        return true;
    }

    /**
     * 发送邮件
     * @param string $email 邮箱
     * @param string $code 验证码
     * @param string $scene 场景
     * @return bool
     */
    private static function sendEmail(string $email, string $code, string $scene): bool
    {
        // 使用之前创建的Mailer类发送邮件
        try {
            // 获取邮箱配置
            $category = \app\common\model\manage\ConfCategory::where('ename', 'email')->find()->toArray();
            if (empty($category)) {
                throw new \Exception('配置为空');
            }
            $configs = \app\common\model\manage\Conf::where('model', $category['id'])->where('status', 1)->select();
            $emailConfig = [];
            foreach ($configs as $config) {
                $emailConfig[$config['ename']] = $config['value'];
            }

            if (empty($emailConfig['emailaddress'])) {
                throw new \Exception('邮箱配置未设置');
            }

            // 构建邮件内容
            $subject = '验证码';
            $content = "<p>您的验证码是：<strong>{$code}</strong></p><p>验证码有效期为5分钟，请尽快使用。</p>";

            // 发送邮件
            \app\common\library\Mailer::create($emailConfig)
                ->to($email)
                ->subject($subject)
                ->content($content, true)
                ->send();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清理过期验证码
     */
    public static function cleanExpired(): void
    {
        self::where('expire_time', '<', date('Y-m-d H:i:s'))
            ->where('status', self::STATUS_UNUSED)
            ->update(['status' => self::STATUS_EXPIRED]);
    }
}
