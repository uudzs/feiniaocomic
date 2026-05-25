<?php

namespace app\common\validate\manage;

use think\Validate;

class Upload extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        // 文件验证规则 (最大10MB)
        'file'  => 'require|fileSize:10485760|fileExt:zip,rar,7z,pdf,doc,docx,xls,xlsx,txt,mp3,mp4,mov',
        // 图片验证规则 (最大1MB)
        'image' => 'require|fileSize:1024000|fileExt:jpg,png,gif,jpeg,webp|fileMime:image/jpeg,image/png,image/gif,image/webp'
    ];

    /**
     * 中文错误提示
     */
    protected $message = [
        // 通用必须上传
        'file.require'     => '请选择要上传的文件',
        'image.require'    => '请选择要上传的图片',

        // 文件相关
        'file.fileSize'    => '文件大小不能超过10MB',
        'file.fileExt'     => '文件类型不支持，支持格式：ZIP/RAR/7Z/PDF/Office文件/TXT/音视频',

        // 图片相关
        'image.fileSize'   => '图片大小不能超过1MB',
        'image.fileExt'    => '图片格式只支持JPG/PNG/GIF/WEBP',
        'image.fileMime'   => '图片MIME类型不合法'
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'file'  => ['file'],   // 文件上传验证
        'image' => ['image']   // 图片上传验证
    ];
}
