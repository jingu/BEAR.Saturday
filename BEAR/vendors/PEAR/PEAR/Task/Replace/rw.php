<?php
/**
 * <tasks:replace> - read/write version
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: rw.php 2551 2011-06-14 09:32:14Z koriyama@bear-project.net $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a10
 */
/**
 * Base class
 */
require_once 'PEAR/Task/Replace.php';
/**
 * Abstracts the replace task xml.
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.3
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a10
 */
class PEAR_Task_Replace_rw extends PEAR_Task_Replace
{
    function PEAR_Task_Replace_rw(&$pkg, &$config, &$logger, $fileXml)
    {
        parent::PEAR_Task_Common($config, $logger, PEAR_TASK_PACKAGE);
        $this->_contents = $fileXml;
        $this->_pkg = &$pkg;
        $this->_params = array();
    }

    function validate()
    {
        return $this->validateXml($this->_pkg, $this->_params, $this->config, $this->_contents);
    }

    function setInfo($from, $to, $type)
    {
        $this->_params = array('attribs' => array('from' => $from, 'to' => $to, 'type' => $type));
    }

    function getName()
    {
        return 'replace';
    }

    function getXml()
    {
        return $this->_params;
    }
}
?>