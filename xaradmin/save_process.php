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
 * the save process administration function
 *
 * @author mikespub
 * @access public
 */
function workflow_admin_save_process()
{
    // Security Check
    if (!xarSecurity::check('AdminWorkflow')) {
        return;
    }

    // Common setup for Galaxia environment
    sys::import('modules.workflow.lib.galaxia.config');
    $tplData = array();

    // Adapted from tiki-g-save_process.php

    include_once(GALAXIA_LIBRARY.'/processmanager.php');

    // The galaxia process manager PHP script.

    // Check if we are editing an existing process
    // if so retrieve the process info and assign it.
    if (!isset($_REQUEST['pid'])) {
        $_REQUEST['pid'] = 0;
    }

    header('Content-type: text/xml');
    echo('<?xml version="1.0"?>');
    $data = $processManager->serialize_process($_REQUEST['pid']);
    echo $data;

    // TODO: clean up properly
    die;
}
