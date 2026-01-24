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
 * Utility class for subplugin management
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\util;


// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Utility functions for working with subplugins
 */
class plugin_util {
    /**
     * Get a verified fully qualified class name of a subplugin.
     *
     * Requested sub-plugins must be installed and enabled, otherwise an exception is thrown.
     *
     * @param string $plugintype The type of the plugin (e.g., 'userdeleteaction').
     * @param string $pluginname The name of the plugin (e.g., 'suspend').
     * @return string The fully qualified class name of the subplugin.
     * @throws \moodle_exception If the plugin is not found or the class does not exist.
     */
    public static function get_subplugin_class(string $plugintype, string $pluginname): string {
        $plugins = \core_plugin_manager::instance()->get_enabled_plugins($plugintype);

        // Make sure that the desired plugin is properly installed and enabled.
        if (!in_array($pluginname, $plugins)) {
            throw new \moodle_exception('subplugin_not_found', 'tool_userautodelete', '', "{$plugintype}_{$pluginname}");
        }

        // Test for the class in the expected namespace.
        $plugincls = "\\{$plugintype}_{$pluginname}\\{$pluginname}";
        if (!class_exists($plugincls)) {
            throw new \moodle_exception('subplugin_class_not_found', 'tool_userautodelete', '', $plugincls);
        }

        return $plugincls;
    }
}
