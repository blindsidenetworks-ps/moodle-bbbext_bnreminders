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
 * Get and set subscription status for a user.
 *
 * @package   bbbext_bnreminders
 * @copyright 2022 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

use bbbext_bnreminders\subscription_utils;
use core\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;

require(__DIR__ . '/../../../../config.php');
require_login();
global $PAGE, $OUTPUT, $DB, $SITE;
$PAGE->set_course($SITE); // Intialise the page and run through the setup.
$userid = required_param('userid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$instance = instance::get_from_cmid($cmid);
if (empty($instance)) {
    throw new moodle_exception('activitynotfound', 'bbbext_bnreminders');
}
$PAGE->set_url(
    '/mod/bigbluebuttonbn/extension/bnreminders/subscription.php',
    ['cmid' => $cmid, 'userid' => $userid]
);
$title = get_string(
    'unsubscribe:title:meeting',
    'bbbext_bnreminders',
    $instance->get_course()->shortname . ': ' . format_string($instance->get_meeting_name())
);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$form = new \bbbext_bnreminders\form\unsubscribe(null);
$form->set_data(['cmid' => $cmid, 'userid' => $userid]);
// Specific for the tests: we allow to set the password in the form here.
if (defined('BEHAT_SITE_RUNNING')) {
    $form->set_data(['password' => optional_param('password', '', PARAM_RAW)]);
}
$formcontent = '';
$managepreferences = new single_button(
    new moodle_url('/mod/bigbluebuttonbn/extension/bnreminders/managesubscriptions.php'),
    get_string('unsubscribe:managepreferences', 'bbbext_bnreminders'),
    'get',
);
$managepreferences->class = 'mdl-align';
if ($form->is_cancelled()) {
    if ($userid) {
        $formcontent = $OUTPUT->render($managepreferences);
    }
    notification::add(
        get_string('subscribed:cancel', 'bbbext_bnreminders'),
        \core\output\notification::NOTIFY_INFO
    );
} else if ($data = $form->get_data()) {
    try {
        if (!empty($data->userid)) {
            subscription_utils::change_reminder_subcription_user(
                !$data->unsubscribe, // We change this because of how user preferences are set.
                $data->userid,
                $instance
            );
            $formcontent = $OUTPUT->render($managepreferences);
        }
        notification::add(
            get_string('unsubscribed', 'bbbext_bnreminders'),
            \core\output\notification::NOTIFY_INFO
        );
    } catch (server_not_available_exception $e) {
        bigbluebutton_proxy::handle_server_not_available($instance);
    }
} else {
    $formcontent = $form->render();
}
echo $OUTPUT->header();
echo $formcontent;
echo $OUTPUT->footer();
