<?php

/**
 * @package modules\workflow
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Workflow\AdminGui;


use Xaraya\Modules\Workflow\AdminGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarModVars;
use xarVar;
use xarMod;
use xarTplPager;
use xarServer;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * workflow admin monitor_processes function
 * @extends MethodClass<AdminGui>
 */
class MonitorProcessesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * the monitor processes administration function
     * @author mikespub
     * @access public
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->checkAccess('AdminWorkflow')) {
            return;
        }

        // Common setup for Galaxia environment
        sys::import('modules.workflow.lib.galaxia.config');
        $maxRecords = $this->getModVar('items_per_page');

        // Adapted from tiki-g-monitor_processes.php
        include_once(GALAXIA_LIBRARY . '/processmonitor.php');

        if (!$this->fetch('filter_process', 'int', $data['filter_process'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('filter_active', 'str', $data['filter_active'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('filter_valid', 'str', $data['filter_valid'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Filtering data to be received by request and
        // used to build the where part of a query
        // filter_active, filter_valid, find, sort_mode,
        // filter_process
        $where = '';
        $wheres = [];

        if (!empty($data['filter_active'])) {
            $wheres[] = "isActive='" . $data['filter_active'] . "'";
        }
        if (!empty($data['filter_valid'])) {
            $wheres[] = "isValid='" . $data['filter_valid'] . "'";
        }
        if (!empty($data['filter_process'])) {
            $wheres[] = "pId='" . $data['filter_process'] . "'";
        }

        $where = implode(' and ', $wheres);

        if (!isset($_REQUEST["sort_mode"])) {
            $sort_mode = 'name_asc';
        } else {
            $sort_mode = $_REQUEST["sort_mode"];
        }

        if (!isset($_REQUEST["offset"])) {
            $offset = 1;
        } else {
            $offset = $_REQUEST["offset"];
        }

        $data['offset'] = &$offset;

        if (isset($_REQUEST["find"])) {
            $find = $_REQUEST["find"];
        } else {
            $find = '';
        }

        $data['find'] =  $find;
        $data['where'] =  $where;
        $data['sort_mode'] = &$sort_mode;

        $items = $processMonitor->monitor_list_processes($offset - 1, $maxRecords, $sort_mode, $find, $where);
        $data['cant'] =  $items['cant'];

        $cant_pages = ceil($items["cant"] / $maxRecords);
        $data['cant_pages'] = &$cant_pages;
        $data['actual_page'] =  1 + (($offset - 1) / $maxRecords);

        if ($items["cant"] >= ($offset + $maxRecords)) {
            $data['next_offset'] =  $offset + $maxRecords;
        } else {
            $data['next_offset'] =  -1;
        }

        if ($offset > 1) {
            $data['prev_offset'] =  $offset - $maxRecords;
        } else {
            $data['prev_offset'] =  -1;
        }

        $data['items'] = &$items["data"];

        $maxtime = 0;
        foreach ($items['data'] as $info) {
            if (isset($info['duration']) && $maxtime < $info['duration']['max']) {
                $maxtime = $info['duration']['max'];
            }
        }
        if ($maxtime > 0) {
            $scale = 200.0 / $maxtime;
        } else {
            $scale = 1.0;
        }
        foreach ($items['data'] as $index => $info) {
            if (isset($info['duration'])) {
                $items['data'][$index]['duration']['min'] = xarMod::apiFunc('workflow', 'user', 'timetodhms', ['time' => $info['duration']['min']]);
                $items['data'][$index]['duration']['avg'] = xarMod::apiFunc('workflow', 'user', 'timetodhms', ['time' => $info['duration']['avg']]);
                $items['data'][$index]['duration']['max'] = xarMod::apiFunc('workflow', 'user', 'timetodhms', ['time' => $info['duration']['max']]);
                $info['duration']['max'] -= $info['duration']['avg'];
                $info['duration']['avg'] -= $info['duration']['min'];
                $items['data'][$index]['timescale'] = [];
                $items['data'][$index]['timescale']['max'] = intval($scale * $info['duration']['max']);
                $items['data'][$index]['timescale']['avg'] = intval($scale * $info['duration']['avg']);
                $items['data'][$index]['timescale']['min'] = intval($scale * $info['duration']['min']);
            }
        }

        $allprocs = $processMonitor->monitor_list_all_processes('name_asc');
        $data['all_procs'] = [];
        foreach ($allprocs as $row) {
            $data['all_procs'][] = ['pId' => $row['pId'], 'name' => $row['name'] . ' ' . $row['version'], 'version' => $row['version']];
        }

        $sameurl_elements = [
            'offset',
            'sort_mode',
            'where',
            'find',
            'filter_valid',
            'filter_process',
            'filter_active',
            'processId',
        ];

        $data['stats'] =  $processMonitor->monitor_stats();

        $data['mid'] =  'tiki-g-monitor_processes.tpl';

        /*        $data['pager'] = xarTplPager::getPager($data['offset'],
                                                   $items['cant'],
                                                   $url,
                                                   $maxRecords);*/
        $data['url'] = xarServer::getCurrentURL(['offset' => '%%']);
        $data['maxRecords'] = $maxRecords;
        return $data;
    }
}