<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\common\service\upgrade\FeedbackReporter;
use think\Request;
use think\Response;

/**
 * 反馈管理
 */
class Feedback extends Base
{
    protected $modelClass = null;
    protected $validateClass = null;

    /**
     * 反馈页面
     */
    public function index()
    {
        $typeOptions = FeedbackReporter::getTypeOptions();
        return view('feedback/index', [
            'typeOptions' => $typeOptions,
        ]);
    }

    /**
     * 提交反馈
     */
    public function submit(Request $request)
    {
        $type = $request->param('type', '');
        $content = $request->param('content', '');
        $contact = $request->param('contact', '');

        // 验证
        if (empty($type)) {
            return json(['code' => 400, 'msg' => '请选择反馈类型']);
        }

        if (empty($content)) {
            return json(['code' => 400, 'msg' => '请填写反馈内容']);
        }

        if (mb_strlen($content) < 10) {
            return json(['code' => 400, 'msg' => '反馈内容至少10个字符']);
        }

        if (mb_strlen($content) > 5000) {
            return json(['code' => 400, 'msg' => '反馈内容不能超过5000字符']);
        }

        // 提交反馈
        $result = FeedbackReporter::submit([
            'type' => $type,
            'content' => $content,
            'contact' => $contact,
        ]);

        if ($result) {
            return json(['code' => 200, 'msg' => '反馈提交成功，感谢您的支持！']);
        } else {
            return json(['code' => 500, 'msg' => '提交失败，请稍后重试']);
        }
    }

    /**
     * 获取反馈类型
     */
    public function getTypes()
    {
        return json([
            'code' => 200,
            'data' => FeedbackReporter::getTypeOptions(),
        ]);
    }
}
