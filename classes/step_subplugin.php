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
 * Abstract base class for sub-plugins that can be attached to workflow steps
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\trait\subplugin_instance_settings;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Abstract base class for sub-plugins that can be attached to workflow steps
 */
abstract class step_subplugin {
    use subplugin_instance_settings;

    /** @var step|null The step this instance belongs to (lazy-loaded) */
    protected ?step $step;

    /**
     * Creates a new instance of this sub-plugin
     *
     * @param int $id The ID of this sub-plugin instance
     * @param int $stepid The ID of the step this instance is part of
     */
    protected function __construct(
        /** @var int The ID of this sub-plugin instance */
        public readonly int $id,
        /** @var int The ID of the step this sub-plugin instance is part of */
        public readonly int $stepid,
    ) {
        $this->step = null;
    }

    /**
     * Returns the ID of this sub-plugin instance
     *
     * @return int The ID of this sub-plugin instance
     */
    public function get_instance_id(): int {
        return $this->id;
    }

    /**
     * Returns the step this sub-plugin instance belongs to
     *
     * @return step The step this sub-plugin instance belongs to
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_step(): step {
        if ($this->step === null) {
            $this->step = step::get_by_id($this->stepid);
        }

        return $this->step;
    }

    /**
     * Returns a descriptive title of this sub-plugin instance to be shown in the UI
     *
     * This should be a very short human-readable string that allows to identify
     * what the sub-plugin does at a glance, e.g., 'Delete user' for an action
     * instance that deletes users.
     *
     * @return string A descriptive title of this sub-plugin instance to be shown in the UI
     * @throws \coding_exception
     */
    public function get_instance_title(): string {
        return get_string(
            'pluginname',
            'userdelete' . static::get_plugin_type()->value . '_' . static::get_plugin_name()
        );
    }

    /**
     * Returns a descriptive string of this instance's settings to be shown in the UI
     *
     * This should be a human-readable string that describes the actual settings
     * of this sub-plugin instance, e.g., 'Mail subject' for an action instance that
     * sends users an email with a subject defined in the instance settings.
     *
     * If no settings are defined, this function can simply return an empty string.
     *
     * @return string A descriptive string of this sub-plugin instance's settings
     * to be shown in the UI
     */
    public function get_instance_details(): string {
        return '';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-question';
    }

    /**
     * Determines if this subplugin instance is valid and ready for use.
     *
     * This is just a convenience wrapper for step_subplugin::validate(). If you
     * need to customize validation behavior please override validate() instead.
     *
     * @return bool True if this instance is valid and ready, false otherwise
     * @throws \dml_exception
     * @throws \coding_exception
     */
    final public function is_valid(): bool {
        return $this->validate() === null;
    }

    /**
     * Validates the settings of this sub-plugin instance and returns whether they
     * are considered valid and ready for execution or not.
     *
     * This function can be extended by individual subplugins to perform custom
     * validity checks.
     *
     * @return string|null Null if this instance is valid, otherwise a localized
     * human-readable string describing why this instance is not valid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate(): string|null {
        if (!$this->is_all_required_instance_settings_set()) {
            return get_string('required_setting_is_unset', 'tool_userautodelete');
        }

        return null;
    }

    /**
     * Returns the name of this sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this sub-plugin
     */
    abstract public static function get_plugin_name(): string;

    /**
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    abstract public static function get_plugin_type(): subplugin_type;

    /**
     * Retrieves an instance of this sub-plugin from the database and creates a
     * local instance of the respective sub-plugin class.
     *
     * @param int $instanceid The ID of the instance to retrieve
     * @return self An instance of the respective step sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    abstract public static function get_instance_by_id(int $instanceid): self;

    /**
     * Creates a new sub-plugin instance record in the database and returns
     * the respective sub-plugin instance.
     *
     * @param step $step The step this sub-plugin instance belongs to
     * @param string|null $pluginname The name of the sub-plugin to instantiate,
     * defaults to the actual plugin name of the class this method is called on
     * @param array $settings An associative array of setting key-value pairs
     * set for the new instance. Falls back to default values defined in th
     * respective sub-plugin implementation if not given explicitly.
     * @return self An instance of the respective step sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    abstract public static function create_instance(
        step $step,
        ?string $pluginname = null,
        array $settings = []
    ): self;

    /**
     * Deletes this sub-plugin instance
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    abstract public function delete(): void;
}
