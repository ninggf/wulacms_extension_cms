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
use wulaphp\conf\ConfigurationLoader;

class ConfigureCommand extends ArtisanCommand {
	/**
	 * @var ConfigurationLoader
	 */
	private $loader;

	public function cmd() {
		return 'config';
	}

	public function desc() {
		return 'configure wulacms';
	}

	protected function execute($options) {
		$cmd = strtolower($this->opt(2));
		if (in_array($cmd, ['set', 'get', 'list', 'del'])) {
			$this->loader = App::cfgLoader();
		} elseif ($cmd) {
			$this->help('unkown command:' . $cmd);
		} else {
			$this->help();
		}

		return 0;
	}

	private function listConfig($group) {

	}

	private function set($key, $value, $group) {

	}

	private function get($key, $group) {

	}

	private function del($key, $group) {

	}

	protected function argDesc() {
		return '<set <key> <value> | get <key> | del <key> | list> [group]';
	}
}