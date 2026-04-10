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

/**
 * This file defines the manager class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Manages the user notification and deletion workflow
 */
class manager {
    /** @var \stdClass Moodle config object for this plugin */
    protected \stdClass $config;

    /**
     * Creates a new manager instance
     *
     * @return void
     * @throws \dml_exception
     */
    public function __construct() {
        $this->config = get_config('tool_userautodelete');
    }

    /**
     * Main entry point for the user autodelete process.
     *
     * This method is called regularly by the executeworkflows scheduled task.
     *
     * @return bool True, if executed successfully
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function execute(): bool {
        if (!$this->config->enable) {
            logger::info(get_string('plugin_disabled_skipping_execution', 'tool_userautodelete'));
            return false;
        }

        $workflows = workflow::get_all();
        $workflowstoprocess = array_filter(
            $workflows,
            fn ($workflow) => ($workflow->active && $workflow->is_valid())
        );
        logger::info('Got ' . count($workflowstoprocess) . ' active and valid workflow(s) to process out of ' .
            count($workflows) . ' total workflows.');

        foreach ($workflowstoprocess as $workflow) {
            logger::info("Start processing workfow: {$workflow->title} (ID: {$workflow->id})");
            $workflow->process();
        }

        return true;
    }
}
