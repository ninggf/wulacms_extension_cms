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

use wulaphp\app\App;
use wulaphp\cache\RtCache;
use wulaphp\conf\ConfigurationLoader;

class CmfConfigurationLoader extends ConfigurationLoader {
	public function __construct() {
		//检测是否安装.
		if (is_file(CONFIG_PATH . 'install.lock')) {
			define('WULACMF_INSTALLED', true);
		} else {
			define('WULACMF_INSTALLED', false);
			bind('artisan\getCommands', function ($cmds) {
				$cmds['wulacms:install'] = new InstallCommand();

				return $cmds;
			});
		}
	}

	public function loadConfig($name = 'default') {
		//优化从文件加载
		$config = parent::loadConfig($name);
		//从缓存加载
		$setting = RtCache::get('cfg.' . $name);
		if ($setting === null) {
			//从数据库加载
			$setting = App::table('settings')->find(['group' => $name], 'name,value')->toArray('value', 'name');
			RtCache::add('cfg.' . $name, $setting);
		}
		if ($setting) {
			$config->setConfigs($setting);
		}

		return $config;
	}

	public function postLoad() {
		parent::postLoad();
		$features = CmsFeatureManager::getFeatures();
		if ($features) {
			ksort($features);
			$rst = [];
			foreach ($features as $fs) {
				/**@var \wula\cms\ICmsFeature $f */
				foreach ($fs as $f) {
					$rst[] = $f->perform() === false ? 0 : 1;
				}
			}

			if ($rst && !array_product($rst)) {//有特性要求停止运行（返回了false）
				exit ();
			}
		}
	}
}