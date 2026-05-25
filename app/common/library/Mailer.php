<?php

declare(strict_types=1);

namespace app\common\library;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * 邮件发送类
 */
class Mailer
{
    private $mail;
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->mail = new PHPMailer(true);

        $this->initConfig();
    }

    private function initConfig()
    {
        if (empty($this->config)) {
            throw new \Exception('邮箱配置不能为空');
        }

        $requiredConfig = ['emailaddress', 'smtpserver', 'smtpport', 'smtpusername', 'smtppassword'];
        foreach ($requiredConfig as $key) {
            if (empty($this->config[$key])) {
                throw new \Exception('邮箱配置不完整：缺少 ' . $key);
            }
        }

        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtpserver'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtpusername'];
            $this->mail->Password = $this->config['smtppassword'];
            $this->mail->SMTPSecure = isset($this->config['smtpssl']) && $this->config['smtpssl'] ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = (int)$this->config['smtpport'];

            $fromName = $this->config['sendername'] ?? '系统通知';
            $this->mail->setFrom($this->config['emailaddress'], $fromName);

            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            throw new \Exception('邮件配置初始化失败：' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new \Exception('邮件配置初始化失败：' . $e->getMessage());
        }
    }

    public function to(string $email, string $name = ''): self
    {
        $this->mail->addAddress($email, $name);
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function content(string $content, bool $isHtml = true): self
    {
        $this->mail->isHTML($isHtml);
        $this->mail->Body = $content;
        $this->mail->AltBody = strip_tags($content);
        return $this;
    }

    public function attach(string $filePath, string $name = ''): self
    {
        $this->mail->addAttachment($filePath, $name);
        return $this;
    }

    public function send(): bool
    {
        try {
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            throw new \Exception('邮件发送失败：' . $e->getMessage());
        }
    }

    public static function create(array $config): self
    {
        return new self($config);
    }
}
