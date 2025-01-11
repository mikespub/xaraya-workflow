<?php

/**
 * @package modules\workflow
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Workflow;

use Xaraya\Modules\UserGuiClass;
use sys;

sys::import('xaraya.modules.usergui');
sys::import('modules.workflow.class.userapi');

/**
 * Handle the workflow user GUI
 *
 * @method mixed activities(array $args)
 * @method mixed display(array $args)
 * @method mixed displayhook(array $args)
 * @method mixed instances(array $args)
 * @method mixed main(array $args)
 * @method mixed processes(array $args)
 * @method mixed runActivity(array $args)
 * @method mixed test(array $args)
 * @method mixed testQueue(array $args)
 * @method mixed testRun(array $args)
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    /**
     * User main GUI function
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        $args['description'] ??= 'Description of workflow';

        // Pass along the context for xarTpl::module() if needed
        $args['context'] ??= $this->getContext();
        return $args;
    }
}
