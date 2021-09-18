<?php

//Code to be executed before an activity
// If we didn't retrieve the instance before
if (empty($instance->instanceId)) {
    // This activity needs an instance to be passed to
    // be started, so get the instance into $instance.
    if (isset($_REQUEST['iid'])) {
        $instance->getInstance($_REQUEST['iid']);
    } else {
        $tplData['msg'] =  xarML("No instance indicated");
        return xarTpl::module('workflow', 'admin', 'error', $tplData);
    }
}
// Set the current user for this activity
if (isset($user) && !empty($instance->instanceId) && !empty($activity->activityId)) {
    $instance->setActivityUser($activity->activityId, $user);
}
