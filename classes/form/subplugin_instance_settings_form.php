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
 * Defines the sub-plugin instance settings edit form
 *
 * @package    tool_userautodelete
 * @copyright  2026 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use context;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use moodle_url;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\step_subplugin;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\userdeletefilter;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

require_once("$CFG->libdir/formslib.php"); // @codeCoverageIgnore


/**
 * Form to edit generic sub-plugin instance settings
 */
class subplugin_instance_settings_form extends dynamic_form {
    /**
     * Retrieves a sub-plugin instance.
     *
     * @param int $instanceid ID of the sub-plugin instance to retrieve
     * @param string $instancetyperaw Raw (string) value of the sub-plugin type
     * @return step_subplugin Instance of the sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_subplugin_instance(
        int $instanceid,
        string $instancetyperaw
    ): step_subplugin {
        $actualtype = subplugin_type::from($instancetyperaw);

        return match ($actualtype) {
            subplugin_type::ACTION => userdeleteaction::get_instance_by_id($instanceid),
            subplugin_type::FILTER => userdeletefilter::get_instance_by_id($instanceid),
        };
    }

    /**
     * Form definition.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        $instance = $this->get_subplugin_instance(
            $this->optional_param('instanceid', null, PARAM_INT),
            $this->optional_param('instancetype', null, PARAM_TEXT)
        );

        // Add internal information.
        $mform->addElement('hidden', 'instanceid', $instance->get_instance_id());
        $mform->setType('instanceid', PARAM_INT);
        $mform->addElement('hidden', 'instancetype', $instance::get_plugin_type()->value);
        $mform->setType('instancetype', PARAM_TEXT);
        $mform->addElement('hidden', 'returnurl', $this->optional_param('returnurl', null, PARAM_RAW));
        $mform->setType('returnurl', PARAM_RAW);

        // Add all instance settings form based on instance descriptors.
        foreach ($instance::instance_setting_descriptors() as $descriptor) {
            $element = 's_' . $descriptor->key;

            $mform->addElement(
                'text',
                $element,
                $descriptor->title->out()
            );
            $mform->setType($element, $descriptor->type);
            $mform->setDefault($element, $descriptor->default);
            $mform->addHelpButton($element, $descriptor->title->get_identifier(), $descriptor->title->get_component());

            if ($descriptor->required) {
                $mform->addRule($element, null, 'required');
            }

            if ($descriptor->readonly) {
                $mform->disabledIf($element, 'nonexistingelementtodisablethiselement');
            }
        }
    }

    /**
     * Returns context where this form is used
     *
     * This context is validated in {@see external_api::validate_context()}
     *
     * If context depends on the form data, it is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * Example:
     *     $cmid = $this->optional_param('cmid', 0, PARAM_INT);
     *     return context_module::instance($cmid);
     *
     * @return context
     * @throws \dml_exception
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * Example:
     *     require_capability('dosomething', $this->get_context_for_dynamic_submission());
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability(
            'moodle/site:config',
            $this->get_context_for_dynamic_submission()
        );
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * Example:
     *     $data = $this->get_data();
     *     file_postupdate_standard_filemanager($data, ....);
     *     api::save_entity($data); // Save into the DB, trigger event, etc.
     *
     * @return mixed
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $instance = $this->get_subplugin_instance(
            $data->instanceid,
            $data->instancetype
        );

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 's_')) {
                $settingkey = substr($key, 2);
                $instance->set_instance_setting($settingkey, $value);
            }
        }

        return $data;
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $id = $this->optional_param('id', 0, PARAM_INT);
     *     $data = api::get_entity($id); // For example, retrieve a row from the DB.
     *     file_prepare_standard_filemanager($data, ...);
     *     $this->set_data($data);
     */
    public function set_data_for_dynamic_submission(): void {
            $instance = $this->get_subplugin_instance(
                $this->optional_param('instanceid', null, PARAM_INT),
                $this->optional_param('instancetype', null, PARAM_TEXT)
            );

            $data = [
                'instanceid' => $instance->get_instance_id(),
                'instancetype' => $instance::get_plugin_type()->value,
            ];
            foreach ($instance->get_all_instance_settings() as $key => $value) {
                $data["s_{$key}"] = $value;
            }

            $this->set_data($data);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * If the form has arguments (such as 'id' of the element being edited), the URL should
     * also have respective argument.
     *
     * Example:
     *     $id = $this->optional_param('id', 0, PARAM_INT);
     *     return new moodle_url('/my/page/where/form/is/used.php', ['id' => $id]);
     *
     * @return moodle_url
     * @throws moodle_exception
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $type = subplugin_type::from($this->optional_param('instancetype', null, PARAM_TEXT));

        return new moodle_url('/admin/tool/userautodelete/manage' . $type->value . '.php', [
            'id' => $this->optional_param('instanceid', null, PARAM_INT),
            'action' => 'edit',
        ]);
    }
}
