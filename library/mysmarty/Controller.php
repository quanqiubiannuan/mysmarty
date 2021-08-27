<?php

namespace library\mysmarty;

class Controller
{
    // 是否开启缓存
    protected bool $myCache = true;
    // 模板对象
    private Template $mySmarty;

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 初始化变量
        $this->mySmarty = Template::getInstance();
        $this->initSmarty();
    }

    /**
     * 初始化smarty
     */
    private function initSmarty(): void
    {
        // 模板文件目录
        $templateDir = ROOT_DIR . '/application/' . Start::$module . '/view/';
        $this->mySmarty->setTemplateDir($templateDir);
        // 编译文件目录
        $compileDir = ROOT_DIR . '/runtime/templates_c/' . Start::$module . '/' . strtolower(str_ireplace('\\', '/', Start::$controller));
        $this->mySmarty->setCompileDir($compileDir);
        // 配置目录
        $configDir = ROOT_DIR . '/application/' . Start::$module . '/config/';
        if (file_exists($configDir)) {
            $this->mySmarty->setConfigDir($configDir);
        }
        // 配置模板分隔标签符
        if (!empty(config('mysmarty.taglib_begin'))) {
            $this->mySmarty->setLeftDelimiter(config('mysmarty.taglib_begin'));
        }
        if (!empty(config('mysmarty.taglib_end'))) {
            $this->mySmarty->setRightDelimiter(config('mysmarty.taglib_end'));
        }
        // 缓存配置
        $cache = $this->myCache ? config('mysmarty.cache', 0) : 0;
        if ($cache > 0) {
            $this->mySmarty->setCaching(true);
        } else {
            $this->mySmarty->setCaching(false);
        }
    }

    /**
     * 显示模板
     * @param string $template
     */
    final protected function display(string $template = ''): void
    {
        if (empty($template)) {
            $template = $this->getMyTemplate();
        } else {
            if (!preg_match('#/#', $template)) {
                $tmp = toDivideName(str_ireplace('\\', '/', Start::$controller), '/') . '/' . $template;
                if (file_exists(APPLICATION_DIR . '/' . formatModule(Start::$module) . '/view/' . $tmp)) {
                    $template = $tmp;
                }
            }
        }
        $this->mySmarty->display($template);
    }

    /**
     * 返回自动生成的模板文件
     * @return string
     */
    final protected function getMyTemplate(): string
    {
        return toDivideName(str_ireplace('\\', '/', Start::$controller), '/') . '/' . toDivideName(Start::$action) . '.' . config('mysmarty.suffix');
    }

    /**
     * 提示信息
     *
     * @param string $message 提示的文字
     * @param string $url 跳转的url
     * @param integer $status 状态
     * @param int $second 多少秒自动跳转，-1 不自动跳转，0 立即跳转 ，大于0 则多少秒 自动跳转
     * @throws
     */
    final protected function sysecho(string $message, string $url, int $status = 200, int $second = -1): void
    {
        http_response_code($status);
        $this->mySmarty->setTemplateDir(LIBRARY_DIR . '/tpl');
        $this->mySmarty->assign('message', $message);
        if (!empty($url)) {
            $url = getFixedUrl($url);
        } else {
            $url = 'javascript:history.go(-1);';
        }
        $this->mySmarty->assign('url', $url);
        $this->mySmarty->assign('second', $second);
        $this->mySmarty->display('_sysecho.html');
        exit();
    }

    /**
     * 成功提示
     * @param string $message
     * @param string $url
     */
    final protected function success(string $message, string $url = ''): void
    {
        $this->sysecho($message, $url);
    }

    /**
     * 错误提示
     * @param string $message
     * @param string $url
     */
    final protected function error(string $message, string $url = ''): void
    {
        $this->sysecho($message, $url);
    }

    /**
     * 页面未找到
     */
    final protected function notFound(): void
    {
        $this->sysecho('页面未找到', '', 404);
    }

    /**
     * 服务器错误
     */
    final protected function systemError(): void
    {
        $this->sysecho('服务器错误', '', 503);
    }

    /**
     * 重定向
     * @param string $path
     * @param integer $code
     */
    final protected function redirect(string $path, int $code = 302): void
    {
        redirect($path, $code);
    }

    /**
     * 渲染模板
     * @param string $template 模板文件
     * @param array $data 分配数据
     */
    final protected function view(string $template = '', array $data = []): void
    {
        if (!empty($template)) {
            $template = str_ireplace('.', '/', $template);
            $template .= '.' . config('mysmarty.suffix');
        }
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $this->mySmarty->assign($k, $v);
            }
        }
        $this->display($template);
    }

    /**
     * 跳转url
     * @param string $url url不可以有pathinfo模式的传参
     * @param array $params 传递的参数，键值对
     */
    final protected function dispatch(string $url, array $params = []): void
    {
        $paramsStr = '';
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $paramsStr .= '/' . $k . '/' . $v;
            }
        }
        if (0 !== stripos($url, 'http')) {
            $url = trim($url, '/');
            $url = match (count(explode('/', $url))) {
                1 => formatModule(Start::$module) . '/' . toDivideName(str_ireplace('\\', '/', Start::$controller)) . '/' . $url,
                2 => formatModule(Start::$module) . '/' . $url,
            };
        }
        redirect($url . $paramsStr);
    }

    /**
     * 分配变量
     * @param string $key 变量名称
     * @param mixed $value 变量值
     */
    final protected function assign(string $key, mixed $value): void
    {
        $this->mySmarty->assign($key, $value);
    }

    /**
     * 删除模板缓存文件目录
     * @return bool
     */
    final protected function clearTemplateDirCache(): bool
    {
        return $this->mySmarty->clearTemplateDirCache();
    }

    /**
     * 清空内容缓存，包括配置、路由缓存
     * @return bool
     */
    final protected function clearCache(): bool
    {
        return $this->mySmarty->clearCache();
    }
}