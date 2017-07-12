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
		if ($con->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
			return false;
		}
		$rst = $this->upgrade($con, $this->currentVersion);
		if ($rst) {
			$data['name']        = $this->namespace;
			$data['version']     = $this->currentVersion;
			$data['create_time'] = $data['update_time'] = time();
			$data['kernel']      = $kernel;
			$rst                 = $con->insert($data)->into('{module}')->exec(true);
		}
		return $rst;
	}

	/**
	 * 卸载.
	 * @return bool
	 */
	public final function uninstall() {
		if (!App::db()->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
			return false;
		}
		$rst = $this->onUninstall();
		if ($rst) {
			$db     = App::db();
			$tables = $this->getDefinedTables($db->getDialect());
			if ($tables ['tables']) {
				foreach ($tables ['tables'] as $table) {
					$db->exec('DROP TABLE IF EXISTS ' . $table);
				}
			}
			if ($tables ['views']) {
				foreach ($tables ['views'] as $table) {
					$db->exec('DROP VIEW IF EXISTS ' . $table);
				}
			}
			$db->delete()->from('{module}')->where(['name' => $this->namespace])->exec();
			App::cfg();
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
		if ($fromVer !== '0.0.0' && !$db->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
			return false;
		}
		$prev = $fromVer;
		foreach ($this->getVersionList() as $ver => $chang) {
			$func = 'upgradeTo' . str_replace('.', '_', $ver);
			if (version_compare($ver, $toVer, '<=') && version_compare($ver, $fromVer, '>')) {
				$sqls = $this->getSchemaSQLs($ver, $prev);
				if ($sqls) {
					$sr = ['{prefix}', '{encoding}'];
					$rp = [$db->getDialect()->getTablePrefix(), $db->getDialect()->getCharset()];
					foreach ($sqls as $_sql) {
						if (!$_sql) {
							continue;
						}
						$_sql = (array)$_sql;
						foreach ($_sql as $sql) {
							$sql = str_replace($sr, $rp, $sql);
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
			$tables = [];
			@include_once $sqlFile;
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

	/**
	 * 取当前模板所定义的表.
	 *
	 * @param \wulaphp\db\dialect\DatabaseDialect $dialect
	 *
	 * @return array
	 */
	public function getDefinedTables($dialect) {
		$sqlFile = MODULES_PATH . $this->dirname . DS . 'schema.sql.php';
		if (is_file($sqlFile)) {
			$file = file_get_contents($sqlFile);

			return $dialect->getTablesFromSQL($file);
		}

		return array();
	}
}