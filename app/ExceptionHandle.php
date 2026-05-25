<?php

declare(strict_types=1);

namespace app;

use app\common\service\upgrade\ErrorReporter;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 记录到日志
        parent::report($exception);

        // 上报错误到官方服务器
        $this->reportError($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    /**
     * 上报错误到官方服务器
     *
     * @access protected
     * @param  Throwable $e
     * @return void
     */
    protected function reportError(Throwable $e): void
    {
        // 检查是否需要上报
        if (!$this->shouldReport($e)) {
            return;
        }

        // 检查是否启用
        if (!ErrorReporter::isEnabled()) {
            return;
        }

        // 上报错误
        try {
            ErrorReporter::report($e);
        } catch (\Throwable $e) {
            // 忽略上报失败
        }
    }

    /**
     * 判断是否应该上报错误
     *
     * @access protected
     * @param  Throwable $e
     * @return bool
     */
    protected function shouldReport(Throwable $e): bool
    {
        // 忽略指定的异常类型
        foreach ($this->ignoreReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        // 验证异常通常不需要上报
        if ($e instanceof ValidateException) {
            return false;
        }

        return true;
    }
}
