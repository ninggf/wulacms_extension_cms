<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wula\cms\feature;

use wula\cms\ICmsFeature;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Request;
use wulaphp\util\RedisClient;

/**
 * 防CC特性.
 *
 * @package wula\cms\feature
 */
class LimitFeature implements ICmsFeature {
	public function getPriority() {
		return 5;
	}

	public function getId() {
		return 'limit';
	}

	public function perform($url) {
		//防CC,取IP
		if (defined('ANTI_CC') && ANTI_CC) {
			$ip = Request::getIp();
			if (defined('ANTI_CC_WHITE') && ANTI_CC_WHITE) {
				$whites = explode(',', ANTI_CC_WHITE);
				if (in_array($ip, $whites)) return true;
			}
			$arg = explode('/', ANTI_CC);
			if (!isset($arg[1])) {
				$arg[1] = 60;
			}
			$arg[0] = intval($arg[0]);
			$arg[1] = intval($arg[1]);
			if (!$arg[0]) {//0就是关闭喽
				return true;
			}
			if (!$arg[1]) {
				$arg[1] = 60;
			}
			$cfgLoader = new ConfigurationLoader();
			$cfg       = $cfgLoader->loadConfig('ccredis');
			$cnf       = [
				$cfg->get('host'),
				$cfg->geti('port'),
				$cfg->geti('timeout', 5),
				$cfg->get('auth'),
				$cfg->geti('db', 0)
			];
			try {
				$redis = RedisClient::getRedis($cnf);
				if ($redis) {
					$key = 'c.' . $ip . ':' . ceil(time() / $arg[1]);
					$cnt = $redis->incr($key);
					$redis->setTimeout($key, $arg[1]);
					if ($cnt > $arg[0]) {//访问太快了
						return false;
					}
				}
			} catch (\Exception $e) {

			}
		}

		return true;
	}
}