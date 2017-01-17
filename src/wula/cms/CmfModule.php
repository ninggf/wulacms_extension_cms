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

use wulaphp\app\Module;
use wulaphp\db\DatabaseConnection;

abstract class CmfModule extends Module {
	protected $currentVersion;

	/**
	 * 当前版本.
	 *
	 * @return string
	 */
	public final function getCurrentVersion() {
		return $this->currentVersion;
	}

	/**
	 * 已经安装版本.
	 *
	 * @return string
	 */
	public function getInstalledVersion() {
		return $this->currentVersion;
	}

	/**
	 * 版本列表.
	 *
	 * @return array
	 */
	protected function getVersionList() {
		$v ['1.0.0'] = 0;

		return $v;
	}

	/**
	 * 依赖.
	 *
	 * @return array|null
	 */
	public function getDependences() {
		return null;
	}

	/**
	 * 运行环境检测.
	 *
	 * @return array
	 */
	public function getEnvCheckers() {
		return [];
	}

	/**
	 * 安装.
	 * @return bool
	 */
	public function install(DatabaseConnection $con) {
		return true;
	}

	/**
	 * 卸载.
	 * @return bool
	 */
	public function uninstall() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function upgrade() {
		return true;
	}
}