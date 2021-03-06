<?php

/**
 * プラグインモデル
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.Model
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */

/**
 * プラグインモデル
 *
 * @package Baser.Model
 */
class Plugin extends AppModel {

/**
 * クラス名
 *
 * @var string
 * @access public
 */
	public $name = 'Plugin';

/**
 * ビヘイビア
 * 
 * @var array
 * @access public
 */
	public $actsAs = array('BcCache');

/**
 * データベース接続
 *
 * @var string
 * @access public
 */
	public $useDbConfig = 'baser';

/**
 * バリデーション
 *
 * @var array
 * @access public
 */
	public $validate = array(
		'name' => array(
			array('rule' => array('alphaNumericPlus'),
				'message' => 'プラグイン名は半角英数字、ハイフン、アンダースコアのみが利用可能です。',
				'required' => true),
			array('rule' => array('isUnique'),
				'on' => 'create',
				'message' => '指定のプラグインは既に使用されています。'),
			array('rule' => array('maxLength', 50),
				'message' => 'プラグイン名は50文字以内としてください。')
		),
		'title' => array(
			array('rule' => array('maxLength', 50),
				'message' => 'プラグインタイトルは50文字以内とします。')
		)
	);

/**
 * データベースを初期化する
 * 既存のテーブルは上書きしない
 *
 * @param string $dbConfigName データベース設定名
 * @param string $pluginName プラグイン名
 * @param bool $loadCsv CSVファイル読込するかどうか
 * @param string $filterTable テーブル指定
 * @param string $filterType 更新タイプ指定
 * @return bool
 */
	public function initDb($dbConfigName = 'plugin', $pluginName = '', $options = array()) {
		$options = array_merge(array(
			'loadCsv'		=> true,
			'filterTable'	=> '',
			'filterType'	=> 'create',
			'dbDataPattern'	=> ''
		), $options);
		return parent::initDb($dbConfigName, $pluginName, array(
			'loadCsv'		=> $options['loadCsv'],
			'filterTable'	=> $options['filterTable'],
			'filterType'	=> $options['filterType'],
			'dbDataPattern'	=> $options['dbDataPattern']
		));
	}

/**
 * データベースをプラグインインストール前の状態に戻す
 * 
 * @param string $plugin プラグイン名
 * @return bool
 */
	public function resetDb($plugin) {
		$path = BcUtil::getSchemaPath($plugin);

		if (!$path) {
			return true;
		}

		$baserDb = ConnectionManager::getDataSource('baser');
		$baserDb->cacheSources = false;
		$baserListSources = $baserDb->listSources();
		$baserPrefix = $baserDb->config['prefix'];
		$pluginDb = ConnectionManager::getDataSource('plugin');
		$pluginDb->cacheSources = false;
		$pluginListSources = $pluginDb->listSources();
		$pluginPrefix = $pluginDb->config['prefix'];

		$Folder = new Folder($path);
		$files = $Folder->read(true, true);

		if (empty($files[1])) {
			return true;
		}

		$tmpdir = TMP . 'schemas' . DS;
		$result = true;

		foreach ($files[1] as $file) {

			$oldSchemaPath = '';

			if (preg_match('/^(.*?)\.php$/', $file, $matches)) {

				$type = 'drop';
				$table = $matches[1];
				$File = new File($path . DS . $file);
				$data = $File->read();
				if (preg_match('/(public|var)\s+\$connection\s+=\s+\'([a-z]+?)\';/', $data, $matches)) {
					$conType = $matches[2];
					$listSources = ${$conType . 'ListSources'};
					$prefix = ${$conType . 'Prefix'};
				} else {
					continue;
				}

				$schemaPath = $tmpdir;
				if (preg_match('/^create_(.*?)\.php$/', $file, $matches)) {
					$type = 'drop';
					$table = $matches[1];
					if (!in_array($prefix . $table, $listSources)) {
						continue;
					}
					copy($path . DS . $file, $tmpdir . $table . '.php');
				} elseif (preg_match('/^alter_(.*?)\.php$/', $file, $matches)) {
					$type = 'alter';
					$table = $matches[1];
					if (!in_array($prefix . $table, $listSources)) {
						continue;
					}

					$corePlugins = implode('|', Configure::read('BcApp.corePlugins'));
					if (preg_match('/^(' . $corePlugins . ')/', Inflector::camelize($table), $matches)) {
						$pluginName = $matches[1];
					}

					$File = new File($path . DS . $file);
					$data = $File->read();
					$data = preg_replace('/class\s+' . Inflector::camelize($table) . 'Schema/', 'class Alter' . Inflector::camelize($table) . 'Schema', $data);
					$oldSchemaPath = $tmpdir . $file;
					$File = new File($oldSchemaPath);
					$File->write($data);

					if ($conType == 'baser') {
						$schemaPath = BcUtil::getSchemaPath() . DS;
					} else {
						$schemaPath = BcUtil::getSchemaPath($pluginName) . DS;
					}
				} elseif (preg_match('/^drop_(.*?)\.php$/', $file, $matches)) {
					$type = 'create';
					$table = $matches[1];
					if (in_array($prefix . $table, $listSources)) {
						continue;
					}
					copy($path . DS . $file, $tmpdir . $table . '.php');
				} else {
					if (!in_array($prefix . $table, $listSources)) {
						continue;
					}
					copy($path . DS . $file, $tmpdir . $table . '.php');
				}

				if ($conType == 'baser') {
					$db = $baserDb;
				} else {
					$db = $pluginDb;
				}

				if (!$db->loadSchema(array('type' => $type, 'path' => $schemaPath, 'file' => $table . '.php', 'dropField' => true, 'oldSchemaPath' => $oldSchemaPath))) {
					$result = false;
				}
				@unlink($tmpdir . $table . '.php');
				if (file_exists($oldSchemaPath)) {
					unlink($oldSchemaPath);
				}
			}
		}

		return $result;
	}

/**
 * データベースの構造を変更する
 * 
 * @param string $plugin プラグイン名
 * @param string $dbConfigName データベース設定名
 * @param string $filterTable テーブル指定
 * @return bool
 */
	public function alterDb($plugin, $dbConfigName = 'baser', $filterTable = '') {
		return parent::initDb($dbConfigName, $plugin, false, $filterTable, 'alter');
	}

/**
 * 指定したフィールドに重複値があるかチェック
 *
 * @param string $fieldName チェックするフィールド名
 * @return bool
 */
	public function hasDuplicateValue($fieldName) {
		$this->cacheQueries = false;

		$duplication = $this->find('all', array(
			'fields' => array(
				"{$this->alias}.{$fieldName}"
			),
			'group' => array(
				"{$this->alias}.{$fieldName} HAVING COUNT({$this->alias}.id) > 1"
			)
		));

		return !empty($duplication);
	}

/**
 * 優先順位を連番で振り直す
 *
 * @return bool
 */
	public function rearrangePriorities() {
		$this->cacheQueries = false;
		$datas = $this->find('all', array(
			'order' => 'Plugin.priority'
		));

		$count = count($datas);
		for ($i = 0; $i < $count; $i++) {
			$datas[$i]['Plugin']['priority'] = $i + 1;
		}

		if (!$this->saveMany($datas)) {
			return false;
		}
		return true;
	}

/**
 * 優先順位を変更する
 *
 * @param string|int $id 起点となるプラグインのID
 * @param string|int $offset 変更する範囲の相対位置
 * @param array $conditions find条件
 * @return bool
 */
	public function changePriority($id, $offset, $conditions = array()) {
		$offset = intval($offset);
		if ($offset === 0) {
			return true;
		}

		$field = 'priority';
		$alias = $this->alias;

		// 一時的にキャッシュをOFFする
		$this->cacheQueries = false;

		$current = $this->findById($id, array("{$alias}.id", "{$alias}.{$field}"));

		// currentを含め変更するデータを取得
		if ($offset > 0) { // DOWN
			$order = array("{$alias}.{$field}");
			$conditions["{$alias}.{$field} >="] = $current[$alias][$field];
		} else { // UP
			$order = array("{$alias}.{$field} DESC");
			$conditions["{$alias}.{$field} <="] = $current[$alias][$field];
		}

		$datas = $this->find('all', array(
			'conditions' => $conditions,
			'fields' => array("{$alias}.id", "{$alias}.{$field}", "{$alias}.name"),
			'order' => $order,
			'limit' => abs($offset) + 1,
			'recursive' => -1
		));

		if (empty($datas)) {
			return false;
		}

		//データをローテーション
		$count = count($datas);
		$currentNewValue = $datas[$count - 1][$alias][$field];
		for ($i = $count - 1; $i > 0; $i--) {
			$datas[$i][$alias][$field] = $datas[$i - 1][$alias][$field];
		}
		$datas[0][$alias][$field] = $currentNewValue;

		if (!$this->saveMany($datas)) {
			return false;
		};

		return true;
	}

/**
 * プラグインのディレクトリパスを取得
 *
 * @param string $pluginName プラグイン名
 * @return string|null
 */
	public function getDirectoryPath($pluginName) {
		$paths = App::path('Plugin');
		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->read(true, true, true);
			foreach ($files[0] as $dir) {
				if (basename($dir) === $pluginName) {
					return $dir;
				}
			};
		}
		return null;
	}

