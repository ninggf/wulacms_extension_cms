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
use wulaphp\cache\Cache;
use wulaphp\io\Response;
use wulaphp\mvc\view\View;
use wulaphp\util\RedisLock;

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
		if (APP_MODE == 'pro') {//只有线上才开启缓存功能
			defined('PAGE_CACHE_PREFIX') or define('PAGE_CACHE_PREFIX', md5(WEB_ROOT));
			$cacher = Cache::getCache();
			$domain = $_SERVER ['HTTP_HOST'];
			$qstr   = get_query_string();//参数
			$cid    = md5(PAGE_CACHE_PREFIX . $domain . $url . $qstr);
			$page   = $cacher->get($cid);
			//防雪崩机制: 加锁读缓存
			if (!$page && defined('ANTI_AVALANCHE') && ANTI_AVALANCHE) {
				$wait = false;
				RedisLock::ulock($cid, 20, $wait);
				if ($wait) {//被锁，说明有其它人会更新缓存 ，再读一次
					$page = $cacher->get($cid);
				}
			}
			//缓存命中
			if ($page && is_array($page)) {
				if (isset($wait) && defined('ANTI_AVALANCHE') && ANTI_AVALANCHE) {
					RedisLock::uunlock($cid);
				}
				if (@ob_get_status()) {
					@ob_end_clean();
				}
				@ob_start();
				if (defined('GZIP_ENABLED') && GZIP_ENABLED && extension_loaded("zlib")) {
					$gzip = @ini_get('zlib.output_compression');
					if (!$gzip) {
						@ini_set('zlib.output_compression', 1);
					}
					@ini_set('zlib.output_compression_level', 9);
				} else {
					@ini_set('zlib.output_compression', 0);
					@ini_set('zlib.output_compression_level', -1);
				}
				$page = apply_filter('alter_page_cache', $page);
				@list($content, $headers, $time, $expire) = $page;
				if (isset ($_SERVER ['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER ['HTTP_IF_MODIFIED_SINCE']) === $time) {
					$protocol = $_SERVER ["SERVER_PROTOCOL"];
					if ('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol) {
						$protocol = 'HTTP/1.0';
					}
					$status_header = "$protocol 304 Not Modified";
					Response::cache($expire, $time);
					@header($status_header, true, 304);
					if (php_sapi_name() == 'cgi-fcgi') {
						@header('Status: 304 Not Modified');
					}
				} else {
					if ($headers) {
						foreach ($headers as $h => $v) {
							@header($h . ': ', $v);
						}
					}
					if ($time !== 0) {
						Response::cache($expire, $time);
					} else if ($time == 0) {
						Response::nocache();
					}
					echo $content;
				}
				exit ();
			} else {
				//注册缓存内容处理器
				$unlock = isset($wait);
				bind('before_output_content', function ($content, View $view) use ($cid, $cacher, $unlock) {
					//需要缓存
					if (defined('CACHE_EXPIRE') && CACHE_EXPIRE > 0) {
						//插件或扩展可以将最后修改时间设为0来取消本次缓存.
						$time = apply_filter('alter_page_modified_time', time());
						$cacher->add($cid, [
							$content,//缓存内容
							$view->getHeaders(),//原输出头
							$time == 0 ? time() : $time,// 最后修改时间
							CACHE_EXPIRE//缓存时间
						], CACHE_EXPIRE);

						if ($time > 0) {
							Response::cache(CACHE_EXPIRE, $time);
						} else if ($time == 0) {
							Response::nocache();
						}
					}

					if ($unlock && defined('ANTI_AVALANCHE') && ANTI_AVALANCHE) {
						RedisLock::uunlock($cid);
					}

					return $content;
				}, 100, 2);
			}
		}
	}
}