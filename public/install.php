<?php
// 启动会话
session_start();

// 定义常量
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// 检查是否已安装
$installLockFile = CONFIG_PATH . '/install.lock';
if (file_exists($installLockFile)) {
    die('系统已安装过，如果需要重新安装，请手动删除 ' . $installLockFile . ' 文件');
}

// 引入ThinkPHP框架
require ROOT_PATH . '/vendor/autoload.php';

// 先处理action请求
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'installTable') {
        // 处理AJAX安装请求
        $config = $_SESSION['install_config'];
        
        // 连接数据库
        try {
            $pdo = new PDO(
                'mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'],
                $config['db_user'],
                $config['db_password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '数据库连接失败：' . $e->getMessage()]);
            exit;
        }
        
        // 读取SQL文件
        $sqlFile = ROOT_PATH . '/database/install.sql';
        $sqlContent = file_get_contents($sqlFile);
        
        // 替换表前缀
        $sqlContent = str_replace('__prefix__', $config['db_prefix'], $sqlContent);
        
        // 解析SQL语句
        $sqlStatements = parseSql($sqlContent);
        
        // 执行指定表的SQL语句
        $tableName = $_POST['table'];
        $success = true;
        $message = '';
        
        try {
            foreach ($sqlStatements as $statement) {
                if (
                    strpos($statement, 'CREATE TABLE `' . $config['db_prefix'] . $tableName . '`') !== false ||
                    (strpos($statement, 'INSERT INTO `' . $config['db_prefix'] . $tableName . '`') !== false)
                ) {
                    $pdo->exec($statement);
                }
            }
            
            // 如果是admin表，更新管理员信息
            if ($tableName == 'admin' && !empty($config['admin_username']) && !empty($config['admin_password'])) {
                // 密码加密：sha1(sha1(PASSWORD_SALT . password))
                // 使用Admin模型中的密码盐常量
                $passwordSalt = \app\common\model\manage\Admin::PASSWORD_SALT;
                $passwordHash = sha1(sha1($passwordSalt . $config['admin_password']));
                $sql = "UPDATE `" . $config['db_prefix'] . "admin` SET `uname` = ?, `password` = ? WHERE `id` = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$config['admin_username'], $passwordHash]);
            }
        } catch (PDOException $e) {
            $success = false;
            $message = $e->getMessage();
        }
        
        // 写入.env配置文件
        if ($success && $tableName == 'admin') {
            $envContent = "APP_DEBUG=false
            
[DATABASE]
type=mysql
hostname=" . $config['db_host'] . "
hostport=" . $config['db_port'] . "
database=" . $config['db_name'] . "
username=" . $config['db_user'] . "
password=" . $config['db_password'] . "
prefix=" . $config['db_prefix'] . "
charset=utf8mb4

[REDIS]
host=127.0.0.1
port=6379
password=
select=0

[CACHE]
default=file
file=runtime/cache

[SESSION]
driver=file

[LANG]
lang_switch_on=true
default_lang=zh-cn
lang_allow_list=zh-cn,en-us

[LOG]
type=file
level=error";
            
            file_put_contents(ROOT_PATH . '/.env', $envContent);
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } elseif ($_GET['action'] == 'finishInstall') {
        // 所有表安装完成后，创建安装锁文件
        $installLockFile = CONFIG_PATH . '/install.lock';
        $lockContent = "<?php
// 安装锁文件
// 安装时间: " . date('Y-m-d H:i:s') . "
// 安装标识: " . md5(uniqid() . mt_rand()) . "\n";
        file_put_contents($installLockFile, $lockContent);
        
        echo json_encode(['success' => true, 'message' => '安装完成']);
        exit;
    } elseif ($_GET['action'] == 'testDb') {
        // 处理数据库连接测试
        $dbHost = $_POST['db_host'];
        $dbPort = $_POST['db_port'];
        $dbName = $_POST['db_name'];
        $dbUser = $_POST['db_user'];
        $dbPassword = $_POST['db_password'];
        
        try {
            // 尝试连接数据库
            $pdo = new PDO(
                'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName,
                $dbUser,
                $dbPassword,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // 保存测试成功状态到会话
            $_SESSION['db_test_success'] = true;
            echo json_encode(['success' => true, 'message' => '连接成功']);
        } catch (PDOException $e) {
            $_SESSION['db_test_success'] = false;
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// 安装步骤
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// 安装流程处理
switch ($step) {
    case 1:
        // 第一步：安装协议与版权申明
        showAgreement();
        break;
    case 2:
        // 第二步：运行环境检测
        checkEnvironment();
        break;
    case 3:
        // 第三步：填写配置信息
        showConfigForm();
        break;
    case 4:
        // 第四步：执行安装
        install();
        break;
    default:
        // 默认跳转到第一步
        header('Location: install.php?step=1');
        exit;
}

// 第一步：安装协议与版权申明
function showAgreement()
{
?>
    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>飞鸟漫画 - 安装协议</title>
        <link rel="stylesheet" href="static/js/layui/css/layui.css">
        <style>
            body {
                font-family: 'Microsoft YaHei', Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }

            .install-container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .install-header {
                background-color: #1E9FFF;
                color: #fff;
                padding: 20px;
                text-align: center;
            }

            .install-header h1 {
                margin: 0;
                font-size: 24px;
            }

            .install-body {
                padding: 30px;
            }

            .install-step {
                display: flex;
                margin-bottom: 30px;
            }

            .step-item {
                flex: 1;
                text-align: center;
                position: relative;
            }

            .step-item::after {
                content: '';
                position: absolute;
                top: 15px;
                right: -50%;
                width: 100%;
                height: 2px;
                background-color: #e0e0e0;
                z-index: 1;
            }

            .step-item:last-child::after {
                display: none;
            }

            .step-item.active .step-number {
                background-color: #1E9FFF;
                color: #fff;
            }

            .step-item.active::after {
                background-color: #1E9FFF;
            }

            .step-number {
                display: inline-block;
                width: 30px;
                height: 30px;
                line-height: 30px;
                border-radius: 50%;
                background-color: #e0e0e0;
                color: #999;
                font-weight: bold;
                margin-bottom: 10px;
                z-index: 2;
                position: relative;
            }

            .step-title {
                font-size: 14px;
                color: #666;
            }

            .step-item.active .step-title {
                color: #1E9FFF;
                font-weight: bold;
            }

            .agreement-content {
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                padding: 20px;
                height: 400px;
                overflow-y: auto;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .agreement-content h3 {
                margin-top: 0;
                color: #333;
            }

            .agreement-content p {
                margin: 10px 0;
                color: #666;
            }

            .install-footer {
                text-align: center;
                padding: 20px;
                border-top: 1px solid #e0e0e0;
            }

            .layui-btn {
                padding: 0 30px;
                height: 40px;
                line-height: 40px;
                font-size: 16px;
            }
        </style>
    </head>

    <body>
        <div class="install-container">
            <div class="install-header">
                <h1>飞鸟漫画系统安装</h1>
            </div>
            <div class="install-body">
                <div class="install-step">
                    <div class="step-item active">
                        <div class="step-number">1</div>
                        <div class="step-title">安装协议</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-title">环境检测</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-title">配置信息</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-title">执行安装</div>
                    </div>
                </div>

                <div class="agreement-content">
                    <h3>安装协议与版权申明</h3>
                    <p>欢迎使用飞鸟漫画系统！本系统是基于ThinkPHP8框架开发的漫画管理系统，为用户提供漫画内容管理、用户管理等功能。</p>
                    <p><strong>一、协议条款</strong></p>
                    <p>1. 本协议是您与飞鸟漫画系统之间的法律协议。</p>
                    <p>2. 您必须同意本协议的所有条款，才能继续安装和使用本系统。</p>
                    <p>3. 本系统仅供合法用途使用，不得用于任何违法活动。</p>
                    <p><strong>二、版权申明</strong></p>
                    <p>1. 飞鸟漫画系统的版权归原作者所有。</p>
                    <p>2. 您可以自由使用、修改本系统，但必须保留原作者的版权信息。</p>
                    <p>3. 未经授权，不得将本系统用于商业用途。</p>
                    <p><strong>三、免责声明</strong></p>
                    <p>1. 本系统按"原样"提供，不提供任何形式的保证。</p>
                    <p>2. 原作者不对因使用本系统而导致的任何损失负责。</p>
                    <p>3. 您在使用本系统时，应遵守相关法律法规。</p>
                    <p><strong>四、其他</strong></p>
                    <p>1. 本协议的最终解释权归原作者所有。</p>
                    <p>2. 如有任何疑问，请联系原作者。</p>
                </div>

                <form action="install.php?step=2" method="post" class="layui-form">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <input type="checkbox" name="agree" id="agree" lay-skin="primary" title="我已阅读并同意安装协议">
                    </div>
                    <div class="install-footer">
                        <button type="submit" class="layui-btn layui-btn-primary" disabled id="submitBtn">继续安装</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="static/js/layui/layui.js"></script>
        <script>
            layui.use(['form'], function() {
                var form = layui.form;

                // 初始化表单
                form.render();

                // 监听协议勾选
                form.on('checkbox', function(data) {
                    var submitBtn = document.getElementById('submitBtn');
                    if (data.elem.checked) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('layui-btn-primary');
                        submitBtn.classList.add('layui-btn-normal');
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('layui-btn-primary');
                        submitBtn.classList.remove('layui-btn-normal');
                    }
                });
            });
        </script>
    </body>

    </html>
<?php
}

// 第二步：运行环境检测
function checkEnvironment()
{
    // 检查是否同意协议
    if (!isset($_POST['agree'])) {
        header('Location: install.php?step=1');
        exit;
    }

    // 环境检测项
    $checkItems = [
        // PHP版本
        [
            'name' => 'PHP版本',
            'current' => PHP_VERSION,
            'required' => '7.4.0',
            'result' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'type' => 'required'
        ],
        // MySQL扩展
        [
            'name' => 'MySQL扩展',
            'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装',
            'required' => '必须',
            'result' => extension_loaded('pdo_mysql'),
            'type' => 'required'
        ],
        // GD扩展
        [
            'name' => 'GD扩展',
            'current' => extension_loaded('gd') ? '已安装' : '未安装',
            'required' => '必须',
            'result' => extension_loaded('gd'),
            'type' => 'required'
        ],
        // JSON扩展
        [
            'name' => 'JSON扩展',
            'current' => extension_loaded('json') ? '已安装' : '未安装',
            'required' => '必须',
            'result' => extension_loaded('json'),
            'type' => 'required'
        ],
        // 目录权限
        [
            'name' => 'runtime目录',
            'current' => is_writable(ROOT_PATH . '/runtime') ? '可写' : '不可写',
            'required' => '必须',
            'result' => is_writable(ROOT_PATH . '/runtime'),
            'type' => 'required'
        ],
        [
            'name' => 'config目录',
            'current' => is_writable(ROOT_PATH . '/config') ? '可写' : '不可写',
            'required' => '必须',
            'result' => is_writable(ROOT_PATH . '/config'),
            'type' => 'required'
        ],
        [
            'name' => 'public/static目录',
            'current' => is_writable(ROOT_PATH . '/public/static') ? '可写' : '不可写',
            'required' => '必须',
            'result' => is_writable(ROOT_PATH . '/public/static'),
            'type' => 'required'
        ],
        [
            'name' => '.env文件',
            'current' => (file_exists(ROOT_PATH . '/.env') ? is_writable(ROOT_PATH . '/.env') : is_writable(ROOT_PATH)) ? '可写' : '不可写',
            'required' => '必须',
            'result' => (file_exists(ROOT_PATH . '/.env') ? is_writable(ROOT_PATH . '/.env') : is_writable(ROOT_PATH)),
            'type' => 'required'
        ],
        // 可选扩展
        [
            'name' => 'Redis扩展',
            'current' => extension_loaded('redis') ? '已安装' : '未安装',
            'required' => '可选',
            'result' => extension_loaded('redis'),
            'type' => 'optional'
        ],
        [
            'name' => 'Memcached扩展',
            'current' => extension_loaded('memcached') ? '已安装' : '未安装',
            'required' => '可选',
            'result' => extension_loaded('memcached'),
            'type' => 'optional'
        ]
    ];

    // 检查是否所有必选项都通过
    $allPass = true;
    foreach ($checkItems as $item) {
        if ($item['type'] == 'required' && !$item['result']) {
            $allPass = false;
            break;
        }
    }

?>
    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>飞鸟漫画 - 环境检测</title>
        <link rel="stylesheet" href="static/js/layui/css/layui.css">
        <style>
            body {
                font-family: 'Microsoft YaHei', Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }

            .install-container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .install-header {
                background-color: #1E9FFF;
                color: #fff;
                padding: 20px;
                text-align: center;
            }

            .install-header h1 {
                margin: 0;
                font-size: 24px;
            }

            .install-body {
                padding: 30px;
            }

            .install-step {
                display: flex;
                margin-bottom: 30px;
            }

            .step-item {
                flex: 1;
                text-align: center;
                position: relative;
            }

            .step-item::after {
                content: '';
                position: absolute;
                top: 15px;
                right: -50%;
                width: 100%;
                height: 2px;
                background-color: #e0e0e0;
                z-index: 1;
            }

            .step-item:last-child::after {
                display: none;
            }

            .step-item.active .step-number {
                background-color: #1E9FFF;
                color: #fff;
            }

            .step-item.active::after {
                background-color: #1E9FFF;
            }

            .step-item.completed .step-number {
                background-color: #5FB878;
                color: #fff;
            }

            .step-item.completed::after {
                background-color: #5FB878;
            }

            .step-number {
                display: inline-block;
                width: 30px;
                height: 30px;
                line-height: 30px;
                border-radius: 50%;
                background-color: #e0e0e0;
                color: #999;
                font-weight: bold;
                margin-bottom: 10px;
                z-index: 2;
                position: relative;
            }

            .step-title {
                font-size: 14px;
                color: #666;
            }

            .step-item.active .step-title {
                color: #1E9FFF;
                font-weight: bold;
            }

            .step-item.completed .step-title {
                color: #5FB878;
            }

            .check-list {
                margin-bottom: 30px;
            }

            .check-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f0;
            }

            .check-item:last-child {
                border-bottom: none;
            }

            .check-item .name {
                font-weight: 500;
            }

            .check-item .status {
                font-size: 14px;
            }

            .check-item .status.pass {
                color: #5FB878;
            }

            .check-item .status.fail {
                color: #FF5722;
            }

            .check-item .status.optional {
                color: #FFB800;
            }

            .install-footer {
                text-align: center;
                padding: 20px;
                border-top: 1px solid #e0e0e0;
            }

            .layui-btn {
                padding: 0 30px;
                height: 40px;
                line-height: 40px;
                font-size: 16px;
            }
        </style>
    </head>

    <body>
        <div class="install-container">
            <div class="install-header">
                <h1>飞鸟漫画系统安装</h1>
            </div>
            <div class="install-body">
                <div class="install-step">
                    <div class="step-item completed">
                        <div class="step-number">1</div>
                        <div class="step-title">安装协议</div>
                    </div>
                    <div class="step-item active">
                        <div class="step-number">2</div>
                        <div class="step-title">环境检测</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-title">配置信息</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-title">执行安装</div>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px;">环境检测结果</h3>
                <div class="check-list">
                    <?php foreach ($checkItems as $item): ?>
                        <div class="check-item">
                            <span class="name"><?php echo $item['name']; ?></span>
                            <span class="status <?php echo $item['result'] ? 'pass' : ($item['type'] == 'optional' ? 'optional' : 'fail'); ?>">
                                <?php echo $item['current']; ?>
                                <?php if ($item['type'] == 'required'): ?>
                                    <span style="margin-left: 10px;">(<?php echo $item['required']; ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="install-footer">
                    <a href="install.php?step=1" class="layui-btn layui-btn-primary">上一步</a>
                    <?php if ($allPass): ?>
                        <a href="install.php?step=3" class="layui-btn layui-btn-normal">下一步</a>
                    <?php else: ?>
                        <button class="layui-btn layui-btn-primary" disabled>环境检测未通过</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
}

// 第三步：填写配置信息
function showConfigForm()
{
?>
    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>飞鸟漫画 - 配置信息</title>
        <link rel="stylesheet" href="static/js/layui/css/layui.css">
        <style>
            body {
                font-family: 'Microsoft YaHei', Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }

            .install-container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .install-header {
                background-color: #1E9FFF;
                color: #fff;
                padding: 20px;
                text-align: center;
            }

            .install-header h1 {
                margin: 0;
                font-size: 24px;
            }

            .install-body {
                padding: 30px;
            }

            .install-step {
                display: flex;
                margin-bottom: 30px;
            }

            .step-item {
                flex: 1;
                text-align: center;
                position: relative;
            }

            .step-item::after {
                content: '';
                position: absolute;
                top: 15px;
                right: -50%;
                width: 100%;
                height: 2px;
                background-color: #e0e0e0;
                z-index: 1;
            }

            .step-item:last-child::after {
                display: none;
            }

            .step-item.active .step-number {
                background-color: #1E9FFF;
                color: #fff;
            }

            .step-item.active::after {
                background-color: #1E9FFF;
            }

            .step-item.completed .step-number {
                background-color: #5FB878;
                color: #fff;
            }

            .step-item.completed::after {
                background-color: #5FB878;
            }

            .step-number {
                display: inline-block;
                width: 30px;
                height: 30px;
                line-height: 30px;
                border-radius: 50%;
                background-color: #e0e0e0;
                color: #999;
                font-weight: bold;
                margin-bottom: 10px;
                z-index: 2;
                position: relative;
            }

            .step-title {
                font-size: 14px;
                color: #666;
            }

            .step-item.active .step-title {
                color: #1E9FFF;
                font-weight: bold;
            }

            .step-item.completed .step-title {
                color: #5FB878;
            }

            .config-form {
                margin-bottom: 30px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #333;
            }

            .form-group input {
                width: 100%;
                padding: 10px;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                font-size: 14px;
            }

            .form-group input:focus {
                outline: none;
                border-color: #1E9FFF;
                box-shadow: 0 0 0 2px rgba(30, 159, 255, 0.2);
            }

            .form-group .layui-form-item {
                margin-bottom: 0;
            }

            .install-footer {
                text-align: center;
                padding: 20px;
                border-top: 1px solid #e0e0e0;
            }

            .layui-btn {
                padding: 0 30px;
                height: 40px;
                line-height: 40px;
                font-size: 16px;
            }

            .error-message {
                color: #FF5722;
                font-size: 12px;
                margin-top: 5px;
            }

            .layui-form-label {
                width: 100px;
            }

            .layui-input-block {
                margin-left: 130px;
            }
        </style>
    </head>

    <body>
        <div class="install-container">
            <div class="install-header">
                <h1>飞鸟漫画系统安装</h1>
            </div>
            <div class="install-body">
                <div class="install-step">
                    <div class="step-item completed">
                        <div class="step-number">1</div>
                        <div class="step-title">安装协议</div>
                    </div>
                    <div class="step-item completed">
                        <div class="step-number">2</div>
                        <div class="step-title">环境检测</div>
                    </div>
                    <div class="step-item active">
                        <div class="step-number">3</div>
                        <div class="step-title">配置信息</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-title">执行安装</div>
                    </div>
                </div>

                <form action="install.php?step=4" method="post" class="config-form layui-form" id="configForm">
                    <h3 style="margin-bottom: 20px;">数据库配置</h3>
                    <div style="background-color: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
                        <div class="layui-form-item">
                            <div class="layui-row">
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">数据库地址</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="db_host" id="db_host" value="localhost" required lay-verify="required" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">数据库端口</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="db_port" id="db_port" value="3306" required lay-verify="required" class="layui-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-row">
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">数据库名称</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="db_name" id="db_name" required lay-verify="required" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">数据库用户名</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="db_user" id="db_user" required lay-verify="required" class="layui-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-row">
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">数据库密码</label>
                                    <div class="layui-input-block">
                                        <input type="password" name="db_password" id="db_password" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">表前缀</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="db_prefix" id="db_prefix" value="fn_" required lay-verify="required" class="layui-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn layui-btn-primary" id="testDbBtn">测试数据库连接</button>
                                <div class="error-message" id="dbTestResult" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <h3 style="margin-bottom: 20px;">管理员配置</h3>
                    <div class="layui-form-item">
                        <div class="layui-row">
                            <div class="layui-col-md6">
                                <label class="layui-form-label">管理员用户名</label>
                                <div class="layui-input-block">
                                    <input type="text" name="admin_username" id="admin_username" value="admin" required lay-verify="required" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-row">
                            <div class="layui-col-md6">
                                <label class="layui-form-label">管理员密码</label>
                                <div class="layui-input-block">
                                    <input type="password" name="admin_password" id="admin_password" required lay-verify="required" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-row">
                            <div class="layui-col-md6">
                                <label class="layui-form-label">确认密码</label>
                                <div class="layui-input-block">
                                    <input type="password" name="admin_password_confirm" id="admin_password_confirm" required lay-verify="required" class="layui-input">
                                </div>
                            </div>
                        </div>
                        <div class="error-message" id="passwordError" style="margin-left: 130px;"></div>
                    </div>

                    <div class="install-footer">
                        <a href="install.php?step=2" class="layui-btn layui-btn-primary">上一步</a>
                        <button type="submit" class="layui-btn layui-btn-normal">下一步</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="static/js/layui/layui.js"></script>
        <script>
            layui.use(['form', 'jquery'], function() {
                var form = layui.form;
                var $ = layui.jquery;
                var dbTestSuccess = false;

                // 初始化表单
                form.render();

                // 密码确认验证
                $('#admin_password_confirm').on('blur', function() {
                    var password = $('#admin_password').val();
                    var confirmPassword = $(this).val();
                    var errorDiv = $('#passwordError');

                    if (password !== confirmPassword) {
                        errorDiv.text('两次输入的密码不一致');
                    } else {
                        errorDiv.text('');
                    }
                });

                // 数据库连接测试
                $('#testDbBtn').on('click', function() {
                    var btn = $(this);
                    var originalText = btn.text();
                    btn.text('测试中...').attr('disabled', true);

                    var dbHost = $('#db_host').val();
                    var dbPort = $('#db_port').val();
                    var dbName = $('#db_name').val();
                    var dbUser = $('#db_user').val();
                    var dbPassword = $('#db_password').val();

                    $.ajax({
                        url: 'install.php?action=testDb',
                        type: 'POST',
                        data: {
                            db_host: dbHost,
                            db_port: dbPort,
                            db_name: dbName,
                            db_user: dbUser,
                            db_password: dbPassword
                        },
                        dataType: 'json',
                        success: function(response) {
                            var resultDiv = $('#dbTestResult');
                            if (response.success) {
                                resultDiv.css('color', '#5FB878').text('数据库连接成功！');
                                dbTestSuccess = true;
                            } else {
                                resultDiv.css('color', '#FF5722').text('数据库连接失败：' + response.message);
                                dbTestSuccess = false;
                            }
                            btn.text(originalText).attr('disabled', false);
                        },
                        error: function() {
                            $('#dbTestResult').css('color', '#FF5722').text('测试请求失败，请重试');
                            dbTestSuccess = false;
                            btn.text(originalText).attr('disabled', false);
                        }
                    });
                });

                // 表单提交验证
                $('#configForm').on('submit', function() {
                    var password = $('#admin_password').val();
                    var confirmPassword = $('#admin_password_confirm').val();

                    if (password !== confirmPassword) {
                        $('#passwordError').text('两次输入的密码不一致');
                        return false;
                    }

                    if (!dbTestSuccess) {
                        $('#dbTestResult').css('color', '#FF5722').text('请先测试数据库连接并确保连接成功！');
                        return false;
                    }

                    return true;
                });
            });
        </script>
    </body>

    </html>
<?php
}

// 第四步：执行安装
function install()
{
    // 验证数据库测试是否成功
    if (!isset($_SESSION['db_test_success']) || !$_SESSION['db_test_success']) {
        header('Location: install.php?step=3');
        exit;
    }

    // 验证配置信息
    if (!isset($_POST['db_host']) || !isset($_POST['db_name']) || !isset($_POST['db_user'])) {
        header('Location: install.php?step=3');
        exit;
    }

    // 获取配置信息
    $config = [
        'db_host' => $_POST['db_host'],
        'db_port' => $_POST['db_port'],
        'db_name' => $_POST['db_name'],
        'db_user' => $_POST['db_user'],
        'db_password' => $_POST['db_password'],
        'db_prefix' => $_POST['db_prefix'],
        'admin_username' => $_POST['admin_username'],
        'admin_password' => $_POST['admin_password']
    ];

    // 保存配置到会话
    $_SESSION['install_config'] = $config;

    // 读取SQL文件
    $sqlFile = ROOT_PATH . '/database/install.sql';
    if (!file_exists($sqlFile)) {
        die('安装SQL文件不存在');
    }

    $sqlContent = file_get_contents($sqlFile);

    // 解析SQL语句
    $sqlStatements = parseSql($sqlContent);

    // 生成表信息
    $tables = [];
    foreach ($sqlStatements as $statement) {
        if (preg_match('/CREATE TABLE `__prefix__(\w+)`/', $statement, $matches)) {
            $tableName = $matches[1];
            $hasData = strpos($statement, 'INSERT INTO') !== false;
            $tables[] = [
                'name' => $tableName,
                'has_data' => $hasData
            ];
        }
    }

?>
    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>飞鸟漫画 - 执行安装</title>
        <link rel="stylesheet" href="static/js/layui/css/layui.css">
        <style>
            body {
                font-family: 'Microsoft YaHei', Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }

            .install-container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .install-header {
                background-color: #1E9FFF;
                color: #fff;
                padding: 20px;
                text-align: center;
            }

            .install-header h1 {
                margin: 0;
                font-size: 24px;
            }

            .install-body {
                padding: 30px;
            }

            .install-step {
                display: flex;
                margin-bottom: 30px;
            }

            .step-item {
                flex: 1;
                text-align: center;
                position: relative;
            }

            .step-item::after {
                content: '';
                position: absolute;
                top: 15px;
                right: -50%;
                width: 100%;
                height: 2px;
                background-color: #e0e0e0;
                z-index: 1;
            }

            .step-item:last-child::after {
                display: none;
            }

            .step-item.active .step-number {
                background-color: #1E9FFF;
                color: #fff;
            }

            .step-item.active::after {
                background-color: #1E9FFF;
            }

            .step-item.completed .step-number {
                background-color: #5FB878;
                color: #fff;
            }

            .step-item.completed::after {
                background-color: #5FB878;
            }

            .step-number {
                display: inline-block;
                width: 30px;
                height: 30px;
                line-height: 30px;
                border-radius: 50%;
                background-color: #e0e0e0;
                color: #999;
                font-weight: bold;
                margin-bottom: 10px;
                z-index: 2;
                position: relative;
            }

            .step-title {
                font-size: 14px;
                color: #666;
            }

            .step-item.active .step-title {
                color: #1E9FFF;
                font-weight: bold;
            }

            .step-item.completed .step-title {
                color: #5FB878;
            }

            .table-list {
                margin-bottom: 30px;
            }

            .table-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f0;
            }

            .table-item:last-child {
                border-bottom: none;
            }

            .table-item .name {
                font-weight: 500;
            }

            .table-item .status {
                font-size: 14px;
            }

            .table-item .status.pending {
                color: #FFB800;
            }

            .table-item .status.success {
                color: #5FB878;
            }

            .table-item .status.error {
                color: #FF5722;
            }

            .install-footer {
                text-align: center;
                padding: 20px;
                border-top: 1px solid #e0e0e0;
            }

            .layui-btn {
                padding: 0 30px;
                height: 40px;
                line-height: 40px;
                font-size: 16px;
            }

            .install-progress {
                margin: 30px 0;
            }

            .progress-bar {
                width: 100%;
                height: 20px;
                background-color: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
                margin-bottom: 10px;
            }

            .progress-fill {
                height: 100%;
                background-color: #1E9FFF;
                border-radius: 10px;
                width: 0%;
                transition: width 0.3s ease;
            }

            .progress-text {
                text-align: center;
                font-size: 14px;
                color: #666;
            }

            .success-message {
                text-align: center;
                margin: 30px 0;
                padding: 20px;
                background-color: #f0f9eb;
                border: 1px solid #e1f3d8;
                border-radius: 4px;
                color: #67C23A;
                display: none;
            }

            .success-message h3 {
                margin-top: 0;
            }

            .success-message a {
                color: #1E9FFF;
                text-decoration: none;
                margin: 0 10px;
            }

            .success-message a:hover {
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div class="install-container">
            <div class="install-header">
                <h1>飞鸟漫画系统安装</h1>
            </div>
            <div class="install-body">
                <div class="install-step">
                    <div class="step-item completed">
                        <div class="step-number">1</div>
                        <div class="step-title">安装协议</div>
                    </div>
                    <div class="step-item completed">
                        <div class="step-number">2</div>
                        <div class="step-title">环境检测</div>
                    </div>
                    <div class="step-item completed">
                        <div class="step-number">3</div>
                        <div class="step-title">配置信息</div>
                    </div>
                    <div class="step-item active">
                        <div class="step-number">4</div>
                        <div class="step-title">执行安装</div>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px;">安装进度</h3>
                <div class="install-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">准备安装...</div>
                </div>

                <div class="table-list" id="tableList">
                    <?php foreach ($tables as $table): ?>
                        <div class="table-item" data-table="<?php echo $table['name']; ?>">
                            <span class="name"><?php echo $table['name']; ?></span>
                            <span class="status pending">等待安装</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="success-message" id="successMessage">
                    <h3>安装成功！</h3>
                    <p>飞鸟漫画系统已成功安装完成。</p>
                    <p>
                        <a href="/" target="_blank">前往前台</a>
                        <a href="/manage/login/index.html" target="_blank">前往后台</a>
                    </p>
                </div>

                <div class="install-footer">
                    <button id="installBtn" class="layui-btn layui-btn-normal">开始安装</button>
                    <a href="install.php?step=3" class="layui-btn layui-btn-primary">上一步</a>
                </div>
            </div>
        </div>
        <script src="static/js/layui/layui.js"></script>
        <script>
            layui.use(['jquery'], function() {
                var $ = layui.jquery;

                // 开始安装
                $('#installBtn').on('click', function() {
                    $(this).attr('disabled', true).text('安装中...');
                    startInstall();
                });

                // 执行安装
                function startInstall() {
                    var tables = [];
                    $('.table-item').each(function() {
                        tables.push($(this).data('table'));
                    });

                    var currentIndex = 0;
                    var totalTables = tables.length;

                    function installNextTable() {
                        if (currentIndex >= totalTables) {
                            // 所有表安装完成，调用finishInstall创建安装锁
                            $.ajax({
                                url: 'install.php?action=finishInstall',
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        $('#progressFill').css('width', '100%');
                                        $('#progressText').text('安装完成！');
                                        $('#successMessage').show();
                                    } else {
                                        $('#progressText').text('创建安装锁失败：' + response.message);
                                    }
                                },
                                error: function() {
                                    $('#progressText').text('创建安装锁请求失败');
                                }
                            });
                            return;
                        }

                        var tableName = tables[currentIndex];
                        var tableItem = $('.table-item[data-table="' + tableName + '"]');

                        // 更新进度
                        var progress = Math.round((currentIndex / totalTables) * 100);
                        $('#progressFill').css('width', progress + '%');
                        $('#progressText').text('正在安装 ' + tableName + ' (' + (currentIndex + 1) + '/' + totalTables + ')');

                        // 发送AJAX请求执行安装
                        $.ajax({
                            url: 'install.php?action=installTable',
                            type: 'POST',
                            data: {
                                table: tableName
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    tableItem.find('.status').removeClass('pending').addClass('success').text('安装成功');
                                } else {
                                    tableItem.find('.status').removeClass('pending').addClass('error').text('安装失败：' + response.message);
                                }

                                currentIndex++;
                                installNextTable();
                            },
                            error: function() {
                                tableItem.find('.status').removeClass('pending').addClass('error').text('安装失败：网络错误');
                                currentIndex++;
                                installNextTable();
                            }
                        });
                    }

                    // 开始安装第一个表
                    installNextTable();
                }
            });
        </script>
    </body>

    </html>
<?php
}

// 解析SQL语句
function parseSql($sql)
{
    // 移除注释
    $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);
    $sql = preg_replace('/--.*$/m', '', $sql);

    // 智能分割SQL语句，处理引号内的分号
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $escaped = false;

    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];

        if ($escaped) {
            $current .= $char;
            $escaped = false;
            continue;
        }

        if ($char === '\\') {
            $current .= $char;
            $escaped = true;
            continue;
        }

        if ($inString) {
            $current .= $char;
            if ($char === $stringChar) {
                $inString = false;
            }
        } else {
            if ($char === ';' && trim($current) !== '') {
                $statements[] = trim($current);
                $current = '';
            } elseif ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($char !== ';' || trim($current) !== '') {
                $current .= $char;
            }
        }
    }

    // 添加最后一个语句
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    // 过滤空语句
    $statements = array_filter($statements, function ($statement) {
        return trim($statement) !== '';
    });

    return $statements;
}
?>