/**
 * プラグイン情報を取得する
 *
 * @param array $datas プラグインのデータ配列
 * @param string $file プラグインファイルのパス
 * @return array
 */
	public function getPluginInfo($datas, $file) {
		$plugin = basename($file);
		$pluginData = array();
		$exists = false;
		foreach ($datas as $data) {
			if ($plugin == $data['Plugin']['name']) {
				$pluginData = $data;
				$exists = true;
				break;
			}
		}

		// プラグインのバージョンを取得
		$corePlugins = Configure::read('BcApp.corePlugins');
		$core = false;
		if (in_array($plugin, $corePlugins)) {
			$core = true;
			$version = getVersion();
		} else {
			$version = getVersion($plugin);
		}

		// 設定ファイル読み込み
		$title = $description = $author = $url = $adminLink = '';

		// TODO 互換性のため古いパスも対応
		$oldAppConfigPath = $file . DS . 'Config' . DS . 'config.php';
		$appConfigPath = $file . DS . 'config.php';
		if (!file_exists($appConfigPath)) {
			$appConfigPath = $oldAppConfigPath;
		}

		if (file_exists($appConfigPath)) {
			include $appConfigPath;
		} elseif (file_exists($oldAppConfigPath)) {
			include $oldAppConfigPath;
		}

		if (isset($title)) {
			$pluginData['Plugin']['title'] = $title;
		}
		if (isset($description)) {
			$pluginData['Plugin']['description'] = $description;
		}
		if (isset($author)) {
			$pluginData['Plugin']['author'] = $author;
		}
		if (isset($url)) {
			$pluginData['Plugin']['url'] = $url;
		}

		$pluginData['Plugin']['update'] = false;
		$pluginData['Plugin']['old_version'] = false;
		$pluginData['Plugin']['core'] = $core;

		if ($exists) {

			if (isset($adminLink)) {
				$pluginData['Plugin']['admin_link'] = $adminLink;
			}
			// バージョンにBaserから始まるプラグイン名が入っている場合は古いバージョン
			if (!$pluginData['Plugin']['version'] && preg_match('/^Baser[a-zA-Z]+\s([0-9\.]+)$/', $version, $matches)) {
				$pluginData['Plugin']['version'] = $matches[1];
				$pluginData['Plugin']['old_version'] = true;
			} elseif (verpoint($pluginData['Plugin']['version']) < verpoint($version) && !in_array($pluginData['Plugin']['name'], Configure::read('BcApp.corePlugins'))) {
				$pluginData['Plugin']['update'] = true;
			}
			$pluginData['Plugin']['registered'] = true;
		} else {
			// バージョンにBaserから始まるプラグイン名が入っている場合は古いバージョン
			if (preg_match('/^Baser[a-zA-Z]+\s([0-9\.]+)$/', $version, $matches)) {
				$version = $matches[1];
				$pluginData['Plugin']['old_version'] = true;
			}
			$pluginData['Plugin']['id'] = '';
			$pluginData['Plugin']['name'] = $plugin;
			$pluginData['Plugin']['created'] = '';
			$pluginData['Plugin']['version'] = $version;
			$pluginData['Plugin']['status'] = false;
			$pluginData['Plugin']['modified'] = '';
			$pluginData['Plugin']['admin_link'] = '';
			$pluginData['Plugin']['registered'] = false;
		}
		return $pluginData;
	}

