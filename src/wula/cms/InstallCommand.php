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
use wulaphp\db\dialect\DatabaseDialect;

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
		$this->log('setp 1: environment');
		$this->log('-----------------------------------------------');
		$env = $this->get('environment [dev]', 'dev');

		$this->log();
		$this->log('setp 2: database');
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
		$this->log('setp 3: site info');
		$this->log('-----------------------------------------------');
		$username  = $this->get('username [admin]', 'admin');
		$password  = $this->get('password [random]', rand_str(15));
		$domain    = $this->get('domain []', '');
		$dashboard = $this->get('dashboard name [backend]', 'backend');

		$this->log();
		$this->log('setp 4: confirm');
		$this->log('-----------------------------------------------');
		$this->log('environment: ' . $env);
		$this->log('database info:');
		$this->log("\tserver  : " . $this->color->str($dbhost . ':' . $dbport, 'blue'));
		$this->log("\tdatabase: " . $this->color->str(str_pad($dbname, 20, ' ', STR_PAD_RIGHT), 'blue') . ' charset : ' . $this->color->str($dbcharset, 'blue'));
		$this->log("\tusername: " . $this->color->str(str_pad($dbuser, 20, ' ', STR_PAD_RIGHT), 'blue') . ' password: ' . $this->color->str($dbpwd, 'blue'));

		$this->log();
		$this->log('admin and dashboard:');
		$this->log("\tadmin    : " . $this->color->str($username, 'blue'));
		$this->log("\tdashboard: " . $this->color->str($dashboard, 'blue'));
		$this->log("\tdomain:" . $this->color->str($domain, 'blue'));
		$this->log();
		$confirm = strtoupper($this->get('is that correct? [Y/n]', 'Y'));
		if ($confirm !== 'Y') {
			return $this->execute($options);
		}
		// install database
		$this->log();
		$this->log('step 5: create configuration files');
		$cfg = CONFIG_PATH . 'install_config.php';
		if (is_file($cfg)) {
			$dbconfig         = file_get_contents($cfg);
			$r['{dashboard}'] = $dashboard;
			$r['{domain}']    = $domain;
			$r['{name}']      = '';
			$this->log('  create config.php ...', false);
			$dbconfig = str_replace(array_keys($r), array_values($r), $dbconfig);
			if (!@file_put_contents(CONFIG_PATH . 'config.php', $dbconfig)) {
				$this->error('cannot save configuration file ' . CONFIG_PATH . 'config.php');

				return 1;
			}
			$this->log('  [' . $this->color->str('done', 'green') . ']');
		}
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
		if ($env != 'pro') {
			$dcf[] = '[app]';
			$dcf[] = 'debug = DEBUG_DEBUG';
			$dcf[] = 'dashboard = ' . $dashboard;
			$dcf[] = 'domain = ' . $domain;
			$dcf[] = '';
			$dcf[] = '[db]';
			$dcf[] = 'db.host = ' . $dbhost;
			$dcf[] = 'db.port = ' . $dbport;
			$dcf[] = 'db.name = ' . $dbname;
			$dcf[] = 'db.user = ' . $dbuser;
			$dcf[] = 'db.password = ' . $dbpwd;
			$dcf[] = 'db.charset = ' . $dbcharset;
			if (!@file_put_contents(CONFIG_PATH . '.env', implode("\n", $dcf))) {
				$this->error('cannot save .env file ');

				return 1;
			}
		}
		$dbconfig   = include CONFIG_PATH . 'dbconfig.php';
		$siteConfig = include CONFIG_PATH . 'install_config.php';
		try {
			// install modules
			$this->log();
			$this->log('step 6: install modules');
			$dbc = $dbconfig->toArray();
			unset($dbc['dbname']);
			$dialect = DatabaseDialect::getDialect($dbc);

			$dbs = $dialect->listDatabases();
			$rst = in_array($dbname, $dbs);
			if (!$rst) {
				$rst = $dialect->createDatabase($dbname, $dbcharset);
			}
			if (!$rst) {
				throw_exception('Cannot create the database ' . $dbname);
			}
			$db = App::db($dbconfig);
			if ($db == null) {
				throw_exception('Cannot connect to the database');
			}
			$modules = ['core', 'dashboard', 'media', 'cms'];
			if (isset($siteConfig['modules'])) {
				$modules = array_merge($modules, (array)$siteConfig['modules']);
			}

			foreach ($modules as $m) {
				$this->log("  install " . $m . ' ... ', false);
				$md = App::getModuleById($m);
				if ($md) {
					if ($md->install($db, true)) {
						$this->log('  [' . $this->color->str('done', 'green') . ']');
					} else {
						$this->log(' [' . $this->color->str('error', 'red') . ']');
					}
				} else {
					$this->log(' [' . $this->color->str('error', 'red') . ']');
				}
			}
		} catch (\Exception $e) {
			$this->error($e->getMessage());
			@unlink(CONFIG_PATH . '.env');
			@unlink(CONFIG_PATH . 'config.php');
			@unlink(CONFIG_PATH . 'dbconfig.php');

			return 1;
		}
		// create admin
		$this->log();
		$this->log('step 7: create admin user');
		$user['id']       = 1;
		$user['username'] = $username;
		$user['nickname'] = '网站所有者';
		$user['hash']     = Passport::passwd($password);

		$db->insert($user)->into('user')->exec();

		$db->insert([
			['user_id' => 1, 'role_id' => 1],
			['user_id' => 1, 'role_id' => 2]
		], true)->into('{user_role}')->exec();

		$this->log('  [' . $this->color->str('done', 'green') . ']');
		// done
		file_put_contents(CONFIG_PATH . 'install.lock', time());
		$this->log();
		$this->log('done: Congratulation');

		$this->log('Your admin password is:');
		$this->log($this->color->str($password, 'green'));
		if ($domain) {
			$this->log('Please goto ' . $this->color->str('http://' . $domain . '/' . $dashboard, 'blue') . ', enjoy your ' . $wulacms . ' please!');
		} else {
			$this->log('Please goto ' . $this->color->str('/' . $dashboard, 'blue') . ', enjoy your ' . $wulacms . ' please!');
		}
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