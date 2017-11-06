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

/**
 * 缓存特性（基于浏览器的缓存，输出缓存头，包括给CDN用的缓存头）
 * @package wula\cms\feature
 */
class CacheFeature implements ICmsFeature {
	public function getPriority() {
		return 100;
	}

	public function getId() {
		return 'cache';
	}

	public function perform($url) {
		//需要防雪崩
	}

	public function postPerform($url) {

	}
}