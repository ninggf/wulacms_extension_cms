<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\cms;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;

class ModuleCommand extends ArtisanCommand {
	public function cmd() {
		return 'module';
	}

	public function desc() {
		return 'list, install, upgrade, uninstall module';
	}

	protected function getOpts() {
		return ['e' => 'list enabled modules only', 'a' => 'list available modules', 'i' => 'list installed modules', 'd' => 'list disabled modules', 'u' => 'list upgradable modules'];
	}

	protected function execute($options) {
		$cmd = $this->opt(-2);
		if ($cmd) {
			$module = $this->opt();
		} else {
			$module = '';
			$cmd    = $this->opt();
		}
		if (!$cmd) {
			$cmd = 'list';
		}
		if ($cmd != 'list' && !$module) {
			$this->error('give me a module please!');

			return 1;
		}
		if ($cmd != 'list') {
			$modulex = App::getModuleById($module);
			if (!$modulex) {
				$this->error('unkown module: ' . $module);

				return 1;
			}
		}
		if ($cmd == 'list') {
			if (empty($options)) {
				$options['e'] = true;
			}
			if ($options['e']) {
				$modules = App::modules('enabled');
			} else if ($options['i']) {
				$modules = App::modules('installed');
			} else if ($options['d']) {
				$modules = App::modules('disabled');
			} else if ($options['u']) {
				$modules = App::modules('upgradable');
			} else {
				$modules = App::modules('uninstalled');
			}

			/** @var CmfModule $module */
			foreach ($modules as $module) {
				$this->log(str_pad($module->getNamespace() . ' [' . $module->getCurrentVersion() . ']', 32, ' ', STR_PAD_RIGHT), false);
				$this->log($module->getName() . ' : ' . $module->getDescription());
			}
		} else if ($cmd == 'install') {
			try {
				/** @var CmfModule $modulex */
				if ($modulex->install(App::db())) {
					$this->success($module . ' installed successfully!');
				} else {
					$this->error('cannot install module : ' . $module);
				}
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}

		} else if ($cmd == 'upgrade') {
			try {
				/** @var CmfModule $modulex */
				$fromVer = $modulex->installedVersion;
				$toVer   = $modulex->getCurrentVersion();
				if (!$modulex->upgradable) {
					$this->log('no new version to upgrade');

					return 0;
				}
				if ($modulex->upgrade(App::db(), $toVer, $fromVer)) {
					$this->success($module . ' upgraded from ' . $fromVer . ' to ' . $toVer . ' successfully!');
				} else {
					$this->error('cannot upgrade module : ' . $module);
				}
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		} else {
			$this->help('unkown command:' . $this->color->str($cmd));
		}

		return 0;
	}

	protected function argDesc() {
		return '[list -[a|i|d|e] |<install|upgrade|uninstall> <module>]';
	}

}