/**
 * プラグイン管理のリンクを指定したユーザーのお気に入りに追加
 *
 * @param string $pluginName プラグイン名
 * @param array $user ユーザーデータの配列
 * @return void
 */
	public function addFavoriteAdminLink($pluginName, $user)
	{
		$plugin = $this->findByName($pluginName);
		$dirPath = $this->getDirectoryPath($pluginName);
		$pluginInfo = $this->getPluginInfo(array($plugin), $dirPath);

		//リンクが設定されていない
		if (empty($pluginInfo['Plugin']['admin_link'])) {
			return;
		}

		if (ClassRegistry::isKeySet('Favorite')) {
			$this->Favorite = ClassRegistry::getObject('Favorite');
		} else {
			$this->Favorite = ClassRegistry::init('Favorite');
		}

		$adminLinkUrl = Router::url($pluginInfo['Plugin']['admin_link']);
		$baseUrl = Configure::read('App.baseUrl');
		if ($baseUrl) {
			$adminLinkUrl = preg_replace('/^' . preg_quote($baseUrl, '/') . '/', '', $adminLinkUrl);
		}
		$request = Router::getRequest();
		if ($request) {
			$base = $request->base;
			if ($request->base) {
				$adminLinkUrl = preg_replace('/^' . preg_quote($request->base, '/') . '/', '', $adminLinkUrl);
			}
		}

		//すでにお気に入りにリンクが含まれている場合
		if ($this->Favorite->find('count', array('conditions' => array('Favorite.url' => $adminLinkUrl, 'Favorite.user_id' => $user['id']))) > 0) {
			return;
		}

		$favorite = array(
			'name' => $pluginInfo['Plugin']['title'] . '管理',
			'url' => $adminLinkUrl,
			'sort' => $this->Favorite->getMax('sort') + 1,
			'user_id' => $user['id'],
		);

		$this->Favorite->create($favorite);
		$this->Favorite->save();
	}
}
