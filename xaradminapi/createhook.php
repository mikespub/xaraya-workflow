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
 * start a create activity for a module item - hook for ('item','create','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return array extrainfo array
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function workflow_adminapi_createhook($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('object id', 'admin', 'createhook', 'workflow');
        throw new BadParameterException($vars, $msg);
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = [];
    }

    // When called via hooks, modname wil be empty, but we get it from the
    // extrainfo or the current module
    if (empty($modname)) {
        if (!empty($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = xarMod::getName();
        }
    }
    $modid = xarMod::getRegID($modname);
    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', 'createhook', 'workflow');
        throw new BadParameterException($vars, $msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
            $itemtype = $extrainfo['itemtype'];
        } else {
            $itemtype = 0;
        }
    }

    // see if we need to start some workflow activity here
    if (!empty($itemtype)) {
        $activityId = xarModVars::get('workflow', "$modname.$itemtype.create");
    }
    if (empty($activityId)) {
        $activityId = xarModVars::get('workflow', "$modname.create");
    }
    if (empty($activityId)) {
        $activityId = xarModVars::get('workflow', 'default.create');
    }
    if (empty($activityId)) {
        return $extrainfo;
    }

    if (!xarMod::apiFunc(
        'workflow',
        'user',
        'run_activity',
        ['activityId' => $activityId,
                             'auto' => 1,
                             // standard arguments for use in activity code
                             'module' => $modname,
                             'itemtype' => $itemtype,
                             'itemid' => $objectid, ]
    )) {
        return $extrainfo;
    }

    return $extrainfo;
}
