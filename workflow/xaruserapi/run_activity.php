<?php

/**
 * the run activity user API function
 * 
 * @author mikespub
 * @access public 
 */
function workflow_userapi_run_activity($args)
{
    // Security Check
    if (!xarSecurityCheck('ReadWorkflow')) return;

// Common setup for Galaxia environment (possibly include more than once here !)
    include('modules/workflow/tiki-setup.php');
    $tplData = array();

// Adapted from tiki-g-run_activity.php

include (GALAXIA_LIBRARY.'/API.php');

// TODO: evaluate why this is here
//include_once ("lib/webmail/htmlMimeMail.php");

global $__activity_completed;

$__activity_completed = false;

// Determine the activity using the activityId request
// parameter and get the activity information
// load then the compiled version of the activity
if (!isset($args['activityId'])) {
	$msg = xarML("No activity indicated");
	xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
			new SystemException($msg));
	return;
}

$activity = $baseActivity->getActivity($args['activityId']);
$process->getProcess($activity->getProcessId());

$source = GALAXIA_PROCESSES . '/' . $process->getNormalizedName(). '/compiled/' . $activity->getNormalizedName(). '.php';
$shared = GALAXIA_PROCESSES . '/' . $process->getNormalizedName(). '/code/shared.php';

// Existing variables here:
// $process, $activity, $instance (if not standalone)

// Include the shared code
include_once ($shared);

// Now do whatever you have to do in the activity
include_once ($source);

    // CHECKME: if we're calling this from a hook module, we need to manually complete this
    if (!empty($args['module'])) {
        $instance->getInstance($instance->instanceId);
        $instance->complete($args['activityId']);
    }

    return true;
}

?>