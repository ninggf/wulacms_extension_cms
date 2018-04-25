<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

\wula\cms\Storage::registerDriver('file', '\wula\cms\LocaleStorage');
\wula\cms\Storage::registerDriver('ssdb', '\wula\cms\SSDBStorageDriver');