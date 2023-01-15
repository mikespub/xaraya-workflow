<?php
/**
 * Workflow Module ItemDisplay GUI Hook Observer
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

//namespace Xaraya\Modules\Workflow\HookObservers;  // not supported by events.php yet
sys::import('modules.workflow.class.hookobserver');

class WorkflowItemDisplayObserver extends xarWorkflowHookObserver implements ixarEventObserver
{
    public $module = 'workflow';
    public $type   = 'user';
    public $func   = 'displayhook';
}
