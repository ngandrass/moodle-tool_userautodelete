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
 * Descriptor for instance settings of sub-plugins
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

use core\lang_string;


/**
 * Descriptor for instance settings of sub-plugins
 */
class instance_setting_descriptor {
    /**
     * Creates a new instance setting descriptor
     *
     * These objects are used to describe a single setting that a sub-plugin
     * instance exposes.
     *
     * @param string $key Internal string that uniquely identifies this setting
     * @param lang_string $title Identifier and component that point to a localized
     * human-readable title of this setting. A _help entry mus also be defined
     * for every setting.
     * @param string $type Moodle parameter type (e.g., PARAM_INT)
     * @param bool $required If true, the value must be set for any instance
     * @param mixed|null $default Default value to load if no concrete value is given
     * @param bool $readonly If true, the value can not be changed by the user
     * @param string $mformtype Moodle form element type that is rendered in the
     * settings edit form
     */
    public function __construct(
        /** @var string Internal string that uniquely identifies this setting */
        public readonly string $key,
        /** @var lang_string Identifier and component that point to a localized
         * human-readable title of this setting. A _help entry must also be
         * defined for every setting. */
        public readonly lang_string $title,
        /** @var string Moodle parameter type (e.g., PARAM_INT) */
        public readonly string $type,
        /** @var bool If true, the value must be set for any instance */
        public readonly bool $required = false,
        /** @var mixed Default value to load if no concrete value is given */
        public readonly mixed $default = null,
        /** @var null|array List of valid choices as key-value pairs where the
         * key represents the data that will be stored and the value the title
         * of the option when displaying in the UI */
        public readonly ?array $choices = null,
        /** @var bool If true, the value will be serialized before storing. This
         * is required when working with complex structures, e.g., arrays */
        public readonly bool $serialize = false,
        /** @var bool If true, the value can not be changed by the user */
        public readonly bool $readonly = false,
        /** @var string Moodle form element type that is rendered in the settings
         * edit form */
        public readonly string $mformtype = 'text',
    ) {
    }
}
