<?php

/**
 * Workflow Module Transition Trait for Symfony Workflow tests - could be used in custom subject, cfr. WorkflowProcess
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

namespace Xaraya\Modules\Workflow\Traits;

use sys;

sys::import('modules.workflow.class.traits.registrytrait');

/**
 * For documentation purposes only - available via TransitionTrait
 */
interface TransitionInterface extends RegistryInterface
{
    public function canTransition(string $workflowName, string $transitionName): bool;
    public function applyTransition(string $workflowName, string $transitionName, array $context = []): mixed;
    public function getEnabledTransitions(string $workflowName): array;
}

trait TransitionTrait
{
    use RegistryTrait;

    // See https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/Workflow/Workflow.php
    public function canTransition(string $workflowName, string $transitionName): bool
    {
        $workflow = $this->getWorkflow($workflowName);
        return $workflow->can($this, $transitionName);
    }

    public function applyTransition(string $workflowName, string $transitionName, array $context = []): mixed
    {
        $workflow = $this->getWorkflow($workflowName);
        return $workflow->apply($this, $transitionName, $context);
    }

    public function getEnabledTransitions(string $workflowName): array
    {
        $workflow = $this->getWorkflow($workflowName);
        return $workflow->getEnabledTransitions($this);
    }
}
