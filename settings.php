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
 * This file defines the admin settings for this plugin
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
defined('MOODLE_INTERNAL') || die();

// Content settings.
$settings->add(new admin_setting_heading(
    'bbbext_bnreminders/emailcontent',
    get_string('emailcontent', 'bbbext_bnreminders'),
    get_string('emailcontent:desc', 'bbbext_bnreminders')
));
// Text field setting.
$textfield = new admin_setting_configtext(
    'bbbext_bnreminders/emailsubject',
    new lang_string('emailsubject', 'bbbext_bnreminders'),
    new lang_string('emailsubject:desc', 'bbbext_bnreminders'),
    new lang_string('emailsubject:default', 'bbbext_bnreminders'),
    PARAM_RAW,
    50
);
$settings->add($textfield);
// Text area with editor for the email template.
$emailtemplateeditor = new admin_setting_confightmleditor(
    'bbbext_bnreminders/emailtemplate',
    new lang_string('emailtemplate', 'bbbext_bnreminders'),
    new lang_string('emailtemplate:desc', 'bbbext_bnreminders'),
    new lang_string('emailtemplate:default', 'bbbext_bnreminders'),
    PARAM_RAW
);
$settings->add($emailtemplateeditor);
// Text area with editor for additional footer information.
$emailfootereditor = new admin_setting_confightmleditor(
    'bbbext_bnreminders/emailfooter',
    new lang_string('emailfooter', 'bbbext_bnreminders'),
    new lang_string('emailfooter:desc', 'bbbext_bnreminders'),
    '',
    PARAM_RAW,
    '0',
    '4'
);
$settings->add($emailfootereditor);
