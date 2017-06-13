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
	/**
	 * 依赖.
	 *
	 * @return array|null
	 */
	public function getDependences() {
		return null;
	}

	/**
	 * 安装.
	 *
	 * @param DatabaseConnection $con
	 * @param int                $kernel 1代表安装的是内核模块.
	 *
	 * @return bool
	 */
	public final function install(DatabaseConnection $con, $kernel = 0) {
		$rst = $this->upgrade($con, $this->currentVersion);
		if ($rst) {
			$data['name']        = $this->namespace;
			$data['version']     = $this->currentVersion;
			$data['create_time'] = $data['update_time'] = time();
			$data['kernel']      = $kernel;
			$rst                 = $con->insert($data)->into('module')->exec(true);
		}

		return $rst;
	}

	/**
	 * 卸载.
	 * @return bool
	 */
	public final function uninstall() {
		$rst = $this->onUninstall();
		if ($rst) {

		}

		return $rst;
	}

	/**
	 * @param DatabaseConnection $db
	 * @param string             $toVer
	 * @param string             $fromVer
	 *
	 * @return bool
	 */
	public final function upgrade($db, $toVer, $fromVer = '0.0.0') {
		$prev = $fromVer;
		foreach ($this->getVersionList() as $ver) {
			$func = 'upgradeTo' . str_replace('.', '_', $ver);
			if (version_compare($ver, $toVer, '<=') && version_compare($ver, $fromVer, '>')) {
				$sqls = $this->getSchemaSQLs($ver, $prev);
				if ($sqls) {
					foreach ($sqls as $_sql) {
						if (!$_sql) {
							continue;
						}
						$_sql = (array)$_sql;
						foreach ($_sql as $sql) {
							$rst = $db->exec($sql);
							if (!$rst) {
								throw_exception($db->error);
							}
						}
					}
				}
				if ($func && method_exists($this, $func)) {
					$rst = $this->{$func}($db);
					if (!$rst) {
						return false;
					}
				}
			}
		}

		return true;
	}

	protected function onUninstall() {
		return true;
	}

	protected function getSchemaSQLs($toVer, $fromVer = '0.0.0') {
		$sqls    = array();
		$sqlFile = MODULES_PATH . $this->dirname . DS . 'schema.sql.php';
		if (is_file($sqlFile)) {
			include_once $sqlFile;
			if (!empty ($tables)) {
				foreach ($tables as $ver => $var) {
					if (version_compare($ver, $toVer, '<=') && version_compare($ver, $fromVer, '>')) {
						$sqls = array_merge($sqls, $var);
					}
				}
			}
		}

		return $sqls;
	}
}