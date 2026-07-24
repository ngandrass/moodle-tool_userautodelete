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
 * Recipient types for the mail action sub-plugin.
 *
 * @package     userdeleteaction_mail
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_mail\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Recipient types for the mail action sub-plugin.
 *
 * Backed value is persisted as the recipient setting in the database.
 */
enum recipient: string {
    /** @var string Send the email to the user referenced by the workflow process. */
    case USER = 'user';
    /** @var string Send the email to all Moodle site administrators. */
    case ADMINS = 'admins';
    /** @var string Send the email to a custom configured email address. */
    case CUSTOM_MAIL = 'custom';
}
