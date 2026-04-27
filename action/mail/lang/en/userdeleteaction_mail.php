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
 * Plugin strings are defined here.
 *
 * @package     userdeleteaction_mail
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'Send mail';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';
$string['error_unknown_variables'] = 'The following variable references are unknown and cannot be resolved: {$a}. Fix or remove them before saving.';
$string['setting_message'] = 'Message';
$string['setting_message_help'] = 'The body of the email that will be sent to the user. You can use all available formatting options as well as the variables listed below.

<strong>Available variables:</strong>

<ul>
<li><code>{{user.id}}</code> – User\'s numeric ID</li>
<li><code>{{user.username}}</code> – Username</li>
<li><code>{{user.firstname}}</code> – First name</li>
<li><code>{{user.lastname}}</code> – Last name</li>
<li><code>{{user.email}}</code> – Email address</li>
<li><code>{{user.idnumber}}</code> – User\'s ID number</li>
<li><code>{{user.institution}}</code> – Institution</li>
<li><code>{{user.timecreated}}</code> – Account creation date and time</li>
<li><code>{{user.lastaccess}}</code> – Last access date and time</li>
<li><code>{{user.lastaccessrelative}}</code> – Relative time since last access</li>
<li><code>{{user.lastip}}</code> – Last IP address</li>
<li><code>{{user.city}}</code> – City</li>
<li><code>{{user.country}}</code> – Country code</li>
</ul>
<hr>
<ul>
<li><code>{{site.name}}</code> – Site full name</li>
<li><code>{{site.shortname}}</code> – Site short name</li>
<li><code>{{site.supportemail}}</code> – Site support email address</li>
</ul>
<hr>
<ul>
<li><code>{{urls.home}}</code> – Site base URL</li>
<li><code>{{urls.login}}</code> – Login page URL</li>
<li><code>{{urls.profile}}</code> – Target user profile URL</li>
<li><code>{{urls.support}}</code> – Contact site support URL</jli>
</ul>';
$string['setting_subject'] = 'Subject';
$string['setting_subject_help'] = 'Subject of the email that will be sent to the user. You can use all available formatting options as well as the variables listed below.

<strong>Available variables:</strong>

<ul>
<li><code>{{user.id}}</code> – User\'s numeric ID</li>
<li><code>{{user.username}}</code> – Username</li>
<li><code>{{user.firstname}}</code> – First name</li>
<li><code>{{user.lastname}}</code> – Last name</li>
<li><code>{{user.email}}</code> – Email address</li>
<li><code>{{user.idnumber}}</code> – User\'s ID number</li>
<li><code>{{user.institution}}</code> – Institution</li>
<li><code>{{user.timecreated}}</code> – Account creation date and time</li>
<li><code>{{user.lastaccess}}</code> – Last access date and time</li>
<li><code>{{user.lastaccessrelative}}</code> – Relative time since last access</li>
<li><code>{{user.lastip}}</code> – Last IP address</li>
<li><code>{{user.city}}</code> – City</li>
<li><code>{{user.country}}</code> – Country code</li>
</ul>
<hr>
<ul>
<li><code>{{site.name}}</code> – Site full name</li>
<li><code>{{site.shortname}}</code> – Site short name</li>
<li><code>{{site.supportemail}}</code> – Site support email address</li>
</ul>
<hr>
<ul>
<li><code>{{urls.home}}</code> – Site base URL</li>
<li><code>{{urls.login}}</code> – Login page URL</li>
<li><code>{{urls.profile}}</code> – Target user profile URL</li>
<li><code>{{urls.support}}</code> – Contact site support URL</jli>
</ul>';