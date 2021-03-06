<?php
/**
 * BEAR
 *
 * PHP versions 5
 *
 * @category  BEAR
 * @package   BEAR_Log
 * @author    Akihito Koriyama <koriyama@bear-project.net>
 * @copyright 2008-2011 Akihito Koriyama  All rights reserved.
 * @license   http://opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: Release: @package_version@ $Id: Log.php 2565 2011-06-19 16:15:22Z koriyama@bear-project.net $
 * @link      http://www.bear-project.net/
 */

/**
 * ログ
 *
 * 開発時のログを扱います
 *
 * @category  BEAR
 * @package   BEAR_Log
 * @author    Akihito Koriyama <koriyama@bear-project.net>
 * @copyright 2008-2011 Akihito Koriyama  All rights reserved.
 * @license   http://opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: Release: @package_version@ $Id: Log.php 2565 2011-06-19 16:15:22Z koriyama@bear-project.net $
 * @link      http://www.bear-project.net/
 *
 * @Singleton
 */
class BEAR_Log extends BEAR_Base
{

    /**
     * アプリケーションログ
     *
     * @var array
     */
    private $_logs = array();

    /**
     * テンポラリーログ記録開始オフセット
     *
     * @var int
     */
    private $_temporaryOffset = 0;

    /**
     * リソースログ
     */
    private $_resourceLog = array();

    /**
     * FirePHP表示もするログキー
     *
     * @var string
     */
    private $_fbKeys = array('onInit');

    /**
     * アプリケーションログを記録
     *
     * <pre>
     * アプリケーションログを記録します。
     * このログは画面上で確認できる一時的なスクリーンログです。
     * </pre>
     *
     * @param string $logKey   ログキー
     * @param mixed  $logValue 値
     *
     * @return void
     */
    public function log($logKey, $logValue = null)
    {
        if ($this->_config['debug'] !== true) {
            return;
        }
        $this->_logs[][$logKey] = $logValue;
        $showFirePHP = (isset($_GET['_firelog']) || array_search($logKey, $this->_fbKeys) !== false);
        if (class_exists('FB', false) && $showFirePHP) {
            $color = ($logValue) ? 'black' : 'grey';
            FB::group($logKey, array('Collapsed' => true, 'Color' => $color));
            FB::log($logValue);
            FB::groupEnd();
        }
        if (!is_scalar($logValue)) {
            $logValue = print_r($logValue, true);
            $logValue = str_replace("\n", '', $logValue);
            $logValue = preg_replace("/\s+/s", " ", $logValue);
        }
    }

    /**
     * リソースログ
     *
     * <pre>
     * read操作はログには記録されません。
     * </pre>
     *
     * @param string $method メソッド
     * @param string $uri    URI
     * @param array  $values 引数
     * @param int    $code   コード
     *
     * @return void
     */
    public function resourceLog($method, $uri, array $values, $code)
    {
        $this->_resourceLog[] = compact('method', 'uri', 'values', 'code');
        $fullUri = ("{$method} {$uri}") . ($values ? '?' . http_build_query($values) : '') . ' ' . $code;
        $this->log('Resource', $fullUri);
        if ($method == BEAR_Resource::METHOD_READ) {
            return;
        }
        if (is_callable(array('App', 'onCall'))) {
            $result = call_user_func(array('App', 'onCall'), 'resource', array('uri' => $fullUri));
        } else {
            $result = true;
        }
        if ($result !== false) {
            $logger = &Log::singleton('syslog', LOG_USER, 'BEAR RES');
            $logger->log($fullUri);
        }
    }

    /**
     * スクリプトシャットダウン時のログ処理
     *
     * shutdown関数から呼ばれます
     *
     * @return void
     */
    public static function onShutdownDebug()
    {
        $bearLog = BEAR::dependency('BEAR_Log');
        if (class_exists("SQLiteDatabase",false)) {
            $bearLog->shutdonwnDbDebug();
        } else {
            $bearLog->shutdownDebug(false);
            $ob = ob_get_clean();
            $ob = str_replace('?id=@@@log_id@@@', '?nosqlite', $ob);
            echo $ob;
        }
    }

    /**
     * Write page log onto DB on shutdown
     *
     * @return void
     */
    public function shutdonwnDbDebug()
    {
        $db = $this->getPageLogDb();
        $log = $this->shutdownDebug();
        $log = sqlite_escape_string(serialize($log));
        $sql = "INSERT INTO pagelog(log) VALUES('{$log}')";
        $db->queryExec($sql);
        $id = $db->lastInsertRowid();
        $ob = ob_get_clean();
        $ob = str_replace('@@@log_id@@@', $id, $ob);
        echo $ob;
        // keep only
        $db->query("DELETE FROM pagelog WHERE rowid IN (SELECT rowid FROM pagelog ORDER BY rowid LIMIT -1 OFFSET 100");
    }

    /**
     * Get log db
     *
     * @return void
     */
    public function getPageLogDb()
    {
        $file = _BEAR_APP_HOME . '/logs/pagelog.sq3';
        $db = new SQLiteDatabase($file);
        if ($db === false) {
            throw new BEAR_Exception('sqlite error');
        }
        $sql = <<<____SQL
CREATE TABLE pagelog (
	 "log" text NOT NULL
);
____SQL;
        $db->queryExec($sql);
        return $db;
    }

