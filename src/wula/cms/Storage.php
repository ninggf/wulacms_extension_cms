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
 * 页面正文存储器.
 *
 * @package wula\cms
 */
final class  Storage implements IStorage {
	private static $IMPLS = [];
	/**
	 * @var \wula\cms\StorageDriver
	 */
	private $driver;

	/**
	 * 创建一个存储器实例.
	 *
	 * @param string $ssn 类似PDO的DSN字符串
	 *
	 * @throws \Exception 当ssn格式错误时抛出
	 */
	public function __construct($ssn) {
		$ssns = explode(':', $ssn, 2);
		if (count($ssns) < 2 || empty($ssns[0]) || empty($ssns[1])) {
			throw_exception($ssn . ' is not valid');
		}
		if (!isset(self::$IMPLS[ $ssns[0] ])) {
			throw_exception($ssns . ' is not valid storage driver');
		}
		$driverCls = self::$IMPLS[ $ssns[0] ];
		/**@var \wula\cms\StorageDriver $driverClz */
		$driverClz = new $driverCls($ssns[1]);
		if ($driverClz->initialize()) {
			$this->driver = $driverClz;
		} else {
			log_warn('cannot initialize storage: ' . $ssn, 'storage');
		}
	}

	/**
	 * 注册存储器驱动器.
	 *
	 * @param string $name 存储器名称.
	 * @param string $clz  存储器类全名.
	 *
	 * @return bool 成功返回true。
	 */
	public static function registerDriver($name, $clz) {
		if (is_subclass_of($clz, StorageDriver::class)) {
			self::$IMPLS[ $name ] = $clz;

			return true;
		}

		return false;
	}

	/**
	 * 保存.
	 *
	 * @param string $filename 文件名
	 * @param string $content  内容
	 *
	 * @return bool
	 */
	public function save($filename, $content) {
		if ($this->driver) {
			return $this->driver->save($filename, $content);
		}

		return false;
	}

	/**
	 * 加载文件正文.
	 *
	 * @param string $filename 文件名
	 *
	 * @return string
	 */
	public function load($filename) {
		if ($this->driver) {
			return $this->driver->load($filename);
		}

		return '';
	}

	/**
	 * 删除文件.
	 *
	 * @param string $filename
	 *
	 * @return bool
	 */
	public function delete($filename) {
		if ($this->driver) {
			return $this->driver->delete($filename);
		}

		return false;
	}
}