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

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\auth\Passport;

class InstallCommand extends ArtisanCommand {
	private $welcomeShow = false;

	public function cmd() {
		return 'wulacms:install';
	}

	public function desc() {
		return 'install cms';
	}

	protected function execute($options) {
		$wulacms = $this->color->str('wulacms', 'red');
		if (!$this->welcomeShow) {
			define('SUPPORTPATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'tpl' . DS);
			$this->welcomeShow = true;
			$this->log('Welcome to the ' . $wulacms . ' Installor!');
			$this->log();
			$this->log($wulacms . ' version: ' . WULA_VERSION . ' - ' . WULA_RELEASE);
			$this->log();
			$this->log(wordwrap($wulacms . ' is an ' . $this->color->str('open source, free', 'green') . ' CMS platform based on wulaphp. Now please flow the below steps to install it for you.', 80));
		}

		$this->log();
		$this->log('setp 1: database');
		$this->log('-----------------------------------------------');
		$dbhost = $this->get('host [localhost]', 'localhost');

		do {
			$dbport = $this->get('port [3306]', '3306');
			if (!preg_match('#^[1-9]\d{1,3}$#', $dbport)) {
				$this->log("\t" . $this->color->str('invalid prot number', null, 'red'));
			} else {
				break;
			}
		} while (true);

		$dbname    = $this->get('dbname [wula]', 'wula');
		$dbcharset = strtoupper($this->get('charset [utf8mb4]', 'utf8mb4'));
		$dbuser    = $this->get('username [root]', 'root');
		$dbpwd     = $this->get('password');
		$this->log();
		$this->log('setp 2: site info');
		$this->log('-----------------------------------------------');
		$username = $this->get('username [admin]', 'admin');
		$password = $this->get('password [random]', rand_str(15));
		do {
			$siteurl = $this->get('site url [/]', '/');
			if (!preg_match('#^(/|https?://[^/]+/)$#', $siteurl)) {
				$this->log("\t" . $this->color->str('invalid site url, / or start with http and end with /', null, 'red'));
			} else {
				break;
			}
		} while (true);

		$dashboard = $this->get('dashboard name [dashboard]', 'dashboard');

		$this->log();
		$this->log('setp 3: confirm');
		$this->log('-----------------------------------------------');
		$this->log('database info:');
		$this->log("\tserver  : " . $this->color->str($dbhost . ':' . $dbport, 'blue'));
		$this->log("\tdatabase: " . $this->color->str(str_pad($dbname, 20, ' ', STR_PAD_RIGHT), 'blue') . ' charset : ' . $this->color->str($dbcharset, 'blue'));
		$this->log("\tusername: " . $this->color->str(str_pad($dbuser, 20, ' ', STR_PAD_RIGHT), 'blue') . ' password: ' . $this->color->str($dbpwd, 'blue'));

		$this->log();
		$this->log('admin and dashboard:');
		$this->log("\tadmin    : " . $this->color->str($username, 'blue'));
		$this->log("\tsite url : " . $this->color->str($siteurl, 'blue'));
		$this->log("\tdashboard: " . $this->color->str($dashboard, 'blue'));

		$this->log();
		$confirm = strtoupper($this->get('is that correct? [Y/n]', 'Y'));
		if ($confirm !== 'Y') {
			return $this->execute($options);
		}
		// install database
		$this->log();
		$this->log('step 4: create configuration files');
		$dbconfig           = file_get_contents(SUPPORTPATH . 'dbconfig.php');
		$r['{db.host}']     = $dbhost;
		$r['{db.port}']     = $dbport;
		$r['{db.name}']     = $dbname;
		$r['{db.charset}']  = $dbcharset;
		$r['{db.user}']     = $dbuser;
		$r['{db.password}'] = $dbpwd;
		$dbconfig           = str_replace(array_keys($r), array_values($r), $dbconfig);
		$this->log('  create dbconfig.php ...', false);
		if (!@file_put_contents(CONFIG_PATH . 'dbconfig.php', $dbconfig)) {
			$this->error('cannot save database configuration file ' . CONFIG_PATH . 'dbconfig.php');

			return 1;
		} else {
			$this->log('  [' . $this->color->str('done', 'green') . ']');
		}

		$cfg = CONFIG_PATH . 'install_config.php';
		if (is_file($cfg)) {
			$dbconfig         = file_get_contents($cfg);
			$r['{dashboard}'] = $dashboard;
			$r['{url}']       = $siteurl;
			$this->log('  create config.php ...', false);
			$dbconfig = str_replace(array_keys($r), array_values($r), $dbconfig);
			if (!@file_put_contents(CONFIG_PATH . 'config.php', $dbconfig)) {
				$this->error('cannot save configuration file ' . CONFIG_PATH . 'config.php');

				return 1;
			}
			$this->log('  [' . $this->color->str('done', 'green') . ']');
		}
		// install modules
		$this->log();
		$this->log('step 5: install modules');
		$siteConfig = include CONFIG_PATH . 'config.php';
		$dbconfig   = include CONFIG_PATH . 'dbconfig.php';
		try {
			$dbc = $dbconfig->toArray();
			$db  = App::db($dbc);

			if ($db == null) {
				throw_exception('Cannot connect to the database');
			}

			if (isset($siteConfig['modules'])) {
				$modules = $siteConfig['modules'];

				foreach ($modules as $m) {
					$this->log("  install " . $m . ' ... ', false);
					$md = App::getModuleById($m);
					if ($md) {
						if ($md->install($db, 1)) {
							$this->log('  [' . $this->color->str('done', 'green') . ']');
						} else {
							$this->log(' [' . $this->color->str('error', 'red') . ']');
						}
					} else {
						$this->log(' [' . $this->color->str('error', 'red') . ']');
					}
				}
			}
		} catch (\Exception $e) {
			$this->error($e->getMessage());

			return 1;
		}
		// create admin
		$this->log();
		$this->log('step 7: create admin user');
		$user['username'] = $username;
		$user['nickname'] = '网站所有者';
		$user['hash']     = Passport::passwd($password);

		$uid = $db->insert($user)->into('user')->exec();
		$uid = $uid[0];

		$db->insert(['user_id' => $uid, 'role_id' => 2])->into('user_role')->exec();
		$this->log('  [' . $this->color->str('done', 'green') . ']');
		// done
		file_put_contents(CONFIG_PATH . 'install.lock', time());
		$this->log();
		$this->log('step 7: Congratulation');

		$this->log('Your admin password is:');
		$this->log($this->color->str($password, 'green'));
		$this->log('Please goto ' . $this->color->str(trailingslashit($siteurl) . $dashboard, 'blue') . ', enjoy your ' . $wulacms . ' please!');
		$this->log();

		return 0;
	}

	private function get($promot = '', $default = '') {
		if ($promot) {
			echo $promot, ' : ';
			flush();
		}

		$line = trim(fgets(STDIN));
		if (!$line) {
			return $default;
		}

		return $line;
	}
}