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
 * the main user function
 *
 * @author mikespub
 * @access public
 * @param no $ parameters
 * @return array empty
 * @throws XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 */
function workflow_user_main()
{
    // Security Check
    if (!xarSecurity::check('ReadWorkflow')) return;

    // Return the output
    return array();
}

?>
