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
 * Utility class for custom admin pages
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\util;

use context_system;
use core\exception\moodle_exception;
use moodle_url;
use navigation_node;
use pix_icon;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Utility class for custom admin pages
 */
class adminpage_util {
    /**
     * Performs setup similar to admin_externalpage_setup() but without requiring
     * a page to be registered to the full admin tree.
     *
     * This is useful for detail pages that should only be accessible via the
     * global management page for the component.
     *
     * @param string $section Name of the page / section
     * @param string $title Human-readable title of the created navigation node
     * @param moodle_url $url URL of this page / navigation node
     * @param string|null $parentsection Name of the parent page / section this
     * hidden admin externalpage will be positioned under. If null, the page will
     * not be added to the navigation tree.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws moodle_exception
     */
    public static function admin_hidden_externalpage_setup(
        string $section,
        string $title,
        moodle_url $url,
        ?string $parentsection,
    ): void {
        global $OUTPUT, $PAGE, $SITE;

        // Setup basic page parameters.
        require_admin();

        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title($title);
        $PAGE->set_url($url);
        $PAGE->set_heading($SITE->fullname);

        // Add search bar.
        $PAGE->add_header_action($OUTPUT->render_from_template('core_admin/header_search_input', [
            'action' => new moodle_url('/admin/search.php'),
            'query' => $PAGE->url->get_param('query'),
        ]));

        // Skip navigation registration if no parent is given.
        if ($parentsection === null) {
            return;
        }

        // Register page in admin settings navigation.
        navigation_node::require_admin_tree();
        $parentnavnode = $PAGE->settingsnav->find($parentsection, navigation_node::TYPE_SETTING);

        if (!$parentnavnode) {
            throw new \coding_exception('invalid_parent_section', 'tool_userautodelete');
        }

        $navnode = $parentnavnode->add(
            text: $title,
            action: $url,
            type: navigation_node::TYPE_SETTING,
            key: $section,
            icon: new pix_icon('i/settings', '')
        );
        $navnode->make_active();

        $PAGE->navigation->clear_cache();
    }
}
