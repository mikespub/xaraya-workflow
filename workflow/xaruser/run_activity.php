<?php

/**
 * the run activity user function
 * 
 * @author mikespub
 * @access public 
 */
function workflow_user_run_activity()
{
    // Security Check
    if (!xarSecurityCheck('ReadWorkflow')) return;

// Common setup for Galaxia environment
    include_once('modules/workflow/tiki-setup.php');
    $tplData = array();

// Adapted from tiki-g-run_activity.php

include (GALAXIA_LIBRARY.'/API.php');

// TODO: evaluate why this is here
//include_once ("lib/webmail/htmlMimeMail.php");

global $__activity_completed;
global $__comments;
$__activity_completed = false;

if ($feature_workflow != 'y') {
	$tplData['msg'] =  xarML("This feature is disabled");

	return xarTplModule('workflow', 'run', 'error', $tplData);
	die;
}

if (!isset($_REQUEST['auto'])) {
	if ($tiki_p_use_workflow != 'y') {
		$tplData['msg'] =  xarML("Permission denied");

		return xarTplModule('workflow', 'run', 'error', $tplData);
		die;
	}
}

// Determine the activity using the activityId request
// parameter and get the activity information
// load then the compiled version of the activity
if (!isset($_REQUEST['activityId'])) {
	$tplData['msg'] =  xarML("No activity indicated");

	return xarTplModule('workflow', 'run', 'error', $tplData);
	die;
}

$activity = $baseActivity->getActivity($_REQUEST['activityId']);
$process->getProcess($activity->getProcessId());

// Get user roles

// Get activity roles
$act_roles = $activity->getRoles();
$user_roles = $activity->getUserRoles($user);

// Only check roles if this is an interactive
// activity
if ($activity->isInteractive() == 'y') {
	if (!count(array_intersect($act_roles, $user_roles))) {
		$tplData['msg'] =  xarML("You cant execute this activity");

		return xarTplModule('workflow', 'run', 'error', $tplData);
		die;
	}
}

$act_role_names = $activity->getActivityRoleNames($user);

// FIXME: what's this for ?
foreach ($act_role_names as $role) {
	$name = 'tiki-role-' . $role['name'];

	if (in_array($role['roleId'], $user_roles)) {
		$smarty->assign("$name", 'y');

		$$name = 'y';
	} else {
		$smarty->assign("$name", 'n');

		$$name = 'n';
	}
}

$source = GALAXIA_PROCESSES.'/' . $process->getNormalizedName(). '/compiled/' . $activity->getNormalizedName(). '.php';
$shared = GALAXIA_PROCESSES.'/' . $process->getNormalizedName(). '/code/shared.php';

// Existing variables here:
// $process, $activity, $instance (if not standalone)

// Include the shared code
include_once ($shared);

// Now do whatever you have to do in the activity
include_once ($source);

// Process comments
if (isset($_REQUEST['__removecomment'])) {
	$__comment = $instance->get_instance_comment($_REQUEST['__removecomment']);

	if ($__comment['user'] == $user or $tiki_p_admin_workflow == 'y') {
		$instance->remove_instance_comment($_REQUEST['__removecomment']);
	}
}

$tplData['__comments'] =&  $__comments;

if (!isset($_REQUEST['__cid']))
	$_REQUEST['__cid'] = 0;

if (isset($_REQUEST['__post'])) {
	$instance->replace_instance_comment($_REQUEST['__cid'], $activity->getActivityId(), $activity->getName(),
		$user, $_REQUEST['__title'], $_REQUEST['__comment']);
}

$__comments = $instance->get_instance_comments();

// This goes to the end part of all activities
// If this activity is interactive then we have to display the template

if (count($smarty->tplData) > 0) {
    foreach (array_keys($smarty->tplData) as $key) {
        $tplData[$key] = $smarty->tplData[$key];
    }
}

$tplData['procname'] =  $process->getName();
$tplData['procversion'] =  $process->getVersion();
$tplData['actname'] =  $activity->getName();
$tplData['actid'] = $activity->getActivityId();

if (!isset($_REQUEST['auto']) && $__activity_completed && $activity->isInteractive()) {
    if (empty($instance->instanceId)) {
        xarResponseRedirect(xarModURL('workflow', 'user', 'activities'));
    } else {
        xarResponseRedirect(xarModURL('workflow', 'user', 'instances'));
    }
    return true;
} else {
	if (!isset($_REQUEST['auto']) && $activity->isInteractive()) {
		//$section = 'workflow';
		//include_once ('tiki-section_options.php');
		$template = $activity->getNormalizedName(). '.tpl';
		$tplData['mid'] =  $process->getNormalizedName(). '/' . $template;
	// not very clean way, but it works :)
                $output = xarTpl__executeFromFile(GALAXIA_PROCESSES . '/' . $process->getNormalizedName(). '/code/templates/' . $template, $tplData);
                $tplData['mid'] = $output;
		$template = 'running';
	} else {
		$template = 'completed';
	}
}

    $tplData['feature_help'] = $feature_help;
    $tplData['direct_pagination'] = $direct_pagination;
    return xarTplModule('workflow','user','activity',$tplData,$template);
}

?>
