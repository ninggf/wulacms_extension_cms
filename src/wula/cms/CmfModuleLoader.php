<?php
/*
 * 模块加载器.
 *
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\cms;

use wulaphp\app\App;
use wulaphp\app\Module;
use wulaphp\app\ModuleLoader;

class CmfModuleLoader extends ModuleLoader {

	public function isEnabled(Module $module) {
		if (WULACMF_INSTALLED) {
			if (!$module instanceof CmfModule) {
				return false;
			}

			$m = App::table('module')->get(['name' => $module->getNamespace()]);
			if ($m['name']) {
				$module->installed        = true;
				$module->installedVersion = $m['version'];
				$module->upgradable       = version_compare($module->getCurrentVersion(), $m['version'], '>');
				$module->enabled          = $m['status'] == 1;

				return $module->enabled;
			}

			return false;
		}
		$name = $module->getNamespace();
		if ($name == 'core') {
			return true;
		}

		return false;
	}
}