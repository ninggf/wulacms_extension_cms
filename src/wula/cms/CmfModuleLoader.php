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
use wulaphp\cache\RtCache;

class CmfModuleLoader extends ModuleLoader {
	private $modules = [];

	public function __construct() {
		if (WULACMF_INSTALLED) {
			$ms = RtCache::get('modules@cmf');
			if ($ms) {
				$this->modules = $ms;
			} else {
				$mt  = App::table('module');
				$mts = $mt->findAll(null, 'name,version,status,kernel');
				$ms  = [];
				foreach ($mts->toArray() as $m) {
					$ms[ $m['name'] ] = $m;
				}
				$this->modules = $ms;
				RtCache::add('modules@cmf', $ms);
				unset($mt);
			}
		}
	}

	/**
	 * @param \wulaphp\app\Module $module
	 *
	 * @return bool
	 */
	public function isEnabled(Module $module) {
		if (WULACMF_INSTALLED) {
			$name = $module->getNamespace();
			if (isset($this->modules[ $name ])) {
				$m                        = $this->modules[ $name ];
				$module->installed        = true;
				$module->installedVersion = $m['version'];
				$module->upgradable       = version_compare($module->getCurrentVersion(), $m['version'], '>');
				$module->enabled          = $m['status'] == 1;
				$module->isKernel         = $module->isKernel || $m['kernel'] == 1;

				return $module->enabled;
			}

			return false;
		} else {
			$name = $module->getNamespace();
			if ($name == 'system') {
				return true;
			}
		}

		return false;
	}
}