    /**
     * Get page log
     *
     * @param array $get $_GET
     */
    public function getPageLog(array $get)
    {
        if (!class_exists("SQLiteDatabase", false)) {
            $pageLogPath = _BEAR_APP_HOME . '/logs/page.log';
            include_once 'BEAR/Util.php';
            $pageLog = file_exists($pageLogPath) ?
            BEAR_Util::unserialize(file_get_contents($pageLogPath)) : array();
            return $pageLog;
        }
        $db = $this->getPageLogDb();
        if (isset($get['id'])) {
            //    $rowid = sqlite
            $rowid = sqlite_escape_string($get['id']);
            $result = $db->query("SELECT log FROM pagelog WHERE rowid = {$rowid}");
        } else {
            $result = $db->query("SELECT log FROM pagelog ORDER BY rowid DESC LIMIT 1");
        }
        $log = $result->fetchAll();
        $pageLog = unserialize($log[0]['log']);
        return $pageLog;
    }

    /**
     * スクリプトシャットダウン時のログ処理
     *
     * <pre>
     * アプリケーションログ、smartyアサインログ、グローバル変数ログ、
     * リクエストURIをシリアライズしてファイル保存します。
     * デバックモードの時のみ使用します。
     * 保存されたログは/__bear/のLogタブでブラウズできます。
     * シャットダウン時実行のメソッドとしてフレームワーク内で登録され、
     * スクリプト終了時に実行されます。
     * フレームワーク内で使用されます。
     * </pre>
     *
     * @return void
     * @ignore
     * @throws BEAR_Log_Exception
     */
    public function shutdownDebug($return = true)
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (strpos($_SERVER['REQUEST_URI'], '__bear/') !== false) {
            return;
        }
        restore_error_handler();
        error_reporting(0);
        try {
            $isBeardev = isset($_SERVER['__bear']);
            $pageLogPath = _BEAR_APP_HOME . '/logs/' . 'debug' . '.log';
            file_put_contents($pageLogPath, $this->_config['debug']);
            if ($isBeardev || PHP_SAPI === 'cli') {
                return;
            }
            $log = array();
            $pageLogPath = _BEAR_APP_HOME . '/logs/page.log';
            if (file_exists($pageLogPath) && !is_writable($pageLogPath)) {
                // 書き込み権限のエラー
                Panda::error('Permission denied.', "[$pageLogPath] is not writable.");
                return;
            }
            // page ログ
            $pageLog = file_exists($pageLogPath) ? BEAR_Util::unserialize(file_get_contents($pageLogPath)) : '';
            //show_vars
            if (!function_exists('show_vars')) {
                include 'BEAR/vendors/debuglib.php';
            }
            $log['var'] = show_vars('trim_tabs:2;show_objects:1;max_y:100;avoid@:1; return:1');
            if (class_exists('BEAR_Smarty', false)) {
                $smarty = BEAR::dependency('BEAR_Smarty');
                unset($smarty->_tpl_vars['content_for_layout']);
                $log['smarty'] = $smarty->_tpl_vars;
            } else {
                $log['smarty'] = '';
            }
            $oldPageLog = isset($pageLog['page']) ? $pageLog['page'] : array();
            $newPageLog = array('page' => $this->_logs,
                'uri' => $_SERVER['REQUEST_URI']);
            $oldPageLog[] = $newPageLog;
            if (count($oldPageLog) > 3) {
                array_shift($oldPageLog);
            }
            $log += array('page' => $oldPageLog,
                'include' => get_included_files(),
                'class' => get_declared_classes());
            if (isset($_SERVER['REQUEST_URI'])) {
                $log += array(
                    'uri' => $_SERVER['REQUEST_URI']);
            }
            $reg = BEAR_Util::getObjectVarsRecursive(BEAR::getAll());
            $log['reg'] = $reg;
            if ($return === true) {
                return $log;
            } else {
                file_put_contents($pageLogPath, serialize($log));
            }
        } catch(Exception $e) {
            throw $e;
        }
    }

    /**
     * AJAX終了処理
     *
     * ajax.logをlogフォルダに作成する
     *
     * @return void
     */
    private function _onShutdownDebugAjax()
    {
        $ajaxLogPath = _BEAR_APP_HOME . '/logs/ajax.log';
        $ajaxLog = file_exists($ajaxLogPath) ? BEAR_Util::unserialize(file_get_contents($ajaxLogPath)) : null;
        $log = array('page' => $this->_logs, 'uri' => $_SERVER['REQUEST_URI']);
        $ajaxLog[] = $log;
        if (count($ajaxLog) > 5) {
            array_shift($ajaxLog);
        }
        file_put_contents(_BEAR_APP_HOME . '/logs/ajax.log', serialize($ajaxLog));
    }

    /**
     * ログを記録開始
     *
     * @return void
     */
    public function start()
    {
        $this->_temporaryOffset = count($this->_logs);
    }

    /**
     * ログを記録開始
     *
     * @return mixed
     */
    public function stop()
    {
        $length = count($this->_logs) - $this->_temporaryOffset;
        $result = array_slice($this->_logs, $this->_temporaryOffset, $length);
        return $result;
    }
}
