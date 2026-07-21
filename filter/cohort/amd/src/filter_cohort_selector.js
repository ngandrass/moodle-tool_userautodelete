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
 * AJAX autocomplete source for cohort selection.
 *
 * @module     userdeletefilter_cohort/filter_cohort_selector
 * @copyright  2026 Niels Gandraß <niels@gandrass.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Fetches cohort autocomplete suggestions.
 *
 * @param {string} selector CSS selector identifying the autocomplete element
 * @param {string} query Current search string entered by the user
 * @param {Function} success Callback to invoke with the raw server response
 * @param {Function} failure Callback to invoke on error
 */
export const transport = (selector, query, success, failure) => {
    Ajax.call([{
        methodname: 'userdeletefilter_cohort_get_cohorts',
        args: {query, limitnum: 50},
    }])[0].then(success).catch(failure);
};

/**
 * Transforms the raw server response into the {value, label} format expected
 * by core/form-autocomplete.
 *
 * @param {string} selector CSS selector identifying the autocomplete element
 * @param {Object} data Raw response returned by the web service
 * @returns {Array<{value: number, label: string}>} Processed suggestion list
 */
export const processResults = (selector, data) => data.map(cohort => ({
    value: cohort.id,
    label: cohort.label,
}));
