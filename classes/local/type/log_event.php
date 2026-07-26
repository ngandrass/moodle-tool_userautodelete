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
 * System log event type identifiers
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * System log event type identifiers.
 *
 * Values are prefixed with '!' to distinguish them from frankenstyle sub-plugin
 * names, which can only contain lowercase letters, digits?, and underscores.
 */
enum log_event: string {
    /** @var string Process was automatically aborted due to step timeout. */
    case PROCESS_TIMEOUT = '!process_timeout';

    /** @var string Process was manually aborted by an administrator. */
    case PROCESS_ABORT_MANUAL = '!process_abort_manual';

    /** @var string Process was aborted because its workflow was deactivated. */
    case PROCESS_ABORT_WORKFLOW_DEACTIVATED = '!process_abort_workflow_deactivated';

    /**
     * Returns the CSS icon class for this event type.
     *
     * @return string CSS icon class string.
     */
    public function get_icon_class(): string {
        return match ($this) {
            self::PROCESS_TIMEOUT => 'fa-regular fa-clock',
            self::PROCESS_ABORT_MANUAL => 'fa fa-ban',
            self::PROCESS_ABORT_WORKFLOW_DEACTIVATED => 'fa fa-power-off',
        };
    }

    /**
     * Returns the language string key for this event type.
     *
     * @return string Language string key for use with get_string().
     */
    public function get_lang_key(): string {
        return 'log_event_' . ltrim($this->value, '!');
    }
}
