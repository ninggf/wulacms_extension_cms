<?php
/*
 * 内容管理框架配置加载器.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\cms;

use wula\cms\feature\LimitFeature;
use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Request;
use wulaphp\router\Router;

class CmfConfigurationLoader extends ConfigurationLoader {
    public function __construct() {
        //检测是否安装.
        if (APP_MODE == 'test' && is_file(CONFIG_PATH . 'install_test.lock')) {
            define('WULACMF_INSTALLED', true);
        } else if (is_file(CONFIG_PATH . 'install.lock')) {
            define('WULACMF_INSTALLED', true);
        } else {
            define('WULACMF_INSTALLED', false);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|\wulaphp\conf\Configuration
     */
    public function loadConfig($name = 'default') {
        //优先从文件加载
        $config = parent::loadConfig($name);
        if (WULACMF_INSTALLED) {
            if ($name == 'default' && !defined('DEBUG')) {
                $debug = $config->get('debug', DEBUG_ERROR);
                if ($debug > 1000 || $debug < 0) {
                    $debug = DEBUG_OFF;
                }
                define('DEBUG', $debug);
            }
            //从缓存加载
            $setting = RtCache::get('cfg.' . $name);
            if (!is_array($setting)) {
                //从数据库加载
                try {
                    $setting = App::table('settings')->findAll(['group' => $name], 'name,value')->toArray('value', 'name');
                    RtCache::add('cfg.' . $name, $setting);
                } catch (\Exception $e) {
                    log_warn($e->getMessage());//无法连接数据库
                }
            }
            if ($setting) {
                $config->setConfigs($setting);
            }
        }

        return $config;
    }

    /**
     * 加载配置前运行CMS特性。
     */
    public function beforeLoad() {
        if (PHP_SAPI != 'cli') {
            if (defined('ANTI_CC') && ANTI_CC) {
                $arg    = explode('/', ANTI_CC);
                $arg[0] = intval($arg[0]);
                if ($arg[0]) {//0就是关闭喽
                    if (!isset($arg[1])) {
                        $arg[1] = 60;
                    }
                    $arg[1] = intval($arg[1]);
                    CmsFeatureManager::register(new LimitFeature($arg[0], $arg[1]));
                }
            }
            $features = CmsFeatureManager::getFeatures();
            if ($features) {
                ksort($features);
                $rst = [];
                $url = Router::getFullURI();
                foreach ($features as $fs) {
                    /**@var \wula\cms\ICmsFeature $f */
                    foreach ($fs as $f) {
                        $rst[] = $f->perform($url) === false ? 0 : 1;
                        if (!array_product($rst)) {//有特性要求停止运行（返回了false）
                            http_response_code(403);
                            exit();
                        }
                    }
                }
            }
        }
    }

    public function postLoad() {
        if (App::bcfg('offline')) {
            $ip = Request::getIp();
            if (!$ip) {
                return;
            }
            $ips = trim(App::cfg('allowedIps'));
            $msg = App::cfg('offlineMsg', 'Service Unavailable');
            if (empty($ips)) {
                $this->httpout(503, $msg);
            }
            $ips = explode("\n", $ips);
            if (!in_array($ip, $ips)) {
                $this->httpout(503, $msg);
            }
        }
    }

    /**
     * 输出http响应输出。
     *
     * @param string|int $status 状态
     * @param string     $message
     */
    private function httpout($status, $message = '') {
        http_response_code($status);
        if ($message) {
            echo $message;
        }
        exit();
    }
}