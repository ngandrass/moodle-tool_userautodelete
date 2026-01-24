<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Commenting.InlineComment.DocBlock

/**
 * Database table name mappings
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Database table name mappings
 */
enum db_table: string {
    /** @var string Name of table that stores workflow metadata */
    case WORKFLOW = 'tool_userautodelete_workflow';

    /** @var string Name of the table that stores steps that make up workflows */
    case WORKFLOW_STEP = 'tool_userautodelete_step';

    /** @var string Name of the table that stores user filter instances as parts of workflow steps */
    case WORKFLOW_FILTER = 'tool_userautodelete_filter';

    /** @var string Name of the table that stores user action instances as parts of workflow steps */
    case WORKFLOW_ACTION = 'tool_userautodelete_action';

    /** @var string Name of the table that stores sub-plugin instance specific settings */
    case INSTANCE_SETTINGS = 'tool_userautodelete_instance_settings';

    /** @var string Name of the table that contains state data for users that are currently part of workflows */
    case USER_PROCESS = 'tool_userautodelete_process';

    /** @var string Name of the table that stores logs */
    case LOG = 'tool_userautodelete_log';
}
