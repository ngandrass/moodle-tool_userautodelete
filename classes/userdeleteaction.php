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

/**
 * Interface for userdeleteaction sub-plugins
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Interface for userdeleteaction sub-plugins
 */
abstract class userdeleteaction {
    /**
     * Creates a new instance of this action sub-plugin
     *
     * @param int $id The ID of this action sub-plugin instance
     */
    public function __construct(
        /** @var int The ID of this action sub-plugin instance */
        public readonly int $id,
    ) {
    }

    /**
     * Returns the name of this action sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this action sub-plugin
     */
    abstract public function get_plugin_name(): string;

    /**
     * Executes this action for a given user deletion process
     *
     * @param int $processid The ID of the user deletion process to execute this action for
     *
     * @return void
     */
    abstract public function execute(int $processid): void;

}
