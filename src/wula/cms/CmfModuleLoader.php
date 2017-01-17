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

use wulaphp\app\Module;
use wulaphp\app\ModuleLoader;

class CmfModuleLoader extends ModuleLoader {

	public function isEnabled(Module $module) {
		if (WULACMF_INSTALLED) {
			if (!$module instanceof CmfModule) {
				return false;
			}

			// TODO: 此处需要从数据进行检验
			return true;
		}
		$name = $module->getNamespace();
		if ($name == 'core') {
			return true;
		}

		return false;
	}
}