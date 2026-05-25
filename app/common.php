<?php

// +----------------------------------------------------------------------
// | 跳转返回信息
// +----------------------------------------------------------------------

// 成功提示
function c_success($data)
{
    return json([
        'code' => 200,
        'msg'  => $data,
    ]);
}

// 失败提示
function c_error($data)
{
    return json([
        'code' => 0,
        'msg'  => $data,
    ]);
}

// +----------------------------------------------------------------------
// | 提取内容摘要
// +----------------------------------------------------------------------

function html2text($data)
{
    $data       = strip_tags(htmlspecialchars_decode($data));
    $data       = trim($data);
    $patternArr = array('/\s/', '/ /');
    $replaceArr = array('', '');
    $data       = preg_replace($patternArr, $replaceArr, $data);
    $data       = preg_replace("/\&nbsp/i", '', $data);
    return mb_strcut($data, 0, 255, 'utf-8');
}

/**
 * 优化版安全提示方法（兼容 TP8）
 * @param string $msg  提示内容
 * @param string $url  跳转地址
 * @param int    $icon 图标类型
 * @param int    $time 显示时长
 */
function c_alert(string $msg = '', string $url = '', int $icon = 5, int $time = 1500): string
{
    // 安全过滤（替换原Html::encode）
    $msg = htmlspecialchars($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $msg = json_encode($msg, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

    // 生成URL（TP8推荐方式）
    $url = $url ? json_encode(url($url)->build(), JSON_HEX_TAG) : 'null';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统提示</title>
    <link rel="stylesheet" href="/static/js/layui/css/layui.css">
</head>
<body>
<script src="/static/js/layui/layui.js"></script>
<script>
layui.use('layer', function(){
    var layer = layui.layer;
    layer.msg({$msg}, {
        icon: {$icon},
        time: {$time},
        shade: 0.3
    }, function(){
        if ({$url}) location.href = {$url};
    });
});
</script>
</body>
</html>
HTML;
}


// +----------------------------------------------------------------------
// | 操作系统获取
// +----------------------------------------------------------------------

function get_os_info()
{
    $ua = request()->header('user-agent');
    $map = [
        'Windows' => 'Windows',
        'Macintosh' => 'MacOS',
        'Linux' => 'Linux',
        'Android' => 'Android',
        'iPhone' => 'iOS'
    ];

    foreach ($map as $key => $os) {
        if (stripos($ua, $key) !== false) return $os;
    }
    return 'Unknown';
}
