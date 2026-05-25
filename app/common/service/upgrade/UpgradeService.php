<?php

declare(strict_types=1);

namespace app\common\service\upgrade;

use app\common\model\manage\SystemUpgradeLog as LogModel;
use think\facade\Cache;
use think\facade\Db;
use ZipArchive;

/**
 * 在线升级服务
 * 对接官网漫画系统 API
 */
class UpgradeService
{
    // 官方API地址（漫画系统）
    const API_BASE = 'https://feiniao.paheng.net/api/comic';

    protected string $rootPath;
    protected string $runtimePath;
    protected string $tempPath;
    protected ?int $logId = null;
    protected array $logs = [];

    public function __construct()
    {
        $this->rootPath = root_path();
        $this->runtimePath = runtime_path();
        $this->tempPath = $this->runtimePath . 'upgrade' . DIRECTORY_SEPARATOR;

        // 确保目录存在
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * 获取API地址
     * @return string
     */
    public static function getApiUrl(): string
    {
        return self::API_BASE;
    }

    /**
     * 获取当前系统版本
     * @return string
     */
    public static function getThisSystemVersion(): string
    {
        return (new self())->getSystemVersion();
    }

    /**
     * 获取官网首页地址
     * @return string
     */
    public static function getRegisterUrl(): string
    {
        $base = self::API_BASE;
        $pos = strpos($base, '/api/');
        return $pos !== false ? substr($base, 0, $pos) : $base;
    }

    /**
     * 设置升级记录ID（用于回滚等操作）
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): self
    {
        $this->logId = $logId;
        return $this;
    }

    /**
     * 获取升级记录ID
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->logId;
    }

    // ==================== 版本相关 ====================

    /**
     * 获取版本信息
     * @param string|null $vid 版本号，为空则获取最新版本
     * @return array
     */
    public function getVersion(?string $vid = null): array
    {
        $url = self::API_BASE . '/version';
        if ($vid) {
            $url .= '/' . $vid;
        }

        return $this->request('GET', $url);
    }

    /**
     * 获取版本详情
     * @param string $vid 版本号
     * @return array
     */
    public function getVersionInfo(string $vid): array
    {
        $url = self::API_BASE . '/info/' . $vid;
        return $this->request('GET', $url);
    }

    // ==================== 检查更新 ====================

    /**
     * 检查系统更新
     * 使用新的 API: GET /api/comic/version/[:vid]
     * @return array
     */
    public function checkSystemUpdate(): array
    {
        $currentVersion = $this->getSystemVersion();
        $response = $this->getVersion($currentVersion);

        if (empty($response)) {
            return [
                'success' => false,
                'has_update' => false,
                'message' => '无法连接到升级服务器',
                'current_version' => $currentVersion,
                'latest_version' => '',
                'versions' => [],
            ];
        }

        // 解析返回的版本数据
        $versions = $response['versions'] ?? $response['data'] ?? [];
        $latestVersion = '';
        $hasUpdate = false;

        foreach ($versions as $item) {
            $version = $item['version'] ?? $item['name'] ?? '';
            if (empty($latestVersion) || version_compare($version, $latestVersion, '>')) {
                $latestVersion = $version;
            }
            // 检查是否有比当前版本更新的版本
            if (!empty($version) && version_compare($version, $currentVersion, '>')) {
                $hasUpdate = true;
            }
        }

        return [
            'success' => true,
            'has_update' => $hasUpdate,
            'message' => $hasUpdate ? '发现新版本' : '当前已是最新版本',
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion ?: $currentVersion,
            'versions' => $versions
        ];
    }

    /**
     * 检查模块更新
     * @param string $moduleName 模块名称
     * @return array
     */
    public function checkModuleUpdate(string $moduleName): array
    {
        $currentVersion = $this->getModuleVersion($moduleName);

        // 使用新的 API: /comic/modulecheck/:name/:version
        $url = self::API_BASE . '/modulecheck/' . $moduleName . '/' . $currentVersion;
        $response = $this->request('GET', $url);

        if (empty($response)) {
            return [
                'success' => false,
                'has_update' => false,
                'message' => '无法连接到升级服务器',
                'current_version' => $currentVersion,
                'latest_version' => '',
                'versions' => [],
            ];
        }

        return [
            'success' => true,
            'has_update' => $response['has_update'] ?? false,
            'message' => '检查成功',
            'current_version' => $currentVersion,
            'latest_version' => $response['version'] ?? '',
            'versions' => $response['versions'] ?? [],
            'path' => $response['path'] ?? '',
        ];
    }

    /**
     * 检查模板更新
     * @param string $templateName 模板名称
     * @return array
     */
    public function checkTemplateUpdate(string $templateName): array
    {
        $currentVersion = $this->getTemplateVersion($templateName);

        // 使用新的 API: /comic/templatecheck/:name/:version
        $url = self::API_BASE . '/templatecheck/' . $templateName . '/' . $currentVersion;
        $response = $this->request('GET', $url);

        if (empty($response)) {
            return [
                'success' => false,
                'has_update' => false,
                'message' => '无法连接到升级服务器',
                'current_version' => $currentVersion,
                'latest_version' => '',
                'versions' => [],
            ];
        }

        return [
            'success' => true,
            'has_update' => $response['has_update'] ?? false,
            'message' => '检查成功',
            'current_version' => $currentVersion,
            'latest_version' => $response['version'] ?? '',
            'versions' => $response['versions'] ?? [],
            'path' => $response['path'] ?? '',
        ];
    }

    // ==================== 获取模块/模板列表 ====================

    /**
     * 获取模块列表（从官网）
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getModuleList(int $page = 1, int $limit = 20): array
    {
        $url = self::API_BASE . '/module?page=' . $page . '&limit=' . $limit;
        return $this->request('GET', $url);
    }

    /**
     * 获取模板列表（从官网）
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getTemplateList(int $page = 1, int $limit = 20): array
    {
        $url = self::API_BASE . '/template?page=' . $page . '&limit=' . $limit;
        return $this->request('GET', $url);
    }

    // ==================== 下载升级包 ====================

    /**
     * 下载升级包
     * @param string $type 类型：system/module/template
     * @param string $name 标识
     * @param string $version 目标版本
     * @return array ['success' => bool, 'message' => string, 'log_id' => int]
     */
    public function download(string $type, string $name, string $version): array
    {
        try {
            // 获取下载链接 - 根据类型使用不同的 API
            if ($type === 'module') {
                // 模块升级: POST /api/comic/moduleupgrade/:name
                $url = self::API_BASE . '/moduleupgrade/' . $name;
                $response = $this->request('POST', $url, [
                    'version' => $version,
                    'site_key' => $this->getSiteKey(),
                ]);
            } elseif ($type === 'template') {
                // 模板升级: POST /api/comic/templateupgrade/:name
                $url = self::API_BASE . '/templateupgrade/' . $name;
                $response = $this->request('POST', $url, [
                    'version' => $version,
                    'site_key' => $this->getSiteKey(),
                ]);
            } else {
                // 系统升级: GET /api/comic/version/:vid 后获取 info
                $versionInfo = $this->getVersionInfo($version);
                $response = $versionInfo;
            }

            // 如果直接返回下载链接
            if (!empty($response['data']['path'])) {
                // 创建升级记录
                $this->logId = $this->createLog($type, $name, $version);
                $deleteFiles = $response['data']['delete_dir'] ?? '';
                $sqlContent = $response['data']['content'] ?? '';
                $deleteFiles = str_replace("\r", "\n", $deleteFiles);
                $deleteFiles = explode("\n", $deleteFiles);
                $deleteFiles = array_map('trim', $deleteFiles);
                $deleteFiles = array_filter($deleteFiles);
                $this->updateLog(['sql_content' => $sqlContent, 'delete_files' => implode("\n", $deleteFiles)]);
                return $this->downloadFile($response['data']['path'], $type, $name, $version);
            }

            return ['success' => false, 'message' => '获取下载链接失败: ' . json_encode($response)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine()];
        }
    }

    /**
     * 下载文件
     */
    protected function downloadFile(string $downloadUrl, string $type, string $name, string $version): array
    {
        try {
            $this->addLog('开始下载升级包');

            // 生成文件名
            $fileName = "{$type}_{$name}_{$version}_" . time() . '.zip';
            $savePath = $this->tempPath . $fileName;

            // 下载文件
            $this->updateLog(['status' => 1, 'package_path' => $savePath]);

            file_put_contents($savePath, fopen($downloadUrl, 'r'));

            $this->addLog('下载完成: ' . $savePath . ' (' . round(filesize($savePath) / 1024 / 1024, 2) . 'MB)');

            return [
                'success' => true,
                'message' => '下载成功',
                'log_id' => $this->logId,
                'package_path' => $savePath,
            ];
        } catch (\Throwable $e) {
            $this->updateLog(['status' => 7, 'error_msg' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 执行升级
     * @param int|null $logId 升级记录ID
     * @return array
     */
    public function execute(?int $logId = null): array
    {
        try {
            if ($logId) {
                $this->logId = $logId;
            }

            $log = LogModel::find($this->logId);
            if (!$log) {
                return ['code' => 400, 'msg' => '升级记录不存在'];
            }

            $packagePath = $log->package_path;
            $deleteFiles = explode("\n", $log->delete_files);
            $sqlContent = $log->sql_content;

            if (empty($packagePath) || !file_exists($packagePath)) {
                return ['code' => 400, 'msg' => '升级包不存在: ' . $packagePath];
            }

            // 1. 解压
            $this->updateLog(['status' => 2]);
            $extractPath = $this->extractPackage($packagePath);
            $this->addLog('解压完成: ' . $extractPath);

            // 2. 分析文件操作
            $files = $this->analyzeFiles($extractPath, $log->type, $log->name);
            if (!$files) {
                return ['code' => 400, 'msg' => '文件分析失败'];
            }
            $addFiles = $files['add'];
            $updateFiles = $files['update'];

            $this->addLog('文件分析完成: 新增' . count($addFiles) . '个, 更新' . count($updateFiles) . '个, 删除' . count($deleteFiles) . '个');

            // 3. 权限检查
            $this->updateLog(['status' => 4]);
            $permChecker = new PermissionChecker();
            $permResult = $permChecker->checkAll($addFiles, $updateFiles, $deleteFiles);

            if (!$permResult['success']) {
                $errorMsg = $permChecker->formatErrors($permResult['errors']);
                $this->updateLog(['status' => 7, 'error_msg' => $errorMsg]);
                return [
                    'code' => 400,
                    'msg' => '权限检查失败' . $errorMsg . json_encode($permResult['errors']),
                ];
            }
            $this->addLog('权限检查通过');

            // 5. 执行升级
            $this->updateLog(['status' => 5]);

            // 5.1 删除文件            
            $this->deleteFiles($deleteFiles);
            $this->addLog('删除文件完成');

            // 5.2 执行SQL
            if ($sqlContent) {
                $this->executeSql($sqlContent);
                $this->addLog('SQL执行完成');
            }

            // 6.3 复制/替换文件
            $this->copyFiles($extractPath, $log->type, $log->name);
            $this->addLog('文件复制完成');

            // 6.4 清理缓存
            $this->clearCache();
            $this->addLog('缓存清理完成');

            // 7. 完成
            $this->updateLog([
                'status' => 6,
            ]);

            // 8. 清理临时文件
            $this->cleanup($extractPath, $packagePath);
            $this->addLog('升级完成');

            return [
                'code' => 200,
                'msg' => '升级成功',
                'version' => $log->from_version,
                'from_version' => $log->from_version,
                'to_version' => $log->to_version,
            ];
        } catch (\Throwable $e) {
            $this->addLog('升级失败: ' . $e->getMessage());
            $this->updateLog([
                'status' => 7,
                'error_msg' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
            return [
                'code' => 500,
                'msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取升级记录列表
     * @param string|null $type 类型筛选
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getLogList(?string $type = null, int $page = 1, int $limit = 20): array
    {
        $query = LogModel::order('id', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 获取升级记录详情
     * @param int $id
     * @return array
     */
    public function getLogDetail(int $id): array
    {
        $log = LogModel::find($id);
        if (!$log) {
            return [];
        }

        $data = $log->toArray();
        $data['execute_log'] = $log->execute_log ? json_decode($log->execute_log, true) : [];
        $data['status_text'] = $this->getStatusText($log->status);

        return $data;
    }

    /**
     * 通用请求方法
     * @param string $method GET|POST
     * @param string $url
     * @param array $params
     * @return array
     */
    protected function request(string $method, string $url, array $params = []): array
    {
        try {
            $ch = curl_init();

            if ($method === 'GET' && !empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            // 获取 token（用于收费模块/模板升级）
            $token = $this->getToken();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 0,  // 升级请求不超时
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: FeiniaoComicUpgrade/1.0',
                    'Referer: ' . (request()->domain() ?? ''),
                    'token: ' . ($token ?? ''),
                ],
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                }
            }

            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => $error];
            }

            if ($httpCode !== 200) {
                return ['success' => false, 'message' => 'HTTP ' . $httpCode, 'code' => $httpCode];
            }


            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'JSON解析失败', 'raw' => $response];
            }

            return $result ?? [];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取官网账号 Token
     * @return string|null
     */
    protected function getToken(): ?string
    {
        return Cache::get('union_token');
    }

    /**
     * 设置官网账号 Token
     * @param string $token
     * @param int $expire 有效期（秒），默认7天
     * @return bool
     */
    public function setToken(string $token, int $expire = 604800): bool
    {
        return Cache::set('union_token', $token, $expire);
    }

    /**
     * 删除官网账号 Token（登出）
     * @return bool
     */
    public function clearToken(): bool
    {
        return Cache::delete('union_token');
    }

    /**
     * 检查是否已登录官网账号
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        $token = Cache::get('union_token');
        return !empty($token);
    }

    /**
     * 登录官网账号
     * @param string $account 账号
     * @param string $password 密码
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function login(string $account, string $password): array
    {
        try {
            $base = self::API_BASE;
            $pos = strpos($base, '/comic');
            $baseUrl = $pos !== false ? substr($base, 0, $pos) : $base;

            $url = $baseUrl . '/login/login';

            $result = $this->httpRequest('POST', $url, [
                'account' => $account,
                'password' => $password,
                'scene' => 'account',
            ]);

            if (!empty($result['code'])) {
                return [
                    'success' => false,
                    'message' => $result['msg'] ?? '登录失败',
                    'data' => null,
                ];
            }

            if (empty($result['data']['token'])) {
                return [
                    'success' => false,
                    'message' => '登录失败，未获取到Token',
                    'data' => null,
                ];
            }

            // 保存 token 到缓存，有效期7天
            $this->setToken($result['data']['token'], 604800);

            return [
                'success' => true,
                'message' => '登录成功',
                'data' => $result['data'] ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => '登录异常: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 登出官网账号
     * @return array
     */
    public function logout(): array
    {
        try {
            $this->clearToken();
            return [
                'success' => true,
                'message' => '已退出登录',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => '退出登录失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 检查登录状态
     * @return array
     */
    public function checkLogin(): array
    {
        $token = Cache::get('union_token');
        return [
            'success' => true,
            'message' => 'success',
            'data' => [
                'is_login' => !empty($token),
            ],
        ];
    }

    /**
     * HTTP请求（带Token认证）
     * @param string $method GET|POST
     * @param string $url
     * @param array $params
     * @return array
     */
    protected function httpRequest(string $method, string $url, array $params = []): array
    {
        try {
            $ch = curl_init();

            if ($method === 'GET' && !empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $token = $this->getToken();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: FeiniaoComicUpgrade/1.0',
                    'Referer: ' . (request()->domain() ?? ''),
                    'token: ' . ($token ?? ''),
                ],
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                }
            }

            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['code' => 1, 'msg' => $error];
            }

            if ($httpCode !== 200) {
                return ['code' => 1, 'msg' => 'HTTP ' . $httpCode];
            }

            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 1, 'msg' => 'JSON解析失败', 'raw' => $response];
            }

            return $result ?? [];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 创建升级记录
     */
    protected function createLog(string $type, string $name, string $version): int
    {
        return LogModel::create([
            'type' => $type,
            'name' => $name,
            'from_version' => $this->getCurrentVersion($type, $name),
            'to_version' => $version,
            'status' => 0,
            'created_at' => time(),
        ])->id;
    }

    /**
     * 更新升级记录
     */
    protected function updateLog(array $data): void
    {
        if (!$this->logId) return;
        LogModel::where('id', $this->logId)->update(array_merge($data, ['updated_at' => time()]));
    }

    /**
     * 添加执行日志
     */
    protected function addLog(string $message): void
    {
        $this->logs[] = [
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
        ];

        if ($this->logId) {
            LogModel::where('id', $this->logId)->update([
                'execute_log' => json_encode($this->logs, JSON_UNESCAPED_UNICODE),
                'updated_at' => time(),
            ]);
        }
    }

    /**
     * 获取当前版本
     */
    protected function getCurrentVersion(string $type, string $name): string
    {
        if ($type === 'system') {
            return $this->getSystemVersion();
        }
        if ($type === 'module') {
            return $this->getModuleVersion($name);
        }
        if ($type === 'template') {
            return $this->getTemplateVersion($name);
        }
        return '1.0.0';
    }

    /**
     * 获取系统版本
     */
    protected function getSystemVersion(): string
    {
        return config('version.version', '1.0.0');
    }

    /**
     * 获取模块版本
     */
    protected function getModuleVersion(string $name): string
    {
        $modulePath = $this->rootPath . 'modules' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        $moduleJson = $modulePath . 'module.json';

        if (file_exists($moduleJson)) {
            $info = json_decode(file_get_contents($moduleJson), true);
            return $info['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    /**
     * 获取模板版本
     */
    protected function getTemplateVersion(string $name): string
    {
        $templatePath = $this->rootPath . 'template' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        $themeJson = $templatePath . 'theme.json';

        if (file_exists($themeJson)) {
            $info = json_decode(file_get_contents($themeJson), true);
            return $info['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    /**
     * 获取站点密钥
     */
    protected function getSiteKey(): string
    {
        return config('system.site_key', '');
    }

    /**
     * 获取已安装模块列表
     */
    public function getInstalledModules(): array
    {
        $modules = [];
        $modulePath = $this->rootPath . 'modules' . DIRECTORY_SEPARATOR;

        if (!is_dir($modulePath)) {
            return $modules;
        }

        $dirs = glob($modulePath . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $moduleJson = $dir . DIRECTORY_SEPARATOR . 'module.json';

            if (file_exists($moduleJson)) {
                $info = json_decode(file_get_contents($moduleJson), true);
                $modules[] = [
                    'name' => $name,
                    'title' => $info['title'] ?? $name,
                    'version' => $info['version'] ?? '1.0.0',
                ];
            }
        }

        return $modules;
    }

    /**
     * 获取已安装模板列表
     */
    public function getInstalledTemplates(): array
    {
        $templates = [];
        $templatePath = $this->rootPath . 'template' . DIRECTORY_SEPARATOR;

        if (!is_dir($templatePath)) {
            return $templates;
        }

        $dirs = glob($templatePath . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $themeJson = $dir . DIRECTORY_SEPARATOR . 'theme.json';

            if (file_exists($themeJson)) {
                $info = json_decode(file_get_contents($themeJson), true);
                $templates[] = [
                    'name' => $name,
                    'title' => $info['title'] ?? $name,
                    'version' => $info['version'] ?? '1.0.0',
                ];
            }
        }

        return $templates;
    }

    /**
     * 解压升级包
     */
    protected function extractPackage(string $zipPath): string
    {
        $extractPath = $this->tempPath . 'extract_' . time() . DIRECTORY_SEPARATOR;

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('无法打开升级包');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        return $extractPath;
    }

    /**
     * 分析升级包中的文件
     */
    protected function analyzeFiles(string $extractPath, string $type, string $name): array
    {
        $addFiles = [];
        $updateFiles = [];

        $basePath = $extractPath;

        // 根据类型确定根目录
        if ($type === 'module') {
            $basePath = $extractPath . $name . DIRECTORY_SEPARATOR;
        } elseif ($type === 'template') {
            $basePath = $extractPath . $name . DIRECTORY_SEPARATOR;
        } else {
            // system: 直接使用 extractPath
            $basePath = $extractPath;
        }

        if (!is_dir($basePath)) {
            return ['add' => [], 'update' => []];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;

            $relativePath = str_replace($basePath, '', $file->getPathname());
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);

            $targetPath = $this->getTargetPath($relativePath, $type, $name);
            $fullTargetPath = $this->rootPath . $targetPath;

            if (file_exists($fullTargetPath)) {
                $updateFiles[] = $targetPath;
            } else {
                $addFiles[] = $targetPath;
            }
        }

        return ['add' => $addFiles, 'update' => $updateFiles];
    }

    /**
     * 获取目标路径
     */
    protected function getTargetPath(string $relativePath, string $type, string $name): string
    {
        // 系统升级：直接使用相对路径
        if ($type === 'system') {
            return $relativePath;
        }

        // 模块升级：modules/{name}/
        if ($type === 'module') {
            if (strpos($relativePath, 'public' . DIRECTORY_SEPARATOR) === 0) {
                return 'public' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . substr($relativePath, 7);
            }
            return 'modules' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $relativePath;
        }

        // 模板升级：template/{name}/
        if ($type === 'template') {
            if (strpos($relativePath, 'public' . DIRECTORY_SEPARATOR) === 0) {
                return 'public' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . substr($relativePath, 7);
            }
            return 'template' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $relativePath;
        }

        return $relativePath;
    }

    /**
     * 执行SQL
     */
    protected function executeSql(string $sqlContent): void
    {
        $prefix = config('database.connections.mysql.prefix', '');

        $sqlContent = str_ireplace('__PREFIX__', $prefix, $sqlContent);

        $sqls = array_filter(array_map('trim', explode(';', $sqlContent)));

        foreach ($sqls as $sql) {
            if (empty($sql)) continue;

            // 跳过注释
            if (strpos(trim($sql), '--') === 0 || strpos(trim($sql), '/*') === 0) {
                continue;
            }

            try {
                Db::execute($sql);
            } catch (\Throwable $e) {
                $this->addLog('SQL执行警告: ' . $e->getMessage() . ' | SQL: ' . substr($sql, 0, 100));
                // SQL 执行失败不中断，继续执行
            }
        }
    }

    /**
     * 删除文件
     */
    protected function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            $file = trim($file);
            if (empty($file)) continue;

            $path = $this->rootPath . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * 复制/替换文件
     */
    protected function copyFiles(string $extractPath, string $type, string $name): void
    {
        $basePath = $extractPath;

        if ($type === 'module') {
            $basePath = $extractPath . $name . DIRECTORY_SEPARATOR;
        } elseif ($type === 'template') {
            $basePath = $extractPath . $name . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;

            $relativePath = str_replace($basePath, '', $file->getPathname());
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);

            $targetPath = $this->getTargetPath($relativePath, $type, $name);
            $fullTargetPath = $this->rootPath . $targetPath;
            $targetDir = dirname($fullTargetPath);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($file->getPathname(), $fullTargetPath);
        }
    }

    /**
     * 复制目录
     */
    protected function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($src, '', $file->getPathname());
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
            $target = $dst . $relativePath;
            $targetDir = dirname($target);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($file->getPathname(), $target);
        }
    }

    /**
     * 清理缓存
     */
    protected function clearCache(): void
    {
        $paths = [
            runtime_path('cache'),
            runtime_path('temp'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }

        // 清理路由缓存
        $routeCache = runtime_path('route' . DIRECTORY_SEPARATOR . 'route.php');
        if (file_exists($routeCache)) {
            unlink($routeCache);
        }
    }

    /**
     * 清理临时文件
     */
    protected function cleanup(string $extractPath, string $packagePath): void
    {
        // 删除解压目录
        if (is_dir($extractPath)) {
            $this->removeDirectory($extractPath);
        }

        // 删除下载的压缩包
        if (file_exists($packagePath)) {
            unlink($packagePath);
        }
    }

    /**
     * 删除目录
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * 获取状态文本
     */
    protected function getStatusText(int $status): string
    {
        return match ($status) {
            0 => '待处理',
            1 => '下载中',
            2 => '解压中',
            3 => '备份中',
            4 => '权限检查',
            5 => '升级中',
            6 => '已完成',
            7 => '失败',
            8 => '回滚中',
            default => '未知',
        };
    }
}
