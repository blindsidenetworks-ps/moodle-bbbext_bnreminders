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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin overrides are located here
 *
 * @package     bbbext_bnnotifications
 * @copyright   2024 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use bbbext_bnnotifications\local\persistent\guest_email;
use bbbext_bnnotifications\subscription_utils;
use bbbext_bnnotifications\utils;

/**
 * Add icon.
 *
 * @return string[]
 */
function bbbext_bnnotifications_get_fontawesome_icon_map() {
    return [
        'bbbext_bnnotifications:i/bell' => 'fa-bell-o',
    ];
}

/**
 * Serves attached files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function bbbext_bnnotifications_pluginfile($course,
    $cm,
    context $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $canview = has_capability('mod/bigbluebuttonbn:view', $context);

    if ($filearea === utils::EMAIL_REMINDER_FILEAREA) {
        $canview = true; // External users can see the image.
    }

    if (!$canview) {
        return false;
    }

    $itemid = (int) array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/bbbext_bnnotifications/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add a new preference for users
 *
 * @param navigation_node $useraccount
 * @param stdClass $user
 * @param context_user $context
 * @param stdClass $course
 * @param context_course $coursecontext
 * @return void
 * @throws coding_exception
 */
function bbbext_bnnotifications_extend_navigation_user_settings(
    navigation_node $useraccount,
    stdClass $user,
    context_user $context,
    stdClass $course,
    context_course $coursecontext) {
    $enabled = \core_plugin_manager::instance()->get_plugin_info('bbbext_bnnotifications')->is_enabled();
    if (!$enabled) {
        return;
    }
    $parent = $useraccount->parent->find('useraccount', navigation_node::TYPE_CONTAINER);
    $parent->add(
        get_string(
            'bnnotifications:preferences',
            'bbbext_bnnotifications'
        ),
        new moodle_url('/mod/bigbluebuttonbn/extension/bnnotifications/managesubscriptions.php')
    );
}

/**
 * Get the list of emails to add to the meeting
 *
 * This is a dummy callback used to demonstrate how to add a new setting to the BigBlueButtonBN settings page.
 *
 * @param array $emails
 * @param int $instanceid
 * @return void
 */
function bbbext_bnnotifications_meeting_add_guests(array $emails, int $instanceid): void {
    global $USER;
    foreach ($emails as $email) {
        guest_email::create_guest_mail_record($email, $instanceid, $USER->id);
    }
}
