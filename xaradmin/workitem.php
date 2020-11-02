<?php
/**
 * Workflow Module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Workflow Module
 * @link http://xaraya.com/index.php/release/188.html
 * @author Workflow Module Development Team
 */
/**
 * the workitem administration function
 *
 * @author mikespub
 * @access public
 */
function workflow_admin_workitem()
{
    // Security Check
    if (!xarSecurity::check('AdminWorkflow')) {
        return;
    }

    // Common setup for Galaxia environment
    sys::import('modules.workflow.lib.galaxia.config');
    $tplData = array();

    // Adapted from tiki-g-view_workitem.php

    include_once(GALAXIA_LIBRARY.'/processmonitor.php');

    if (!isset($_REQUEST['itemId'])) {
        $tplData['msg'] =  xarML("No item indicated");

        return xarTpl::module('workflow', 'admin', 'error', $tplData);
    }

    $wi = $processMonitor->monitor_get_workitem($_REQUEST['itemId']);
    if (is_numeric($wi['user'])) {
        $wi['user'] = xarUser::getVar('name', $wi['user']);
    }
    $tplData['wi'] =&  $wi;

    $tplData['stats'] =  $processMonitor->monitor_stats();

    $sameurl_elements = array(
    'offset',
    'sort_mode',
    'where',
    'find',
    'itemId'
);

    $tplData['mid'] =  'tiki-g-view_workitem.tpl';

    return $tplData;
}
