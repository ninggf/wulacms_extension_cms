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

use wula\cms\feature\CacheFeature;
use wula\cms\feature\LimitFeature;
use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Response;
use wulaphp\router\Router;

class CmfConfigurationLoader extends ConfigurationLoader {
	public function __construct() {
		//检测是否安装.
		if (is_file(CONFIG_PATH . 'install.lock')) {
			define('WULACMF_INSTALLED', true);
		} else {
			define('WULACMF_INSTALLED', false);
			if (!defined('WULACMF_WEB_INSTALLER')) {
				bind('artisan\getCommands', function ($cmds) {
					$cmds['wulacms:install'] = new InstallCommand();

					return $cmds;
				});
			}
		}
	}

	public function loadConfig($name = 'default') {
		//优先从文件加载
		$config = parent::loadConfig($name);
		if (WULACMF_INSTALLED) {
			//从缓存加载
			$setting = RtCache::get('cfg.' . $name);
			if ($setting === null) {
				//从数据库加载
				try {
					$setting = App::table('settings')->find(['group' => $name], 'name,value')->toArray('value', 'name');
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

	public function beforeLoad() {
		CmsFeatureManager::register(new LimitFeature());
		CmsFeatureManager::register(new CacheFeature());
		$features = CmsFeatureManager::getFeatures();
		if ($features) {
			ksort($features);
			$rst = [];
			$url = Router::getFullURI();
			foreach ($features as $fs) {
				/**@var \wula\cms\ICmsFeature $f */
				foreach ($fs as $f) {
					$rst[] = $f->perform($url) === false ? 0 : 1;
				}
			}

			if ($rst && !array_product($rst)) {//有特性要求停止运行（返回了false）
				Response::respond(403);
			}
		}
	}
}