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
 * CMS特性.
 *
 * @package wula\cms
 */
interface ICmsFeature {
	public function getPriority();

	public function getId();

	public function perform($url);
}