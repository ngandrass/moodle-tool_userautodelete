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
     * Creates a new instance setting descriptor.
     *
     * These objects are used to describe a single setting that a sub-plugin
     * instance exposes.
     *
     * @param string $key Internal string that uniquely identifies this setting
     * @param lang_string $title Identifier and component that point to a localized
     * human-readable title of this setting. A _help entry must also be defined
     * for every setting.
     * @param string $type Moodle parameter type (e.g., PARAM_INT)
     * @param bool $required If true, the value must be set for any instance
     * @param mixed|null $default Default value to load if no concrete value is given
     * @param array|null $choices List of valid choices as key-value pairs where the
     * key represents the data that will be stored and the value the title of the
     * option when displaying in the UI. Required for select and non-AJAX autocomplete
     * elements. Required (non-empty) for readonly autocomplete elements, unless both
     * $ajax and $choicesresolver are provided.
     * @param bool $serialize If true, the value will be serialized before storing.
     * This is required when working with complex structures, e.g., arrays
     * @param bool $readonly If true, the value can not be changed by the user
     * @param string $mformtype Moodle form element type that is rendered in the
     * settings edit form
     * @param string|null $ajax AMD module name for AJAX-backed autocomplete search
     * (e.g. 'userdeletefilter_cohort/filter_cohort_selector'). Valid only for
     * 'autocomplete' and 'autocomplete-multi' mformtypes. Must always be set
     * together with $choicesresolver.
     * @param \Closure|null $choicesresolver Resolves display labels for
     * already-selected values to avoid a full table scan. Signature:
     * fn(array $selectedValues): array<int|string, string>. Requires $ajax to be
     * set. Also used to display selected values when the form is in readonly mode.
     * @throws \coding_exception If the provided combination of arguments violates
     * the descriptor contract.
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
        /** @var string|null AMD module name for AJAX-backed autocomplete search.
         * Valid only for 'autocomplete' and 'autocomplete-multi' mformtypes.
         * Must always be set together with $choicesresolver. */
        public readonly ?string $ajax = null,
        /** @var \Closure|null Resolves display labels for already-selected values.
         * Signature: fn(array $selectedValues): array<int|string, string>.
         * Requires $ajax to be set; never called otherwise. */
        public readonly ?\Closure $choicesresolver = null,
    ) {
        // AJAX is only meaningful for autocomplete elements.
        if ($ajax !== null && !str_starts_with($mformtype, 'autocomplete')) {
            throw new \coding_exception(
                'instance_setting_descriptor: $ajax is only valid for autocomplete mformtypes.'
            );
        }

        // AJAX requires choicesresolver to resolve display labels for selected values.
        if ($ajax !== null && $choicesresolver === null) {
            throw new \coding_exception(
                'instance_setting_descriptor: $ajax requires $choicesresolver to be set.'
            );
        }

        // The $choicesresolver is only invoked when $ajax is set; without it the closure is dead code.
        if ($choicesresolver !== null && $ajax === null) {
            throw new \coding_exception(
                'instance_setting_descriptor: $choicesresolver requires $ajax to be set.'
            );
        }

        // Non-AJAX autocomplete elements need $choices to populate their options.
        if ($ajax === null && str_starts_with($mformtype, 'autocomplete') && $choices === null) {
            throw new \coding_exception(
                'instance_setting_descriptor: $choices must be provided for non-AJAX autocomplete elements.'
            );
        }
    }
}
