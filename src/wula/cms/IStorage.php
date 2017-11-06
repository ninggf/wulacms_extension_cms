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

interface IStorage {
	/**
	 * 保存.
	 *
	 * @param string $filename 文件名
	 * @param string $content  内容
	 *
	 * @return bool
	 */
	public function save($filename, $content);

	/**
	 * 加载文件正文.
	 *
	 * @param string $filename 文件名
	 *
	 * @return string
	 */
	public function load($filename);

	/**
	 * 删除文件.
	 *
	 * @param string $filename
	 *
	 * @return bool
	 */
	public function delete($filename);
}