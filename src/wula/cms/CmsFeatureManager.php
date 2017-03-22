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
/**
 * CMS特性管理器.
 *
 * @package wula\cms
 */
class CmsFeatureManager {
	private static $features = [];

	public static function register(ICmsFeature $feature) {
		self::$features[ $feature->getPriority() ][ $feature->getId() ] = $feature;
	}

	public static function getFeatures() {
		return self::$features;
	}
}