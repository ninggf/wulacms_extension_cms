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

abstract class StorageDriver implements IStorage {
	protected $options;

	/**
	 * StorageDriver constructor.
	 *
	 * @param string $ssn 类似PDO的DSN字符串
	 */
	public function __construct($ssn) {
		$ssn           = str_replace(';', "\n", $ssn);
		$this->options = @parse_ini_string($ssn);
	}

	/**
	 * 初始化存储器.
	 *
	 * @return bool 初始化存储器成功返回true.
	 */
	public abstract function initialize();
}