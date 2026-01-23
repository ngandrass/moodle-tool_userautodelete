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
 * Subplugin info class for userdeleteaction
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\plugininfo;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * Subplugin info class for userdeleteaction
 */
class userdeleteaction extends \core\plugininfo\base {
    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * By default uninstallation is not allowed, plugin developers must enable it explicitly!
     *
     * @return bool
     */
    #[\Override]
    public function is_uninstall_allowed(): bool {
        // TODO (MDL-0): Only allow uninstall if no instance is using this plugin.
        return !$this->is_standard();
    }

    /**
     * Whether this plugintype supports its plugins being disabled.
     *
     * @return bool
     */
    #[\Override]
    public static function plugintype_supports_disabling(): bool {
        return false;
    }
}
