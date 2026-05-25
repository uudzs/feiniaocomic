<?php

declare(strict_types=1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\common\model\manage\Conf;
use app\common\model\manage\Link;
use think\exception\HttpResponseException;
use think\facade\View;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $appName = app('http')->getName();

        // 设置视图路径为当前主题对应的目录
        if ($appName !== 'manage') {
            setViewPath();
        }
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        if (!$this->checkSiteStatus()) {
            $this->throwMaintenanceException();
        }

        // 将 request 和 app 信息传递给视图
        $this->assignViewShareData();
    }

    /**
     * 分配视图共享数据
     * 供前端导航栏菜单高亮、路径判断等使用
     */
    protected function assignViewShareData(): void
    {
        // 当前请求信息
        View::assign('Request', $this->request);

        // 解析控制器名称获取模块（格式：app\module\controller\Controller）
        $controllerClass = get_class($this);
        $controllerParts = explode('\\', $controllerClass);
        $module = $controllerParts[1] ?? 'app'; // 默认 app

        // 控制器名（去除 namespace）
        $controller = $this->request->controller();
        if (is_string($controller)) {
            $lastPos = strrpos($controller, '\\');
            $controller = $lastPos !== false ? substr($controller, $lastPos + 1) : $controller;
        } else {
            $controller = '';
        }

        $links = cache('links');
        if (empty($links)) {
            $links = Link::where('status', 1)->order('sort asc, create_time DESC')->select()->toArray();
            cache('links', $links, 3600);
        }

        // App 基础信息
        View::assign('App', [
            'module'        => $module,
            'controller'    => $controller,
            'action'        => $this->request->action(),
            'route'         => $this->request->route(),
            'pathinfo'      => $this->request->pathinfo(),
            'url'           => $this->request->url(),
            'baseUrl'       => $this->request->baseUrl(),
            'domain'        => $this->request->domain(),
            'root'          => $this->request->root(),
            'scheme'        => $this->request->scheme(),
            'isAjax'        => $this->request->isAjax(),
            'isMobile'      => $this->request->isMobile(),
            'method'        => $this->request->method(),
            'linksData'     => $links,
        ]);

        // 当前路由参数
        View::assign('RouteParams', $this->request->param());
    }

    //-----------------------------------------------------------------
    // 抛出维护异常（统一异常处理）
    //-----------------------------------------------------------------
    protected function throwMaintenanceException(): void
    {
        $response = request()->isAjax()
            ? json(['code' => 503, 'msg' => '网站维护中，请稍后访问'])
            : view('/site_off', ['message' => '网站维护中，请稍后访问'])->code(503);

        throw new HttpResponseException($response);
    }

    //-----------------------------------------------------------------
    // 站点状态检查
    //-----------------------------------------------------------------
    protected function checkSiteStatus(): bool
    {
        return Conf::cache(3600)->where('ename', 'siteon')->value('value') === '1';
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 格式化图片地址
     * 
     * @param string $image 图片地址
     * @return string
     */
    protected function formatImage(string $image): string
    {
        if (empty($image)) {
            return '';
        }

        if (strpos($image, 'http') === 0) {
            return $image;
        }

        return request()->domain() . $image;
    }

    /**
     * 返回成功响应
     */
    protected function success(array $data, string $message = '操作成功', int $code = 200)
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ]);
    }

    /**
     * 返回错误响应
     */
    protected function error(string $message, int $code = 400)
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time(),
        ], $code);
    }
}
