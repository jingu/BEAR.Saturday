<?php
/**
 * BEAR
 *
 * PHP versions 5
 *
 * @category   BEAR
 * @package    BEAR_Resource
 * @subpackage Request
 * @author     Akihito Koriyama <koriyama@bear-project.net>
 * @copyright  2008-2011 Akihito Koriyama All rights reserved.
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @version    SVN: Release: @package_version@ $Id:$
 * @link      http://www.bear-project.net/
 */

/**
 * AJAXリソースリクエスト
 *
 * @category   BEAR
 * @package    BEAR_Resource
 * @subpackage Request
 * @author     Akihito Koriyama <koriyama@bear-project.net>
 * @copyright  2008-2011 Akihito Koriyama All rights reserved.
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @version    Release: @package_version@ $Id:$
 * @link       http://www.bear-project.net
 */
class BEAR_Resource_Request_Ajax extends BEAR_Base
{
    /**
     * JS取得
     *
     * @return string
     */
    public function getJs()
    {
        $requestId = md5(serialize($this->_config) .  session_id());
        $params = array('key' => $requestId);
        $json = json_encode($params);
        $js = "<script type=\"text/javascript\">$(\"#{$requestId}\").ready(function(){ $(\"#{$requestId}\").load(\"/bear/r/\", $json); });</scprit>";
        return "<span id=\"{$requestId}\">*</span>" . $js;
    }
}

