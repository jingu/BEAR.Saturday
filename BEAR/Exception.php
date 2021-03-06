<?php
/**
 * BEAR
 *
 * PHP versions 5
 *
 * @category  BEAR
 * @package   BEAR_Exception
 * @author    Akihito Koriyama <koriyama@bear-project.net>
 * @copyright 2008-2011 Akihito Koriyama  All rights reserved.
 * @license   http://opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: Release: @package_version@ $Id: Exception.php 2486 2011-06-06 07:44:05Z koriyama@bear-project.net $
 * @link      http://www.bear-project.net/
 */

/**
 * BEAR_Exception
 *
 * @category  BEAR
 * @package   BEAR_Exception
 * @author    Akihito Koriyama <koriyama@bear-project.net>
 * @copyright 2008-2011 Akihito Koriyama  All rights reserved.
 * @license   http://opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: Release: @package_version@ $Id: Exception.php 2486 2011-06-06 07:44:05Z koriyama@bear-project.net $
 * @link      http://www.bear-project.net/
 */
class BEAR_Exception extends Exception
{

    /**
     * 設定　クラス名
     */
    const CONFIG_CLASS = 'class';

    /**
     * 設定　コード
     */
    const CONFIG_CODE = 'code';

    /**
     * 設定　インフォ
     */
    const CONFIG_INFO = 'info';

    /**
     * 設定　リダイレクトURL
     */
    const CONFIG_REDIRECT = 'redirect';

    /**
     * 例外情報
     *
     * @var array
     *
     */
    protected $_info = array();

    /**
     * クラス
     *
     * @var string
     *
     */
    protected $_class;

    /**
     * リダイレクト
     *
     * @var string
     *
     */
    protected $_redirect;

    /**
     * デフォルトconfig
     *
     * @var array
     */
    protected $_default = array('class' => null,
        'code' => BEAR::CODE_ERROR,
        'info' => array(),
        'redirect' => null);

    /**
     * Constructor
     *
     * @param string $msg
     * @param array  $config
     */
    public function __construct($msg, array $config = array())
    {
        $config = array_merge($this->_default, (array)$config);
        parent::__construct($msg);
        $this->code = $config['code']; //native
        $this->_class = get_class($this);
        $this->_info = (array)$config['info'];
        $this->_redirect = $config['redirect'];
    }

    /**
     * 情報取得
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->_info;
    }

    /**
     * リダイレクト先取得
     *
     * @return string
     * @deprecated
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        $str = "exception '" . get_class($this) . "'\n" . "class::code '" . $this->_class . "::" . $this->code . "' \n";
        $str .= "with message '" . $this->message . "' \n" . "information " . var_export($this->_info, true) . " \n";
        $str .= "redirect to '" . $this->_redirect . "' \n";
        $str .= "Stack trace:\n" . "  " . str_replace("\n", "\n  ", $this->getTraceAsString());
        return $str;
    }
}
