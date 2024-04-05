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
 * utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @return array containing the itemlink(s) for the item(s).
 */
function workflow_userapi_getitemlinks(array $args = [], $context = null)
{
    extract($args);

    $itemlinks = [];
    if (empty($itemtype)) {
        return $itemlinks;
    }

    // Common setup for Galaxia environment
    sys::import('modules.workflow.lib.galaxia.config');
    include(GALAXIA_LIBRARY . '/gui.php');

    if (empty($user)) {
        $user = xarSession::getVar('role_id') ?? 0;
    }

    // get the instances this user has access to
    $sort = 'pId_asc, instanceId_asc';
    $find = '';
    $where = "gi.pId=$itemtype";
    $items = $GUI->gui_list_user_instances($user, 0, -1, $sort, $find, $where);

    // TODO: add the instances you're the owner of (if relevant)

    if (empty($items['data']) || !is_array($items['data']) || count($items['data']) == 0) {
        return $itemlinks;
    }

    $itemid2key = [];
    foreach ($items['data'] as $key => $item) {
        $itemid2key[$item['instanceId']] = $key;
    }
    // if we didn't have a list of itemids, return all the items we found
    if (empty($itemids)) {
        $itemids = array_keys($itemid2key);
    }
    foreach ($itemids as $itemid) {
        if (!isset($itemid2key[$itemid])) {
            continue;
        }
        $item = $items['data'][$itemid2key[$itemid]];
        $itemlinks[$itemid] = ['url'   => xarController::URL(
            'workflow',
            'user',
            'instances',
            ['filter_process' => $itemtype]
        ),
                                    'title' => xarML('Display Instance'),
                                    'label' => xarVar::prepForDisplay($item['procname'] . ' ' . $item['version'] . ' # ' . $item['instanceId']), ];
    }
    return $itemlinks;
}
