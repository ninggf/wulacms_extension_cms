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

use wulaphp\db\DatabaseConnection;

/**
 * 模块安装器.
 *
 * @package wula\cms
 */
class ModuleInstaller {

	public function install(CmfModule $module, DatabaseConnection $con) {

	}

	public function uninstall(CmfModule $module, DatabaseConnection $con) {

	}

	public function upgrade(CmfModule $module, DatabaseConnection $con) {

	}

	public function update(CmfModule $module) {

	}
}