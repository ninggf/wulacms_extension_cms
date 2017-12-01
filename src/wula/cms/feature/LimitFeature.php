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
use wulaphp\io\Request;

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
		$ip = Request::getIp();

	}
}