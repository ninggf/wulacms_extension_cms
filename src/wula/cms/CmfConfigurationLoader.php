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

use wulaphp\command\CreateTableCommand;
use wulaphp\conf\ConfigurationLoader;

class CmfConfigurationLoader extends ConfigurationLoader {
	public function __construct() {
		//检测是否安装.
		if (is_file(CONFIG_PATH . 'install.lock')) {
			define('WULACMF_INSTALLED', true);
			bind('artisan\getCommands', function ($cmds) {
				$cmds['config']       = new ConfigureCommand();
				$cmds['create:table'] = new CreateTableCommand();
				$cmds['module']       = new ModuleCommand();

				return $cmds;
			});
		} else {
			define('WULACMF_INSTALLED', false);
			bind('artisan\getCommands', function ($cmds) {
				$cmds['wulacms:install'] = new InstallCommand();

				return $cmds;
			});
		}
	}
}