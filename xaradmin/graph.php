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
sys::import('modules.workflow.lib.galaxia.api');
/**
 * the graph administration function
 *
 * @author mikespub
 * @access public
 */
function workflow_admin_graph()
{
    // Security Check
    if (!xarSecurity::check('AdminWorkflow')) return;

    // Common setup for Galaxia environment
    sys::import('modules.workflow.lib.galaxia.config');
    $tplData = array();
$maxRecords = xarModVars::get('workflow','items_per_page');
    // Adapted from tiki-g-admin_processes.php

    include_once(GALAXIA_LIBRARY.'/processmanager.php');

    // Check if we are editing an existing process
    // if so retrieve the process info and assign it.
    if (!isset($_REQUEST['pid']))
        $_REQUEST['pid'] = 0;

    if ($_REQUEST["pid"]) {
        xarLog::message("WORKFLOW: Getting process");
        $process = new Process($_REQUEST['pid']);
        $procNName = $process->getNormalizedName();

        $info = $processManager->get_process($_REQUEST["pid"]);

        $info['graph'] = GALAXIA_PROCESSES."/" . $procNName . "/graph/" . $procNName . ".png";
        $mapfile = GALAXIA_PROCESSES."/" . $procNName . "/graph/" . $procNName. ".map";

        if(!file_exists($process->getGraph()) or !file_exists($mapfile)) {
            // Try to build it
            xarLog::message("WF: need to build graph files");
            $activityManager->build_process_graph($_REQUEST['pid']);
        }

        if (file_exists($process->getGraph()) && file_exists($mapfile)) {
            xarLog::message("WF: graph files exist");
            $map = join('',file($mapfile));

            $url = xarController::URL('workflow','admin','activities',
                             array('pid' => $info['pId']));
            $map = preg_replace('/href=".*?activityId/', 'href="' . $url . '&amp;activityId', $map);
            // Darn graphviz does not close the area tags
            $map = preg_replace('#<area (.*[^/])>#','<area $1/>',$map);
            $info['map'] = $map;
        } else {
            $info['graph'] = '';
        }
    } else {
        $info = array(
                      'name' => '',
                      'description' => '',
                      'version' => '1.0',
                      'isActive' => 0,
                      'pId' => 0
                      );
    }

    $tplData['proc_info'] = $info;
    $tplData['pid'] =  $_REQUEST['pid'];
    $tplData['info'] =  $info;

    $where = '';
    $wheres = array();

    if (isset($_REQUEST['filter'])) {
        if ($_REQUEST['filter_name']) {
            $wheres[] = " name='" . $_REQUEST['filter_name'] . "'";
        }

        if ($_REQUEST['filter_active']) {
            $wheres[] = " isActive='" . $_REQUEST['filter_active'] . "'";
        }

        $where = implode('and', $wheres);
    }

    if (isset($_REQUEST['where'])) {
        $where = $_REQUEST['where'];
    }

    if (!isset($_REQUEST["sort_mode"])) {
        $sort_mode = 'lastModif_desc';
    } else {
        $sort_mode = $_REQUEST["sort_mode"];
    }

    if (!isset($_REQUEST["offset"])) {
        $offset = 1;
    } else {
        $offset = $_REQUEST["offset"];
    }

    $tplData['offset'] = $offset;

    if (isset($_REQUEST["find"])) {
        $find = $_REQUEST["find"];
    } else {
        $find = '';
    }

    $tplData['find'] =  $find;
    $tplData['where'] =  $where;
    $tplData['sort_mode'] = $sort_mode;

    $items = $processManager->list_processes($offset - 1, $maxRecords, $sort_mode, $find, $where);
    $tplData['cant'] =  $items['cant'];

    $cant_pages = ceil($items["cant"] / $maxRecords);
    $tplData['cant_pages'] =  $cant_pages;
    $tplData['actual_page'] =  1 + (($offset - 1) / $maxRecords);

    if ($items["cant"] >= ($offset + $maxRecords)) {
        $tplData['next_offset'] =  $offset + $maxRecords;
    } else {
        $tplData['next_offset'] =  -1;
    }

    if ($offset > 1) {
        $tplData['prev_offset'] =  $offset - $maxRecords;
    } else {
        $tplData['prev_offset'] =  -1;
    }

    $tplData['items'] =  $items["data"];

    if ($_REQUEST['pid']) {
        $valid = $activityManager->validate_process_activities($_REQUEST['pid']);

        $errors = array();

        if (!$valid) {
            $process->deactivate();

            $errors = $activityManager->get_error();
        }

        $tplData['errors'] =  $errors;
    }

    $sameurl_elements = array(
                              'offset',
                              'sort_mode',
                              'where',
                              'find',
                              'filter_name',
                              'filter_active'
                              );

    $all_procs = $items = $processManager->list_processes(0, -1, 'name_desc', '', '');
    $tplData['all_procs'] =  $all_procs['data'];

    $tplData['mid'] =  'tiki-g-admin_processes.tpl';

/*    $tplData['pager'] = xarTplPager::getPager($tplData['offset'],
                                       $items['cant'],
                                       $url,
                                       $maxRecords);*/
    $tplData['url'] = xarServer::getCurrentURL(array('offset' => '%%'));
    $tplData['maxRecords'] = $maxRecords;
    return $tplData;
}

?>