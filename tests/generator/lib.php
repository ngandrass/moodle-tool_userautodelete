<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use tool_userautodelete\step;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\userdeletefilter;
use tool_userautodelete\workflow;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Tests generator for the tool_userautodelete plugin
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_userautodelete_generator extends \testing_data_generator {
    /**
     * Prepares request and page globals for form instantiation.
     *
     * @param string $path Page path for the form
     * @param array $params Request parameters
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \dml_exception
     */
    public function prepare_form_environment(string $path, array $params): void {
        global $PAGE;

        $_GET = $params;
        $_POST = [];
        $_REQUEST = $params;

        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url(new \moodle_url($path, $params));
    }

    /**
     * Creates a new workflow and loads the default workflow steps
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom description for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_default_workflow(
        ?string $title = null,
        ?string $description = null,
        bool $active = true
    ): workflow {
        $workflow = workflow::create(
            $title ?? 'Default Workflow',
            $description ?? 'This is the default workflow created by the generator.',
        );
        $workflow->load_default_workflow();

        if ($active) {
            $workflow->activate();
        }

        return $workflow;
    }

    /**
     * Creates a new workflow with a single step containing a suspension state
     * filter and an unsuspend action used for unit tests.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom description for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_simple_suspend_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = workflow::create(
            $title ?? 'Suspend Test Workflow',
            $description ?? 'Simple workflow used for unit tests.',
        );
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => true]);
        userdeleteaction::create_instance($step, 'unsuspend');

        if ($active) {
            $workflow->activate();
        }

        return $workflow;
    }

    /**
     * Creates a new workflow with two steps where the first step unsuspends a
     * previously suspended user and the second step re-suspends the previously
     * unsuspended user.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom title for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_multistep_suspend_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = $this->create_simple_suspend_workflow($title, $description, $active);

        $step = step::create(workflow: $workflow, title: 'Step 2', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);
        userdeleteaction::create_instance($step, 'suspend');

        return $workflow;
    }

    /**
     * Creates a new workflow with two steps where the first step unsuspends a
     * previously suspended user and the second step deletes the user.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom title for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_multistep_suspend_delete_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = $this->create_simple_suspend_workflow($title, $description, $active);

        $step = step::create(workflow: $workflow, title: 'Step 2', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);
        userdeleteaction::create_instance($step, 'delete');

        return $workflow;
    }

    /**
     * Creates a new workflow with a single empty step and returns both objects.
     *
     * This is a lightweight fixture helper used by base test cases that need a
     * step to attach sub-plugin instances to without any pre-configured filters
     * or actions.
     *
     * @param string|null $workflowtitle Optional workflow title (defaults to 'Test Workflow')
     * @param string|null $steptitle     Optional step title (defaults to 'Step 1')
     * @return array{0: workflow, 1: step} Tuple of [$workflow, $step]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_workflow_with_empty_step(
        ?string $workflowtitle = null,
        ?string $steptitle = null
    ): array {
        $workflow = workflow::create($workflowtitle ?? 'Test Workflow', '');
        $step = step::create(workflow: $workflow, title: $steptitle ?? 'Step 1', description: '');
        return [$workflow, $step];
    }
}
